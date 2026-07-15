<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds `user_type` ENUM column to `users` to distinguish between
 * students, employees, and staff. This enables polymorphic patient
 * references throughout the system.
 */
class AddUserTypeToUsers extends Migration
{
    public function up(): void
    {
        $this->db->query("
            ALTER TABLE users
            ADD COLUMN user_type ENUM('student','employee','staff') NOT NULL DEFAULT 'student'
            AFTER email
        ");

        $this->db->query("CREATE INDEX idx_users_user_type ON users(user_type)");
    }

    public function down(): void
    {
        $this->db->query("DROP INDEX idx_users_user_type ON users");
        $this->db->query("ALTER TABLE users DROP COLUMN user_type");
    }
}
