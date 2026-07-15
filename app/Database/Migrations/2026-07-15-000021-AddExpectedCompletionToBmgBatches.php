<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds `expected_completion_date` and `expected_duration_days` to
 * `bmg_batches` so the system can show staff a clear estimate of when
 * a batch should be ready for harvest, derived from the waste
 * category's typical decomposition duration.
 *
 * The values are computed when a batch is started (see
 * BmgBatchModel::startBatch) using the waste category's reference
 * expected duration. They are advisory — actual completion happens
 * when the operator marks the batch complete.
 */
class AddExpectedCompletionToBmgBatches extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('bmg_batches', [
            'expected_duration_days' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'comment'    => 'Estimated decomposition duration in days, derived from the waste category reference duration at start time.',
            ],
            'expected_completion_date' => [
                'type'       => 'DATE',
                'null'       => true,
                'comment'    => 'Estimated date the batch will be ready for harvest (start_date + expected_duration_days).',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('bmg_batches', ['expected_completion_date', 'expected_duration_days']);
    }
}
