<?php

namespace App\Controllers\Bmg;

use App\Controllers\BaseController;
use App\Models\BmgBatchModel;
use App\Models\BmgProcessLogModel;

/**
 * ProcessController — records process observations during decomposition.
 *
 * Allows facilities staff to log temperature, moisture levels, and free-text
 * observations throughout the composting cycle. Useful for debugging slow
 * or unusual batches.
 */
class ProcessController extends BaseController
{
    protected BmgProcessLogModel $processLogModel;
    protected BmgBatchModel $batchModel;

    public function __construct()
    {
        $this->processLogModel = new BmgProcessLogModel();
        $this->batchModel      = new BmgBatchModel();
        helper(['form']);
    }

    /**
     * Show form to add a process log.
     */
    public function create($batchId)
    {
        $batch = $this->batchModel->find($batchId);
        if (!$batch) {
            return redirect()->to('/bmg/batches')->with('error', 'Batch not found.');
        }

        return view('bmg/batches/process_log_form', [
            'title'   => 'Add Observation — SYNAPSE',
            'heading' => 'Process Log for ' . $batch['batch_code'],
            'batch'   => $batch,
        ]);
    }

    /**
     * Store a process log.
     */
    public function store($batchId)
    {
        $batch = $this->batchModel->find($batchId);
        if (!$batch) {
            return redirect()->to('/bmg/batches')->with('error', 'Batch not found.');
        }

        $rules = [
            'observation_note'    => 'permit_empty|string|max_length[1000]',
            'temperature_celsius' => 'permit_empty|decimal',
            'moisture_level'      => 'permit_empty|in_list[low,normal,high]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }

        $userId = (int) session()->get('user_id');
        $this->processLogModel->addLog(
            $batchId,
            (string) $this->request->getPost('observation_note'),
            $userId,
            $this->request->getPost('temperature_celsius') !== '' ? (float) $this->request->getPost('temperature_celsius') : null,
            $this->request->getPost('moisture_level') ?: null
        );

        return redirect()->to('/bmg/batches/' . $batchId)->with('success', 'Observation logged.');
    }
}