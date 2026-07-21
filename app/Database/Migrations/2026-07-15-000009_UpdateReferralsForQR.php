<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds QR code generation + verification fields to `referrals`.
 *
 * Each referral/excuse slip is assigned a unique scannable token. The
 * receiving party (clinic or counselling) can scan the QR to instantly
 * verify authenticity and view referral details.
 */
class UpdateReferralsForQR extends Migration
{
    public function up(): void
    {
        $this->db->query("
            ALTER TABLE referrals
            ADD COLUMN qr_code_token VARCHAR(64) NULL UNIQUE AFTER status,
            ADD COLUMN qr_code_path VARCHAR(255) NULL AFTER qr_code_token,
            ADD COLUMN qr_generated_at TIMESTAMP NULL AFTER qr_code_path,
            ADD COLUMN qr_verified_at TIMESTAMP NULL AFTER qr_generated_at,
            ADD COLUMN qr_verified_by BIGINT UNSIGNED NULL AFTER qr_verified_at
        ");

        $this->db->query("
            ALTER TABLE referrals
            ADD CONSTRAINT fk_referrals_qr_verified_by
            FOREIGN KEY (qr_verified_by) REFERENCES users(id) ON DELETE SET NULL
        ");

        $this->db->query("CREATE INDEX idx_referrals_qr_token ON referrals(qr_code_token)");
    }

    public function down(): void
    {
        $this->db->query("DROP INDEX idx_referrals_qr_token ON referrals");
        $this->db->query("ALTER TABLE referrals DROP FOREIGN KEY fk_referrals_qr_verified_by");
        $this->db->query("ALTER TABLE referrals DROP COLUMN qr_verified_by, DROP COLUMN qr_verified_at, DROP COLUMN qr_generated_at, DROP COLUMN qr_code_path, DROP COLUMN qr_code_token");
    }
}
