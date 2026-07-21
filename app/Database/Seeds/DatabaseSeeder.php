<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Master seeder — runs all seeders in the correct order.
 *
 * Usage: php spark db:seed DatabaseSeeder
 */
class DatabaseSeeder extends Seeder
{
    public function run()
    {
        echo "=== SYNAPSE Database Seeder ===\n\n";

        echo "[1/9] Seeding Roles...\n";
        $this->call('RoleSeeder');

        echo "\n[2/9] Seeding Permissions...\n";
        $this->call('PermissionSeeder');

        echo "\n[3/9] Seeding Role-Permission Assignments...\n";
        $this->call('RolePermissionSeeder');

        echo "[4/9] Seeding Admin User...\n";
        $this->call('AdminSeeder');

        echo "\n[5/9] Seeding Clinic Staff User...\n";
        $this->call('ClinicStaffSeeder');

        echo "\n[6/9] Seeding Counsellor User...\n";
        $this->call('CounsellorSeeder');

        echo "\n[7/9] Seeding Medicines & Batches...\n";
        $this->call('MedicineSeeder');

        echo "\n[8/9] Seeding Students...\n";
        $this->call('StudentSeeder');

        echo "\n[9/9] Seeding Facilities (BMG) + System Modules registry...\n";
        $this->call('FacilitiesSeeder');
        $this->call('SystemModulesSeeder');

        echo "\n=== Seeding Complete ===\n";
    }
}
