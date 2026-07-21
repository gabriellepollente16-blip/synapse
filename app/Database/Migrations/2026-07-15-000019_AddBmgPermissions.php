<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Seeds the `permissions` table with a granular permission catalog
 * and binds them to the appropriate roles.
 *
 * Permission codes follow a `<module>.<action>` naming convention.
 *
 * Idempotent: re-running the migration does not duplicate rows.
 */
class AddBmgPermissions extends Migration
{
    public function up(): void
    {
        // ----- Permission catalog -----
        $permissions = [
            // Clinic
            ['name' => 'view_clinic_dashboard',  'module' => 'clinic',      'description' => 'View clinic landing page'],
            ['name' => 'create_consultation',    'module' => 'clinic',      'description' => 'Create new consultation'],
            ['name' => 'record_vitals',          'module' => 'clinic',      'description' => 'Record patient vitals'],
            ['name' => 'manage_treatments',      'module' => 'clinic',      'description' => 'Prescribe / manage treatments'],
            ['name' => 'create_referral',        'module' => 'clinic',      'description' => 'Create referral and generate QR'],
            ['name' => 'verify_referral',        'module' => 'clinic',      'description' => 'Verify referral via QR scan'],
            ['name' => 'view_clinic_audit',      'module' => 'clinic',      'description' => 'View clinic audit log'],

            // Counselling
            ['name' => 'view_counselling_dashboard', 'module' => 'counselling', 'description' => 'View counselling landing page'],
            ['name' => 'book_appointment',            'module' => 'counselling', 'description' => 'Book / manage appointments'],
            ['name' => 'record_intake',              'module' => 'counselling', 'description' => 'Record intake session notes'],
            ['name' => 'view_own_intake',            'module' => 'counselling', 'description' => 'View own intake notes'],

            // BMG (Facilities)
            ['name' => 'view_bmg_dashboard',  'module' => 'bmg', 'description' => 'View BMG dashboard'],
            ['name' => 'manage_drums',        'module' => 'bmg', 'description' => 'Add/edit/archive BMG drums'],
            ['name' => 'log_bmg_input',       'module' => 'bmg', 'description' => 'Record batch input weight'],
            ['name' => 'log_bmg_process',     'module' => 'bmg', 'description' => 'Log process observations'],
            ['name' => 'record_bmg_output',   'module' => 'bmg', 'description' => 'Record batch output / yield'],
            ['name' => 'view_bmg_reports',    'module' => 'bmg', 'description' => 'View BMG analytics & export'],

            // Inventory & Procurement
            ['name' => 'manage_inventory',    'module' => 'inventory', 'description' => 'Manage medicines and batches'],
            ['name' => 'view_reorder_requests', 'module' => 'inventory', 'description' => 'View reorder requests'],
            ['name' => 'approve_reorder',     'module' => 'inventory', 'description' => 'Approve / route reorder requests'],

            // Admin
            ['name' => 'manage_users',        'module' => 'admin', 'description' => 'User CRUD'],
            ['name' => 'manage_roles',        'module' => 'admin', 'description' => 'Role & permission management'],
            ['name' => 'view_audit_log',      'module' => 'admin', 'description' => 'View system audit log'],

            // Reports
            ['name' => 'view_reports',        'module' => 'reports', 'description' => 'View cross-module reports'],
            ['name' => 'export_reports',      'module' => 'reports', 'description' => 'Export reports (CSV/PDF)'],
        ];

        foreach ($permissions as $p) {
            $this->db->table('permissions')->ignore(true)->insert($p);
        }

        // ----- Role-Permission bindings -----
        $bindings = $this->buildBindings();
        foreach ($bindings as $binding) {
            $this->db->table('role_permissions')->ignore(true)->insert($binding);
        }
    }

    public function down(): void
    {
        // Best-effort cleanup: remove the permissions we added
        $names = [
            'view_clinic_dashboard', 'create_consultation', 'record_vitals', 'manage_treatments',
            'create_referral', 'verify_referral', 'view_clinic_audit',
            'view_counselling_dashboard', 'book_appointment', 'record_intake', 'view_own_intake',
            'view_bmg_dashboard', 'manage_drums', 'log_bmg_input', 'log_bmg_process',
            'record_bmg_output', 'view_bmg_reports',
            'manage_inventory', 'view_reorder_requests', 'approve_reorder',
            'manage_users', 'manage_roles', 'view_audit_log',
            'view_reports', 'export_reports',
        ];

        // Delete role_permissions first, then permissions
        $permIds = $this->db->table('permissions')
            ->whereIn('name', $names)
            ->get()
            ->getResultArray();

        if (!empty($permIds)) {
            $idList = array_column($permIds, 'id');
            $this->db->table('role_permissions')->whereIn('permission_id', $idList)->delete();
            $this->db->table('permissions')->whereIn('id', $idList)->delete();
        }
    }

    /**
     * Build (role_id, permission_id) bindings from permission names.
     */
    private function buildBindings(): array
    {
        // Get role IDs by name
        $roles = $this->db->table('roles')->get()->getResultArray();
        $roleByName = array_column($roles, 'id', 'name');

        // Get permission IDs by name
        $perms = $this->db->table('permissions')->get()->getResultArray();
        $permByName = array_column($perms, 'id', 'name');

        // Define the matrix
        $matrix = [
            'admin' => array_keys($permByName), // all permissions

            'clinic_staff' => [
                'view_clinic_dashboard', 'create_consultation', 'record_vitals',
                'manage_treatments', 'create_referral', 'verify_referral',
                'manage_inventory', 'view_reorder_requests', 'approve_reorder',
                'view_reports',
            ],

            'counsellor' => [
                'view_counselling_dashboard', 'book_appointment', 'record_intake',
                'view_own_intake', 'create_referral', 'verify_referral',
                'view_reports',
            ],

            'facilities_staff' => [
                'view_bmg_dashboard', 'manage_drums', 'log_bmg_input',
                'log_bmg_process', 'record_bmg_output', 'view_bmg_reports',
                'view_reports',
            ],

            'report_viewer' => [
                'view_reports', 'export_reports', 'view_bmg_reports',
                'view_reorder_requests', 'view_clinic_audit',
            ],

            'employee' => [
                'book_appointment', 'view_own_intake',
            ],

            'student' => [
                'book_appointment',
            ],
        ];

        $bindings = [];
        foreach ($matrix as $roleName => $permNames) {
            if (!isset($roleByName[$roleName])) continue;
            foreach ($permNames as $permName) {
                if (!isset($permByName[$permName])) continue;
                $bindings[] = [
                    'role_id'       => $roleByName[$roleName],
                    'permission_id' => $permByName[$permName],
                ];
            }
        }
        return $bindings;
    }
}
