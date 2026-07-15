<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds the `bmg_outputs` table for recording harvest events.
 *
 * Each row records the final fertilizer weight harvested from a completed
 * batch. The application AND database enforce that output_weight_kg cannot
 * exceed the batch's input_weight_kg (scientific impossibility guard).
 */
class AddBmgOutputsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'batch_id'        => ['type' => 'BIGINT', 'unsigned' => true],
            'output_weight_kg'=> ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'harvest_date'    => ['type' => 'DATE', 'null' => true],
            'quality_grade'   => ['type' => 'ENUM', 'constraint' => ['excellent', 'good', 'fair'], 'null' => true],
            'notes'           => ['type' => 'TEXT', 'null' => true],
            'recorded_by'     => ['type' => 'BIGINT', 'unsigned' => true],
            'created_at'      => ['type' => 'TIMESTAMP', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('batch_id', 'bmg_batches', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('recorded_by', 'users', 'id', '', 'RESTRICT');
        $this->forge->addKey('batch_id');
        $this->forge->addKey('harvest_date');
        $this->forge->createTable('bmg_outputs', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('bmg_outputs', true);
    }
}
