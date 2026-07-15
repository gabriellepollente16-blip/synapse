<?php

namespace App\Controllers\Bmg;

use App\Controllers\BaseController;
use App\Models\BmgBatchModel;
use App\Models\BmgOutputModel;

/**
 * OutputController — records harvest events for completed batches.
 *
 * Each row records the final fertilizer weight harvested from a completed
 * batch. Validates that output_weight_kg cannot exceed the batch's
 * input_weight_kg (scientific impossibility guard, enforced both at
 * application layer and DB constraint).
 */
class OutputController extends BaseController
{
    protected BmgOutputModel $outputModel;
    protected BmgBatchModel $batchModel;

    public function __construct()
    {
        $this->outputModel = new BmgOutputModel();
        $this->batchModel  = new BmgBatchModel();
        helper(['form']);
    }

    /**
     * Show form to record output for a batch.
     */
    public function create($batchId)
    {
        $batch = $this->batchModel->find($batchId);
        if (!$batch) {
            return redirect()->to('/bmg/batches')->with('error', 'Batch not found.');
        }
        if (!in_array($batch['status'], ['processing', 'completed'])) {
            return redirect()->to('/bmg/batches/' . $batchId)
                ->with('error', 'Cannot record output: batch is not yet processing.');
        }

        return view('bmg/batches/output_form', [
            'title'   => 'Record Output — SYNAPSE',
            'heading' => 'Record Output for ' . $batch['batch_code'],
            'batch'   => $batch,
        ]);
    }

    /**
     * Store the harvest event.
     */
    public function store($batchId)
    {
        $batch = $this->batchModel->find($batchId);
        if (!$batch) {
            return redirect()->to('/bmg/batches')->with('error', 'Batch not found.');
        }

        $rules = [
            'output_weight_kg' => 'required|decimal|greater_than[0]',
            'quality_grade'    => 'permit_empty|in_list[excellent,good,fair]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }

        $outputWeight = (float) $this->request->getPost('output_weight_kg');
        $userId = (int) session()->get('user_id');

        try {
            $this->outputModel->recordHarvest(
                $batchId,
                $outputWeight,
                $userId,
                $this->request->getPost('quality_grade') ?: null,
                $this->request->getPost('notes') ?: null
            );
        } catch (\RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->to('/bmg/batches/' . $batchId)
            ->with('success', "Output recorded: {$outputWeight}kg. Yield analytics updated.");
    }
}