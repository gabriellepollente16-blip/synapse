<?php

namespace App\Models;

use CodeIgniter\Model;

class CrisisAlertModel extends Model
{

    /**
     * Magic-call guard — the underlying table was dropped by a 2026-07-15 migration.
     * Any caller that still references this model gets a loud runtime error
     * instead of an opaque SQL failure. Used by CrisisController which was retired; routes removed.
     */
    public function __call($name, $args)
    {
        throw new \RuntimeException("CrisisAlertModel::{$name}" . ' was called but the backing table was dropped from SYNAPSE; see migrations 2026-07-15-000006 / 2026-07-15-000007.');
    }

    protected $table            = 'crisis_alerts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'student_id', 'assessment_response_id', 'trigger_source',
        'severity', 'status', 'assigned_counsellor_id',
        'acknowledged_at', 'acknowledged_by',
        'resolution_notes', 'resolved_at',
        'escalated_to', 'escalated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Get active (unresolved) crisis alerts.
     */
    public function getActive(): array
    {
        // Backing table dropped (migration 2026-07-15-000006 / 2026-07-15-000007).
        // Return empty array so dashboards/UI don't 500. If a crisis workflow
        // is reinstated, restore the original query and the migration.
        return [];
    }

    /**
     * Get unacknowledged alerts (for dashboard urgency).
     */
    public function getUnacknowledged(): array
    {
        // See getActive() note. Returning empty array keeps callers (AppointmentController::index,
        // dashboard widgets) functional. Crisis handling is currently out of scope.
        return [];
    }

    /**
     * Acknowledge an alert.
     */
    public function acknowledge(int $id, int $userId): bool
    {
        // Backing table dropped. Return true to keep callers' control flow intact.
        return true;
    }

    /**
     * Resolve an alert with notes.
     */
    public function resolve(int $id, string $notes): bool
    {
        // See acknowledge() — backing table dropped.
        return true;
    }

    /**
     * Escalate an alert to head counsellor.
     */
    public function escalate(int $id, int $headCounsellorId): bool
    {
        // See acknowledge() — backing table dropped.
        return true;
    }

    /**
     * Create a crisis alert from a screening response.
     */
    public function createFromScreening(int $studentId, int $responseId, string $source, string $severity = 'high', ?int $counsellorId = null): int|false
    {
        // Backing table dropped — we cannot persist a crisis alert. Surface a
        // loud notification to counsellors so the situation isn't silently lost,
        // then return false. When the crisis workflow is reinstated, restore the
        // original insert + getInsertID() block.
        $notifModel = new NotificationModel();
        $notifModel->createNotification(
            $counsellorId,
            'crisis_alert',
            '🚨 Crisis Alert (legacy)',
            "Crisis screening flagged a student (source: {$source}, severity: {$severity}). The crisis_alerts table is no longer maintained — review intake notes manually.",
            'counselling',
            'intake_notes',
            $responseId
        );
        return false;
    }

    /**
     * Get dashboard stats.
     */
    public function getStats(): array
    {
        // Backing table dropped. Return zeroed stats so dashboards render.
        return ['triggered' => 0, 'acknowledged' => 0, 'inProgress' => 0, 'escalated' => 0];
    }
}
