<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * BmgInputModel — records individual input events for a batch.
 *
 * While most batches have a single input record, this table allows
 * partial inputs (e.g., waste added over several days) to be tracked
 * individually. The sum of all inputs equals the batch's input_weight_kg.
 */
class BmgInputModel extends Model
{
    protected $table            = 'bmg_inputs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'batch_id', 'weight_kg', 'recorded_at', 'recorded_by', 'notes',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    // bmg_inputs has no updated_at column; disable by setting empty string.
    protected $updatedField  = '';

    protected $validationRules = [
        'batch_id'    => 'required|is_natural_no_zero',
        'weight_kg'   => 'required|decimal|greater_than[0]',
        'recorded_by' => 'required|is_natural_no_zero',
    ];

    /**
     * Get all input records for a batch.
     */
    public function getForBatch(int $batchId): array
    {
        return $this->where('batch_id', $batchId)
            ->orderBy('recorded_at', 'ASC')
            ->findAll();
    }

    /**
     * Get total input weight for a batch.
     */
    public function getTotalForBatch(int $batchId): float
    {
        $result = $this->selectSum('weight_kg')
            ->where('batch_id', $batchId)
            ->get()
            ->getRowArray();
        return (float) ($result['weight_kg'] ?? 0);
    }

    /**
     * Record a new input for a batch.
     * After recording, updates the parent batch's input_weight_kg total
     * and transitions the batch to 'processing' if not already.
     */
    public function recordInput(int $batchId, float $weightKg, int $userId, string $notes = null): int
    {
        // Validate batch exists and is not completed
        $batchModel = new BmgBatchModel();
        $batch = $batchModel->find($batchId);
        if (!$batch || $batch['status'] === 'completed' || $batch['status'] === 'cancelled') {
            throw new \RuntimeException('Cannot add input to a completed or cancelled batch.');
        }

        $id = $this->insert([
            'batch_id'    => $batchId,
            'weight_kg'   => $weightKg,
            'recorded_at' => date('Y-m-d H:i:s'),
            'recorded_by' => $userId,
            'notes'       => $notes,
        ], true);

        // Update parent batch total
        $batchModel->updateInputTotal($batchId);

        // Transition batch to processing if still in 'input' state
        if ($batch['status'] === 'input') {
            $batchModel->markProcessing($batchId);
        }

        return $id;
    }
}
