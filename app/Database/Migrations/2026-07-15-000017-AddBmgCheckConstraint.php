<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds the database-level CHECK constraint that enforces a fundamental
 * rule of composting: output weight cannot exceed input weight.
 *
 * This is a defense-in-depth measure. The application layer also validates
 * this rule, but the DB constraint guarantees data integrity even if a
 * future controller skips the check.
 *
 * MySQL 8.0+ supports CHECK constraints; on older versions, this is a no-op.
 */
class AddBmgCheckConstraint extends Migration
{
    public function up(): void
    {
        // bmg_batches.output_weight_kg must be <= input_weight_kg (or NULL)
        $this->db->query("
            ALTER TABLE bmg_batches
            ADD CONSTRAINT chk_bmg_batch_output_lte_input
            CHECK (output_weight_kg IS NULL OR output_weight_kg <= input_weight_kg)
        ");

        // bmg_outputs.output_weight_kg must be <= the parent batch's input_weight_kg
        $this->db->query("
            ALTER TABLE bmg_outputs
            ADD CONSTRAINT chk_bmg_output_lte_batch_input
            CHECK (output_weight_kg <= (
                SELECT b.input_weight_kg
                FROM bmg_batches b
                WHERE b.id = batch_id
            ))
        ");
    }

    public function down(): void
    {
        // MySQL doesn't support DROP CONSTRAINT in older versions; use
        // the auto-generated constraint name pattern. If this fails, drop
        // manually via the migration table.
        try {
            $this->db->query("ALTER TABLE bmg_batches DROP CONSTRAINT chk_bmg_batch_output_lte_input");
        } catch (\Throwable $e) {
            log_message('warning', 'Could not drop batch constraint: ' . $e->getMessage());
        }

        try {
            $this->db->query("ALTER TABLE bmg_outputs DROP CONSTRAINT chk_bmg_output_lte_batch_input");
        } catch (\Throwable $e) {
            log_message('warning', 'Could not drop output constraint: ' . $e->getMessage());
        }
    }
}
