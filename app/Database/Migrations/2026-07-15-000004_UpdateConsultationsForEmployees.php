<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Updates `consultations` to support a polymorphic patient reference
 * (student OR employee). Existing `student_id` references remain
 * intact; `patient_type` and `employee_id` are added.
 */
class UpdateConsultationsForEmployees extends Migration
{
    public function up(): void
    {
        // Add columns
        $this->db->query("
            ALTER TABLE consultations
            ADD COLUMN patient_type ENUM('student','employee') NOT NULL DEFAULT 'student' AFTER id,
            ADD COLUMN employee_id BIGINT UNSIGNED NULL AFTER student_id
        ");

        // Add FK for employee_id
        $this->db->query("
            ALTER TABLE consultations
            ADD CONSTRAINT fk_consultations_employee
            FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL
        ");

        // Add composite index for polymorphic lookups
        $this->db->query("
            CREATE INDEX idx_consultations_patient ON consultations(patient_type, student_id, employee_id)
        ");
    }

    public function down(): void
    {
        $this->db->query("DROP INDEX idx_consultations_patient ON consultations");
        $this->db->query("ALTER TABLE consultations DROP FOREIGN KEY fk_consultations_employee");
        $this->db->query("ALTER TABLE consultations DROP COLUMN employee_id, DROP COLUMN patient_type");
    }
}
