<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds the `bmg_drums` table for the Biodegradable Waste Management
 * (BMG) module. Each drum is a rotating composting unit.
 */
class AddBmgDrumsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'drum_code'         => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'name'              => ['type' => 'VARCHAR', 'constraint' => 150],
            'location'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'capacity_kg'       => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 100.00],
            'current_status'    => ['type' => 'ENUM', 'constraint' => ['idle', 'processing', 'maintenance', 'archived'], 'default' => 'idle'],
            'installation_date' => ['type' => 'DATE', 'null' => true],
            'is_archived'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'notes'             => ['type' => 'TEXT', 'null' => true],
            'created_at'        => ['type' => 'TIMESTAMP', 'null' => true],
            'updated_at'        => ['type' => 'TIMESTAMP', 'null' => true],
            'deleted_at'        => ['type' => 'TIMESTAMP', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('current_status', 'idx_drums_status');
        $this->forge->addKey('is_archived', 'idx_drums_archived');
        $this->forge->createTable('bmg_drums', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('bmg_drums', true);
    }
}
