<?php

namespace App\Controllers\Bmg;

use App\Controllers\BaseController;
use App\Models\BmgBatchModel;
use App\Models\BmgInputModel;

/**
 * InputController — records input events for a batch.
 *
 * While most batches have a single input record, this controller allows
 * additional inputs to be added over time. The sum of all inputs equals
 * the batch's input_weight_kg total.
 */
class InputController extends BaseController
{
    protected BmgInputModel $inputModel;
    protected BmgBatchModel $batchModel;

    public function __construct()
    {
        $this->inputModel = new BmgInputModel();
        $this->batchModel = new BmgBatchModel();
        helper(['form']);
    }

    /**
     * Show form to add an input record.
     */
    public function create($batchId)
    {
        $batch = $this->batchModel->find($batchId);
        if (!$batch) {
            return redirect()->to('/bmg/batches')->with('error', 'Batch not found.');
        }
        if (in_array($batch['status'], ['completed', 'cancelled'])) {
            return redirect()->to('/bmg/batches/' . $batchId)->with('error', 'Cannot add input to a finished batch.');
        }

        return view('bmg/batches/input_form', [
            'title'   => 'Record Input — SYNAPSE',
            'heading' => 'Record Input for ' . $batch['batch_code'],
            'batch'   => $batch,
        ]);
    }

    /**
     * Store a new input record.
     */
    public function store($batchId)
    {
        $batch = $this->batchModel->find($batchId);
        if (!$batch) {
            return redirect()->to('/bmg/batches')->with('error', 'Batch not found.');
        }

        $rules = [
            'weight_kg' => 'required|decimal|greater_than[0]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }

        $weight = (float) $this->request->getPost('weight_kg');
        $userId = (int) session()->get('user_id');

        try {
            $this->inputModel->recordInput($batchId, $weight, $userId, $this->request->getPost('notes'));
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->to('/bmg/batches/' . $batchId)->with('success', "Input recorded: {$weight}kg.");
    }
}