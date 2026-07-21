<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Updates `counselling_appointments` to support a polymorphic patient
 * reference (student OR employee).
 */
class UpdateCounsellingForEmployees extends Migration
{
    public function up(): void
    {
        $this->db->query("
            ALTER TABLE counselling_appointments
            ADD COLUMN patient_type ENUM('student','employee') NOT NULL DEFAULT 'student' AFTER id,
            ADD COLUMN employee_id BIGINT UNSIGNED NULL AFTER student_id
        ");

        $this->db->query("
            ALTER TABLE counselling_appointments
            ADD CONSTRAINT fk_counselling_employee
            FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL
        ");

        $this->db->query("
            CREATE INDEX idx_counselling_patient ON counselling_appointments(patient_type, student_id, employee_id)
        ");
    }

    public function down(): void
    {
        $this->db->query("DROP INDEX idx_counselling_patient ON counselling_appointments");
        $this->db->query("ALTER TABLE counselling_appointments DROP FOREIGN KEY fk_counselling_employee");
        $this->db->query("ALTER TABLE counselling_appointments DROP COLUMN employee_id, DROP COLUMN patient_type");
    }
}
