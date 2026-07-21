<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds the `checkin_logs` table to record every RFID-based check-in.
 *
 * Each entry represents one scan of an institutional ID. The system looks
 * up the scanned tag against students and employees, then logs the
 * encounter with timestamp and module (clinic or counselling).
 */
class AddCheckinLogsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                 => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'patient_type'       => ['type' => 'ENUM', 'constraint' => ['student', 'employee']],
            'student_id'         => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'employee_id'        => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'rfid_tag_scanned'   => ['type' => 'VARCHAR', 'constraint' => 255],
            'checkin_at'         => ['type' => 'TIMESTAMP', 'null' => true],
            'module'             => ['type' => 'ENUM', 'constraint' => ['clinic', 'counselling']],
            'notes'              => ['type' => 'TEXT', 'null' => true],
            'created_at'         => ['type' => 'TIMESTAMP', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('student_id', 'students', 'id', '', 'SET NULL');
        $this->forge->addForeignKey('employee_id', 'employees', 'id', '', 'SET NULL');
        $this->forge->addKey(['patient_type', 'student_id', 'employee_id'], false, false, 'idx_checkin_patient');
        $this->forge->addKey('checkin_at', false, false, 'idx_checkin_at');
        $this->forge->addKey('rfid_tag_scanned');
        $this->forge->createTable('checkin_logs', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('checkin_logs', true);
    }
}
