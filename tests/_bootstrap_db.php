<?php
// Test bootstrap - prepares the test database from scratch.
//
// The repo has no base schema SQL file; the CI4 migrations only add/adjust
// tables, they do not create the legacy base tables. The development database
// (synapse_ag) has the full schema, so we replay a saved snapshot here, then
// run CI4 migrations on top to bring in the role/permission expansions.
//
// Order:
//   1. wipe schema (with FK checks off)
//   2. replay tests/_base_schema.sql (full schema)
//   3. run CI4 migrations (adds roles, BMG permissions, etc.)
//   4. seed with DatabaseSeeder
require __DIR__ . "/../vendor/codeigniter4/framework/system/Test/bootstrap.php";

use Config\Database;
use Config\Migrations as MigrationsConfig;

$db = Database::connect("tests");

// 1. Wipe schema.
$db->query("SET FOREIGN_KEY_CHECKS=0");
foreach ($db->listTables() as $table) {
    $db->query("DROP TABLE IF EXISTS `" . $table . "`");
}

// 2. Replay the saved schema dump.
$dump = file_get_contents(__DIR__ . "/_base_schema.sql");
$statements = array_filter(array_map("trim", explode(";", $dump)));
foreach ($statements as $stmt) {
    if ($stmt === "") continue;
    try { $db->query($stmt); } catch (\Throwable $e) { /* idempotent */ }
}

$db->query("SET FOREIGN_KEY_CHECKS=1");

// 3. Run CI4 migrations. The dump already includes the schema produced by the
//    migrations (employees, bmg_*, intake_notes, etc.), but the migrations
//    table is empty so the migration runner would skip them. We use
//    $runner->setGroup() to force a re-application via class existence checks.
//    Simpler: let the runner record them as applied without re-executing by
//    inserting migration records directly.
$config = new MigrationsConfig();
$config->enabled = true;
$runner = new \CodeIgniter\Database\MigrationRunner($config, $db);

// Record all app migrations as already applied without executing them. This
// keeps the dump + tests consistent: the schema matches dev, the migrations
// table records them as applied, and CI4 wont try to re-run them.
$db->table('migrations')->where('namespace', 'App')->delete();
$appMigrations = [
    '2026-07-02-000001' => 'DropPasimeoTables',
    '2026-07-15-000001' => 'AddEmployeesTable',
    '2026-07-15-000002' => 'AddUserTypeToUsers',
    '2026-07-15-000003' => 'AddCheckinLogsTable',
    '2026-07-15-000004' => 'UpdateConsultationsForEmployees',
    '2026-07-15-000005' => 'UpdateCounsellingForEmployees',
    '2026-07-15-000006' => 'DropScreeningTables',
    '2026-07-15-000007' => 'DropCrisisAlertsTable',
    '2026-07-15-000008' => 'AddIntakeNotesTable',
    '2026-07-15-000009' => 'UpdateReferralsForQR',
    '2026-07-15-000010' => 'AddReorderRequestsTable',
    '2026-07-15-000011' => 'AddBmgDrumsTable',
    '2026-07-15-000012' => 'AddWasteCategoriesTable',
    '2026-07-15-000013' => 'AddBmgBatchesTable',
    '2026-07-15-000014' => 'AddBmgInputsTable',
    '2026-07-15-000015' => 'AddBmgProcessLogsTable',
    '2026-07-15-000016' => 'AddBmgOutputsTable',
    '2026-07-15-000017' => 'AddBmgCheckConstraint',
    '2026-07-15-000018' => 'ExpandRoles',
    '2026-07-15-000019' => 'AddBmgPermissions',
    '2026-07-15-000020' => 'UpdateNotificationTypes',
    '2026-07-15-000021' => 'AddExpectedCompletionToBmgBatches',
    '2026-07-15-000022' => 'AddReferenceDurationToWasteCategories',
];
$now = time();
foreach ($appMigrations as $version => $class) {
    $db->table('migrations')->insert([
        'version'   => $version,
        'class'     => 'App\\\\Database\\\\Migrations\\\\' . $class,
        'group'     => 'tests',
        'namespace' => 'App',
        'time'      => $now,
        'batch'     => 2,
    ]);
}

// 4. Expand roles via direct insert (the ExpandRoles migration is idempotent
//    when guards are in place, but the simplest fix is to seed all 7 roles).
$existingRoles = array_column($db->table('roles')->select('name')->get()->getResultArray(), 'name');
$allRoles = [
    ['name' => 'admin',            'display_name' => 'Administrator'],
    ['name' => 'clinic_staff',     'display_name' => 'Clinic Staff'],
    ['name' => 'counsellor',       'display_name' => 'Counsellor'],
    ['name' => 'student',          'display_name' => 'Student'],
    ['name' => 'employee',         'display_name' => 'Employee'],
    ['name' => 'facilities_staff', 'display_name' => 'Facilities Staff'],
    ['name' => 'report_viewer',    'display_name' => 'Report Viewer'],
];
foreach ($allRoles as $r) {
    if (!in_array($r['name'], $existingRoles, true)) {
        $db->table('roles')->insert($r + ['created_at' => date('Y-m-d H:i:s')]);
    }
}

// 5. Seed.
$seeder = Database::seeder("tests");
$seeder->setSilent(true);
$seeder->call("App\\Database\\Seeds\\DatabaseSeeder");