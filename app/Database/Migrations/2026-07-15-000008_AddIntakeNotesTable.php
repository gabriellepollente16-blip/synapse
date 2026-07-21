<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds the `intake_notes` table for counsellor-only session notes.
 *
 * Replaces the removed screening functionality. Notes are free-text,
 * confidential, and access-restricted to the assigned counsellor and
 * administrators.
 */
class AddIntakeNotesTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                  => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'patient_type'        => ['type' => 'ENUM', 'constraint' => ['student', 'employee']],
            'student_id'          => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'employee_id'         => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'counsellor_id'       => ['type' => 'BIGINT', 'unsigned' => true],
            'appointment_id'      => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'presenting_concern'  => ['type' => 'TEXT', 'null' => true],
            'session_notes'       => ['type' => 'TEXT', 'null' => true],
            'action_items'        => ['type' => 'TEXT', 'null' => true],
            'session_date'        => ['type' => 'DATE', 'null' => true],
            'is_confidential'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'          => ['type' => 'TIMESTAMP', 'null' => true],
            'updated_at'          => ['type' => 'TIMESTAMP', 'null' => true],
            'deleted_at'          => ['type' => 'TIMESTAMP', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('student_id', 'students', 'id', '', 'SET NULL');
        $this->forge->addForeignKey('employee_id', 'employees', 'id', '', 'SET NULL');
        $this->forge->addForeignKey('counsellor_id', 'users', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('appointment_id', 'counselling_appointments', 'id', '', 'SET NULL');
        $this->forge->addKey(['patient_type', 'student_id', 'employee_id'], false, false, 'idx_intake_patient');
        $this->forge->addKey('counsellor_id');
        $this->forge->addKey('session_date');
        $this->forge->createTable('intake_notes', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('intake_notes', true);
    }
}
