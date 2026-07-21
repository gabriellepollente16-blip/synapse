<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * No-op seeder.
 *
 * Previously seeded PHQ-9 / GAD-7 assessment templates and crisis alert
 * trigger data. Per the revised capstone scope (see SYSTEM_ARCHITECTURE.md
 * section 6), mental-health screening tools are excluded from the system.
 * Counsellors document concerns via free-text intake notes (intake_notes).
 *
 * The ssessment_templates, ssessment_responses, ssessment_questions,
 * and crisis_alerts tables were dropped by migrations
 * DropScreeningTables and DropCrisisAlertsTable.
 */
class AssessmentSeeder extends Seeder
{
    public function run()
    {
        echo '  [skipped] Screening tools are out of scope (use intake_notes instead).' . PHP_EOL;
    }
}
