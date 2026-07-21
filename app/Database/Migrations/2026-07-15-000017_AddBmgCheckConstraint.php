<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds a database-level CHECK constraint that enforces the fundamental
 * rule of composting: a batch's output_weight_kg cannot exceed its input_weight_kg.
 *
 * The application layer also validates this rule, but the DB constraint
 * is defense-in-depth.
 *
 * Note: cross-table CHECK constraints (output_weight_kg <= the *parent
 * batch's* input) cannot be expressed as MariaDB/MySQL CHECK clauses
 * because subqueries are not allowed in CHECK expressions. That rule is
 * enforced at the application layer (BmgOutputModel::validateYield()).
 */
class AddBmgCheckConstraint extends Migration
{
    public function up(): void
    {
        // Per-row constraint: a batch's output_weight_kg must not exceed its input_weight_kg.
        // MariaDB 10.4 parses CHECK but does not enforce it. MySQL 8.0+ does enforce it.
        $this->db->query("
            ALTER TABLE bmg_batches
            ADD CONSTRAINT chk_bmg_batch_output_lte_input
            CHECK (output_weight_kg IS NULL OR output_weight_kg <= input_weight_kg)
        ");
    }

    public function down(): void
    {
        $this->db->query("ALTER TABLE bmg_batches DROP CONSTRAINT IF EXISTS chk_bmg_batch_output_lte_input");
    }
}
