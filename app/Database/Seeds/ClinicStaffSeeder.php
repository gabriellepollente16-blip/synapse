<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * ClinicStaffSeeder
 *
 * Creates the canonical clinic-staff demo account so the README
 * "Default Test Credentials" section is accurate out of the box.
 *
 * Credentials:
 *   email     : clinic@synapse.edu.ph
 *   password  : TestPass123!
 *   role      : clinic_staff
 *
 * Re-running is safe — the user is looked up by email first and
 * the password is reset to the canonical value.
 */
class ClinicStaffSeeder extends Seeder
{
    public function run()
    {
        $email       = 'clinic@synapse.edu.ph';
        $password    = 'TestPass123!';
        $firstName   = 'Clinic';
        $lastName    = 'Staff';

        $existing = $this->db->table('users')->where('email', $email)->get()->getRow();

        if ($existing !== null) {
            // Reset password to the canonical README value so a fresh DB
            // and an upgraded DB land on the same credentials.
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $this->db->table('users')
                ->where('id', $existing->id)
                ->update([
                    'password_hash' => $hash,
                    'first_name'    => $firstName,
                    'last_name'     => $lastName,
                    'is_active'     => 1,
                    'updated_at'    => date('Y-m-d H:i:s'),
                ]);
            $userId = (int) $existing->id;
            echo "  Reset password/active for existing clinic user: {$email}\n";
        } else {
            $this->db->table('users')->insert([
                'email'             => $email,
                'password_hash'     => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
                'first_name'        => $firstName,
                'last_name'         => $lastName,
                'is_active'         => true,
                'email_verified_at' => date('Y-m-d H:i:s'),
                'created_at'        => date('Y-m-d H:i:s'),
                'updated_at'        => date('Y-m-d H:i:s'),
            ]);
            $userId = (int) $this->db->insertID();
            echo "  Created clinic user: {$email} (password: {$password})\n";
        }

        // Ensure the clinic_staff role exists and is assigned.
        $role = $this->db->table('roles')->where('name', 'clinic_staff')->get()->getRow();
        if ($role === null) {
            echo "  WARNING: role 'clinic_staff' not found. Run RoleSeeder first.\n";
            return;
        }

        $hasRole = $this->db->table('user_roles')
            ->where('user_id', $userId)
            ->where('role_id', $role->id)
            ->get()->getRow();

        if ($hasRole === null) {
            $this->db->table('user_roles')->insert([
                'user_id'     => $userId,
                'role_id'     => (int) $role->id,
                'assigned_at' => date('Y-m-d H:i:s'),
            ]);
            echo "  Assigned role: clinic_staff\n";
        }
    }
}
