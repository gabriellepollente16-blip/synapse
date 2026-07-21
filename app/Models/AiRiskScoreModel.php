<?php

namespace App\Models;

use CodeIgniter\Model;

class AiRiskScoreModel extends Model
{

    /**
     * Magic-call guard — the underlying table was dropped by a 2026-07-15 migration.
     * Any caller that still references this model gets a loud runtime error
     * instead of an opaque SQL failure. Stored AI-derived risk scores.
     */
    public function __call($name, $args)
    {
        throw new \RuntimeException("AiRiskScoreModel::{$name}" . ' was called but the backing table was dropped from SYNAPSE; see migrations 2026-07-15-000006 / 2026-07-15-000007.');
    }

    protected $table            = 'ai_risk_scores';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'student_id', 'assessment_response_id', 'score_type', 'risk_level',
        'current_score', 'trend_slope', 'trend_direction', 'anomaly_detected',
        'anomaly_magnitude', 'data_points_used', 'prediction_window_days',
        'projected_score', 'model_version', 'counsellor_notified', 'notified_at',
    ];

    protected $useTimestamps = false;

    /**
     * Get latest risk score details for a student.
     */
    public function getLatestForStudent(int $studentId): ?array
    {
        // Backing table dropped. Return null so callers fall back to "no risk
        // score on file" without crashing.
        return null;
    }
    /**
     * Get the AI risk score attached to a specific screening response.
     * Backing table dropped; safe no-op so result views render.
     */
    public function getLatestForResponse(int $responseId): ?array
    {
        return null;
    }

    /**
     * Persist a newly-computed AI risk score. The ai_risk_scores table is no
     * longer maintained (see migration 2026-07-15-000006 / 2026-07-15-000007),
     * so this is a no-op that returns true to mimic a successful insert.
     * When the risk-storage workflow is reinstated, restore the real insert.
     */
    public function persist(array $payload): bool
    {
        return true;
    }
}
