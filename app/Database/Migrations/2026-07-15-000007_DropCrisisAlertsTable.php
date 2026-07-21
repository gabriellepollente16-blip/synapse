<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Drops the `crisis_alerts` table.
 *
 * The crisis alert protocol (PHQ-9 Item 9 auto-detection) has been
 * removed from the counselling module per the revised scope.
 */
class DropCrisisAlertsTable extends Migration
{
    public function up(): void
    {
        $this->db->query("DROP TABLE IF EXISTS crisis_alerts");
    }

    public function down(): void
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS crisis_alerts (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                student_id BIGINT UNSIGNED NULL,
                assessment_response_id BIGINT UNSIGNED NULL,
                phq9_item9_score TINYINT NULL,
                status ENUM('active','acknowledged','resolved') DEFAULT 'active',
                escalated_to BIGINT UNSIGNED NULL,
                acknowledgement_notes TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL
            ) ENGINE=InnoDB
        ");
    }
}
