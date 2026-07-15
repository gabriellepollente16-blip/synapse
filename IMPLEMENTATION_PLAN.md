# SYNAPSE Capstone — Implementation Plan

> **Project**: SYNAPSE — Unified Web-Based Platform for Campus Health, Counselling, and Biodegradable Waste Management
> **Institution**: Foundation University
> **Document Version**: 1.0
> **Last Updated**: 2026-07-11
> **Status**: Ready for implementation

---

## Table of Contents

1. [Overview & Goals](#overview--goals)
2. [Architecture Recap](#architecture-recap)
3. [Implementation Phases](#implementation-phases)
   - [Phase 1 — Database Schema Updates (Migrations)](#phase-1--database-schema-updates-migrations)
   - [Phase 2 — Models Layer](#phase-2--models-layer)
   - [Phase 3 — Libraries (Business Logic)](#phase-3--libraries-business-logic)
   - [Phase 4 — Authentication & RBAC Updates](#phase-4--authentication--rbac-updates)
   - [Phase 5 — Controllers: Employee / Checkin / QR Referral](#phase-5--controllers-employee--checkin--qr-referral)
   - [Phase 6 — Controllers: BMG Module](#phase-6--controllers-bmg-module)
   - [Phase 7 — Controllers: Procurement / Reorder Requests](#phase-7--controllers-procurement--reorder-requests)
   - [Phase 8 — Routes & Filter Configuration](#phase-8--routes--filter-configuration)
   - [Phase 9 — Views & UI Components](#phase-9--views--ui-components)
   - [Phase 10 — BMG Dashboard Tab & Facility-Operations Dashboard](#phase-10--bmg-dashboard-tab--facility-operations-dashboard)
   - [Phase 11 — QR / RFID Scanner Integration (Frontend)](#phase-11--qr--rfid-scanner-integration-frontend)
   - [Phase 12 — Reports Module](#phase-12--reports-module)
   - [Phase 13 — HR Integration (Employee Sync)](#phase-13--hr-integration-employee-sync)
   - [Phase 14 — Audit Logging Integration](#phase-14--audit-logging-integration)
   - [Phase 15 — Notifications Expansion](#phase-15--notifications-expansion)
   - [Phase 16 — Testing (Unit + Integration)](#phase-16--testing-unit--integration)
   - [Phase 17 — Seed Data & Environment Setup](#phase-17--seed-data--environment-setup)
   - [Phase 18 — UAT Prep & ISO/IEC 25010 Evaluation](#phase-18--uat-prep--isoiec-25010-evaluation)
4. [Cross-Cutting Concerns](#cross-cutting-concerns)
5. [Risk & Dependency Matrix](#risk--dependency-matrix)
6. [Suggested Milestones & Timeline](#suggested-milestones--timeline)
7. [Definition of Done (Per Phase)](#definition-of-done-per-phase)

---

## Overview & Goals

This plan details the changes required to evolve the existing **SYNAPSE** codebase (a clinic + counselling platform) into the **full capstone scope** described in the post-defense document. The work covers **three integrated modules**:

1. **Clinic Core** — student + employee records, RFID check-in, QR referrals, medicine inventory + procurement
2. **Counselling Core** — appointments, confidential intake notes, QR referrals (no clinical screening)
3. **Facilities/Sustainability Core (BMG)** — biodegradable waste input/process/output tracking, drum management, yield analytics

All three modules share **authentication, RBAC, and audit logging** but maintain **independent data domains**.

### Top-Level Goals

- ✅ Centralize all institutional data (students, employees, BMG operations) under one platform
- ✅ Eliminate paper-based records in clinic, counselling, and composting
- ✅ Implement hardware integration (RFID check-in, QR referrals)
- ✅ Automate medicine reorder through procurement workflow
- ✅ Provide real-time BMG analytics for facilities management
- ✅ Maintain full audit trail with hash-chaining across all 3 modules
- ✅ Comply with **RA 10173 (Data Privacy Act)**, **RA 9003 (Ecological Solid Waste Act)**, and **ISO/IEC 25010**

---

## Architecture Recap

### Existing Components (No Changes)

- CodeIgniter 4.7 + PHP 8.2
- MySQL 8.0+ with utf8mb4
- Authentication (AuthController, AuthFilter)
- Role-Based Access Control (RoleFilter, 4 roles)
- Existing clinic & counselling models / controllers
- Hash-chained audit log
- AI libraries (TriageAssistant, InventoryForecaster)
- Chart.js + vanilla JS frontend

### New Components (To Be Added)

- 7 new models (Employee, CheckinLog, ReorderRequest, BmgDrum, BmgBatch, BmgInput, BmgOutput, WasteCategory, IntakeNote)
- 3 new libraries (BmgYieldCalculator, BmgDurationCalculator, QrCodeGenerator)
- 7 new role definitions
- 5 new controller directories (Clinic/Employee, Clinic/Checkin, Inventory/Reorder, Bmg/*, Hri/*)
- 1 new dashboard tab (Facility Operations / BMG)
- QR code generation + scanning UI
- RFID check-in UI
- Procurement workflow UI
- HR employee sync endpoint

---

## Implementation Phases

---

### Phase 1 — Database Schema Updates (Migrations)

**Goal**: Translate the [SYSTEM_ARCHITECTURE.md](SYSTEM_ARCHITECTURE.md) database blueprint into idempotent CodeIgniter migration files.

**Location**: `app/Database/Migrations/`

#### 1.1 New Migrations to Create

| # | Migration File | Tables Affected | Purpose |
|---|---------------|-----------------|---------|
| 1 | `2026-07-15-0001_AddEmployeesTable.php` | `employees` | HR-integrated employee identity |
| 2 | `2026-07-15-0002_AddUserTypeToUsers.php` | `users` | Add `user_type` ENUM column |
| 3 | `2026-07-15-0003_AddCheckinLogsTable.php` | `checkin_logs` | RFID check-in audit trail |
| 4 | `2026-07-15-0004_UpdateConsultationsForEmployees.php` | `consultations` | Add `patient_type`, `employee_id` |
| 5 | `2026-07-15-0005_UpdateCounsellingForEmployees.php` | `counselling_appointments` | Polymorphic patient ref |
| 6 | `2026-07-15-0006_DropScreeningTables.php` | `assessment_templates`, `assessment_responses` | Remove per spec |
| 7 | `2026-07-15-0007_DropCrisisAlertsTable.php` | `crisis_alerts` | Remove per spec |
| 8 | `2026-07-15-0008_AddIntakeNotesTable.php` | `intake_notes` | Free-text session notes |
| 9 | `2026-07-15-0009_UpdateReferralsForQR.php` | `referrals` | Add QR token, path, verification fields |
| 10 | `2026-07-15-0010_AddReorderRequestsTable.php` | `reorder_requests` | Procurement workflow |
| 11 | `2026-07-15-0011_AddBmgDrumsTable.php` | `bmg_drums` | BMG unit master |
| 12 | `2026-07-15-0012_AddWasteCategoriesTable.php` | `waste_categories` | Waste type taxonomy |
| 13 | `2026-07-15-0013_AddBmgBatchesTable.php` | `bmg_batches` | Batch lifecycle (input → output) |
| 14 | `2026-07-15-0014_AddBmgInputsTable.php` | `bmg_inputs` | Input audit trail |
| 15 | `2026-07-15-0015_AddBmgProcessLogsTable.php` | `bmg_process_logs` | Process observations |
| 16 | `2026-07-15-0016_AddBmgOutputsTable.php` | `bmg_outputs` | Harvest records |
| 17 | `2026-07-15-0017_AddBmgCheckConstraint.php` | DB constraint | `CHECK (output_weight_kg <= input_weight_kg)` |
| 18 | `2026-07-15-0018_ExpandRoles.php` | `roles` | Add `facilities_staff`, `report_viewer`, `employee` |
| 19 | `2026-07-15-0019_AddBmgPermissions.php` | `permissions` | Granular BMG permissions |
| 20 | `2026-07-15-0020_UpdateNotificationTypes.php` | `notifications` | Add new notification types |

#### 1.2 SQL Constraints to Apply

```sql
-- BMG scientific validity
ALTER TABLE bmg_batches
  ADD CONSTRAINT chk_output_lte_input
  CHECK (output_weight_kg IS NULL OR output_weight_kg <= input_weight_kg);

ALTER TABLE bmg_outputs
  ADD CONSTRAINT chk_output_lte_batch_input
  CHECK (output_weight_kg <= (
    SELECT input_weight_kg FROM bmg_batches WHERE id = batch_id
  ));
```

#### 1.3 Indexes to Add

- `employees`: `idx_employees_employee_number`, `idx_employees_rfid_tag`
- `checkin_logs`: `idx_checkin_patient`, `idx_checkin_at`
- `bmg_drums`: `idx_drums_status`, `idx_drums_archived`
- `bmg_batches`: `idx_batches_drum`, `idx_batches_status`, `idx_batches_start_date`
- `referrals`: `idx_referrals_qr_token` (UNIQUE)
- `reorder_requests`: `idx_reorder_status`, `idx_reorder_urgency`

**Deliverables**:
- 20 migration files
- Updated `Database/synapse_ag.sql` baseline schema
- Migration runner verification: `php spark migrate`

---

### Phase 2 — Models Layer

**Goal**: Create model classes that match the new schema, replacing the deleted screening/crisis models.

**Location**: `app/Models/`

#### 2.1 New Models

| Model | Extends | Notable Methods |
|-------|---------|----------------|
| `EmployeeModel` | `CodeIgniter\Model` | `findByRfid($tag)`, `findByQrCode($token)`, `syncFromHr($data)` |
| `IntakeNoteModel` | `CodeIgniter\Model` | `getForCounsellor($userId)`, `getForPatient($patientType, $patientId)` |
| `CheckinLogModel` | `CodeIgniter\Model` | `logCheckin($data)`, `getRecentForPatient($id, $type)` |
| `ReferralModel` (updated) | `CodeIgniter\Model` | `generateQrToken()`, `verifyByQr($token)`, `markVerified()` |
| `ReorderRequestModel` | `CodeIgniter\Model` | `createAuto($medicineId)`, `advanceStatus($id, $newStatus)` |
| `BmgDrumModel` | `CodeIgniter\Model` | `getActive()`, `getArchived()`, `getByStatus($status)` |
| `WasteCategoryModel` | `CodeIgniter\Model` | `getActive()` |
| `BmgBatchModel` | `CodeIgniter\Model` | `startBatch()`, `markProcessing()`, `markCompleted()`, `recordOutput()` |
| `BmgInputModel` | `CodeIgniter\Model` | `getForBatch($batchId)` |
| `BmgProcessLogModel` | `CodeIgniter\Model` | `addLog($data)` |
| `BmgOutputModel` | `CodeIgniter\Model` | `recordHarvest($data)` (with validation) |

#### 2.2 Models to Remove

- `AssessmentTemplateModel`
- `AssessmentResponseModel`
- `CrisisAlertModel`
- `AiRiskScoreModel`

#### 2.3 Models to Update

- `UserModel`: add `user_type` to allowed fields
- `ConsultationModel`: handle polymorphic patient reference
- `CounsellingAppointmentModel`: handle polymorphic patient reference
- `RoleModel`: register new 7-role taxonomy

**Deliverables**:
- 11 new model files
- 4 model deletions
- 4 model updates
- Validation rules in each model (`$validationRules`)

---

### Phase 3 — Libraries (Business Logic)

**Goal**: Encapsulate complex business rules and computed values in reusable libraries.

**Location**: `app/Libraries/`

#### 3.1 New Libraries

| Library | Responsibility | Key Methods |
|---------|---------------|-------------|
| `BmgYieldCalculator` | Compute fertilizer yield | `computeYield($inputKg, $outputKg)`, `computeMassReduction($yieldPct)` |
| `BmgDurationCalculator` | Compute decomposition time | `computeDurationDays($startDate, $endDate)` |
| `QrCodeGenerator` | QR generation + verification | `generateForReferral($referralId)`, `verifyToken($token)` |
| `ProcurementRouter` | Auto-routing reorder requests | `routeToProcurement($medicineId)`, `getPendingForProcurement()` |

#### 3.2 Libraries to Update

- `TriageAssistant`: ensure polymorphic patient support
- `InventoryForecaster`: add reorder-level threshold trigger
- `ReportSummarizer`: add BMG report module
- `AppExceptionHandler`: add new exception types for BMG, procurement

#### 3.3 Libraries to Remove

- `RiskScorer` (no longer needed; no screening)

**Deliverables**:
- 4 new library files
- 4 updated libraries
- 1 removed library
- Unit tests for each calculator (Phase 16)

---

### Phase 4 — Authentication & RBAC Updates

**Goal**: Expand RBAC to support 7 roles and route-level enforcement for the BMG module.

**Location**: `app/Controllers/AuthController.php`, `app/Filters/`, `app/Database/Seeds/`

#### 4.1 Role Expansion

| Role | Description | Default Permissions |
|------|-------------|---------------------|
| `admin` | (existing) | All |
| `clinic_staff` | (existing) | Clinic, inventory, check-in |
| `counsellor` | (existing) | Counselling, referrals (no crisis) |
| `facilities_staff` | **NEW** | BMG module only |
| `report_viewer` | **NEW** | Reports (read-only, all modules) |
| `employee` | **NEW** | View own records, book appointments |
| `student` | (existing) | View own records, book appointments |

#### 4.2 Permission Catalog (per role)

Create seed data for `permissions` table:

- Clinic: `view_clinic_dashboard`, `create_consultation`, `record_vitals`, `manage_treatments`, `create_referral`, `view_audit_clinic`
- Counselling: `view_counselling_dashboard`, `book_appointment`, `record_intake`, `view_own_intake`
- BMG: `view_bmg_dashboard`, `manage_drums`, `log_bmg_input`, `log_bmg_process`, `record_bmg_output`, `view_bmg_reports`
- Inventory: `manage_inventory`, `approve_reorder`, `view_reorder_requests`
- Admin: `manage_users`, `manage_roles`, `view_audit_log`
- Reports: `view_reports`, `export_reports`

#### 4.3 Filter Updates

- `AuthFilter`: ensure new roles get redirected to role-specific dashboards
- `RoleFilter`: add support for comma-separated role list (already supported)

**Deliverables**:
- Updated `AuthController::attemptLogin` to set dashboard URL per role
- Database seeder for 7 roles + 20+ permissions
- Dashboard routing map: `admin → /dashboard/admin`, `facilities_staff → /dashboard/bmg`, etc.

---

### Phase 5 — Controllers: Employee / Checkin / QR Referral

**Goal**: Add the new Clinic sub-controllers for HR-integrated employees, RFID check-in, and QR-based referrals.

**Location**: `app/Controllers/Clinic/`

#### 5.1 EmployeeController

```
Routes:
GET    /clinic/employees              index
GET    /clinic/employees/create       create
POST   /clinic/employees/store        store
GET    /clinic/employees/search       search (AJAX)
GET    /clinic/employees/(:num)       show
GET    /clinic/employees/edit/(:num)  edit
POST   /clinic/employees/update/(:num) update
POST   /clinic/employees/sync-hr      syncFromHr  ← HR Integration
```

Methods:
- `index()` — list employees with filters
- `search()` — AJAX autocomplete
- `syncFromHr()` — pull data from HR system
- `findByRfid()` — internal helper

#### 5.2 CheckinController

```
Routes:
POST   /clinic/checkin/scan         scan (RFID)
GET    /clinic/checkin/log          checkin log
GET    /clinic/checkin/recent/(:type)/(:id)  recent checkins for patient
```

Methods:
- `scan()` — accepts RFID tag, looks up student OR employee, logs check-in
- `getRecentForPatient()` — query helper

#### 5.3 Updated ReferralController (QR Generation)

```
New methods:
generateQr($id)        — generate QR code, save PNG to writable/bmg_qr_codes/
verifyQr($token)       — public endpoint to verify a scanned QR
downloadQr($id)        — return PNG file
```

Libraries used: `QrCodeGenerator` (Phase 3)

**Deliverables**:
- 2 new controllers (Employee, Checkin)
- 1 updated controller (Referral)
- 8 new route definitions

---

### Phase 6 — Controllers: BMG Module

**Goal**: Implement the full BMG (Biodegradable Waste Management) module with drum management, batch lifecycle, input/process/output tracking, waste categorization, and reports.

**Location**: `app/Controllers/Bmg/` (NEW directory)

#### 6.1 Controller Inventory

| Controller | Routes | Methods |
|-----------|--------|---------|
| `DrumController` | `/bmg/drums` (index, create, store, show, edit, update, archive) | `getActive()`, `getArchived()` |
| `WasteCategoryController` | `/bmg/categories` (CRUD) | `getActive()` |
| `BatchController` | `/bmg/batches` (index, show) | `start()`, `markProcessing()`, `markCompleted()`, `cancel()` |
| `InputController` | `/bmg/batches/(:num)/inputs` (create, store) | `validateAgainstDrumCapacity()` |
| `ProcessController` | `/bmg/batches/(:num)/process-logs` (create, store) | `addObservation()` |
| `OutputController` | `/bmg/batches/(:num)/output` (create, store) | `recordHarvest()` (validates output ≤ input) |
| `BmgReportController` | `/bmg/reports` (index) | `getYieldByDrum()`, `getDurationByWasteType()`, `getMonthlyTotals()`, `exportCsv()`, `exportPdf()` |

#### 6.2 Key Workflows Implemented

**Input → Process → Output lifecycle:**

```
1. POST /bmg/drums/:drumId/batches
   → create batch record (status='input', input_weight=0)
   → redirect to input form

2. POST /bmg/batches/:id/inputs
   → validate weight <= drum capacity
   → update batch.input_weight_kg (sum of all inputs)
   → set batch.start_date = NOW
   → set drum.current_status = 'processing'
   → set batch.status = 'processing'
   → redirect to batch view

3. POST /bmg/batches/:id/process-logs
   → add observation log
   → status unchanged

4. POST /bmg/batches/:id/mark-completed
   → set batch.completion_date = NOW
   → compute duration_days
   → set drum.current_status = 'idle'

5. POST /bmg/batches/:id/output
   → validate output_weight_kg <= batch.input_weight_kg (app + DB)
   → set batch.output_weight_kg
   → compute yield_percentage, mass_reduction_pct
   → set batch.status = 'completed'
   → trigger notification to facilities_staff
   → redirect to reports
```

#### 6.3 Validation Rules

- Input weight > 0, <= drum.capacity_kg
- Output weight > 0, <= input weight (DB CHECK + app validation)
- Completion date >= start date
- Status transitions follow state machine

**Deliverables**:
- 7 new controllers in `app/Controllers/Bmg/`
- 1 dedicated BMG dashboard controller: `DashboardController::bmg()`
- 15+ new routes (all filtered to `role:admin,facilities_staff` for writes; `role:admin,facilities_staff,report_viewer` for reads)

---

### Phase 7 — Controllers: Procurement / Reorder Requests

**Goal**: Add procurement workflow that auto-triggers when medicine stock falls below reorder level.

**Location**: `app/Controllers/Inventory/`

#### 7.1 ReorderController (NEW)

```
Routes:
GET    /inventory/reorders              index (list all)
GET    /inventory/reorders/create       create
POST   /inventory/reorders/store        store
GET    /inventory/reorders/(:num)       show
POST   /inventory/reorders/(:num)/approve    approve
POST   /inventory/reorders/(:num)/order      mark as ordered
POST   /inventory/reorders/(:num)/receive    mark as received
POST   /inventory/reorders/(:num)/cancel     cancel
```

Methods:
- `checkStockAndCreate()` — auto-triggered when stock < reorder_level
- `advanceStatus()` — state machine: pending → approved → ordered → received
- `getPendingForProcurement()` — query helper

#### 7.2 Trigger Logic

**Where to add the auto-trigger:**

- `Inventory\MedicineController::update()` — when stock changes
- `Inventory\TransactionController::store()` — after a stock-out transaction
- `Clinic\TreatmentController::store()` — when medicine is prescribed

**Pseudo-code:**
```php
$medicineModel = new MedicineModel();
$medicine = $medicineModel->find($medicineId);
$totalStock = $medicineBatchModel->getTotalStock($medicineId);

if ($totalStock <= $medicine->reorder_level) {
    $reorderModel = new ReorderRequestModel();
    if (!$reorderModel->hasOpenRequest($medicineId)) {
        $reorderModel->createAuto($medicineId, $totalStock);
        // notify procurement personnel
    }
}
```

#### 7.3 Notification Routing

- `request.created` → notify procurement staff (new role: `procurement_staff` or use admin)
- `request.approved` → notify requester
- `request.received` → notify requester + admin

**Deliverables**:
- 1 new controller (ReorderController)
- 1 updated service/hook for auto-trigger
- 9 new route definitions

---

### Phase 8 — Routes & Filter Configuration

**Goal**: Wire all new controllers into the routing system and configure role-based access filters.

**Location**: `app/Config/Routes.php`, `app/Config/Filters.php`

#### 8.1 New Route Groups

```php
// ============================================================
// FACILITIES / SUSTAINABILITY (BMG) MODULE
// ============================================================
$routes->group('bmg', ['filter' => 'role:admin,facilities_staff,report_viewer'], static function ($routes) {
    $routes->get('/', 'Bmg\DashboardController::index');
    $routes->get('dashboard', 'Bmg\DashboardController::index');
    
    // Drums
    $routes->get('drums', 'Bmg\DrumController::index');
    $routes->get('drums/create', 'Bmg\DrumController::create', ['filter' => 'role:admin,facilities_staff']);
    $routes->post('drums/store', 'Bmg\DrumController::store', ['filter' => 'role:admin,facilities_staff']);
    // ... edit, update, archive
    
    // Waste Categories
    $routes->resource('categories', ['controller' => 'Bmg\WasteCategoryController']);
    
    // Batches
    $routes->get('batches', 'Bmg\BatchController::index');
    $routes->get('batches/(:num)', 'Bmg\BatchController::show/$1');
    
    // Inputs
    $routes->get('batches/(:num)/inputs/create', 'Bmg\InputController::create/$1', ['filter' => 'role:admin,facilities_staff']);
    $routes->post('batches/(:num)/inputs/store', 'Bmg\InputController::store/$1', ['filter' => 'role:admin,facilities_staff']);
    
    // Process logs
    $routes->get('batches/(:num)/process-logs/create', 'Bmg\ProcessController::create/$1', ['filter' => 'role:admin,facilities_staff']);
    $routes->post('batches/(:num)/process-logs/store', 'Bmg\ProcessController::store/$1', ['filter' => 'role:admin,facilities_staff']);
    
    // Mark completed
    $routes->post('batches/(:num)/mark-completed', 'Bmg\BatchController::markCompleted/$1', ['filter' => 'role:admin,facilities_staff']);
    
    // Outputs
    $routes->get('batches/(:num)/output/create', 'Bmg\OutputController::create/$1', ['filter' => 'role:admin,facilities_staff']);
    $routes->post('batches/(:num)/output/store', 'Bmg\OutputController::store/$1', ['filter' => 'role:admin,facilities_staff']);
    
    // Reports
    $routes->get('reports', 'Bmg\BmgReportController::index');
    $routes->get('reports/export/csv', 'Bmg\BmgReportController::exportCsv');
    $routes->get('reports/export/pdf', 'Bmg\BmgReportController::exportPdf');
});

// ============================================================
// EMPLOYEE (HR Integration)
// ============================================================
$routes->group('clinic/employees', ['filter' => 'role:admin,clinic_staff'], static function ($routes) {
    $routes->get('/', 'Clinic\EmployeeController::index');
    $routes->get('create', 'Clinic\EmployeeController::create');
    $routes->post('store', 'Clinic\EmployeeController::store');
    // ... search, show, edit, update
    $routes->post('sync-hr', 'Clinic\EmployeeController::syncFromHr');
});

// ============================================================
// RFID CHECK-IN
// ============================================================
$routes->group('clinic/checkin', ['filter' => 'role:admin,clinic_staff,counsellor'], static function ($routes) {
    $routes->post('scan', 'Clinic\CheckinController::scan');
    $routes->get('log', 'Clinic\CheckinController::log');
});

// ============================================================
// QR REFERRAL VERIFICATION (PUBLIC endpoint)
// ============================================================
$routes->get('referral/verify/(:any)', 'Clinic\ReferralController::verifyQr/$1');
$routes->get('referral/qr/(:num)', 'Clinic\ReferralController::downloadQr/$1', ['filter' => 'role:admin,clinic_staff,counsellor']);

// ============================================================
// REORDER / PROCUREMENT
// ============================================================
$routes->group('inventory/reorders', ['filter' => 'role:admin,clinic_staff,report_viewer'], static function ($routes) {
    $routes->get('/', 'Inventory\ReorderController::index');
    $routes->get('(:num)', 'Inventory\ReorderController::show/$1');
});
$routes->group('inventory/reorders', ['filter' => 'role:admin,clinic_staff'], static function ($routes) {
    $routes->get('create', 'Inventory\ReorderController::create');
    $routes->post('store', 'Inventory\ReorderController::store');
    $routes->post('(:num)/approve', 'Inventory\ReorderController::approve/$1');
    $routes->post('(:num)/order', 'Inventory\ReorderController::order/$1');
    $routes->post('(:num)/receive', 'Inventory\ReorderController::receive/$1');
    $routes->post('(:num)/cancel', 'Inventory\ReorderController::cancel/$1');
});

// ============================================================
// FACILITY-OPERATIONS DASHBOARD (admin + facilities_staff)
// ============================================================
$routes->get('dashboard/facility-operations', 'Bmg\DashboardController::index', ['filter' => 'role:admin,facilities_staff,report_viewer']);

// ============================================================
// HR SYNC (admin only)
// ============================================================
$routes->group('hri', ['filter' => 'role:admin'], static function ($routes) {
    $routes->get('/', 'Hri\EmployeeSyncController::index');
    $routes->post('sync', 'Hri\EmployeeSyncController::sync');
    $routes->get('logs', 'Hri\EmployeeSyncController::logs');
});
```

#### 8.2 Filters Configuration

In `app/Config/Filters.php`, ensure `auth` filter is applied globally and `role` filter is available.

**Deliverables**:
- Updated `Routes.php` with 4 new route groups (~50 new routes)
- Updated `Filters.php` aliases
- Route testing via `php spark routes`

---

### Phase 9 — Views & UI Components

**Goal**: Build the front-end templates for all new functionality.

**Location**: `app/Views/`

#### 9.1 New View Directories

```
app/Views/
├── bmg/                                  # NEW
│   ├── dashboard.php                     # Multi-drum overview
│   ├── drums/
│   │   ├── index.php
│   │   ├── create.php
│   │   ├── show.php
│   │   └── edit.php
│   ├── categories/
│   │   ├── index.php
│   │   ├── create.php
│   │   └── edit.php
│   ├── batches/
│   │   ├── index.php
│   │   ├── show.php
│   │   ├── input_form.php
│   │   ├── process_log_form.php
│   │   ├── output_form.php
│   │   └── complete_form.php
│   └── reports/
│       ├── index.php
│       ├── yield_by_drum.php
│       ├── duration_by_waste.php
│       └── monthly_totals.php
├── clinic/
│   ├── employees/                        # NEW
│   │   ├── index.php
│   │   ├── create.php
│   │   ├── show.php
│   │   └── edit.php
│   ├── checkin/                          # NEW
│   │   ├── scan.php
│   │   └── log.php
│   └── referrals/
│       ├── generate_qr_modal.php         # NEW
│       └── verify_qr.php                 # NEW
├── inventory/
│   └── reorders/                         # NEW
│       ├── index.php
│       ├── create.php
│       └── show.php
├── dashboard/
│   ├── bmg.php                           # NEW - BMG dashboard
│   ├── facility_operations.php           # NEW - Facility ops dashboard
│   └── employee.php                      # NEW
├── reports/
│   ├── bmg.php                           # NEW
│   ├── clinic.php
│   └── counselling.php
└── components/                           # NEW shared components
    ├── drum_card.php
    ├── qr_scanner.php
    ├── rfid_input.php
    └── stat_tile.php
```

#### 9.2 Component Library

- **`drum_card.php`**: Reusable card showing drum status, days processing, last batch info
- **`qr_scanner.php`**: Wrapper around html5-qrcode JS library
- **`rfid_input.php`**: Auto-focus input for RFID reader devices
- **`stat_tile.php`**: KPI tile for dashboards (matches existing design system)

#### 9.3 Layout Updates

- Update `layouts/sidebar.php` to add **BMG** menu items (conditional on role)
- Add a "Facility Operations" tab on the main dashboard for `admin` and `facilities_staff` users
- Update `layouts/header.php` to show BMG notifications for `facilities_staff`

**Deliverables**:
- 25+ new view files
- 4 shared component partials
- 1 updated sidebar with new menu items
- 1 updated header with role-based notification display

---

### Phase 10 — BMG Dashboard Tab & Facility-Operations Dashboard

**Goal**: Add a dedicated **Facility Operations** section in the system, accessible to admins and facilities staff, surfacing the BMG key metrics.

**Location**: `app/Controllers/DashboardController.php`, `app/Views/dashboard/`

#### 10.1 Dashboard Routing Map

Update `DashboardController::index()` to route by role:

```php
public function index()
{
    $role = session()->get('primary_role');
    return match($role) {
        'admin'             => $this->admin(),
        'clinic_staff'      => $this->clinic(),
        'counsellor'        => $this->counsellor(),
        'facilities_staff'  => $this->bmg(),          // NEW
        'report_viewer'     => $this->reports(),      // NEW
        'employee'          => $this->employee(),     // NEW
        'student'           => $this->student(),
        default             => redirect()->to('/login'),
    };
}
```

#### 10.2 New Dashboard Methods

```php
public function bmg()
{
    $data = [
        'drumCount'          => $this->drumModel->countAll(),
        'activeBatches'      => $this->batchModel->where('status', 'processing')->countAllResults(),
        'idleDrums'          => $this->drumModel->where('current_status', 'idle')->countAllResults(),
        'avgYieldLast30Days' => $this->bmgReportModel->getAvgYield(30),
        'totalFertilizerKg'  => $this->bmgReportModel->getTotalOutput(30),
        'recentBatches'      => $this->batchModel->getRecent(5),
        'drumCards'          => $this->drumModel->getActive(),  // for card grid
    ];
    return view('dashboard/bmg', $data);
}

public function facilityOperations()
{
    // Combined admin + facilities view with cross-module ops metrics
    // - All drums + status
    // - Active batches with progress bars
    // - Yield % by waste type chart
    // - Average decomposition duration chart
    // - Total fertilizer this month
    // - Alerts (drums idle > 30 days, batches processing > 60 days, etc.)
}
```

#### 10.3 New View: `app/Views/dashboard/facility_operations.php`

**UI structure:**

```
┌──────────────────────────────────────────────────────────────────────┐
│ Facility Operations Dashboard                                        │
├──────────────────────────────────────────────────────────────────────┤
│ [Total Drums: 6]  [Active: 4]  [Idle: 2]  [Archived: 0]            │
│ [Total Input: 250kg/mo]  [Output: 80kg/mo]  [Avg Yield: 32%]       │
├──────────────────────────────────────────────────────────────────────┤
│ ┌────────────┐ ┌────────────┐ ┌────────────┐ ┌────────────┐       │
│ │  Drum #1   │ │  Drum #2   │ │  Drum #3   │ │  Drum #4   │       │
│ │ 🟢 Process │ │ ⚪ Idle    │ │ 🟢 Process │ │ ⚪ Idle    │       │
│ │  Day 12    │ │  -         │ │  Day 5     │ │  -         │       │
│ │  [View]    │ │  [Add]     │ │  [View]    │ │  [Add]     │       │
│ └────────────┘ └────────────┘ └────────────┘ └────────────┘       │
├──────────────────────────────────────────────────────────────────────┤
│ ┌──────────────────────┐  ┌──────────────────────────────────────┐ │
│ │ Yield % by Drum      │  │ Avg. Duration by Waste Type          │ │
│ │ [Bar Chart]          │  │ [Line Chart]                          │ │
│ └──────────────────────┘  └──────────────────────────────────────┘ │
├──────────────────────────────────────────────────────────────────────┤
│ Recent Activity                                                     │
│ • Drum #1: Input recorded 50kg Food Waste (2h ago)                  │
│ • Drum #3: Batch completed, Yield 34% (yesterday)                   │
│ • Drum #2: Status idle, no activity for 12 days                    │
├──────────────────────────────────────────────────────────────────────┤
│ [View Full Reports] [Export PDF] [Export CSV]                      │
└──────────────────────────────────────────────────────────────────────┘
```

#### 10.4 Sidebar Menu Update

Add a "Facility Operations" menu item visible to `admin`, `facilities_staff`, and `report_viewer`:

```php
<!-- In sidebar.php -->
<?php if (in_array('facilities_staff', $userRoles) || in_array('admin', $userRoles)): ?>
    <li class="menu-item">
        <a href="<?= base_url('dashboard/facility-operations') ?>">
            <i class="fa-solid fa-recycle"></i> Facility Operations
        </a>
        <ul class="submenu">
            <li><a href="<?= base_url('bmg/drums') ?>">Drums</a></li>
            <li><a href="<?= base_url('bmg/batches') ?>">Batches</a></li>
            <li><a href="<?= base_url('bmg/categories') ?>">Waste Categories</a></li>
            <li><a href="<?= base_url('bmg/reports') ?>">Reports</a></li>
        </ul>
    </li>
<?php endif; ?>
```

**Deliverables**:
- 1 new dashboard method: `DashboardController::facilityOperations()`
- 1 new view: `dashboard/facility_operations.php`
- 1 updated view: `dashboard/bmg.php`
- 1 updated view: `layouts/sidebar.php` (with role-based menu)
- KPI tiles: drum count, active/idle/archived, total input/output, avg yield

---

### Phase 11 — QR / RFID Scanner Integration (Frontend)

**Goal**: Implement the browser-side QR code scanning and RFID input handling.

**Location**: `app/Views/components/`, `public/assets/js/`

#### 11.1 QR Scanner Component

**Add to `composer.json`:**
```json
"chillerlan/php-qrcode": "^4.0"
```

**Frontend library (CDN):**
```html
<script src="https://unpkg.com/html5-qrcode" defer></script>
```

**Component: `app/Views/components/qr_scanner.php`**
```php
<div id="qr-scanner-container" data-mode="<?= $mode ?? 'referral' ?>">
  <div id="qr-reader" style="width: 300px;"></div>
  <div id="qr-result"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const html5QrCode = new Html5Qrcode("qr-reader");
  html5QrCode.start(
    { facingMode: "environment" },
    { fps: 10, qrbox: 250 },
    (decodedText) => {
      // POST to /referral/verify/{token} or /clinic/checkin/scan
      handleScanResult(decodedText);
    }
  );
});
</script>
```

#### 11.2 RFID Input Component

**Component: `app/Views/components/rfid_input.php`**
```php
<form id="rfid-form" action="<?= base_url('clinic/checkin/scan') ?>" method="post">
  <?= csrf_field() ?>
  <input type="text" 
         name="rfid_tag" 
         id="rfid-input" 
         class="rfid-auto-focus" 
         autocomplete="off" 
         placeholder="Scan RFID..." 
         autofocus>
  <input type="hidden" name="module" value="<?= $module ?? 'clinic' ?>">
</form>

<script>
document.getElementById('rfid-input').addEventListener('change', (e) => {
  e.target.form.submit();
});
</script>
```

#### 11.3 QR Code Generation (Server-side)

In `ReferralController::generateQr($id)`:

```php
public function generateQr($id)
{
    $referral = $this->referralModel->find($id);
    if (!$referral) return $this->failNotFound();
    
    $token = bin2hex(random_bytes(16));
    $this->referralModel->update($id, [
        'qr_code_token' => $token,
        'qr_code_path'  => "writable/bmg_qr_codes/{$token}.png",
        'qr_generated_at' => date('Y-m-d H:i:s'),
    ]);
    
    $qrCode = new QrCode("https://{$_SERVER['HTTP_HOST']}/referral/verify/{$token}");
    $qrCode->render("writable/bmg_qr_codes/{$token}.png");
    
    return $this->respond(['token' => $token, 'url' => base_url("referral/qr/{$id}")]);
}
```

**Deliverables**:
- 1 new composer package: `chillerlan/php-qrcode`
- 2 reusable view components (qr_scanner, rfid_input)
- 1 server-side QR generation method
- JavaScript for both scanners

---

### Phase 12 — Reports Module

**Goal**: Add cross-module reporting, with emphasis on the new BMG analytics.

**Location**: `app/Controllers/Reports/`, `app/Views/reports/`

#### 12.1 New Reports

| Report | Controller Method | Data Source | Export |
|--------|-------------------|-------------|--------|
| Clinic Overview | `ClinicReportController::overview` | consultations, patients, low-stock | CSV, PDF |
| Counselling Overview | `CounsellingReportController::overview` | appointments, intake notes | CSV |
| Inventory Status | `InventoryReportController::status` | medicines, batches, reorders | CSV |
| **BMG Yield by Drum** | `BmgReportController::yieldByDrum` | bmg_batches | CSV, PDF |
| **BMG Duration by Waste Type** | `BmgReportController::durationByWasteType` | bmg_batches, waste_categories | CSV, PDF |
| **BMG Monthly Totals** | `BmgReportController::monthlyTotals` | aggregated bmg_batches | CSV, PDF |
| **BMG Drum Utilization** | `BmgReportController::drumUtilization` | bmg_drums, bmg_batches | CSV |

#### 12.2 BmgReportController Methods

```php
public function index()
{
    return view('reports/bmg', [
        'yieldByDrum'       => $this->getYieldByDrum(),
        'durationByWaste'   => $this->getDurationByWasteType(),
        'monthlyTotals'     => $this->getMonthlyTotals(),
        'drumUtilization'   => $this->getDrumUtilization(),
    ]);
}

public function getYieldByDrum($dateRange = null)
{
    // SQL: SELECT drum_id, AVG(yield_percentage) FROM bmg_batches
    //      WHERE status='completed' GROUP BY drum_id
}

public function exportCsv($reportType)
{
    $data = $this->{'get' . ucfirst($reportType)}();
    $filename = "bmg_{$reportType}_" . date('Ymd') . ".csv";
    header('Content-Type: text/csv');
    header("Content-Disposition: attachment; filename=\"$filename\"");
    $fp = fopen('php://output', 'w');
    foreach ($data as $row) fputcsv($fp, $row);
    fclose($fp);
    exit;
}
```

**Deliverables**:
- 1 new controller: `BmgReportController` (7 report methods)
- 1 new view: `reports/bmg.php`
- CSV export for all reports
- PDF export (using `dompdf/dompdf` or `tecnickcom/tcpdf`)
- 1 chart per report (Chart.js)

---

### Phase 13 — HR Integration (Employee Sync)

**Goal**: Implement the HR Department employee data sync.

**Location**: `app/Controllers/Hri/`, `app/Models/EmployeeModel.php`

#### 13.1 EmployeeSyncController

```php
namespace App\Controllers\Hri;

class EmployeeSyncController extends BaseController
{
    public function index()
    {
        return view('hri/index', [
            'lastSync' => setting('Hr.lastSyncAt'),
            'pendingCount' => $this->getPendingCount(),
        ]);
    }
    
    public function sync()
    {
        // 1. Fetch employees from HR system (CSV upload OR API call)
        // 2. Compare with existing employees
        // 3. Insert new, update changed
        // 4. Log sync result
        // 5. Return summary
    }
    
    public function logs()
    {
        return view('hri/logs', [
            'logs' => $this->syncLogModel->orderBy('synced_at', 'DESC')->findAll(50)
        ]);
    }
}
```

#### 13.2 Sync Method (CSV-based for v1)

```php
public function sync()
{
    $file = $this->request->getFile('hr_csv');
    if (!$file->isValid()) return $this->fail('Invalid file');
    
    $handle = fopen($file->getTempName(), 'r');
    $header = fgetcsv($handle);
    $expectedHeader = ['employee_number', 'first_name', 'last_name', 'department', 'position', 'email'];
    
    if ($header !== $expectedHeader) {
        return $this->fail('CSV format mismatch');
    }
    
    $stats = ['inserted' => 0, 'updated' => 0, 'errors' => 0];
    while (($row = fgetcsv($handle)) !== false) {
        try {
            $existing = $this->employeeModel->where('employee_number', $row[0])->first();
            $payload = $this->mapRowToEmployee($row);
            
            if ($existing) {
                $this->employeeModel->update($existing['id'], $payload);
                $stats['updated']++;
            } else {
                // Create user account too
                $userId = $this->userModel->insert([
                    'email' => $payload['email'],
                    'password_hash' => password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT),
                    'first_name' => $payload['first_name'],
                    'last_name' => $payload['last_name'],
                    'user_type' => 'employee',
                ]);
                $this->userModel->addRole($userId, 'employee');
                $payload['user_id'] = $userId;
                $this->employeeModel->insert($payload);
                $stats['inserted']++;
            }
        } catch (\Exception $e) {
            $stats['errors']++;
            log_message('error', 'HR sync error: ' . $e->getMessage());
        }
    }
    fclose($handle);
    
    $this->syncLogModel->insert([
        'synced_at' => date('Y-m-d H:i:s'),
        'synced_by' => session()->get('user_id'),
        'stats' => json_encode($stats),
    ]);
    
    return $this->respond($stats);
}
```

#### 13.3 Notification

After successful sync:
- Notify admin: "HR sync completed: X inserted, Y updated, Z errors"

**Deliverables**:
- 1 new controller: `EmployeeSyncController`
- 1 new model: `HrSyncLogModel`
- 1 new view: `hri/index.php` (CSV upload form)
- 1 new view: `hri/logs.php` (sync history)
- CSV template for HR Department

---

### Phase 14 — Audit Logging Integration

**Goal**: Ensure every write across all 3 modules produces an audit log entry.

**Location**: `app/Libraries/AuditLogger.php` (NEW), `app/Models/AuditLogModel.php`

#### 14.1 New AuditLogger Library

```php
namespace App\Libraries;

class AuditLogger
{
    public function log(string $action, string $module, ?string $entityType = null, 
                        ?int $entityId = null, ?array $oldValues = null, 
                        ?array $newValues = null, string $status = 'success')
    {
        $auditModel = new \App\Models\AuditLogModel();
        $previousHash = $auditModel->getLastHash();
        
        $entry = [
            'user_id'       => session()->get('user_id'),
            'action'        => $action,
            'module'        => $module,  // 'clinic' | 'counselling' | 'bmg' | 'auth' | 'admin'
            'entity_type'   => $entityType,
            'entity_id'     => $entityId,
            'old_values'    => $oldValues ? json_encode($oldValues) : null,
            'new_values'    => $newValues ? json_encode($newValues) : null,
            'request_id'    => $this->getRequestId(),
            'ip_address'    => $this->request->getIPAddress(),
            'user_agent'    => $this->request->getUserAgent()->getAgentString(),
            'status'        => $status,
            'previous_hash' => $previousHash,
            'created_at'    => date('Y-m-d H:i:s'),
        ];
        
        $entry['hash'] = $this->computeHash($entry, $previousHash);
        $auditModel->insert($entry);
    }
    
    private function computeHash(array $entry, string $previousHash): string
    {
        $payload = $previousHash 
                 . $entry['action'] 
                 . $entry['module'] 
                 . $entry['entity_id'] 
                 . $entry['created_at'];
        return hash('sha256', $payload);
    }
}
```

#### 14.2 Integration Points

Add `AuditLogger` calls to every controller method that performs a write:

| Module | Method | Action |
|--------|--------|--------|
| Auth | `attemptLogin`, `logout` | `auth.login`, `auth.logout` |
| Clinic Employee | `store`, `update` | `clinic.employee.create`, `clinic.employee.update` |
| Clinic Checkin | `scan` | `clinic.checkin.create` |
| Clinic Referral | `store`, `verifyQr` | `clinic.referral.create`, `clinic.referral.verify` |
| Counselling | `store` (intake) | `counselling.intake.create` |
| Inventory | `storeBatch`, `updateStock` | `inventory.batch.create`, `inventory.stock.update` |
| Reorder | `store`, `approve`, `order`, `receive` | `inventory.reorder.*` |
| BMG Drum | `store`, `update`, `archive` | `bmg.drum.*` |
| BMG Batch | `start`, `markCompleted` | `bmg.batch.*` |
| BMG Input | `store` | `bmg.input.create` |
| BMG Output | `store` | `bmg.output.create` |
| HR Sync | `sync` | `hri.sync.execute` |
| Admin User | `store`, `update` | `admin.user.*` |

**Deliverables**:
- 1 new library: `AuditLogger`
- Audit log calls in 25+ controller methods
- Verification: Admin can view chain integrity status

---

### Phase 15 — Notifications Expansion

**Goal**: Expand the notification system to support new event types.

**Location**: `app/Controllers/NotificationController.php`, `app/Models/NotificationModel.php`

#### 15.1 New Notification Types

| Type | Trigger | Recipient |
|------|---------|-----------|
| `appointment_reminder` | 24h before appointment | Patient |
| `low_stock` | Stock <= reorder_level | Clinic staff, admin |
| `referral_received` | New referral | Target module staff |
| `referral_verified` | QR scanned | Source module staff |
| `reorder_request` | Auto-trigger | Procurement staff, admin |
| `reorder_approved` | Status → approved | Original requester |
| `reorder_received` | Status → received | Requester, admin |
| `bmg_batch_started` | New batch input | Facilities staff |
| `bmg_batch_completed` | Mark as completed | Facilities staff, admin |
| `bmg_idle_drum` | Drum idle > 30 days | Facilities staff, admin |
| `crisis_idle_drum` | Drum idle > 60 days | Admin (escalation) |
| `hr_sync_completed` | After sync | Admin |

#### 15.2 Notification Model Updates

Add `module` column to notifications table (already in schema, just verify).

#### 15.3 Polling Endpoint Updates

`NotificationController::unread()` returns notifications scoped to:
- User's roles
- Modules the user has access to

**Deliverables**:
- 1 new model method: `getForUser($userId, $roles)`
- 12 new notification trigger points
- Updated `unread` endpoint with role-based filtering
- Updated `header.php` to show BMG icon for facilities staff

---

### Phase 16 — Testing (Unit + Integration)

**Goal**: Comprehensive test coverage for the new modules.

**Location**: `tests/`

#### 16.1 Unit Tests

| Test File | Coverage |
|-----------|----------|
| `BmgYieldCalculatorTest.php` | Yield %, mass reduction, edge cases (0, negative, > 100) |
| `BmgDurationCalculatorTest.php` | Same day, multi-day, month boundaries |
| `QrCodeGeneratorTest.php` | Token uniqueness, PNG generation, file existence |
| `AuditLoggerTest.php` | Hash chain integrity, payload structure |
| `BmgBatchModelTest.php` | Lifecycle transitions, validation rules |
| `BmgOutputModelTest.php` | Output > input rejected at DB level |

#### 16.2 Integration Tests

| Test File | Coverage |
|-----------|----------|
| `BmgWorkflowTest.php` | Full input → process → output cycle |
| `ReorderWorkflowTest.php` | Auto-trigger, status transitions |
| `CheckinWorkflowTest.php` | RFID scan with valid + invalid tags |
| `HrSyncTest.php` | CSV upload, employee creation |
| `RoleEnforcementTest.php` | Each role can/cannot access each module |

#### 16.3 Test Fixtures

Create database seeder for tests:
- 1 admin user
- 1 clinic_staff user
- 1 counsellor user
- 1 facilities_staff user
- 1 report_viewer user
- 1 employee user
- 1 student user
- 6 BMG drums (3 idle, 3 processing)
- 3 waste categories
- Sample batches in various states

**Deliverables**:
- 6 unit test files
- 5 integration test files
- 1 test seeder
- Test runner: `composer test` (already configured)
- Coverage target: ≥ 70% for new code

---

### Phase 17 — Seed Data & Environment Setup

**Goal**: Provide working seed data for development and demo.

**Location**: `app/Database/Seeds/`

#### 17.1 Seeders to Create/Update

| Seeder | Purpose |
|--------|---------|
| `RoleSeeder` | Insert 7 roles |
| `PermissionSeeder` | Insert 20+ permissions + role_permissions mappings |
| `UserSeeder` | Insert demo users (1 per role) |
| `StudentSeeder` | 50 sample students |
| `EmployeeSeeder` | 30 sample employees |
| `MedicineSeeder` | 20 sample medicines with reorder levels |
| `MedicineBatchSeeder` | 50 batches with various expiration dates |
| `WasteCategorySeeder` | 3 categories (food, twigs/leaves, mixed) |
| `BmgDrumSeeder` | 6 drums (3 idle, 3 processing) |
| `BmgBatchSeeder` | 10 historical batches (completed) |
| `BmgInputSeeder` | 10 input records |
| `BmgOutputSeeder` | 8 output records (2 batches still processing) |

#### 17.2 .env Updates

Add to `.env`:
```ini
# QR Code generation
QR_CODE_PATH = writable/bmg_qr_codes/

# HR Sync
HR_SYNC_CSV_TEMPLATE = writable/templates/hr_employees.csv

# BMG default settings
BMG_DEFAULT_CAPACITY_KG = 100
BMG_IDLE_ALERT_DAYS = 30
```

#### 17.3 Demo Credentials (in README)

```
admin              admin@synapse.edu.ph          / Admin@123
clinic_staff       clinic@synapse.edu.ph         / Clinic@123
counsellor         counsellor@synapse.edu.ph    / Counsel@123
facilities_staff   bmg@synapse.edu.ph           / Bmg@123
report_viewer      reports@synapse.edu.ph       / Reports@123
employee           employee@synapse.edu.ph    / Employee@123
student            student@synapse.test       / Student@123
```

**Deliverables**:
- 12 seeder files
- Updated `.env.example`
- Updated README with demo credentials
- Run sequence: `php spark db:seed RoleSeeder` etc.

---

### Phase 18 — UAT Prep & ISO/IEC 25010 Evaluation

**Goal**: Prepare User Acceptance Testing materials and evaluation framework.

**Location**: `tests/uat/`, `docs/`

#### 18.1 UAT Test Scenarios

For each role, define 5-10 realistic test scenarios:

| Role | Test Scenario |
|------|---------------|
| Clinic Staff | "Process a student consultation from check-in to treatment" |
| Clinic Staff | "Generate QR referral and verify by another staff" |
| Clinic Staff | "Trigger reorder when medicine stock is low" |
| Counsellor | "Book appointment and record intake notes" |
| Counsellor | "Refer patient to clinic with QR code" |
| Facilities Staff | "Add new BMG drum to system" |
| Facilities Staff | "Process full batch: input → mark completed → record output" |
| Facilities Staff | "View yield analytics by drum and waste type" |
| Admin | "View audit log for a specific request ID" |
| Admin | "Add new user and assign role" |
| Report Viewer | "Export BMG report to CSV" |
| Student | "Book counselling appointment" |
| Employee | "Check own medical record history" |

#### 18.2 ISO/IEC 25010 Quality Model

Evaluate each module against 8 quality characteristics:

1. **Functional Suitability** — Does it do what's needed?
2. **Performance Efficiency** — Response time, resource use
3. **Compatibility** — Browser, OS, integration
4. **Usability** — Learnability, operability, error protection
5. **Reliability** — Maturity, availability, fault tolerance
6. **Security** — Confidentiality, integrity, non-repudiation
7. **Maintainability** — Modularity, reusability, analyzability
8. **Portability** — Adaptability, installability

**Deliverables**:
- 1 UAT test scenario document
- 1 ISO/IEC 25010 evaluation rubric
- 1 UAT results spreadsheet template
- 1 facilitator guide for UAT sessions

---

## Cross-Cutting Concerns

### Security Considerations

| Concern | Mitigation |
|---------|-----------|
| QR token leakage | Short-lived tokens (1 hour) with manual refresh |
| RFID tag cloning | Audit trail in `checkin_logs`, anomaly detection |
| Reorder request forgery | Role-restricted to `admin,clinic_staff` |
| BMG data tampering | DB CHECK constraint + audit log + hash chain |
| HR CSV injection | Validate file type, parse strictly, log all imports |
| Employee PII exposure | Field-level encryption for sensitive data (DOB, address) |

### Performance Considerations

- Cache `permissions` per user (Redis or file cache, 1 hour TTL)
- Cache `waste_categories` (rarely changes)
- Index all FK + filter columns
- Paginate all list views (default 20 per page)
- Defer chart rendering to Chart.js (client-side)

### Accessibility (WCAG 2.1 AA)

- All forms have `<label>` for every input
- ARIA roles on custom components
- Keyboard navigation for drum cards, modal dialogs
- High contrast for status colors
- Skip-to-content link

### Internationalization

- All user-facing strings via `lang/en/*.php`
- Date formatting via `IntlDateFormatter`
- Currency in PHP (no hard-coded ₱)

---

## Risk & Dependency Matrix

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|-----------|
| HR Department CSV format changes | High | Medium | Versioned template, validation on import |
| BMG drum sensor data integration deferred | Low | High | Already in scope as manual entry (per PDF) |
| QR code library compatibility | Medium | Low | Pin chillerlan/php-qrcode ^4.0, test on PHP 8.2 |
| MySQL CHECK constraint support | Medium | Low | Verify on MySQL 8.0+; fallback to trigger |
| RFID hardware availability for UAT | High | Medium | Use keyboard-emulated input as fallback |
| 7-role permission explosion | Medium | High | Use resource-based wildcards, group permissions |
| Migration ordering errors | High | Medium | Numbered timestamps, test rollback |
| Time constraint (capstone deadline) | High | High | Prioritize clinic + counselling first, then BMG |

---

## Suggested Milestones & Timeline

This is a rough estimate assuming a single developer working full-time:

| Week | Phase | Deliverable |
|------|-------|-------------|
| 1 | Phase 1 | All 20 migrations run successfully |
| 2 | Phase 2, 3 | All models + libraries working with manual tests |
| 3 | Phase 4, 5 | Auth, RBAC, Employee, Checkin, QR Referral functional |
| 4 | Phase 6, 7 | BMG module complete, reorder workflow |
| 5 | Phase 8, 9 | All routes wired, all views rendering |
| 6 | Phase 10, 11 | BMG dashboard + Facility Operations tab + QR/RFID scanners |
| 7 | Phase 12, 13 | Reports module + HR sync complete |
| 8 | Phase 14, 15 | Audit logging + notifications across all modules |
| 9 | Phase 16 | Tests written, ≥ 70% coverage |
| 10 | Phase 17 | Seeders, demo data, environment ready |
| 11 | Phase 18 | UAT executed, ISO/IEC 25010 evaluated |
| 12 | Polish | Bug fixes, performance tuning, documentation |

**Total**: ~12 weeks for a single full-time developer.

---

## Definition of Done (Per Phase)

For each phase to be considered **complete**, the following must be true:

- [ ] All code committed to a feature branch
- [ ] All migrations apply cleanly on a fresh database
- [ ] All new routes return HTTP 200 on success, proper error codes on failure
- [ ] All new models have at least basic validation rules
- [ ] All new controllers are filtered with `auth` + appropriate `role` filters
- [ ] All new views use the existing layout (header, sidebar, footer)
- [ ] All new views pass a basic accessibility check (labels, contrast)
- [ ] All new write operations produce an audit log entry
- [ ] Unit tests written and passing (Phase 16+)
- [ ] No new PHP errors or warnings in `writable/logs/`
- [ ] CSRF tokens included in all POST forms
- [ ] No SQL queries using string concatenation
- [ ] No `dd()`, `var_dump()`, or `die()` left in production code

---

## Summary

This plan transforms the existing **SYNAPSE** codebase into the full capstone scope across **18 phases**. The work is ordered to:

1. **Build the data foundation first** (Phases 1-3): Migrations, models, libraries
2. **Secure access next** (Phase 4): Auth, RBAC, permissions
3. **Implement core features in dependency order** (Phases 5-7): Employee → Checkin → QR Referral → BMG → Reorder
4. **Wire it all together** (Phases 8-11): Routes, views, scanners
5. **Add cross-cutting features** (Phases 12-15): Reports, HR sync, audit, notifications
6. **Validate and polish** (Phases 16-18): Tests, seeders, UAT

The most critical path is **Phase 1 (migrations) → Phase 2 (models) → Phase 6 (BMG controllers) → Phase 10 (BMG dashboard)** because the BMG module is the new centerpiece of the capstone.

The most independent work (can be parallelized) is **Phase 5 (Checkin/QR) → Phase 7 (Reorder) → Phase 11 (Scanners)** as they don't depend on the BMG module.

---

**Plan Version**: 1.0
**Last Updated**: 2026-07-11
**Maintained By**: SYNAPSE Development Team
