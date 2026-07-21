<?php

namespace App\Models;

use CodeIgniter\Model;

class AssessmentQuestionModel extends Model
{

    /**
     * Magic-call guard — the underlying table was dropped by a 2026-07-15 migration.
     * Any caller that still references this model gets a loud runtime error
     * instead of an opaque SQL failure. Read question banks.
     */
    public function __call($name, $args)
    {
        throw new \RuntimeException("AssessmentQuestionModel::{$name}" . ' was called but the backing table was dropped from SYNAPSE; see migrations 2026-07-15-000006 / 2026-07-15-000007.');
    }

    protected $table            = 'assessment_questions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'template_id', 'question_text', 'question_type',
        'options', 'order_index', 'is_required',
    ];

    protected $useTimestamps = false;

    /**
     * Get questions for a template, ordered.
     */
    public function getByTemplate(int $templateId): array
    {
        // Backing table dropped. Return empty array so the survey/take view
        // at least renders an empty question set.
        return [];
    }
}
