<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * IntakeNoteModel — manages counsellor session notes.
 *
 * Replaces the removed screening/assessment functionality. Notes are
 * free-text, confidential, and access-restricted to the assigned
 * counsellor and administrators.
 */
class IntakeNoteModel extends Model
{
    protected $table            = 'intake_notes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;

    protected $allowedFields = [
        'patient_type', 'student_id', 'employee_id', 'counsellor_id', 'appointment_id',
        'presenting_concern', 'session_notes', 'action_items',
        'session_date', 'is_confidential',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'patient_type'  => 'required|in_list[student,employee]',
        'counsellor_id' => 'required|is_natural_no_zero',
    ];

    /**
     * Get intake notes created by a specific counsellor.
     */
    public function getForCounsellor(int $counsellorId, int $limit = 50): array
    {
        return $this->where('counsellor_id', $counsellorId)
            ->orderBy('session_date', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Get intake notes for a specific patient (student or employee).
     */
    public function getForPatient(string $patientType, int $patientId, int $limit = 50): array
    {
        $column = $patientType === 'student' ? 'student_id' : 'employee_id';
        return $this->where('patient_type', $patientType)
            ->where($column, $patientId)
            ->orderBy('session_date', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Get intake notes linked to a specific appointment.
     */
    public function getForAppointment(int $appointmentId): ?array
    {
        return $this->where('appointment_id', $appointmentId)->first();
    }

    /**
     * Get recent intake notes across all counsellors (admin view).
     */
    public function getRecent(int $limit = 30): array
    {
        return $this->select('intake_notes.*, users.first_name AS counsellor_first_name, users.last_name AS counsellor_last_name')
            ->join('users', 'users.id = intake_notes.counsellor_id', 'left')
            ->orderBy('intake_notes.session_date', 'DESC')
            ->limit($limit)
            ->findAll();
    }
}
