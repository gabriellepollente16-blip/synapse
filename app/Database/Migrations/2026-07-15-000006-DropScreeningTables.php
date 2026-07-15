<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Drops `assessment_templates` and `assessment_responses` tables.
 *
 * Per the capstone's revised scope, mental health screening tools (PHQ-9,
 * GAD-7, etc.) are excluded from the system. Counsellors document
 * concerns and actions via free-text intake notes only.
 *
 * Safe to run on installs that never had the tables — uses IF EXISTS.
 */
class DropScreeningTables extends Migration
{
    public function up(): void
    {
        // assessment_responses references assessment_templates; drop child first
        $this->db->query("DROP TABLE IF EXISTS assessment_responses");
        $this->db->query("DROP TABLE IF EXISTS assessment_templates");
    }

    public function down(): void
    {
        // Recreate lightweight stubs for rollback (no actual screening data)
        $this->db->query("
            CREATE TABLE IF NOT EXISTS assessment_templates (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(50) NOT NULL UNIQUE,
                name VARCHAR(150) NOT NULL,
                description TEXT NULL,
                total_questions INT NOT NULL DEFAULT 0,
                scoring_rules JSON NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS assessment_responses (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                student_id BIGINT UNSIGNED NULL,
                template_id INT UNSIGNED NULL,
                responses JSON NULL,
                total_score INT NULL,
                severity_level VARCHAR(50) NULL,
                interpreted_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL,
                FOREIGN KEY (template_id) REFERENCES assessment_templates(id) ON DELETE SET NULL
            ) ENGINE=InnoDB
        ");
    }
}
