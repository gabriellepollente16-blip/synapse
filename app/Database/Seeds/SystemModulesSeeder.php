<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds the system_modules registry so the admin dashboard has a real
 * list of enabled modules to display. Idempotent on module `name`.
 *
 * Modules are mapped from the existing Controllers/* directory layout;
 * version is bumped per the dev branch (`1.0.0-dev`).
 */
class SystemModulesSeeder extends Seeder
{
    public function run()
    {
        $modules = [
            [
                'name'         => 'clinic',
                'display_name' => 'Clinic & Triage',
                'description'  => 'Student/employee consultations, vitals, treatments, allergies.',
                'version'      => '1.0.0-dev',
                'is_enabled'   => 1,
                'config'       => null,
            ],
            [
                'name'         => 'counselling',
                'display_name' => 'Counselling',
                'description'  => 'Appointments, availability, referrals. (Crisis/screening flows retired.)',
                'version'      => '1.0.0-dev',
                'is_enabled'   => 1,
                'config'       => null,
            ],
            [
                'name'         => 'inventory',
                'display_name' => 'Pharmacy Inventory',
                'description'  => 'Medicines, batches, transactions, reorder requests, AI forecasts.',
                'version'      => '1.0.0-dev',
                'is_enabled'   => 1,
                'config'       => null,
            ],
            [
                'name'         => 'bmg',
                'display_name' => 'BMG / Sustainability',
                'description'  => 'Biodegradable waste drums, batches, inputs/process/output tracking.',
                'version'      => '1.0.0-dev',
                'is_enabled'   => 1,
                'config'       => null,
            ],
            [
                'name'         => 'reports',
                'display_name' => 'Reports & AI',
                'description'  => 'Configurable reports, NLG summaries, AI triage/risk/forecasting.',
                'version'      => '1.0.0-dev',
                'is_enabled'   => 1,
                'config'       => null,
            ],
            [
                'name'         => 'iot',
                'display_name' => 'IoT & Kiosk',
                'description'  => 'Self-service kiosk check-in and offline sync buffer.',
                'version'      => '1.0.0-dev',
                'is_enabled'   => 1,
                'config'       => null,
            ],
            [
                'name'         => 'admin',
                'display_name' => 'Administration',
                'description'  => 'User/role/permission management, audit log viewer, module toggles.',
                'version'      => '1.0.0-dev',
                'is_enabled'   => 1,
                'config'       => null,
            ],
        ];

        foreach ($modules as $m) {
            $exists = $this->db->table('system_modules')->where('name', $m['name'])->get()->getRow();
            if ($exists) {
                continue;
            }
            $this->db->table('system_modules')->insert($m);
        }

        echo '  SystemModules OK (' . count($modules) . " defined)\n";
    }
}
