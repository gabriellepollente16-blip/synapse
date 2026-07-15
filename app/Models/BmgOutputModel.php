<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * BmgOutputModel — records harvest events for completed batches.
 *
 * Each row records the final fertilizer weight harvested from a completed
 * batch. The application enforces that output_weight_kg cannot exceed
 * the batch's input_weight_kg (scientific impossibility guard).
 */
class BmgOutputModel extends Model
{
    protected $table            = 'bmg_outputs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'batch_id', 'output_weight_kg', 'harvest_date',
        'quality_grade', 'notes', 'recorded_by',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    // bmg_outputs has no updated_at column; disable by setting empty string.
    protected $updatedField  = '';

    protected $validationRules = [
        'batch_id'         => 'required|is_natural_no_zero',
        'output_weight_kg' => 'required|decimal|greater_than[0]',
        'recorded_by'      => 'required|is_natural_no_zero',
        'quality_grade'    => 'permit_empty|in_list[excellent,good,fair]',
    ];

    /**
     * Record a harvest event for a completed batch.
     * Validates the scientific impossibility rule at the application layer.
     */
    public function recordHarvest(int $batchId, float $outputWeightKg, int $userId, string $qualityGrade = null, string $notes = null): int
    {
        $batchModel = new BmgBatchModel();
        $batch = $batchModel->find($batchId);
        if (!$batch) {
            throw new \RuntimeException('Batch not found.');
        }

        if ($batch['status'] !== 'processing' && $batch['status'] !== 'completed') {
            throw new \RuntimeException('Cannot record output for a batch that is not yet processing.');
        }

        // Scientific impossibility check
        if ($outputWeightKg > $batch['input_weight_kg']) {
            throw new \RuntimeException("Output weight ({$outputWeightKg}kg) cannot exceed input weight ({$batch['input_weight_kg']}kg).");
        }

        $id = $this->insert([
            'batch_id'         => $batchId,
            'output_weight_kg' => $outputWeightKg,
            'harvest_date'     => date('Y-m-d'),
            'quality_grade'    => $qualityGrade,
            'notes'            => $notes,
            'recorded_by'      => $userId,
        ], true);

        // Update the parent batch with output & yield analytics
        $batchModel->recordOutput($batchId, $outputWeightKg, $userId);

        return $id;
    }

    /**
     * Get harvest records for a batch.
     */
    public function getForBatch(int $batchId): array
    {
        return $this->where('batch_id', $batchId)
            ->orderBy('harvest_date', 'DESC')
            ->findAll();
    }

    /**
     * Get total output weight for a given period.
     */
    public function getTotalOutputSince(string $since): float
    {
        $result = $this->selectSum('output_weight_kg')
            ->where('harvest_date >=', $since)
            ->get()
            ->getRowArray();
        return (float) ($result['output_weight_kg'] ?? 0);
    }
}
