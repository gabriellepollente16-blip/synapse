<?php

namespace App\Controllers\Bmg;

use App\Controllers\BaseController;
use App\Models\BmgDrumModel;
use App\Models\BmgBatchModel;

/**
 * DrumController — manages BMG (rotating composting) drums.
 *
 * Each drum is a physical composting unit at a specific location.
 * Drums alternate between "idle" and "processing" states based on
 * batch activity. Archived drums are kept for historical reference.
 */
class DrumController extends BaseController
{
    protected BmgDrumModel $drumModel;
    protected BmgBatchModel $batchModel;

    public function __construct()
    {
        $this->drumModel  = new BmgDrumModel();
        $this->batchModel = new BmgBatchModel();
        helper(['form']);
    }

    /**
     * List all drums with optional search and status filter.
     */
    public function index()
    {
        $q      = trim((string) $this->request->getGet('q'));
        $status = $this->request->getGet('status');
        $allowed = ['idle', 'processing', 'maintenance', 'archived'];

        // Build the query
        $builder = $this->drumModel;

        // By default we show active (non-archived) drums. If the filter
        // asks specifically for archived ones, include them.
        if ($status === 'archived') {
            $builder = $builder->onlyDeleted();
        } else {
            $builder = $builder->where('is_archived', 0);
        }

        if ($q !== '') {
            $builder = $builder->groupStart()
                ->like('drum_code', $q)
                ->orLike('name', $q)
                ->orLike('location', $q)
                ->groupEnd();
        }

        if (in_array($status, $allowed, true)) {
            $builder = $builder->where('current_status', $status);
        }

        $drums = $builder
            ->orderBy('drum_code', 'ASC')
            ->findAll();

        $data = [
            'title'   => 'BMG Drums — SYNAPSE',
            'heading' => 'Composting Drums',
            'drums'   => $drums,
            'counts'  => $this->drumModel->getStatusCounts(),
            'search'  => $q,
            'filter'  => $status,
        ];
        return view('bmg/drums/index', $data);
    }

    /**
     * Show form to create a new drum.
     */
    public function create()
    {
        return view('bmg/drums/create', [
            'title'   => 'New Drum — SYNAPSE',
            'heading' => 'Add New BMG Drum',
        ]);
    }

    /**
     * Store a new drum.
     */
    public function store()
    {
        $rules = [
            'drum_code' => 'required|max_length[50]|is_unique[bmg_drums.drum_code]',
            'name'      => 'required|max_length[150]',
            'capacity_kg' => 'required|decimal|greater_than[0]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'drum_code'      => strtoupper(trim($this->request->getPost('drum_code'))),
            'name'           => $this->request->getPost('name'),
            'location'       => $this->request->getPost('location'),
            'capacity_kg'    => $this->request->getPost('capacity_kg'),
            'current_status' => 'idle',
            'installation_date' => $this->request->getPost('installation_date') ?: null,
            'notes'          => $this->request->getPost('notes'),
        ];

        // Disable the model's auto-validation rules here — the controller
        // already validated via $this->validate() above and we don't want
        // a second pass to silently fail and return false from insert().
        $this->drumModel->skipValidation(true);
        $id = $this->drumModel->insert($data, true);

        if (! $id) {
            $errors = $this->drumModel->errors() ?: ['Unknown database error.'];
            return redirect()->back()->withInput()->with('errors', $errors);
        }

        return redirect()->to('/bmg/drums/' . $id)->with('success', 'Drum added successfully.');
    }

    /**
     * Show drum details with active batch info.
     */
    public function show($id)
    {
        // Include soft-deleted (archived) drums so users can still view them
        $drum = $this->drumModel->withDeleted()->find($id);
        if (!$drum) {
            return redirect()->to('/bmg/drums')->with('error', 'Drum not found.');
        }

        // Find active batch for this drum
        $activeBatch = $this->batchModel
            ->where('drum_id', $id)
            ->whereIn('status', ['input', 'processing'])
            ->orderBy('id', 'DESC')
            ->first();

        // Recent batches for this drum
        $recentBatches = $this->batchModel
            ->where('drum_id', $id)
            ->orderBy('id', 'DESC')
            ->limit(10)
            ->findAll();

        return view('bmg/drums/show', [
            'title'         => 'Drum Details — SYNAPSE',
            'heading'       => $drum['drum_code'] . ' — ' . $drum['name'],
            'drum'          => $drum,
            'activeBatch'   => $activeBatch,
            'recentBatches' => $recentBatches,
        ]);
    }

    /**
     * Show edit form.
     */
    public function edit($id)
    {
        $drum = $this->drumModel->withDeleted()->find($id);
        if (!$drum) {
            return redirect()->to('/bmg/drums')->with('error', 'Drum not found.');
        }

        return view('bmg/drums/edit', [
            'title' => 'Edit Drum — SYNAPSE',
            'drum'  => $drum,
        ]);
    }

    /**
     * Update a drum.
     */
    public function update($id)
    {
        $drum = $this->drumModel->find($id);
        if (!$drum) {
            return redirect()->to('/bmg/drums')->with('error', 'Drum not found.');
        }

        $rules = [
            'name'       => 'required|max_length[150]',
            'capacity_kg' => 'required|decimal|greater_than[0]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }

        $this->drumModel->update($id, [
            'name'           => $this->request->getPost('name'),
            'location'       => $this->request->getPost('location'),
            'capacity_kg'    => $this->request->getPost('capacity_kg'),
            'installation_date' => $this->request->getPost('installation_date') ?: null,
            'current_status' => $this->request->getPost('current_status') ?: $drum['current_status'],
            'notes'          => $this->request->getPost('notes'),
        ]);

        return redirect()->to('/bmg/drums/' . $id)->with('success', 'Drum updated.');
    }

    /**
     * Archive a drum (soft delete + flag).
     */
    public function archive($id)
    {
        $drum = $this->drumModel->find($id);
        if (!$drum) {
            return redirect()->to('/bmg/drums')->with('error', 'Drum not found.');
        }

        $this->drumModel->update($id, ['is_archived' => 1, 'current_status' => 'archived']);
        $this->drumModel->delete($id);  // soft delete

        return redirect()->to('/bmg/drums')->with('success', 'Drum archived.');
    }

    /**
     * Permanently delete a drum (hard delete).
     *
     * Refuses if any batches reference this drum — the operator should
     * archive the drum instead. This protects the audit trail and avoids
     * orphaning historical batch records.
     */
    public function delete($id)
    {
        $drum = $this->drumModel->find($id);
        if (! $drum) {
            return redirect()->to('/bmg/drums')->with('error', 'Drum not found.');
        }

        $batchCount = $this->batchModel->where('drum_id', $id)->countAllResults();
        if ($batchCount > 0) {
            return redirect()->to('/bmg/drums/' . $id)->with(
                'error',
                "Cannot delete: drum has {$batchCount} batch record(s) on file. Archive it instead."
            );
        }

        $this->drumModel->delete($id, true); // true = hard delete (purge)
        return redirect()->to('/bmg/drums')->with('success', 'Drum permanently deleted.');
    }

    /**
     * Quick action: set drum to idle.
     */
    public function markIdle($id)
    {
        return $this->transitionStatus($id, 'idle', 'Drum set to idle.');
    }

    /**
     * Quick action: set drum to processing.
     *
     * Refuses if the drum already has an active batch (status input/processing).
     * The operator should open and complete the existing batch first, or use
     * the "Start Batch" action to track a new batch properly.
     */
    public function markProcessing($id)
    {
        $drum = $this->drumModel->find($id);
        if (! $drum) {
            return redirect()->to('/bmg/drums')->with('error', 'Drum not found.');
        }

        $existing = $this->batchModel
            ->where('drum_id', $id)
            ->whereIn('status', ['input', 'processing'])
            ->first();
        if ($existing) {
            return redirect()->to('/bmg/drums/' . $id)->with(
                'error',
                'Drum already has an active batch. Complete the current batch first.'
            );
        }

        return $this->transitionStatus($id, 'processing', 'Drum set to processing.');
    }

    /**
     * Quick action: set drum to maintenance.
     */
    public function markMaintenance($id)
    {
        return $this->transitionStatus($id, 'maintenance', 'Drum set to maintenance.');
    }

    /**
     * Quick action: "Done" — completes any active batch and returns
     * the drum to idle. This is the typical "harvest finished" flow.
     */
    public function completeAndIdle($id)
    {
        $drum = $this->drumModel->find($id);
        if (! $drum) {
            return redirect()->to('/bmg/drums')->with('error', 'Drum not found.');
        }

        // Find an active batch
        $active = $this->batchModel
            ->where('drum_id', $id)
            ->whereIn('status', ['input', 'processing'])
            ->first();

        if (! $active) {
            // No active batch — just flip to idle
            $this->drumModel->setStatus($id, 'idle');
            return redirect()->to('/bmg/drums/' . $id)->with('success', 'Drum is now idle.');
        }

        // Mark the batch as completed (skips strict processing-only check)
        $userId = (int) session()->get('user_id');
        $this->batchModel->markCompleted($active['id'], $userId);
        $this->drumModel->setStatus($id, 'idle');

        return redirect()->to('/bmg/drums/' . $id)->with(
            'success',
            "Batch {$active['batch_code']} marked complete. Drum is now idle. Don't forget to record the harvest weight."
        );
    }

    /**
     * Internal: apply a status transition to a drum, with consistent
     * error handling and redirect.
     */
    protected function transitionStatus(int $id, string $status, string $successMessage)
    {
        $drum = $this->drumModel->find($id);
        if (! $drum) {
            return redirect()->to('/bmg/drums')->with('error', 'Drum not found.');
        }

        $this->drumModel->setStatus($id, $status);
        return redirect()->to('/bmg/drums/' . $id)->with('success', $successMessage);
    }
}