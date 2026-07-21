<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds the `bmg_batches` table — the central lifecycle record for the
 * BMG (Biodegradable Waste Management) module.
 *
 * One batch = one load of waste in one drum, from input through
 * processing to output. Computed fields:
 *   - duration_days      = completion_date - start_date
 *   - yield_percentage   = (output_weight_kg / input_weight_kg) * 100
 *   - mass_reduction_pct = 100 - yield_percentage
 */
class AddBmgBatchesTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                  => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'batch_code'          => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'drum_id'             => ['type' => 'BIGINT', 'unsigned' => true],
            'waste_category_id'   => ['type' => 'INT', 'unsigned' => true],
            'status'              => ['type' => 'ENUM', 'constraint' => ['input', 'processing', 'completed', 'cancelled'], 'default' => 'input'],
            'input_weight_kg'     => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00],
            'input_recorded_at'   => ['type' => 'DATETIME', 'null' => true],
            'input_recorded_by'   => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'start_date'          => ['type' => 'DATE', 'null' => true],
            'completion_date'     => ['type' => 'DATE', 'null' => true],
            'duration_days'       => ['type' => 'INT', 'null' => true],
            'output_weight_kg'    => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
            'yield_percentage'    => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'mass_reduction_pct'  => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'completed_by'        => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'output_recorded_at'  => ['type' => 'DATETIME', 'null' => true],
            'notes'               => ['type' => 'TEXT', 'null' => true],
            'created_at'          => ['type' => 'TIMESTAMP', 'null' => true],
            'updated_at'          => ['type' => 'TIMESTAMP', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('drum_id', 'bmg_drums', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('waste_category_id', 'waste_categories', 'id', '', 'RESTRICT');
        $this->forge->addForeignKey('input_recorded_by', 'users', 'id', '', 'SET NULL');
        $this->forge->addForeignKey('completed_by', 'users', 'id', '', 'SET NULL');
        $this->forge->addKey('status', false, false, 'idx_batches_status');
        $this->forge->addKey('drum_id', false, false, 'idx_batches_drum');
        $this->forge->addKey('start_date', false, false, 'idx_batches_start_date');
        $this->forge->createTable('bmg_batches', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('bmg_batches', true);
    }
}
