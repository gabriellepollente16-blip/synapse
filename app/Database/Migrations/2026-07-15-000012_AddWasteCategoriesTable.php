<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds the `waste_categories` table for classifying biodegradable waste
 * loaded into BMG drums (food waste, twigs & leaves, mixed, etc.).
 *
 * Categorization supports comparative analysis of decomposition
 * duration and yield across waste types.
 */
class AddWasteCategoriesTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'code'                => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'name'                => ['type' => 'VARCHAR', 'constraint' => 100],
            'description'         => ['type' => 'TEXT', 'null' => true],
            'expected_yield_pct'  => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true, 'default' => null],
            'is_active'           => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'          => ['type' => 'TIMESTAMP', 'null' => true],
            'updated_at'          => ['type' => 'TIMESTAMP', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('is_active');
        $this->forge->createTable('waste_categories', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('waste_categories', true);
    }
}
