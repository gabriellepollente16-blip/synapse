<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds the Facilities/Sustainability (BMG) module so the drum/batch/
 * input/process/output tables all carry a handful of demo records that
 * make the dashboard, reports, and UX screens actually render data.
 *
 *  - 4 waste categories
 *  - 6 drums (4 active, 1 maintenance, 1 idle)
 *  - 6 batches (2 completed, 2 processing, 2 awaiting input)
 *  - 4 input records
 *  - 4 process logs
 *  - 2 output records
 *
 * Safe to re-run — it skips records whose `batch_code` already exists.
 */
class FacilitiesSeeder extends Seeder
{
    public function run()
    {
        // Use the inherited test-group connection so this seeder
        // works correctly under PHPUnit (where the default DB
        // group would otherwise point at the dev database).
        $db = $this->db;
        $db->transStart();

        // 1. Waste categories — idempotent on `code`
        $categories = [
            ['code' => 'VEG-SCRP',  'name' => 'Vegetable Scraps',          'description' => 'Peels, ends, trimmings from the campus canteen fruit-and-veg prep.', 'expected_yield_pct' => 35.00, 'reference_duration_days' => 45],
            ['code' => 'COFF-GRD',  'name' => 'Coffee Grounds',             'description' => 'Spent grounds from the campus cafe, high nitrogen.',                     'expected_yield_pct' => 28.00, 'reference_duration_days' => 35],
            ['code' => 'YARD-CLP',  'name' => 'Yard Clippings',             'description' => 'Grass and hedge trimmings from grounds maintenance.',                   'expected_yield_pct' => 22.00, 'reference_duration_days' => 60],
            ['code' => 'PAPER',     'name' => 'Paper / Cardboard Shredded', 'description' => 'Uncoated paper, shredded cardboard trays.',                            'expected_yield_pct' => 18.00, 'reference_duration_days' => 50],
        ];
        foreach ($categories as $c) {
            $exists = $db->table('waste_categories')->where('code', $c['code'])->get()->getRowArray();
            if (!$exists) {
                $db->table('waste_categories')->insert($c + ['is_active' => 1]);
            }
        }

        // 2. BMG drums
        $drums = [
            ['DRUM-01', 'Drum 01 - North Canopy',  'North campus, near the canteen',                  120.00, 'processing'],
            ['DRUM-02', 'Drum 02 - North Canopy',  'North campus, near the canteen',                  120.00, 'idle'],
            ['DRUM-03', 'Drum 03 - South Lawn',    'South lawn, behind the science building',         100.00, 'processing'],
            ['DRUM-04', 'Drum 04 - South Lawn',    'South lawn, behind the science building',         100.00, 'idle'],
            ['DRUM-05', 'Drum 05 - East Garden',   'East garden, near dormitories',                   80.00, 'maintenance'],
            ['DRUM-06', 'Drum 06 - West Annex',    'West annex, behind the chapel',                   80.00, 'idle'],
        ];
        $drumMap = [];
        foreach ($drums as $d) {
            $exists = $db->table('bmg_drums')->where('drum_code', $d[0])->get()->getRowArray();
            if ($exists) {
                $drumMap[$d[0]] = $exists['id'];
                continue;
            }
            $db->table('bmg_drums')->insert([
                'drum_code'         => $d[0],
                'name'              => $d[1],
                'location'          => $d[2],
                'capacity_kg'       => $d[3],
                'current_status'    => $d[4],
                'installation_date' => '2024-08-15',
                'is_archived'       => 0,
            ]);
            $drumMap[$d[0]] = $db->insertID();
        }

        // 3. Batches
        $catIds = [];

        // Resolve a real user_id for recorded_by FK columns.
        // Tests and dev DBs both bump auto_increment well past 1, so
        // hardcoding 1 leaves the FK constraint unsatisfied.
        $userId = (int) ($db->table('users')->select('id')->orderBy('id','asc')->limit(1)->get()->getRow()->id ?? 0);
        foreach ($db->table('waste_categories')->get()->getResultArray() as $c) {
            $catIds[$c['code']] = $c['id'];
        }

        $batches = [
            ['DRUM-01', 'processing', 'VEG-SCRP', 85.00, 12],
            ['DRUM-01', 'completed',  'YARD-CLP', 70.00, 55],
            ['DRUM-03', 'processing', 'VEG-SCRP', 78.50,  5],
            ['DRUM-02', 'input',      'COFF-GRD',  0.00,  0],
            ['DRUM-04', 'input',      'PAPER',     0.00,  0],
            ['DRUM-06', 'completed',  'COFF-GRD', 60.00, 37],
        ];

        foreach ($batches as $i => [$drum, $status, $cat, $input, $daysIn]) {
            $batchCode = sprintf('BATCH-%04d', $i + 1);
            if ($db->table('bmg_batches')->where('batch_code', $batchCode)->countAllResults() > 0) {
                continue;
            }
            $drumId = $drumMap[$drum] ?? null;
            $catId  = $catIds[$cat] ?? null;
            if (!$drumId || !$catId) {
                continue;
            }
            $startDate    = $daysIn > 0 ? date('Y-m-d', strtotime("-$daysIn days")) : null;
            $expCompDate  = $startDate ? date('Y-m-d', strtotime($startDate . ' +45 days')) : null;
            $completionDt = ($status === 'completed') ? date('Y-m-d', strtotime($startDate . " +{$daysIn} days")) : null;
            $outputKg     = ($status === 'completed') ? round($input * 0.30, 2) : null;
            $duration     = ($status === 'completed') ? $daysIn : null;

            $db->table('bmg_batches')->insert([
                'batch_code'              => $batchCode,
                'drum_id'                 => $drumId,
                'waste_category_id'       => $catId,
                'status'                  => $status,
                'input_weight_kg'         => $input,
                'input_recorded_at'       => $input > 0 ? ($startDate . ' 09:00:00') : null,
                'input_recorded_by'       => $input > 0 ? $userId : null,
                'start_date'              => $startDate,
                'completion_date'         => $completionDt,
                'duration_days'           => $duration,
                'output_weight_kg'        => $outputKg,
                'yield_percentage'        => ($outputKg && $input) ? round($outputKg / $input * 100, 2) : null,
                'mass_reduction_pct'      => ($outputKg && $input) ? round((1 - $outputKg / $input) * 100, 2) : null,
                'completed_by'            => ($status === 'completed') ? $userId : null,
                'output_recorded_at'      => ($status === 'completed') ? ($completionDt . ' 15:00:00') : null,
                'notes'                   => 'Auto-seeded by FacilitiesSeeder.',
                'expected_duration_days'  => 45,
                'expected_completion_date'=> $expCompDate,
            ]);
            $batchId = $db->insertID();

            if ($input > 0) {
                $db->table('bmg_inputs')->insert([
                    'batch_id'    => $batchId,
                    'weight_kg'   => $input,
                    'recorded_at' => $startDate . ' 09:00:00',
                    'recorded_by' => $userId,
                    'notes'       => 'Initial input',
                ]);
            }
            if (in_array($status, ['processing', 'completed'], true) && $startDate) {
                $logDate = date('Y-m-d', strtotime($startDate . ' +' . max(1, intval($daysIn / 2)) . ' days'));
                $db->table('bmg_process_logs')->insert([
                    'batch_id'            => $batchId,
                    'log_date'            => $logDate,
                    'observation_note'    => 'Mid-cycle temperature & moisture check.',
                    'temperature_celsius' => 32.0,
                    'moisture_level'      => 'normal',
                    'recorded_by'         => $userId,
                ]);
            }
            if ($status === 'completed' && $completionDt) {
                $db->table('bmg_outputs')->insert([
                    'batch_id'         => $batchId,
                    'output_weight_kg' => $outputKg,
                    'harvest_date'     => $completionDt,
                    'quality_grade'    => 'good',
                    'notes'            => 'Harvested and bagged.',
                    'recorded_by'      => $userId,
                ]);
            }
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            echo "  FacilitiesSeeder FAILED (transaction aborted)\n";
            return;
        }
        echo "  FacilitiesSeeder OK\n";
        echo '    waste_categories : ' . $db->table('waste_categories')->countAll() . "\n";
        echo '    bmg_drums        : ' . $db->table('bmg_drums')->countAll() . "\n";
        echo '    bmg_batches      : ' . $db->table('bmg_batches')->countAll() . "\n";
        echo '    bmg_inputs       : ' . $db->table('bmg_inputs')->countAll() . "\n";
        echo '    bmg_process_logs : ' . $db->table('bmg_process_logs')->countAll() . "\n";
        echo '    bmg_outputs      : ' . $db->table('bmg_outputs')->countAll() . "\n";
    }
}