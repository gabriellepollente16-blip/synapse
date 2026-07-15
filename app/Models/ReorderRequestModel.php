<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * ReorderRequestModel — manages medicine procurement workflow.
 *
 * Auto-triggered when medicine stock falls below reorder_level. Routes
 * to procurement personnel through a state-machine workflow:
 *   pending → approved → ordered → received
 * (cancelled is also possible from any non-received state)
 */
class ReorderRequestModel extends Model
{
    protected $table            = 'reorder_requests';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'medicine_id', 'requested_quantity', 'current_stock', 'reorder_level',
        'urgency', 'status', 'requested_by', 'approved_by', 'procurement_notes',
        'order_date', 'expected_delivery_date', 'actual_delivery_date',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'medicine_id'        => 'required|is_natural_no_zero',
        'requested_quantity' => 'required|is_natural',
        'current_stock'      => 'required|is_natural',
        'reorder_level'      => 'required|is_natural',
        'urgency'            => 'in_list[low,medium,high,critical]',
        'status'             => 'in_list[pending,approved,ordered,received,cancelled]',
        'requested_by'       => 'required|is_natural_no_zero',
    ];

    /**
     * Create an auto-triggered reorder request for a medicine.
     * Idempotent: if there's already an open request, returns its ID.
     */
    public function createAuto(int $medicineId, int $currentStock, int $reorderLevel, int $userId): int
    {
        // Check for an existing open request
        $existing = $this->where('medicine_id', $medicineId)
            ->whereIn('status', ['pending', 'approved', 'ordered'])
            ->first();

        if ($existing) {
            return (int) $existing['id'];
        }

        // Calculate suggested quantity (2x reorder level difference + buffer)
        $suggested = max(($reorderLevel * 2) - $currentStock, 50);

        // Determine urgency based on stock vs level
        $urgency = 'medium';
        if ($currentStock === 0) {
            $urgency = 'critical';
        } elseif ($currentStock <= ($reorderLevel * 0.5)) {
            $urgency = 'high';
        } elseif ($currentStock <= $reorderLevel * 0.75) {
            $urgency = 'medium';
        } else {
            $urgency = 'low';
        }

        return $this->insert([
            'medicine_id'        => $medicineId,
            'requested_quantity' => $suggested,
            'current_stock'      => $currentStock,
            'reorder_level'      => $reorderLevel,
            'urgency'            => $urgency,
            'status'             => 'pending',
            'requested_by'       => $userId,
        ], true);
    }

    /**
     * Check if a medicine has an open reorder request.
     */
    public function hasOpenRequest(int $medicineId): bool
    {
        return $this->where('medicine_id', $medicineId)
            ->whereIn('status', ['pending', 'approved', 'ordered'])
            ->countAllResults() > 0;
    }

    /**
     * Advance a request to a new status. Returns false on invalid transitions.
     */
    public function advanceStatus(int $id, string $newStatus, int $userId = null): bool
    {
        $validTransitions = [
            'pending'   => ['approved', 'cancelled'],
            'approved'  => ['ordered', 'cancelled'],
            'ordered'   => ['received', 'cancelled'],
            'received'  => [],
            'cancelled' => [],
        ];

        $current = $this->find($id);
        if (!$current) return false;

        if (!in_array($newStatus, $validTransitions[$current['status']] ?? [])) {
            return false;
        }

        $update = ['status' => $newStatus];

        if ($newStatus === 'approved' && $userId) {
            $update['approved_by'] = $userId;
        }
        if ($newStatus === 'ordered') {
            $update['order_date'] = date('Y-m-d');
        }
        if ($newStatus === 'received') {
            $update['actual_delivery_date'] = date('Y-m-d');
        }

        return $this->update($id, $update);
    }

    /**
     * Get pending requests for procurement staff.
     */
    public function getPending(): array
    {
        return $this->select('reorder_requests.*, medicines.brand_name, medicines.generic_name')
            ->join('medicines', 'medicines.id = reorder_requests.medicine_id', 'left')
            ->where('status', 'pending')
            ->orderBy('urgency', 'DESC')
            ->orderBy('created_at', 'ASC')
            ->findAll();
    }
}
