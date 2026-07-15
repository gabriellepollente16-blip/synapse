<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds `reference_duration_days` to `waste_categories` so the BMG module
 * can estimate when a new batch will be ready for harvest.
 *
 * Reference values (defaults):
 *   - food_waste:   30 days  (warmer, faster decomposition)
 *   - twigs_leaves: 60 days  (woody, slower)
 *   - mixed:        45 days  (mixed organic, average)
 *
 * The operator can adjust these on the category edit form to reflect
 * the actual local conditions (climate, drum design, mix ratios).
 */
class AddReferenceDurationToWasteCategories extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('waste_categories', [
            'reference_duration_days' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'default'    => 45,
                'comment'    => 'Reference decomposition duration in days, used to estimate completion date for new batches.',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('waste_categories', 'reference_duration_days');
    }
}
