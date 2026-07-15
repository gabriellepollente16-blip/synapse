<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * BmgProcessLogModel — records process observations during decomposition.
 *
 * Allows facilities staff to log temperature, moisture levels, and free-text
 * observations throughout the composting cycle. Used for debugging slow or
 * unusual batches.
 */
class BmgProcessLogModel extends Model
{
    protected $table            = 'bmg_process_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'batch_id', 'log_date', 'observation_note',
        'temperature_celsius', 'moisture_level', 'recorded_by',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    // bmg_process_logs has no updated_at column; disable by setting empty string.
    protected $updatedField  = '';

    protected $validationRules = [
        'batch_id'    => 'required|is_natural_no_zero',
        'log_date'    => 'required|valid_date',
        'recorded_by' => 'required|is_natural_no_zero',
        'moisture_level' => 'permit_empty|in_list[low,normal,high]',
        'temperature_celsius' => 'permit_empty|decimal',
    ];

    /**
     * Add a process log entry for a batch.
     */
    public function addLog(int $batchId, string $observation, int $userId, float $temp = null, string $moisture = null): int
    {
        return $this->insert([
            'batch_id'           => $batchId,
            'log_date'           => date('Y-m-d'),
            'observation_note'   => $observation,
            'temperature_celsius'=> $temp,
            'moisture_level'     => $moisture,
            'recorded_by'        => $userId,
        ], true);
    }

    /**
     * Get all process logs for a batch (chronological).
     */
    public function getForBatch(int $batchId): array
    {
        return $this->where('batch_id', $batchId)
            ->orderBy('log_date', 'ASC')
            ->findAll();
    }
}
