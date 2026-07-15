<?php

namespace App\Controllers\Bmg;

use App\Controllers\BaseController;
use App\Models\BmgBatchModel;
use App\Models\BmgDrumModel;
use App\Models\BmgInputModel;
use App\Models\BmgOutputModel;
use App\Models\BmgProcessLogModel;

/**
 * BatchController — manages the full lifecycle of a BMG batch.
 *
 * Lifecycle: input → processing → completed (or cancelled)
 *
 * A batch represents one load of waste in one drum, from initial
 * input through final harvest. Each transition is logged and the
 * drum's current_status is kept in sync.
 */
class BatchController extends BaseController
{
    protected BmgBatchModel $batchModel;
    protected BmgDrumModel $drumModel;
    protected BmgInputModel $inputModel;
    protected BmgOutputModel $outputModel;
    protected BmgProcessLogModel $processLogModel;

    public function __construct()
    {
        $this->batchModel      = new BmgBatchModel();
        $this->drumModel       = new BmgDrumModel();
        $this->inputModel      = new BmgInputModel();
        $this->outputModel     = new BmgOutputModel();
        $this->processLogModel = new BmgProcessLogModel();
        helper(['form']);
    }

    /**
     * List all batches (active + recent completed).
     */
    public function index()
    {
        $status = $this->request->getGet('status');

        $builder = $this->batchModel
            ->select('bmg_batches.*, bmg_drums.drum_code, bmg_drums.name AS drum_name, waste_categories.name AS waste_name')
            ->join('bmg_drums', 'bmg_drums.id = bmg_batches.drum_id', 'left')
            ->join('waste_categories', 'waste_categories.id = bmg_batches.waste_category_id', 'left')
            ->orderBy('bmg_batches.id', 'DESC')
            ->limit(100);

        if ($status) {
            $builder->where('bmg_batches.status', $status);
        }

        return view('bmg/batches/index', [
            'title'   => 'BMG Batches — SYNAPSE',
            'heading' => 'Composting Batches',
            'batches' => $builder->findAll(),
            'filter'  => $status,
        ]);
    }

    /**
     * Show batch details.
     */
    public function show($id)
    {
        $batch = $this->batchModel
            ->select('bmg_batches.*, bmg_drums.drum_code, bmg_drums.name AS drum_name, waste_categories.name AS waste_name')
            ->join('bmg_drums', 'bmg_drums.id = bmg_batches.drum_id', 'left')
            ->join('waste_categories', 'waste_categories.id = bmg_batches.waste_category_id', 'left')
            ->where('bmg_batches.id', $id)
            ->first();

        if (!$batch) {
            return redirect()->to('/bmg/batches')->with('error', 'Batch not found.');
        }

        $inputs     = $this->inputModel->getForBatch($id);
        $processLogs = $this->processLogModel->getForBatch($id);
        $outputs    = $this->outputModel->getForBatch($id);

        return view('bmg/batches/show', [
            'title'       => 'Batch Details — SYNAPSE',
            'heading'     => $batch['batch_code'],
            'batch'       => $batch,
            'inputs'      => $inputs,
            'processLogs' => $processLogs,
            'outputs'     => $outputs,
        ]);
    }

    /**
     * Show form to start a new batch on a specific drum.
     */
    public function startOnDrum($drumId)
    {
        $drum = $this->drumModel->find($drumId);
        if (!$drum) {
            return redirect()->to('/bmg/drums')->with('error', 'Drum not found.');
        }

        // Check if drum already has an active batch
        $activeBatch = $this->batchModel
            ->where('drum_id', $drumId)
            ->whereIn('status', ['input', 'processing'])
            ->first();
        if ($activeBatch) {
            return redirect()->to('/bmg/batches/' . $activeBatch['id'])
                ->with('error', 'This drum already has an active batch.');
        }

        $categoryModel = new \App\Models\WasteCategoryModel();
        return view('bmg/batches/start', [
            'title'      => 'Start Batch — SYNAPSE',
            'heading'    => 'Start New Batch on ' . $drum['drum_code'],
            'drum'       => $drum,
            'categories' => $categoryModel->getActive(),
        ]);
    }

    /**
     * Create a new batch.
     */
    public function create()
    {
        $rules = [
            'drum_id'           => 'required|is_natural_no_zero',
            'waste_category_id' => 'required|is_natural_no_zero',
            'input_weight_kg'   => 'required|decimal|greater_than[0]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $drumId = (int) $this->request->getPost('drum_id');
        $inputWeight = (float) $this->request->getPost('input_weight_kg');
        $userId = (int) session()->get('user_id');

        // Check capacity
        $drum = $this->drumModel->find($drumId);
        if ($inputWeight > (float) $drum['capacity_kg']) {
            return redirect()->back()->withInput()
                ->with('error', "Input weight ({$inputWeight}kg) exceeds drum capacity ({$drum['capacity_kg']}kg).");
        }

        // Check for existing active batch
        $existing = $this->batchModel
            ->where('drum_id', $drumId)
            ->whereIn('status', ['input', 'processing'])
            ->first();
        if ($existing) {
            return redirect()->to('/bmg/batches/' . $existing['id'])
                ->with('error', 'This drum already has an active batch.');
        }

        // Create batch + record initial input
        // skipValidation: the controller already validated above.
        $this->batchModel->skipValidation(true);
        $batchId = $this->batchModel->startBatch(
            $drumId,
            (int) $this->request->getPost('waste_category_id'),
            $userId
        );

        if (! $batchId) {
            $errors = $this->batchModel->errors() ?: ['Unknown database error creating batch.'];
            return redirect()->back()->withInput()->with('errors', $errors);
        }

        $this->inputModel->recordInput($batchId, $inputWeight, $userId, $this->request->getPost('notes'));
        $this->drumModel->setStatus($drumId, 'processing');

        // Update the batch's start_date to today (in case it differs from creation time)
        $this->batchModel->update($batchId, ['start_date' => date('Y-m-d')]);

        return redirect()->to('/bmg/batches/' . $batchId)->with('success', 'Batch started.');
    }

    /**
     * Mark a batch as completed (after visual decomposition).
     */
    public function markCompleted($id)
    {
        $batch = $this->batchModel->find($id);
        if (!$batch) {
            return redirect()->to('/bmg/batches')->with('error', 'Batch not found.');
        }
        if ($batch['status'] !== 'processing') {
            return redirect()->to('/bmg/batches/' . $id)->with('error', 'Only processing batches can be marked completed.');
        }

        $userId = (int) session()->get('user_id');
        $this->batchModel->markCompleted($id, $userId);

        // Reset drum status to idle (output recording will re-set to processing if more inputs added)
        $this->drumModel->setStatus($batch['drum_id'], 'idle');

        return redirect()->to('/bmg/batches/' . $id)->with('success', 'Batch marked as completed. Please record the output weight.');
    }

    /**
     * Cancel a batch.
     */
    public function cancel($id)
    {
        $batch = $this->batchModel->find($id);
        if (!$batch) {
            return redirect()->to('/bmg/batches')->with('error', 'Batch not found.');
        }
        if (in_array($batch['status'], ['completed', 'cancelled'])) {
            return redirect()->to('/bmg/batches/' . $id)->with('error', 'Batch cannot be cancelled in its current state.');
        }

        $reason = $this->request->getPost('reason') ?? null;
        $this->batchModel->cancel($id, $reason);
        $this->drumModel->setStatus($batch['drum_id'], 'idle');

        return redirect()->to('/bmg/batches/' . $id)->with('success', 'Batch cancelled.');
    }
}