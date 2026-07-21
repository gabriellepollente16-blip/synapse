<?php

namespace App\Models;

use CodeIgniter\Model;

class AssessmentTemplateModel extends Model
{

    /**
     * Magic-call guard — the underlying table was dropped by a 2026-07-15 migration.
     * Any caller that still references this model gets a loud runtime error
     * instead of an opaque SQL failure. Read screening templates.
     */
    public function __call($name, $args)
    {
        throw new \RuntimeException("AssessmentTemplateModel::{$name}" . ' was called but the backing table was dropped from SYNAPSE; see migrations 2026-07-15-000006 / 2026-07-15-000007.');
    }

    protected $table            = 'assessment_templates';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'title', 'description', 'type', 'is_active', 'created_by',
    ];

    protected $useTimestamps = false;

    /**
     * Get all active screening templates.
     */
    public function getActive(?string $type = null): array
    {
        // Backing table dropped (migration 2026-07-15-000006 / 2026-07-15-000007).
        // Return empty array so ScreeningController::index() can render the page.
        // Re-add the original query when the screening workflow is reinstated.
        return [];
    }

    /**
     * Get a template with its questions (ordered).
     */
    public function getWithQuestions(int $id): ?array
    {
        // Backing table dropped. No live template to surface; return null so
        // callers redirect away with a clear "not found" message.
        return null;
    }
}
