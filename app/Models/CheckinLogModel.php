<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * CheckinLogModel — records every RFID-based check-in.
 *
 * Each entry represents one scan of an institutional ID. Used for
 * point-of-service record retrieval, audit trail, and analytics on
 * patient visit patterns.
 */
class CheckinLogModel extends Model
{
    protected $table            = 'checkin_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'patient_type', 'student_id', 'employee_id', 'rfid_tag_scanned',
        'checkin_at', 'module', 'notes',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';

    protected $validationRules = [
        'patient_type'     => 'required|in_list[student,employee]',
        'rfid_tag_scanned' => 'required|max_length[255]',
        'module'           => 'required|in_list[clinic,counselling]',
    ];

    /**
     * Log a check-in from an RFID scan.
     *
     * @param string $patientType 'student' or 'employee'
     * @param int    $patientId   students.id or employees.id
     * @param string $rfidTag     The raw scanned tag
     * @param string $module      'clinic' or 'counselling'
     */
    public function logCheckin(string $patientType, int $patientId, string $rfidTag, string $module): int
    {
        $data = [
            'patient_type'     => $patientType,
            'rfid_tag_scanned' => $rfidTag,
            'checkin_at'       => date('Y-m-d H:i:s'),
            'module'           => $module,
        ];

        if ($patientType === 'student') {
            $data['student_id'] = $patientId;
        } else {
            $data['employee_id'] = $patientId;
        }

        return $this->insert($data, true);
    }

    /**
     * Get recent check-ins for a specific patient.
     */
    public function getRecentForPatient(string $patientType, int $patientId, int $limit = 10): array
    {
        $column = $patientType === 'student' ? 'student_id' : 'employee_id';
        return $this->where('patient_type', $patientType)
            ->where($column, $patientId)
            ->orderBy('checkin_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Get today's check-ins (for lobby/queue display).
     */
    public function getTodayCheckins(string $module = null): array
    {
        $builder = $this->where('DATE(checkin_at)', date('Y-m-d'));
        if ($module) {
            $builder->where('module', $module);
        }
        return $builder->orderBy('checkin_at', 'DESC')->findAll();
    }
}
