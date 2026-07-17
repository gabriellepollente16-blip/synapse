<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// ============================================================
// PUBLIC ROUTES (no auth required)
// ============================================================
$routes->get('/', 'AuthController::showLogin');
$routes->get('login', 'AuthController::showLogin');
$routes->post('login', 'AuthController::attemptLogin');
$routes->get('logout', 'AuthController::logout');
$routes->get('testai', 'Home::testAi');

// UI component showcase (developer reference page)
$routes->get('ui', 'UiController::showcase');

// ============================================================
// DASHBOARD (auth required — applied via Filters config)
// ============================================================
$routes->get('dashboard', 'DashboardController::index');
$routes->get('dashboard/admin', 'DashboardController::admin', ['filter' => 'role:admin']);
$routes->get('dashboard/clinic', 'DashboardController::clinic', ['filter' => 'role:admin,clinic_staff']);
$routes->get('dashboard/counsellor', 'DashboardController::counsellor', ['filter' => 'role:admin,counsellor']);
$routes->get('dashboard/bmg', 'DashboardController::bmg', ['filter' => 'role:admin,facilities_staff,report_viewer']);
$routes->get('dashboard/facility-operations', 'DashboardController::facilityOperations', ['filter' => 'role:admin,facilities_staff,report_viewer']);
$routes->get('dashboard/reports', 'DashboardController::reports', ['filter' => 'role:admin,report_viewer']);
$routes->get('dashboard/employee', 'DashboardController::employee', ['filter' => 'role:admin,employee']);
$routes->get('dashboard/student', 'DashboardController::student', ['filter' => 'role:admin,student']);

$routes->get('notifications/unread', 'NotificationController::unread');
$routes->post('notifications/read/(:any)', 'NotificationController::markRead/$1');

$routes->get('profile', 'ProfileController::index');
$routes->post('profile/update', 'ProfileController::update');

// ============================================================
// CLINIC MODULE (Phase 2)
// ============================================================
// Queue management (staff) + waiting-room display (patient TV + kiosk).
// state.json + display are intentionally PUBLIC — the kiosk tablet and the
// lobby TV use them, and the data they expose (queue position, "now
// serving" number + first name only) is not sensitive.
$routes->get('consultations/queue/display', 'Clinic\ConsultationController::display');
$routes->get('consultations/queue/state.json', 'Clinic\ConsultationController::state');

// Staff-only queue actions (call-next / start / skip / dashboard).
$routes->group('', ['filter' => 'role:admin,clinic_staff'], static function ($routes) {
    $routes->get('consultations/queue', 'Clinic\ConsultationController::queue');
    $routes->post('consultations/call-next', 'Clinic\ConsultationController::callNext');
    $routes->post('consultations/start/(:num)', 'Clinic\ConsultationController::start/$1');
    $routes->post('consultations/skip/(:num)', 'Clinic\ConsultationController::skip/$1');
});

$routes->group('clinic', ['filter' => 'role:admin,clinic_staff'], static function ($routes) {
    // Students
    $routes->get('students', 'Clinic\StudentController::index');
    $routes->get('students/create', 'Clinic\StudentController::create');
    $routes->post('students/store', 'Clinic\StudentController::store');
    $routes->get('students/(:num)', 'Clinic\StudentController::show/$1');
    $routes->get('students/edit/(:num)', 'Clinic\StudentController::edit/$1');
    $routes->post('students/update/(:num)', 'Clinic\StudentController::update/$1');

    // Consultations
    $routes->get('consultations', 'Clinic\ConsultationController::index');
    $routes->get('consultations/create/(:num)', 'Clinic\ConsultationController::create/$1');
    $routes->post('consultations/store', 'Clinic\ConsultationController::store');
    $routes->post('consultations/ajax-triage', 'Clinic\ConsultationController::ajaxTriage');
    $routes->get('consultations/(:num)', 'Clinic\ConsultationController::show/$1');
    $routes->get('consultations/vitals/(:num)', 'Clinic\ConsultationController::recordVitals/$1');
    $routes->post('consultations/vitals/(:num)', 'Clinic\ConsultationController::storeVitals/$1');
    $routes->get('consultations/diagnosis/(:num)', 'Clinic\ConsultationController::addDiagnosis/$1');
    $routes->post('consultations/diagnosis/(:num)', 'Clinic\ConsultationController::storeDiagnosis/$1');
    $routes->post('consultations/complete/(:num)', 'Clinic\ConsultationController::complete/$1');
    $routes->get('consultations/history/(:num)', 'Clinic\ConsultationController::history/$1');

    $routes->get('treatments/create/(:num)', 'Clinic\TreatmentController::create/$1');
    $routes->post('treatments/store', 'Clinic\TreatmentController::store');
    $routes->get('treatments/batches/(:num)', 'Clinic\TreatmentController::getBatches/$1'); // AJAX

    // Referrals
    $routes->get('referrals', 'Clinic\ReferralController::index');
});

$routes->get('clinic/referrals/create', 'Clinic\ReferralController::create', ['filter' => 'role:admin,clinic_staff,employee']);
$routes->get('clinic/referrals/create/(:num)', 'Clinic\ReferralController::create/$1', ['filter' => 'role:admin,clinic_staff,employee']);
$routes->post('clinic/referrals/store', 'Clinic\ReferralController::store', ['filter' => 'role:admin,clinic_staff,employee']);
$routes->get('clinic/students/search', 'Clinic\StudentController::search', ['filter' => 'role:admin,clinic_staff,employee']);

// ============================================================
// INVENTORY MODULE (Phase 2)
// ============================================================
$routes->group('inventory', ['filter' => 'role:admin,clinic_staff'], static function ($routes) {
    $routes->get('/', 'Inventory\MedicineController::index');
    $routes->get('medicines/create', 'Inventory\MedicineController::create');
    $routes->post('medicines/store', 'Inventory\MedicineController::store');
    $routes->get('medicines/(:num)', 'Inventory\MedicineController::show/$1');
    $routes->get('medicines/edit/(:num)', 'Inventory\MedicineController::edit/$1');
    $routes->post('medicines/update/(:num)', 'Inventory\MedicineController::update/$1');
    $routes->get('medicines/(:num)/batch', 'Inventory\MedicineController::addBatch/$1');
    $routes->post('medicines/(:num)/batch', 'Inventory\MedicineController::storeBatch/$1');
    $routes->get('low-stock', 'Inventory\MedicineController::lowStock');
    $routes->get('expiring', 'Inventory\MedicineController::expiring');
});

// ============================================================
// IOT KIOSK MODULE (Phase 4) — public-facing tablet outside the clinic,
// no role gate. The kiosk is meant to be used by any student walking in.
// ============================================================
$routes->group('iot', static function ($routes) {
    $routes->get('kiosk', 'Iot\KioskController::index');
    $routes->post('scan', 'Iot\KioskController::processScan');
    $routes->post('sync', 'Iot\KioskController::syncBuffer'); // For the sync button
});

// ============================================================
// COUNSELLING MODULE (Phase 3)
// ============================================================
$routes->group('counselling', ['filter' => 'role:admin,counsellor'], static function ($routes) {
    // Dashboard
    $routes->get('/', 'Counselling\AppointmentController::index');

    // Appointments
    $routes->get('appointments/create/(:num)', 'Counselling\AppointmentController::create/$1');
    $routes->post('appointments/store', 'Counselling\AppointmentController::store');
    $routes->get('appointments/(:num)', 'Counselling\AppointmentController::show/$1');
    $routes->post('appointments/start/(:num)', 'Counselling\AppointmentController::startSession/$1');
    $routes->post('appointments/complete/(:num)', 'Counselling\AppointmentController::completeSession/$1');
    $routes->post('appointments/no-show/(:num)', 'Counselling\AppointmentController::markNoShow/$1');
    $routes->post('appointments/cancel/(:num)', 'Counselling\AppointmentController::cancel/$1');

    // Screenings
    $routes->get('screenings', 'Counselling\ScreeningController::index');
    $routes->get('screenings/take/(:num)', 'Counselling\ScreeningController::take/$1');
    $routes->post('screenings/submit', 'Counselling\ScreeningController::submit');
    $routes->get('screenings/results/(:num)', 'Counselling\ScreeningController::results/$1');
    $routes->get('screenings/history/(:num)', 'Counselling\ScreeningController::history/$1');

    // Crisis Alerts
    $routes->get('crisis', 'Counselling\CrisisController::index');
    $routes->post('crisis/acknowledge/(:num)', 'Counselling\CrisisController::acknowledge/$1');
    $routes->post('crisis/resolve/(:num)', 'Counselling\CrisisController::resolve/$1');
    $routes->post('crisis/escalate/(:num)', 'Counselling\CrisisController::escalate/$1');

    // Availability
    $routes->get('availability', 'Counselling\AvailabilityController::index');
    $routes->post('availability/add', 'Counselling\AvailabilityController::addSlot');
    $routes->post('availability/remove/(:num)', 'Counselling\AvailabilityController::removeSlot/$1');

    // Referrals (incoming from clinic)
    $routes->get('referrals', 'Counselling\ReferralController::index');
    $routes->post('referrals/accept/(:num)', 'Counselling\ReferralController::accept/$1');
    $routes->post('referrals/decline/(:num)', 'Counselling\ReferralController::decline/$1');
});

// ============================================================
// ADMIN MODULE
// ============================================================
$routes->group('admin', ['filter' => 'role:admin'], static function ($routes) {
    // Landing
    $routes->get('/', 'Admin\DashboardController::index');

    // Users
    $routes->get('users',                          'Admin\UserController::index');
    $routes->get('users/create',                   'Admin\UserController::create');
    $routes->post('users/store',                   'Admin\UserController::store');
    $routes->get('users/(:num)',                   'Admin\UserController::show/$1');
    $routes->get('users/(:num)/edit',              'Admin\UserController::edit/$1');
    $routes->post('users/update/(:num)',           'Admin\UserController::update/$1');
    $routes->post('users/toggle/(:num)',           'Admin\UserController::toggle/$1');
    $routes->post('users/bulk-toggle',              'Admin\UserController::bulkToggle');
    $routes->post('users/delete/(:num)',            'Admin\UserController::delete/$1');
    $routes->post('users/bulk-delete',              'Admin\UserController::bulkDelete');
    $routes->post('users/reset-password/(:num)',   'Admin\UserController::resetPassword/$1');
    $routes->post('users/assign-role/(:num)',      'Admin\UserController::assignRole/$1');
    $routes->post('users/revoke-role/(:num)',      'Admin\UserController::revokeRole/$1');

    // Roles
    $routes->get('roles',                                  'Admin\RoleController::index');
    $routes->get('roles/create',                           'Admin\RoleController::create');
    $routes->post('roles/store',                           'Admin\RoleController::store');
    $routes->get('roles/(:num)',                           'Admin\RoleController::show/$1');
    $routes->get('roles/(:num)/edit',                      'Admin\RoleController::edit/$1');
    $routes->post('roles/update/(:num)',                   'Admin\RoleController::update/$1');
    $routes->post('roles/toggle-permission/(:num)',        'Admin\RoleController::togglePermission/$1');

    // Audit Logs
    $routes->get('audit',                'Admin\AuditController::index');
    $routes->get('audit/verify',        'Admin\AuditController::verify');
    $routes->get('audit/export',        'Admin\AuditController::export');
});

// ============================================================
// REPORTS & ANALYTICS MODULE
// ============================================================
$routes->group('reports', ['filter' => 'role:admin'], static function ($routes) {
    $routes->get('/',            'Reports\ReportController::index');
    $routes->get('clinic',       'Reports\ReportController::clinic');
    $routes->get('counselling',  'Reports\ReportController::counselling');
    $routes->get('inventory',    'Reports\ReportController::inventory');
    $routes->get('export/(:any)', 'Reports\ReportController::export/$1');
});

// ============================================================
// EMPLOYEES (HR-integrated patients)
// ============================================================
$routes->group('clinic/employees', ['filter' => 'role:admin,clinic_staff'], static function ($routes) {
    $routes->get('/',                  'Clinic\EmployeeController::index');
    $routes->get('create',             'Clinic\EmployeeController::create');
    $routes->post('store',             'Clinic\EmployeeController::store');
    $routes->get('search',             'Clinic\EmployeeController::search');
    $routes->get('(:num)',             'Clinic\EmployeeController::show/$1');
    $routes->get('edit/(:num)',        'Clinic\EmployeeController::edit/$1');
    $routes->post('update/(:num)',     'Clinic\EmployeeController::update/$1');
    $routes->post('sync-hr',           'Clinic\EmployeeController::syncFromHr');
});

// ============================================================
// RFID CHECK-IN
// ============================================================
$routes->group('clinic/checkin', ['filter' => 'role:admin,clinic_staff,counsellor'], static function ($routes) {
    $routes->post('scan', 'Clinic\CheckinController::scan');
    $routes->get('log',  'Clinic\CheckinController::log');
});

// ============================================================
// QR REFERRAL VERIFICATION
// ============================================================
// Public endpoint for QR verification
$routes->get('referral/verify/(:any)', 'Clinic\ReferralController::verifyQr/$1');
// Download QR code image
$routes->get('referral/qr/(:num)', 'Clinic\ReferralController::downloadQr/$1', ['filter' => 'role:admin,clinic_staff,counsellor']);

// ============================================================
// FACILITIES / SUSTAINABILITY (BMG) MODULE
// ============================================================
$routes->group('bmg', ['filter' => 'role:admin,facilities_staff,report_viewer'], static function ($routes) {
    $routes->get('/',        'Bmg\DashboardController::index');
    $routes->get('dashboard','Bmg\DashboardController::index');
    $routes->get('facility-operations', 'Bmg\DashboardController::facilityOperations');

    // Drums
    $routes->get('drums',             'Bmg\DrumController::index');
    $routes->get('drums/create',      'Bmg\DrumController::create',  ['filter' => 'role:admin,facilities_staff']);
    $routes->post('drums/store',      'Bmg\DrumController::store',  ['filter' => 'role:admin,facilities_staff']);
    $routes->get('drums/(:num)',      'Bmg\DrumController::show/$1');
    $routes->get('drums/edit/(:num)', 'Bmg\DrumController::edit/$1', ['filter' => 'role:admin,facilities_staff']);
    $routes->post('drums/update/(:num)', 'Bmg\DrumController::update/$1', ['filter' => 'role:admin,facilities_staff']);
    $routes->post('drums/archive/(:num)', 'Bmg\DrumController::archive/$1', ['filter' => 'role:admin']);
    // Drum management quick actions
    $routes->post('drums/delete/(:num)',          'Bmg\DrumController::delete/$1',          ['filter' => 'role:admin,facilities_staff']);
    $routes->post('drums/mark-idle/(:num)',       'Bmg\DrumController::markIdle/$1',       ['filter' => 'role:admin,facilities_staff']);
    $routes->post('drums/mark-processing/(:num)', 'Bmg\DrumController::markProcessing/$1', ['filter' => 'role:admin,facilities_staff']);
    $routes->post('drums/mark-maintenance/(:num)','Bmg\DrumController::markMaintenance/$1',['filter' => 'role:admin,facilities_staff']);
    $routes->post('drums/complete-and-idle/(:num)','Bmg\DrumController::completeAndIdle/$1',['filter' => 'role:admin,facilities_staff']);

    // Waste Categories
    $routes->get('categories',         'Bmg\WasteCategoryController::index');
    $routes->get('categories/create',  'Bmg\WasteCategoryController::create', ['filter' => 'role:admin,facilities_staff']);
    $routes->post('categories/store',  'Bmg\WasteCategoryController::store', ['filter' => 'role:admin,facilities_staff']);
    $routes->get('categories/edit/(:num)', 'Bmg\WasteCategoryController::edit/$1', ['filter' => 'role:admin,facilities_staff']);
    $routes->post('categories/update/(:num)', 'Bmg\WasteCategoryController::update/$1', ['filter' => 'role:admin,facilities_staff']);
    $routes->post('categories/delete/(:num)', 'Bmg\WasteCategoryController::delete/$1', ['filter' => 'role:admin']);

    // Batches
    $routes->get('batches',                       'Bmg\BatchController::index');
    $routes->get('batches/(:num)',                'Bmg\BatchController::show/$1');
    $routes->get('batches/startOnDrum/(:num)',    'Bmg\BatchController::startOnDrum/$1', ['filter' => 'role:admin,facilities_staff']);
    $routes->post('batches/create',               'Bmg\BatchController::create', ['filter' => 'role:admin,facilities_staff']);
    $routes->post('batches/(:num)/mark-completed','Bmg\BatchController::markCompleted/$1', ['filter' => 'role:admin,facilities_staff']);
    $routes->post('batches/(:num)/cancel',        'Bmg\BatchController::cancel/$1', ['filter' => 'role:admin,facilities_staff']);

    // Inputs (under a batch)
    $routes->get('batches/(:num)/inputs/create',  'Bmg\InputController::create/$1', ['filter' => 'role:admin,facilities_staff']);
    $routes->post('batches/(:num)/inputs/store',  'Bmg\InputController::store/$1', ['filter' => 'role:admin,facilities_staff']);

    // Process logs (under a batch)
    $routes->get('batches/(:num)/process-logs/create', 'Bmg\ProcessController::create/$1', ['filter' => 'role:admin,facilities_staff']);
    $routes->post('batches/(:num)/process-logs/store', 'Bmg\ProcessController::store/$1', ['filter' => 'role:admin,facilities_staff']);

    // Outputs (under a batch)
    $routes->get('batches/(:num)/output/create', 'Bmg\OutputController::create/$1', ['filter' => 'role:admin,facilities_staff']);
    $routes->post('batches/(:num)/output/store', 'Bmg\OutputController::store/$1', ['filter' => 'role:admin,facilities_staff']);

    // Reports
    $routes->get('reports',                                'Bmg\BmgReportController::index');
    $routes->get('reports/export-csv/(:any)',               'Bmg\BmgReportController::exportCsv/$1');
});

// ============================================================
// PROCUREMENT / REORDER REQUESTS
// ============================================================
$routes->group('inventory/reorders', ['filter' => 'role:admin,clinic_staff,report_viewer'], static function ($routes) {
    $routes->get('/',             'Inventory\ReorderController::index');
    $routes->get('auto-check',    'Inventory\ReorderController::autoCheck', ['filter' => 'role:admin,clinic_staff']);
    $routes->get('create',        'Inventory\ReorderController::create', ['filter' => 'role:admin,clinic_staff']);
    $routes->post('store',        'Inventory\ReorderController::store', ['filter' => 'role:admin,clinic_staff']);
    $routes->get('(:num)',        'Inventory\ReorderController::show/$1');
    $routes->post('(:num)/approve', 'Inventory\ReorderController::approve/$1', ['filter' => 'role:admin,clinic_staff']);
    $routes->post('(:num)/order',   'Inventory\ReorderController::order/$1', ['filter' => 'role:admin,clinic_staff']);
    $routes->post('(:num)/receive', 'Inventory\ReorderController::receive/$1', ['filter' => 'role:admin,clinic_staff']);
    $routes->post('(:num)/cancel',  'Inventory\ReorderController::cancel/$1', ['filter' => 'role:admin,clinic_staff']);
    $routes->get('trigger/(:num)',  'Inventory\ReorderController::trigger/$1', ['filter' => 'role:admin,clinic_staff']);
});
