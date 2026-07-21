<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds the `bmg_inputs` table for the input tracking audit trail.
 *
 * While most batches have a single input record, this table allows
 * partial inputs (e.g., waste added over several days) to be tracked
 * individually. The sum of all inputs equals the batch's input_weight_kg.
 */
class AddBmgInputsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'batch_id'    => ['type' => 'BIGINT', 'unsigned' => true],
            'weight_kg'   => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'recorded_at' => ['type' => 'DATETIME', 'null' => true],
            'recorded_by' => ['type' => 'BIGINT', 'unsigned' => true],
            'notes'       => ['type' => 'TEXT', 'null' => true],
            'created_at'  => ['type' => 'TIMESTAMP', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('batch_id', 'bmg_batches', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('recorded_by', 'users', 'id', '', 'RESTRICT');
        $this->forge->addKey('batch_id');
        $this->forge->createTable('bmg_inputs', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('bmg_inputs', true);
    }
}
