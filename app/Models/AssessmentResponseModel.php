<?php

namespace App\Models;

use CodeIgniter\Model;

class AssessmentResponseModel extends Model
{

    /**
     * Magic-call guard — the underlying table was dropped by a 2026-07-15 migration.
     * Any caller that still references this model gets a loud runtime error
     * instead of an opaque SQL failure. Wrote screening scores.
     */
    public function __call($name, $args)
    {
        throw new \RuntimeException("AssessmentResponseModel::{$name}" . ' was called but the backing table was dropped from SYNAPSE; see migrations 2026-07-15-000006 / 2026-07-15-000007.');
    }

    protected $table            = 'assessment_responses';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'template_id', 'student_id', 'appointment_id',
        'responses', 'total_score',
    ];

    protected $useTimestamps = false;

    /**
     * Submit a screening response with auto-calculated score.
     */
    public function submit(array $data): int|false
    {
        // Backing table dropped (migration 2026-07-15-000006 / 2026-07-15-000007).
        // Return false so ScreeningController::submit() surfaces a clear
        // "failed to save" error to the user instead of crashing on insert.
        return false;
    }

    /**
     * Get responses for a student (most recent first).
     */
    public function getByStudent(int $studentId, int $limit = 10): array
    {
        // Backing table dropped. Return empty array so history views render.
        return [];
    }

    /**
     * Get score history for a specific template+student (for trend charts).
     */
    public function getScoreHistory(int $studentId, int $templateId): array
    {
        // Backing table dropped. Trend charts show an empty series.
        return [];
    }

    /**
     * Get a response with template + questions for results display.
     */
    public function getWithTemplate(int $id): ?array
    {
        // Backing table dropped. Return null so ScreeningController::results()
        // redirects to the index with a "not found" flash message.
        return null;
    }

    /**
     * Calculate total score from Likert responses.
     */
    public static function calculateTotalScore(array $responses): int
    {
        $total = 0;
        foreach ($responses as $answer) {
            if (is_numeric($answer)) {
                $total += (int) $answer;
            }
        }
        return $total;
    }

    /**
     * Get severity interpretation for PHQ-9.
     */
    public static function getPHQ9Severity(int $score): array
    {
        if ($score <= 4)  return ['level' => 'minimal',           'color' => '#10B981', 'label' => 'Minimal (0-4)'];
        if ($score <= 9)  return ['level' => 'mild',              'color' => '#84CC16', 'label' => 'Mild (5-9)'];
        if ($score <= 14) return ['level' => 'moderate',          'color' => '#F59E0B', 'label' => 'Moderate (10-14)'];
        if ($score <= 19) return ['level' => 'moderately_severe', 'color' => '#EF4444', 'label' => 'Moderately Severe (15-19)'];
        return                   ['level' => 'severe',            'color' => '#DC2626', 'label' => 'Severe (20-27)'];
    }

    /**
     * Get severity interpretation for GAD-7.
     */
    public static function getGAD7Severity(int $score): array
    {
        if ($score <= 4)  return ['level' => 'minimal',  'color' => '#10B981', 'label' => 'Minimal (0-4)'];
        if ($score <= 9)  return ['level' => 'mild',     'color' => '#84CC16', 'label' => 'Mild (5-9)'];
        if ($score <= 14) return ['level' => 'moderate', 'color' => '#F59E0B', 'label' => 'Moderate (10-14)'];
        return                   ['level' => 'severe',   'color' => '#DC2626', 'label' => 'Severe (15-21)'];
    }
}
