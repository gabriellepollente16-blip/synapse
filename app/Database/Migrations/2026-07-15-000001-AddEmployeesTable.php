<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds the `employees` table to support HR-integrated employee identity.
 *
 * Employees are university staff who are also patients of the clinic and/or
 * counselling office. Their data is gathered in coordination with the HR
 * Department and synced via the HR Integration module.
 */
class AddEmployeesTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                    => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'               => ['type' => 'BIGINT', 'unsigned' => true, 'unique' => true],
            'employee_number'       => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'qr_code'               => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'unique' => true],
            'rfid_tag'              => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'unique' => true],
            'department'            => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'position'              => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'date_hired'            => ['type' => 'DATE', 'null' => true],
            'employment_status'     => ['type' => 'ENUM', 'constraint' => ['active', 'inactive', 'on_leave'], 'default' => 'active'],
            'hr_synced_at'          => ['type' => 'TIMESTAMP', 'null' => true],
            'emergency_contact_name'=> ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'emergency_contact_phone'=> ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'date_of_birth'         => ['type' => 'DATE', 'null' => true],
            'gender'                => ['type' => 'ENUM', 'constraint' => ['male', 'female', 'other'], 'null' => true],
            'address'               => ['type' => 'TEXT', 'null' => true],
            'created_at'            => ['type' => 'TIMESTAMP', 'null' => true],
            'updated_at'            => ['type' => 'TIMESTAMP', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->addKey('employee_number');
        $this->forge->addKey('rfid_tag');
        $this->forge->addKey('employment_status');
        $this->forge->createTable('employees', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('employees', true);
    }
}
