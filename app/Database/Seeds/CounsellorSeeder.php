<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * CounsellorSeeder
 *
 * Creates the canonical counsellor demo account so the README
 * "Default Test Credentials" section is accurate out of the box.
 *
 * Credentials:
 *   email     : counsellor@synapse.edu.ph
 *   password  : TestPass123!
 *   role      : counsellor
 *
 * Re-running is safe — the user is looked up by email first and
 * the password is reset to the canonical value.
 */
class CounsellorSeeder extends Seeder
{
    public function run()
    {
        $email       = 'counsellor@synapse.edu.ph';
        $password    = 'TestPass123!';
        $firstName   = 'Guidance';
        $lastName    = 'Counsellor';

        $existing = $this->db->table('users')->where('email', $email)->get()->getRow();

        if ($existing !== null) {
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
            echo "  Reset password/active for existing counsellor user: {$email}\n";
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
            echo "  Created counsellor user: {$email} (password: {$password})\n";
        }

        $role = $this->db->table('roles')->where('name', 'counsellor')->get()->getRow();
        if ($role === null) {
            echo "  WARNING: role 'counsellor' not found. Run RoleSeeder first.\n";
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
            echo "  Assigned role: counsellor\n";
        }
    }
}
