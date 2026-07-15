<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds a `module` column to `notifications` to support role-based and
 * module-based filtering of notifications.
 *
 * Also widens the `type` ENUM to include the new notification types
 * introduced by the BMG, HR, and procurement modules.
 */
class UpdateNotificationTypes extends Migration
{
    public function up(): void
    {
        // Add module column
        $this->db->query("
            ALTER TABLE notifications
            ADD COLUMN module ENUM('auth','clinic','counselling','bmg','inventory','admin','reports','hri') NULL AFTER type
        ");

        $this->db->query("CREATE INDEX idx_notifications_module ON notifications(module)");

        // Widen the type column
        $this->db->query("
            ALTER TABLE notifications
            MODIFY COLUMN type ENUM(
                'appointment_reminder',
                'low_stock',
                'referral_received',
                'referral_verified',
                'reorder_request',
                'reorder_approved',
                'reorder_received',
                'bmg_batch_started',
                'bmg_batch_completed',
                'bmg_idle_drum',
                'hr_sync_completed',
                'system_announcement'
            ) NOT NULL
        ");
    }

    public function down(): void
    {
        $this->db->query("DROP INDEX idx_notifications_module ON notifications");
        $this->db->query("ALTER TABLE notifications DROP COLUMN module");

        // Restore the original narrower ENUM (best-effort)
        $this->db->query("
            ALTER TABLE notifications
            MODIFY COLUMN type ENUM(
                'appointment_reminder',
                'low_stock',
                'referral_received',
                'reorder_request',
                'system_announcement'
            ) NOT NULL
        ");
    }
}
