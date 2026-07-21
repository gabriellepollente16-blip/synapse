<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds the `bmg_process_logs` table for tracking process observations
 * during the decomposition period.
 *
 * Allows facilities staff to log temperature, moisture levels, and free-text
 * observations throughout the composting cycle. Used for debugging slow or
 * unusual batches.
 */
class AddBmgProcessLogsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'batch_id'          => ['type' => 'BIGINT', 'unsigned' => true],
            'log_date'          => ['type' => 'DATE', 'null' => true],
            'observation_note'  => ['type' => 'TEXT', 'null' => true],
            'temperature_celsius'=> ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'moisture_level'    => ['type' => 'ENUM', 'constraint' => ['low', 'normal', 'high'], 'null' => true],
            'recorded_by'       => ['type' => 'BIGINT', 'unsigned' => true],
            'created_at'        => ['type' => 'TIMESTAMP', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('batch_id', 'bmg_batches', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('recorded_by', 'users', 'id', '', 'RESTRICT');
        $this->forge->addKey('batch_id');
        $this->forge->addKey('log_date');
        $this->forge->createTable('bmg_process_logs', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('bmg_process_logs', true);
    }
}
