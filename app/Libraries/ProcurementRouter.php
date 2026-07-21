<?php

namespace App\Libraries;

use App\Models\ReorderRequestModel;
use App\Models\MedicineModel;
use App\Models\MedicineBatchModel;
use App\Models\NotificationModel;

/**
 * ProcurementRouter — auto-triggers reorder requests when stock is low.
 *
 * The capstone's medicine inventory module integrates with the
 * procurement process. This service:
 *   1. Monitors medicine stock against reorder_level thresholds
 *   2. Auto-creates reorder requests when triggered
 *   3. Sends notifications to procurement personnel
 *
 * Workflow: pending → approved → ordered → received
 */
class ProcurementRouter
{
    protected ReorderRequestModel $reorderModel;
    protected MedicineModel $medicineModel;
    protected MedicineBatchModel $batchModel;
    protected NotificationModel $notificationModel;

    public function __construct()
    {
        $this->reorderModel = new ReorderRequestModel();
        $this->medicineModel = new MedicineModel();
        $this->batchModel = new MedicineBatchModel();
        $this->notificationModel = new NotificationModel();
    }

    /**
     * Check a single medicine's stock and create a reorder if needed.
     *
     * @param int  $medicineId
     * @param int  $userId     User who triggered the check (for audit)
     * @return int|null  Reorder request ID if created, null if not needed
     */
    public function checkAndReorder(int $medicineId, int $userId): ?int
    {
        $medicine = $this->medicineModel->find($medicineId);
        if (!$medicine) {
            return null;
        }

        $totalStock = $this->batchModel->getTotalStock($medicineId);
        $reorderLevel = (int) ($medicine['reorder_threshold'] ?? 0);

        if ($reorderLevel <= 0 || $totalStock > $reorderLevel) {
            return null;
        }

        // Check for an existing open request
        if ($this->reorderModel->hasOpenRequest($medicineId)) {
            return null;
        }

        $reorderId = $this->reorderModel->createAuto(
            $medicineId,
            $totalStock,
            $reorderLevel,
            $userId
        );

        // Notify procurement staff
        $this->notifyProcurement($reorderId, $medicine);

        return $reorderId;
    }

    /**
     * Check all medicines and trigger reorders as needed.
     *
     * @param int $userId
     * @return array List of reorder IDs created
     */
    public function checkAll(int $userId): array
    {
        $created = [];
        $medicines = $this->medicineModel->where('is_active', 1)->findAll();

        foreach ($medicines as $medicine) {
            $reorderId = $this->checkAndReorder((int) $medicine['id'], $userId);
            if ($reorderId) {
                $created[] = $reorderId;
            }
        }

        return $created;
    }

    /**
     * Notify procurement staff of a new reorder request.
     */
    protected function notifyProcurement(int $reorderId, array $medicine): void
    {
        $medicineName = $medicine['brand_name'] ?? $medicine['generic_name'] ?? "Medicine #{$medicine['id']}";

        // Use createNotification helper which serializes module/entityId
        // into the JSON `data` column. user_id=null triggers broadcast
        // resolution for admin/procurement roles inside NotificationModel.
        $this->notificationModel->createNotification(
            null,
            'reorder_request',
            "Reorder Required: {$medicineName}",
            'Stock has fallen to the reorder threshold. A new reorder request has been created.',
            'inventory',
            'reorder_requests',
            $reorderId
        );
    }
}