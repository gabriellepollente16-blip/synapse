<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Expands the role taxonomy from 4 to 7 roles.
 *
 * New roles:
 *   - facilities_staff  : BMG module operator
 *   - report_viewer     : cross-module read-only access
 *   - employee          : university employee (patient)
 *
 * Uses INSERT IGNORE so this migration is idempotent — running it twice
 * does not produce duplicate role entries.
 */
class ExpandRoles extends Migration
{
    public function up(): void
    {
        $roles = [
            ['name' => 'admin',            'display_name' => 'System Administrator',  'description' => 'Full system access including audit log'],
            ['name' => 'clinic_staff',     'display_name' => 'Clinic Staff',          'description' => 'Doctor, Nurse, Clinic Administrator'],
            ['name' => 'counsellor',       'display_name' => 'Guidance Counsellor',   'description' => 'Guidance counselling and intake notes'],
            ['name' => 'facilities_staff', 'display_name' => 'Facilities / Composting Staff', 'description' => 'BMG module operator (drums, batches)'],
            ['name' => 'report_viewer',    'display_name' => 'Report Viewer',         'description' => 'Cross-module read-only report access'],
            ['name' => 'employee',         'display_name' => 'Employee',              'description' => 'University employee (patient role)'],
            ['name' => 'student',          'display_name' => 'Student',               'description' => 'Student (patient role)'],
        ];

        foreach ($roles as $role) {
            $this->db->table('roles')->ignore(true)->insert($role);
        }
    }

    public function down(): void
    {
        $this->db->table('roles')->whereIn('name', ['facilities_staff', 'report_viewer', 'employee'])->delete();
    }
}
