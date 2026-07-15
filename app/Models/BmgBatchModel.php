<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Libraries\BmgYieldCalculator;
use App\Libraries\BmgDurationCalculator;

/**
 * BmgBatchModel — central lifecycle record for the BMG module.
 *
 * One batch = one load of waste in one drum, from input through
 * processing to output. Enforces state-machine transitions:
 *   input → processing → completed
 *                       → cancelled
 */
class BmgBatchModel extends Model
{
    protected $table            = 'bmg_batches';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = [
        'batch_code', 'drum_id', 'waste_category_id', 'status',
        'input_weight_kg', 'input_recorded_at', 'input_recorded_by',
        'start_date', 'completion_date', 'duration_days',
        'output_weight_kg', 'yield_percentage', 'mass_reduction_pct',
        'completed_by', 'output_recorded_at', 'notes',
        'expected_duration_days', 'expected_completion_date',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'batch_code'        => 'required|max_length[50]|is_unique[bmg_batches.batch_code,id,{id}]',
        'drum_id'           => 'required|is_natural_no_zero',
        'waste_category_id' => 'required|is_natural_no_zero',
        'status'            => 'in_list[input,processing,completed,cancelled]',
        'input_weight_kg'   => 'required|decimal|greater_than_equal_to[0]',
        'output_weight_kg'  => 'permit_empty|decimal|greater_than_equal_to[0]',
    ];

    /**
     * Generate a unique batch code: BATCH-YYYYMMDD-NNNN
     */
    public function generateBatchCode(): string
    {
        $date = date('Ymd');
        $prefix = "BATCH-{$date}-";

        $lastBatch = $this->like('batch_code', $prefix, 'after')
            ->orderBy('id', 'DESC')
            ->first();

        $nextSeq = 1;
        if ($lastBatch) {
            $parts = explode('-', $lastBatch['batch_code']);
            $nextSeq = ((int) end($parts)) + 1;
        }

        return $prefix . str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Start a new batch (transition to 'input' state).
     *
     * Also resolves the waste category's reference duration and stores
     * both the expected_duration_days and the expected_completion_date
     * so the dashboard and batch show page can display a clear estimate
     * of when the batch should be ready for harvest.
     */
    public function startBatch(int $drumId, int $wasteCategoryId, int $userId): int
    {
        $startDate   = date('Y-m-d');
        $categoryModel = new WasteCategoryModel();
        $expectedDays = $categoryModel->getReferenceDuration($wasteCategoryId);
        $expectedDate = BmgDurationCalculator::expectedCompletionDate($startDate, $expectedDays);

        return $this->insert([
            'batch_code'              => $this->generateBatchCode(),
            'drum_id'                 => $drumId,
            'waste_category_id'       => $wasteCategoryId,
            'status'                  => 'input',
            'input_weight_kg'         => 0,
            'input_recorded_at'       => date('Y-m-d H:i:s'),
            'input_recorded_by'       => $userId,
            'start_date'              => $startDate,
            'expected_duration_days'  => $expectedDays,
            'expected_completion_date' => $expectedDate,
        ], true);
    }

    /**
     * Mark batch as processing (after first input).
     */
    public function markProcessing(int $batchId): bool
    {
        return $this->update($batchId, ['status' => 'processing']) !== false;
    }

    /**
     * Mark a batch as completed and compute duration.
     */
    public function markCompleted(int $batchId, int $userId): bool
    {
        $batch = $this->find($batchId);
        if (!$batch || $batch['status'] === 'completed') {
            return false;
        }

        $completionDate = date('Y-m-d');
        $durationDays = BmgDurationCalculator::computeDurationDays($batch['start_date'], $completionDate);

        return $this->update($batchId, [
            'status'         => 'completed',
            'completion_date' => $completionDate,
            'duration_days'  => $durationDays,
            'completed_by'   => $userId,
        ]) !== false;
    }

    /**
     * Record batch output and compute yield analytics.
     * Returns false if output > input (scientific impossibility).
     */
    public function recordOutput(int $batchId, float $outputWeightKg, int $userId): bool
    {
        $batch = $this->find($batchId);
        if (!$batch) return false;

        // Validate: output cannot exceed input
        if ($outputWeightKg > $batch['input_weight_kg']) {
            return false;
        }

        $yieldPct = BmgYieldCalculator::computeYield($batch['input_weight_kg'], $outputWeightKg);
        $massReduction = BmgYieldCalculator::computeMassReduction($yieldPct);

        return $this->update($batchId, [
            'output_weight_kg'    => $outputWeightKg,
            'yield_percentage'    => $yieldPct,
            'mass_reduction_pct'  => $massReduction,
            'output_recorded_at'  => date('Y-m-d H:i:s'),
            'completed_by'        => $userId,
        ]) !== false;
    }

    /**
     * Cancel a batch.
     */
    public function cancel(int $batchId, string $reason = null): bool
    {
        $update = ['status' => 'cancelled'];
        if ($reason) {
            $update['notes'] = $reason;
        }
        return $this->update($batchId, $update) !== false;
    }

    /**
     * Get active batches (status = processing or input).
     */
    public function getActiveBatches(): array
    {
        return $this->select('bmg_batches.*, bmg_drums.drum_code, bmg_drums.name AS drum_name, waste_categories.name AS waste_name')
            ->join('bmg_drums', 'bmg_drums.id = bmg_batches.drum_id', 'left')
            ->join('waste_categories', 'waste_categories.id = bmg_batches.waste_category_id', 'left')
            ->whereIn('bmg_batches.status', ['input', 'processing'])
            ->orderBy('bmg_batches.start_date', 'ASC')
            ->findAll();
    }

    /**
     * Get recent completed batches.
     */
    public function getRecentCompleted(int $limit = 10): array
    {
        return $this->select('bmg_batches.*, bmg_drums.drum_code, waste_categories.name AS waste_name')
            ->join('bmg_drums', 'bmg_drums.id = bmg_batches.drum_id', 'left')
            ->join('waste_categories', 'waste_categories.id = bmg_batches.waste_category_id', 'left')
            ->where('bmg_batches.status', 'completed')
            ->orderBy('bmg_batches.completion_date', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Update the input weight total for a batch (called when a new input is added).
     */
    public function updateInputTotal(int $batchId): bool
    {
        $inputModel = new BmgInputModel();
        $total = $inputModel->getTotalForBatch($batchId);
        return $this->update($batchId, ['input_weight_kg' => $total]) !== false;
    }
}
