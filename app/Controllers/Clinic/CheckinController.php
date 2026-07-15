<?php

namespace App\Controllers\Clinic;

use App\Controllers\BaseController;
use App\Models\CheckinLogModel;
use App\Models\StudentModel;
use App\Models\EmployeeModel;

/**
 * CheckinController — handles RFID-based patient check-in.
 *
 * Staff scans an institutional ID at the point of service. The system
 * looks up the scanned tag against students and employees, then logs
 * the encounter with timestamp and module (clinic or counselling).
 */
class CheckinController extends BaseController
{
    protected CheckinLogModel $checkinLogModel;
    protected StudentModel $studentModel;
    protected EmployeeModel $employeeModel;

    public function __construct()
    {
        $this->checkinLogModel = new CheckinLogModel();
        $this->studentModel    = new StudentModel();
        $this->employeeModel   = new EmployeeModel();
    }

    /**
     * Process an RFID scan.
     * POST /clinic/checkin/scan
     * Body: { rfid_tag: "string", module: "clinic" | "counselling" }
     */
    public function scan()
    {
        $rfidTag = trim((string) $this->request->getPost('rfid_tag'));
        $module  = $this->request->getPost('module') ?? 'clinic';

        if (empty($rfidTag)) {
            return $this->response->setJSON([
                'success' => false,
                'error'   => 'No RFID tag provided',
            ])->setStatusCode(400);
        }

        // Try to find a student by RFID
        $student = $this->studentModel->findByRFID($rfidTag);
        if ($student) {
            $checkinId = $this->checkinLogModel->logCheckin('student', $student['id'], $rfidTag, $module);
            return $this->response->setJSON([
                'success'     => true,
                'checkin_id'  => $checkinId,
                'patient_type' => 'student',
                'patient'     => [
                    'id'             => $student['id'],
                    'student_number' => $student['student_number'],
                ],
            ]);
        }

        // Try employees
        $employee = $this->employeeModel->findByRFID($rfidTag);
        if ($employee) {
            $checkinId = $this->checkinLogModel->logCheckin('employee', $employee['id'], $rfidTag, $module);
            return $this->response->setJSON([
                'success'     => true,
                'checkin_id'  => $checkinId,
                'patient_type' => 'employee',
                'patient'     => [
                    'id'             => $employee['id'],
                    'employee_number' => $employee['employee_number'],
                ],
            ]);
        }

        // No match — log anyway
        $checkinId = $this->checkinLogModel->insert([
            'patient_type'     => 'student',  // default
            'rfid_tag_scanned' => $rfidTag,
            'checkin_at'       => date('Y-m-d H:i:s'),
            'module'           => $module,
            'notes'            => 'No matching record found',
        ], true);

        return $this->response->setJSON([
            'success'   => false,
            'checkin_id' => $checkinId,
            'error'     => 'No matching student or employee found for this RFID tag.',
        ])->setStatusCode(404);
    }

    /**
     * Show today's check-in log.
     */
    public function log()
    {
        $module = $this->request->getGet('module');

        return view('clinic/checkin/log', [
            'title'     => 'Check-in Log — SYNAPSE',
            'checkins'  => $this->checkinLogModel->getTodayCheckins($module),
        ]);
    }
}
