<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * BmgDrumModel — manages the BMG rotating composting drums.
 *
 * Each drum is a physical unit at a specific location. Drums alternate
 * between "idle" and "processing" states based on batch activity.
 */
class BmgDrumModel extends Model
{
    protected $table            = 'bmg_drums';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;

    protected $allowedFields = [
        'drum_code', 'name', 'location', 'capacity_kg',
        'current_status', 'installation_date', 'is_archived', 'notes',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'drum_code'      => 'required|max_length[50]|is_unique[bmg_drums.drum_code,id,{id}]',
        'name'           => 'required|max_length[150]',
        'capacity_kg'    => 'required|decimal|greater_than[0]',
        'current_status' => 'in_list[idle,processing,maintenance,archived]',
        'is_archived'    => 'in_list[0,1]',
    ];

    /**
     * Get all active (non-archived) drums.
     */
    public function getActive(): array
    {
        return $this->where('is_archived', 0)
            ->orderBy('drum_code', 'ASC')
            ->findAll();
    }

    /**
     * Get all archived drums.
     */
    public function getArchived(): array
    {
        return $this->where('is_archived', 1)
            ->orderBy('drum_code', 'ASC')
            ->findAll();
    }

    /**
     * Get drums by status (idle, processing, maintenance, archived).
     */
    public function getByStatus(string $status): array
    {
        return $this->where('current_status', $status)
            ->where('is_archived', 0)
            ->orderBy('drum_code', 'ASC')
            ->findAll();
    }

    /**
     * Find drum by its drum_code.
     */
    public function findByCode(string $code): ?array
    {
        return $this->where('drum_code', $code)->first();
    }

    /**
     * Update a drum's status (used when batch lifecycle changes).
     */
    public function setStatus(int $drumId, string $status): bool
    {
        return $this->update($drumId, ['current_status' => $status]) !== false;
    }

    /**
     * Get count of drums by status (for dashboard).
     */
    public function getStatusCounts(): array
    {
        $rows = $this->select('current_status, COUNT(*) AS cnt')
            ->where('is_archived', 0)
            ->groupBy('current_status')
            ->findAll();

        $counts = ['idle' => 0, 'processing' => 0, 'maintenance' => 0];
        foreach ($rows as $row) {
            $counts[$row['current_status']] = (int) $row['cnt'];
        }
        return $counts;
    }

    /**
     * Find drums that have been idle for more than N days (for alerts).
     */
    public function getIdleForMoreThan(int $days = 30): array
    {
        // Drums that are idle AND have no batch in last N days
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        return $this->select('bmg_drums.*, MAX(bmg_batches.start_date) AS last_batch_date')
            ->join('bmg_batches', 'bmg_batches.drum_id = bmg_drums.id', 'left')
            ->where('bmg_drums.current_status', 'idle')
            ->where('bmg_drums.is_archived', 0)
            ->groupBy('bmg_drums.id')
            ->having('last_batch_date IS NULL OR last_batch_date <', $cutoff)
            ->findAll();
    }
}
