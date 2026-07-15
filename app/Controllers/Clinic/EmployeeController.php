<?php

namespace App\Controllers\Clinic;

use App\Controllers\BaseController;
use App\Models\EmployeeModel;
use App\Models\UserModel;
use App\Models\UserRoleModel;

/**
 * EmployeeController — manages employee patient records.
 *
 * Employees are HR-managed users who are also clinic/counselling patients.
 * This controller provides CRUD operations and HR integration.
 */
class EmployeeController extends BaseController
{
    protected EmployeeModel $employeeModel;
    protected UserModel $userModel;
    protected UserRoleModel $userRoleModel;

    public function __construct()
    {
        $this->employeeModel  = new EmployeeModel();
        $this->userModel      = new UserModel();
        $this->userRoleModel  = new UserRoleModel();
        helper(['form']);
    }

    /**
     * List all employees.
     */
    public function index()
    {
        $data = [
            'title'     => 'Employees — SYNAPSE',
            'employees' => $this->employeeModel->orderBy('employee_number', 'ASC')->findAll(),
        ];
        return view('clinic/employees/index', $data);
    }

    /**
     * Show form to create a new employee.
     */
    public function create()
    {
        return view('clinic/employees/create', [
            'title' => 'New Employee — SYNAPSE',
        ]);
    }

    /**
     * Store a new employee record.
     */
    public function store()
    {
        $rules = [
            'email'           => 'required|valid_email|is_unique[users.email]',
            'employee_number' => 'required|max_length[50]|is_unique[employees.employee_number]',
            'first_name'      => 'required|max_length[100]',
            'last_name'       => 'required|max_length[100]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Create user account first
        $tempPassword = bin2hex(random_bytes(8));
        $userId = $this->userModel->insert([
            'email'         => strtolower(trim($this->request->getPost('email'))),
            'password_hash' => password_hash($tempPassword, PASSWORD_DEFAULT),
            'first_name'    => $this->request->getPost('first_name'),
            'last_name'     => $this->request->getPost('last_name'),
            'user_type'     => 'employee',
        ], true);

        // Assign employee role
        $this->userRoleModel->assignRole($userId, 'employee');

        // Create employee record
        $this->employeeModel->insert([
            'user_id'         => $userId,
            'employee_number' => $this->request->getPost('employee_number'),
            'department'      => $this->request->getPost('department'),
            'position'        => $this->request->getPost('position'),
            'date_hired'      => $this->request->getPost('date_hired') ?: null,
            'employment_status' => 'active',
        ], true);

        return redirect()->to('/clinic/employees')->with('success', "Employee created. Temporary password: {$tempPassword}");
    }

    /**
     * Show a specific employee.
     */
    public function show($id)
    {
        $employee = $this->employeeModel->find($id);
        if (!$employee) {
            return redirect()->to('/clinic/employees')->with('error', 'Employee not found.');
        }

        $user = $this->userModel->find($employee['user_id']);

        return view('clinic/employees/show', [
            'title'    => 'Employee Details — SYNAPSE',
            'employee' => $employee,
            'user'     => $user,
        ]);
    }

    /**
     * Show edit form.
     */
    public function edit($id)
    {
        $employee = $this->employeeModel->find($id);
        if (!$employee) {
            return redirect()->to('/clinic/employees')->with('error', 'Employee not found.');
        }

        return view('clinic/employees/edit', [
            'title'    => 'Edit Employee — SYNAPSE',
            'employee' => $employee,
        ]);
    }

    /**
     * Update employee record.
     */
    public function update($id)
    {
        $employee = $this->employeeModel->find($id);
        if (!$employee) {
            return redirect()->to('/clinic/employees')->with('error', 'Employee not found.');
        }

        $this->employeeModel->update($id, [
            'department'        => $this->request->getPost('department'),
            'position'          => $this->request->getPost('position'),
            'date_hired'        => $this->request->getPost('date_hired') ?: null,
            'employment_status' => $this->request->getPost('employment_status') ?: 'active',
        ]);

        return redirect()->to('/clinic/employees/' . $id)->with('success', 'Employee updated.');
    }

    /**
     * AJAX search for autocomplete.
     */
    public function search()
    {
        $term = $this->request->getGet('term') ?? '';
        $results = $this->employeeModel->search($term, 20);
        return $this->response->setJSON($results);
    }

    /**
     * Sync employees from HR Department CSV upload.
     * (Phase 13 will expand this with full HR system integration.)
     */
    public function syncFromHr()
    {
        $file = $this->request->getFile('hr_csv');
        if (! $file || ! $file->isValid()) {
            return redirect()->back()->with('error', 'Please upload a valid HR CSV file.');
        }

        $handle = fopen($file->getTempName(), 'r');
        $header = fgetcsv($handle);

        // Expected header: employee_number,first_name,last_name,email,department,position,date_hired
        $stats = ['inserted' => 0, 'updated' => 0, 'errors' => 0];

        while (($row = fgetcsv($handle)) !== false) {
            try {
                $data = array_combine($header, $row);
                $existing = $this->employeeModel->findByEmployeeNumber($data['employee_number']);

                if ($existing) {
                    $this->employeeModel->update($existing['id'], [
                        'department'  => $data['department'] ?? null,
                        'position'    => $data['position'] ?? null,
                        'date_hired'  => $data['date_hired'] ?? null,
                    ]);
                    $this->employeeModel->markSynced($existing['id']);
                    $stats['updated']++;
                } else {
                    // Create user + employee
                    $userId = $this->userModel->insert([
                        'email'         => strtolower(trim($data['email'])),
                        'password_hash' => password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT),
                        'first_name'    => $data['first_name'],
                        'last_name'     => $data['last_name'],
                        'user_type'     => 'employee',
                    ], true);
                    $this->userRoleModel->assignRole($userId, 'employee');
                    $this->employeeModel->insert([
                        'user_id'         => $userId,
                        'employee_number' => $data['employee_number'],
                        'department'      => $data['department'] ?? null,
                        'position'        => $data['position'] ?? null,
                        'date_hired'      => $data['date_hired'] ?? null,
                        'employment_status' => 'active',
                    ], true);
                    $stats['inserted']++;
                }
            } catch (\Throwable $e) {
                $stats['errors']++;
                log_message('error', 'HR sync error: ' . $e->getMessage());
            }
        }
        fclose($handle);

        return redirect()->to('/clinic/employees')->with('success',
            "HR sync complete: {$stats['inserted']} inserted, {$stats['updated']} updated, {$stats['errors']} errors.");
    }
}
