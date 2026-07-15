<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * EmployeeModel — manages university employee records.
 *
 * Employees are HR-managed users who are also patients of the clinic and/or
 * counselling office. The HR Department syncs employee data into this
 * table; clinics and counselling use it for patient lookups by employee
 * number, QR code, or RFID tag.
 */
class EmployeeModel extends Model
{
    protected $table            = 'employees';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'user_id', 'employee_number', 'qr_code', 'rfid_tag',
        'department', 'position', 'date_hired', 'employment_status',
        'hr_synced_at', 'emergency_contact_name', 'emergency_contact_phone',
        'date_of_birth', 'gender', 'address',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'user_id'         => 'required|is_natural_no_zero|is_unique[employees.user_id,id,{id}]',
        'employee_number' => 'required|max_length[50]|is_unique[employees.employee_number,id,{id}]',
        'employment_status' => 'in_list[active,inactive,on_leave]',
        'gender'          => 'in_list[male,female,other]',
    ];

    /**
     * Find an employee by their employee number.
     */
    public function findByEmployeeNumber(string $number): ?array
    {
        return $this->where('employee_number', $number)->first();
    }

    /**
     * Find an employee by their institutional QR code.
     */
    public function findByQR(string $qrCode): ?array
    {
        return $this->where('qr_code', $qrCode)->first();
    }

    /**
     * Find an employee by their RFID tag.
     */
    public function findByRFID(string $rfidTag): ?array
    {
        return $this->where('rfid_tag', $rfidTag)->first();
    }

    /**
     * Get all active employees for dropdown lists.
     */
    public function getActive(): array
    {
        return $this->where('employment_status', 'active')->orderBy('employee_number', 'ASC')->findAll();
    }

    /**
     * Search employees by name or employee_number (autocomplete).
     */
    public function search(string $term, int $limit = 20): array
    {
        if (empty($term)) {
            return [];
        }

        return $this->groupStart()
            ->like('employee_number', $term)
            ->orLike('qr_code', $term)
            ->orWhere('user_id IN (SELECT id FROM users WHERE first_name LIKE "%' . $this->db->escLike($term) . '%" OR last_name LIKE "%' . $this->db->escLike($term) . '%")')
            ->groupEnd()
            ->limit($limit)
            ->find();
    }

    /**
     * Mark an employee as just-synced from HR.
     */
    public function markSynced(int $employeeId): bool
    {
        return $this->update($employeeId, ['hr_synced_at' => date('Y-m-d H:i:s')]) !== false;
    }
}
