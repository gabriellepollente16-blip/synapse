<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds the `reorder_requests` table for medicine procurement workflow.
 *
 * Auto-triggered when medicine stock falls below reorder_level. Routes
 * to procurement personnel through a status workflow:
 *   pending → approved → ordered → received
 */
class AddReorderRequestsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                       => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'medicine_id'              => ['type' => 'INT', 'unsigned' => true],
            'requested_quantity'       => ['type' => 'INT', 'unsigned' => true],
            'current_stock'            => ['type' => 'INT', 'unsigned' => true],
            'reorder_level'            => ['type' => 'INT', 'unsigned' => true],
            'urgency'                  => ['type' => 'ENUM', 'constraint' => ['low', 'medium', 'high', 'critical'], 'default' => 'medium'],
            'status'                   => ['type' => 'ENUM', 'constraint' => ['pending', 'approved', 'ordered', 'received', 'cancelled'], 'default' => 'pending'],
            'requested_by'             => ['type' => 'BIGINT', 'unsigned' => true],
            'approved_by'              => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'procurement_notes'        => ['type' => 'TEXT', 'null' => true],
            'order_date'               => ['type' => 'DATE', 'null' => true],
            'expected_delivery_date'   => ['type' => 'DATE', 'null' => true],
            'actual_delivery_date'     => ['type' => 'DATE', 'null' => true],
            'created_at'               => ['type' => 'TIMESTAMP', 'null' => true],
            'updated_at'               => ['type' => 'TIMESTAMP', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('medicine_id', 'medicines', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('requested_by', 'users', 'id', '', 'RESTRICT');
        $this->forge->addForeignKey('approved_by', 'users', 'id', '', 'SET NULL');
        $this->forge->addKey('status', 'idx_reorder_status');
        $this->forge->addKey('urgency', 'idx_reorder_urgency');
        $this->forge->addKey('medicine_id');
        $this->forge->createTable('reorder_requests', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('reorder_requests', true);
    }
}
