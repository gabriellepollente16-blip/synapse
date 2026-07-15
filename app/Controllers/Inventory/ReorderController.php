<?php

namespace App\Controllers\Inventory;

use App\Controllers\BaseController;
use App\Models\ReorderRequestModel;
use App\Models\MedicineModel;
use App\Models\MedicineBatchModel;
use App\Libraries\ProcurementRouter;

/**
 * ReorderController — medicine procurement workflow.
 *
 * Auto-triggers reorder requests when medicine stock falls below
 * reorder_level. Routes to procurement personnel through a state-machine
 * workflow:
 *   pending → approved → ordered → received
 *   (any non-received state can transition to cancelled)
 */
class ReorderController extends BaseController
{
    protected ReorderRequestModel $reorderModel;
    protected MedicineModel $medicineModel;
    protected MedicineBatchModel $batchModel;
    protected ProcurementRouter $procurementRouter;

    public function __construct()
    {
        $this->reorderModel      = new ReorderRequestModel();
        $this->medicineModel     = new MedicineModel();
        $this->batchModel        = new MedicineBatchModel();
        $this->procurementRouter = new ProcurementRouter();
        helper(['form']);
    }

    /**
     * List all reorder requests.
     */
    public function index()
    {
        $status = $this->request->getGet('status');

        $builder = $this->reorderModel
            ->select('reorder_requests.*, medicines.generic_name, medicines.brand_name, medicines.unit')
            ->join('medicines', 'medicines.id = reorder_requests.medicine_id', 'left')
            ->orderBy('reorder_requests.created_at', 'DESC')
            ->limit(200);

        if ($status) {
            $builder->where('reorder_requests.status', $status);
        }

        return view('inventory/reorders/index', [
            'title'   => 'Reorder Requests — SYNAPSE',
            'heading' => 'Medicine Reorder Requests',
            'requests' => $builder->findAll(),
            'filter'  => $status,
        ]);
    }

    /**
     * Show reorder request details.
     */
    public function show($id)
    {
        $request = $this->reorderModel
            ->select('reorder_requests.*, medicines.generic_name, medicines.brand_name, medicines.unit')
            ->join('medicines', 'medicines.id = reorder_requests.medicine_id', 'left')
            ->where('reorder_requests.id', $id)
            ->first();

        if (!$request) {
            return redirect()->to('/inventory/reorders')->with('error', 'Reorder request not found.');
        }

        $currentStock = $this->batchModel->getTotalStock((int) $request['medicine_id']);

        return view('inventory/reorders/show', [
            'title'         => 'Reorder Details — SYNAPSE',
            'heading'       => 'Reorder Request #' . $id,
            'reorder'       => $request,
            'current_stock' => $currentStock,
        ]);
    }

    /**
     * Manually create a new reorder request.
     */
    public function create()
    {
        return view('inventory/reorders/create', [
            'title'     => 'New Reorder — SYNAPSE',
            'heading'   => 'Create Reorder Request',
            'medicines' => $this->medicineModel->where('is_active', 1)->orderBy('generic_name', 'ASC')->findAll(),
        ]);
    }

    /**
     * Store a manually-created reorder request.
     */
    public function store()
    {
        $rules = [
            'medicine_id'        => 'required|is_natural_no_zero',
            'requested_quantity' => 'required|is_natural',
            'urgency'            => 'required|in_list[low,medium,high,critical]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $medicineId = (int) $this->request->getPost('medicine_id');
        $medicine = $this->medicineModel->find($medicineId);
        if (!$medicine) {
            return redirect()->back()->withInput()->with('error', 'Medicine not found.');
        }

        $userId = (int) session()->get('user_id');
        $currentStock = $this->batchModel->getTotalStock($medicineId);

        if ($this->reorderModel->hasOpenRequest($medicineId)) {
            return redirect()->back()->withInput()->with('error', 'There is already an open reorder for this medicine.');
        }

        $reorderId = $this->reorderModel->insert([
            'medicine_id'        => $medicineId,
            'requested_quantity' => (int) $this->request->getPost('requested_quantity'),
            'current_stock'      => $currentStock,
            'reorder_level'      => (int) ($medicine['reorder_threshold'] ?? 0),
            'urgency'            => $this->request->getPost('urgency'),
            'status'             => 'pending',
            'requested_by'       => $userId,
            'procurement_notes'  => $this->request->getPost('procurement_notes'),
        ], true);

        return redirect()->to('/inventory/reorders/' . $reorderId)->with('success', 'Reorder request created.');
    }

    /**
     * Approve a pending request.
     */
    public function approve($id)
    {
        $request = $this->reorderModel->find($id);
        if (!$request) {
            return redirect()->to('/inventory/reorders')->with('error', 'Reorder request not found.');
        }

        $userId = (int) session()->get('user_id');
        $ok = $this->reorderModel->advanceStatus($id, 'approved', $userId);

        if (!$ok) {
            return redirect()->to('/inventory/reorders/' . $id)
                ->with('error', 'Cannot approve: invalid status transition.');
        }

        return redirect()->to('/inventory/reorders/' . $id)->with('success', 'Reorder approved.');
    }

    /**
     * Mark as ordered (sent to supplier).
     */
    public function order($id)
    {
        $request = $this->reorderModel->find($id);
        if (!$request) {
            return redirect()->to('/inventory/reorders')->with('error', 'Reorder request not found.');
        }

        $ok = $this->reorderModel->advanceStatus($id, 'ordered');

        if (!$ok) {
            return redirect()->to('/inventory/reorders/' . $id)
                ->with('error', 'Cannot mark as ordered: invalid status transition.');
        }

        return redirect()->to('/inventory/reorders/' . $id)->with('success', 'Marked as ordered.');
    }

    /**
     * Mark as received (medicine stock should be updated).
     */
    public function receive($id)
    {
        $request = $this->reorderModel->find($id);
        if (!$request) {
            return redirect()->to('/inventory/reorders')->with('error', 'Reorder request not found.');
        }

        $ok = $this->reorderModel->advanceStatus($id, 'received');

        if (!$ok) {
            return redirect()->to('/inventory/reorders/' . $id)
                ->with('error', 'Cannot mark as received: invalid status transition.');
        }

        return redirect()->to('/inventory/reorders/' . $id)
            ->with('success', 'Reorder received. Remember to add the new batch in inventory.');
    }

    /**
     * Cancel a reorder request.
     */
    public function cancel($id)
    {
        $request = $this->reorderModel->find($id);
        if (!$request) {
            return redirect()->to('/inventory/reorders')->with('error', 'Reorder request not found.');
        }

        $ok = $this->reorderModel->advanceStatus($id, 'cancelled');
        if (!$ok) {
            return redirect()->to('/inventory/reorders/' . $id)
                ->with('error', 'Cannot cancel: invalid status transition.');
        }

        return redirect()->to('/inventory/reorders/' . $id)->with('success', 'Reorder cancelled.');
    }

    /**
     * Run automatic reorder check across all medicines.
     */
    public function autoCheck()
    {
        $userId = (int) session()->get('user_id');
        $created = $this->procurementRouter->checkAll($userId);

        if (empty($created)) {
            return redirect()->to('/inventory/reorders')->with('info', 'No new reorder requests needed.');
        }

        return redirect()->to('/inventory/reorders')->with('success',
            'Auto-check complete: ' . count($created) . ' new reorder request(s) created.');
    }

    /**
     * Manually trigger reorder for a specific medicine.
     */
    public function trigger($medicineId)
    {
        $userId = (int) session()->get('user_id');
        $reorderId = $this->procurementRouter->checkAndReorder($medicineId, $userId);

        if ($reorderId === null) {
            return redirect()->back()->with('info', 'No reorder needed (stock is above threshold).');
        }

        return redirect()->to('/inventory/reorders/' . $reorderId)
            ->with('success', 'Reorder request created.');
    }
}