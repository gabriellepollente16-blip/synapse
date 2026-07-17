# SYNAPSE — Step-by-Step Workflows by User Role

> **Project:** SYNAPSE: A Unified Web-Based Campus Management System — An IoT-Enabled Approach to Health, Counselling, and Facilities Operations at Foundation University
> **Stack:** PHP 8.2 + CodeIgniter 4.7.3 + MySQL 8
> **Source of truth:** [`app/Config/Routes.php`](../app/Config/Routes.php), [`app/Controllers/**`](../app/Controllers/), [`app/Views/components/sidebar.php`](../app/Views/components/sidebar.php)
> **Last updated:** 2026-07-15

This document describes the canonical user journeys through the system, one section per role. Every step lists the **action**, the **page** the user lands on, the **route** (so you can verify against `Routes.php`), and the **result**.

---

## Table of Contents

1. [Role Catalog](#role-catalog)
2. [Admin (`admin`)](#1-system-administrator-admin)
3. [Clinic Staff (`clinic_staff`)](#2-clinic-staff-clinic_staff)
4. [Counsellor (`counsellor`)](#3-counsellor-counsellor)
5. [Facilities / Composting Staff (`facilities_staff`)](#4-facilities--composting-staff-facilities_staff)
6. [Report Viewer (`report_viewer`)](#5-report-viewer-report_viewer)
7. [Employee (`employee`)](#6-employee-employee)
8. [Student (`student`)](#7-student-student)
9. [Cross-Module Flows](#cross-module-flows)
10. [CRUD Matrix by Role](#crud-matrix-by-role)
11. [Public (Unauthenticated) Endpoints](#public-unauthenticated-endpoints)

---

## Role Catalog

| Role | Display name | Default landing page |
|---|---|---|
| `admin` | System Administrator | `/dashboard/admin` |
| `clinic_staff` | Clinic Staff (Doctor, Nurse, Clinic Admin) | `/dashboard/clinic` |
| `counsellor` | Guidance Counsellor | `/dashboard/counsellor` |
| `facilities_staff` | Facilities / Composting Staff | `/dashboard/bmg` |
| `report_viewer` | Report Viewer | `/dashboard/reports` |
| `employee` | University employee with referral access | `/dashboard/employee` |
| `student` | Student | `/dashboard/student` |

---

## 1. System Administrator (`admin`)

**Sidebar sections visible:** Main · Administration · Clinic · Inventory · Counselling · Facility Operations (all sections)

**Landing page:** `/dashboard/admin`

### 1.1 Manage user accounts

| # | Action | Route |
|---|---|---|
| 1 | Open **User Management** | `GET /admin/users` |
| 2 | Filter / search the user list (by name, email, role, status) | same page — query params `q`, `role`, `status`, `sort`, `per_page`, `page` |
| 3 | Click **"Create user"** | `GET /admin/users/create` |
| 4 | Fill in name, email, temp password (min 10 chars), role checkboxes → **Save** | `POST /admin/users/store` |
| 5 | Click a user row to open their profile | `GET /admin/users/{id}` (shows roles + last 20 audit entries) |
| 6 | From the profile, click **"Edit"** | `GET /admin/users/{id}/edit` |
| 7 | Update fields → **Save** | `POST /admin/users/update/{id}` |
| 8 | **Toggle active / inactive** for one user (system refuses to deactivate yourself) | `POST /admin/users/toggle/{id}` |
| 9 | **Bulk activate / deactivate** (AJAX) | `POST /admin/users/bulk-toggle` |
| 10 | **Reset another user's password** (min 10 chars) | `POST /admin/users/reset-password/{id}` |
| 11 | **Assign a role** to a user | `POST /admin/users/assign-role/{id}` |
| 12 | **Revoke a role** | `POST /admin/users/revoke-role/{id}` |
| 13 | **Delete a user** — choose mode `soft` / `anonymize` / `hard` (hard requires typing the user's email) | `POST /admin/users/delete/{id}` |
| 14 | **Bulk delete** (hard mode requires typing the literal word `DELETE`) | `POST /admin/users/bulk-delete` |

### 1.2 Manage roles & permissions

| # | Action | Route |
|---|---|---|
| 1 | Open **Roles & Permissions** | `GET /admin/roles` |
| 2 | Click **"Create role"** | `GET /admin/roles/create` |
| 3 | Set name + permission checkboxes → **Save** | `POST /admin/roles/store` |
| 4 | Click a role to see its permission matrix and assigned users | `GET /admin/roles/{id}` |
| 5 | Click **"Edit"** → toggle permissions → **Save** | `GET /admin/roles/{id}/edit` → `POST /admin/roles/update/{id}` |
| 6 | **Toggle a single permission** on a role (AJAX) | `POST /admin/roles/toggle-permission/{id}` |

### 1.3 Audit & compliance

| # | Action | Route |
|---|---|---|
| 1 | Open **Audit Logs** | `GET /admin/audit` |
| 2 | Filter by `q`, `module`, `action`, `user_id`, `start`, `end` | same page |
| 3 | **Verify hash-chain integrity** (proves no log row has been tampered with) | `GET /admin/audit/verify` |
| 4 | **Export filtered audit log** as CSV — the export action is itself logged | `GET /admin/audit/export` |

### 1.4 Cross-module reports & analytics

| # | Action | Route |
|---|---|---|
| 1 | Open the report module picker | `GET /reports` |
| 2 | Drill into **Clinic analytics** (filter by date range) | `GET /reports/clinic` |
| 3 | Drill into **Counselling analytics** | `GET /reports/counselling` |
| 4 | Drill into **Inventory analytics** | `GET /reports/inventory` |
| 5 | **Export a module's report** as CSV (`clinic` / `counselling` / `inventory`) | `GET /reports/export/{module}` |

### 1.5 System admin (diagnostics)

| # | Action | Route |
|---|---|---|
| 1 | Open **Admin Console** (read-only module status) | `GET /admin` |
| 2 | Open **UI Components** showcase (developer reference, opens new tab) | `GET /ui` |

---

## 2. Clinic Staff (`clinic_staff`)

**Sidebar sections visible:** Main · Clinic · Inventory

**Landing page:** `/dashboard/clinic`

### 2.1 Run today's consultation queue

| # | Action | Route |
|---|---|---|
| 1 | Open **Consultations** | `GET /clinic/consultations` |
| 2 | Open the **Queue Control Board** (staff-side) | `GET /consultations/queue` |
| 3 | Switch the lobby TV display on (public, shows first names only) | `GET /consultations/queue/display` |
| 4 | TV auto-polls state (no user action) | `GET /consultations/queue/state.json` (every 1s) |
| 5 | **Call the next patient** (auto-promotes highest-priority waiting entry) | `POST /consultations/call-next` |
| 6 | **Start the called patient** (status → `in_progress`) | `POST /consultations/start/{id}` |
| 7 | **Skip / no-show** a patient (re-packs the queue) | `POST /consultations/skip/{id}` |

### 2.2 Open a consultation for a student

| # | Action | Route |
|---|---|---|
| 1 | From a student profile or queue, click **"New consultation"** | `GET /clinic/consultations/create/{studentId}` |
| 2 | Optionally run **AJAX triage preview** to see the AI's predicted priority | `POST /clinic/consultations/ajax-triage` |
| 3 | Fill in chief complaint, history → **Start** (auto-runs AI triage; you can override the priority) | `POST /clinic/consultations/store` |
| 4 | Open the consultation's detail page | `GET /clinic/consultations/{id}` |
| 5 | Click **"Record Vitals"** → enter vitals → **Save** (re-runs triage with vitals) | `GET /clinic/consultations/vitals/{id}` → `POST .../vitals/{id}` |
| 6 | Click **"Add Diagnosis"** → enter ICD / notes → **Save** | `GET /clinic/consultations/diagnosis/{id}` → `POST .../diagnosis/{id}` |
| 7 | Click **"Add Treatment"** → pick medicine + dose → **Save** (a `medication` line triggers FEFO batch dispense) | `GET /clinic/treatments/create/{id}` → `POST /clinic/treatments/store` |
| 8 | **Mark Complete** (or schedule follow-up) | `POST /clinic/consultations/complete/{id}` |
| 9 | Open the student's **full history** (last 50 visits) | `GET /clinic/consultations/history/{studentId}` |

### 2.3 Manage student records

| # | Action | Route |
|---|---|---|
| 1 | Open **Students** | `GET /clinic/students` |
| 2 | Filter / search by name or ID | same page — `q`, `per_page` |
| 3 | Click **"Create student"** → fill form (default password = `Student@<year>`) → **Save** (creates user + assigns `student` role) | `GET /clinic/students/create` → `POST /clinic/students/store` |
| 4 | Click a student row to open their profile | `GET /clinic/students/{id}` |
| 5 | Click **"Edit"** → update → **Save** | `GET /clinic/students/edit/{id}` → `POST /clinic/students/update/{id}` |
| 6 | **Search by QR / RFID / manual entry** (AJAX) | `GET /clinic/students/search?method=qr\|rfid\|manual&q=...` |

### 2.4 Manage employee records (HR-integrated)

| # | Action | Route |
|---|---|---|
| 1 | Open **Employees** | `GET /clinic/employees` |
| 2 | Click **"Create employee"** → fill form → **Save** (temp password shown in success flash) | `GET /clinic/employees/create` → `POST /clinic/employees/store` |
| 3 | Open employee profile | `GET /clinic/employees/{id}` |
| 4 | Click **"Edit"** → update → **Save** | `GET /clinic/employees/edit/{id}` → `POST /clinic/employees/update/{id}` |
| 5 | **Search (AJAX)** | `GET /clinic/employees/search?term=...` |
| 6 | **Sync employees from HR CSV** | `POST /clinic/employees/sync-hr` |

### 2.5 Refer a student to counselling

| # | Action | Route |
|---|---|---|
| 1 | Open **Referrals** | `GET /clinic/referrals` (filters: `status`, `direction`) |
| 2 | From a consultation, click **"Refer to Counselling"** | `GET /clinic/referrals/create/{consultId}` |
| 3 | Pick a reason + priority → **Save** (auto-generates QR code, broadcasts to all counsellors) | `POST /clinic/referrals/store` |
| 4 | If authorized as an employee, use direct referral mode to refer a student without a consultation | `GET /clinic/referrals/create` → `POST /clinic/referrals/store` |
| 5 | **Download the QR image** to give to the student (admin / clinic / counsellor) | `GET /referral/qr/{id}` |
| 6 | Student or counsellor scans the QR — opens the public verifier | `GET /referral/verify/{token}` |

### 2.6 Medicine inventory

| # | Action | Route |
|---|---|---|
| 1 | Open **Medicine Catalog** (with AI stock forecasts) | `GET /inventory` |
| 2 | Click **"Add medicine"** → fill form → **Save** | `GET /inventory/medicines/create` → `POST /inventory/medicines/store` |
| 3 | Click a medicine row to see its batches | `GET /inventory/medicines/{id}` |
| 4 | Click **"Edit"** → update → **Save** | `GET /inventory/medicines/edit/{id}` → `POST /inventory/medicines/update/{id}` |
| 5 | From a medicine detail page, click **"Add batch"** → fill lot + expiry + qty → **Save** | `GET /inventory/medicines/{id}/batch` → `POST .../batch` |
| 6 | Open **Low Stock** list | `GET /inventory/low-stock` |
| 7 | Open **Expiring Batches** list | `GET /inventory/expiring` |
| 8 | **Get FEFO batches for a medicine** (used by the treatment form, AJAX) | `GET /clinic/treatments/batches/{medicineId}` |

### 2.7 Reorder requests (procurement)

| # | Action | Route |
|---|---|---|
| 1 | Open **Reorder Requests** | `GET /inventory/reorders` (filter by `status`) |
| 2 | Open a single request | `GET /inventory/reorders/{id}` |
| 3 | Click **"New reorder"** → fill form → **Save** | `GET /inventory/reorders/create` → `POST /inventory/reorders/store` |
| 4 | Click **"Auto-check all medicines"** (AI scans stock levels) | `GET /inventory/reorders/auto-check` |
| 5 | **Trigger** an individual medicine for reorder | `GET /inventory/reorders/trigger/{medicineId}` |
| 6 | **Approve** a request | `POST /inventory/reorders/{id}/approve` |
| 7 | **Mark ordered** (sent to supplier) | `POST /inventory/reorders/{id}/order` |
| 8 | **Mark received** (stock added) | `POST /inventory/reorders/{id}/receive` |
| 9 | **Cancel** a request | `POST /inventory/reorders/{id}/cancel` |

### 2.8 RFID check-in (also used by counsellor)

| # | Action | Route |
|---|---|---|
| 1 | From the kiosk tablet, scan an RFID card | `POST /clinic/checkin/scan` |

---

## 3. Counsellor (`counsellor`)

**Sidebar sections visible:** Main · Counselling

**Landing page:** `/dashboard/counsellor`

### 3.1 Manage today's appointment schedule

| # | Action | Route |
|---|---|---|
| 1 | Open **Counselling** (today's schedule + slot analytics) | `GET /counselling` (alias `GET /counselling/appointments`) |
| 2 | Click **"Book appointment"** → pick a student and date | `GET /counselling/appointments/create/{studentId}?date=YYYY-MM-DD` |
| 3 | Fill in slot, purpose → **Save** (runs no-show AI prediction; notifies the student) | `POST /counselling/appointments/store` |
| 4 | Open the appointment detail | `GET /counselling/appointments/{id}` |
| 5 | **Start session** (status → `confirmed`) | `POST /counselling/appointments/start/{id}` |
| 6 | **Complete session** (saves notes, resets student's consecutive no-show counter) | `POST /counselling/appointments/complete/{id}` |
| 7 | **Mark no-show** (3 consecutive → triggers a follow-up alert) | `POST /counselling/appointments/no-show/{id}` |
| 8 | **Cancel** (with reason) | `POST /counselling/appointments/cancel/{id}` |

### 3.2 Support notes & follow-up

| # | Action | Route |
|---|---|---|
| 1 | Open the student support record from the appointment detail | `GET /counselling/appointments/{id}` |
| 2 | Enter case notes, action points, and follow-up details → **Save** | `POST /counselling/appointments/complete/{id}` |
| 3 | Review the student’s prior support history | `GET /counselling/appointments` |

### 3.3 Personal availability

| # | Action | Route |
|---|---|---|
| 1 | Open **My Availability** | `GET /counselling/availability` |
| 2 | **Add a slot** (day of week + start/end) | `POST /counselling/availability/add` |
| 3 | **Add a full day** of slots (send `slots` JSON in body) | `POST /counselling/availability/add` |
| 4 | **Remove a slot** (soft delete, sets `is_active=0`) | `POST /counselling/availability/remove/{id}` |

### 3.4 Incoming referrals (from clinic)

| # | Action | Route |
|---|---|---|
| 1 | Open **Referrals** (incoming) | `GET /counselling/referrals` |
| 2 | **Accept** a referral (sets `referred_to = self`) | `POST /counselling/referrals/accept/{id}` |
| 3 | **Decline** a referral | `POST /counselling/referrals/decline/{id}` |

### 3.5 RFID check-in (also used by clinic staff)

| # | Action | Route |
|---|---|---|
| 1 | From the kiosk tablet, scan an RFID card | `POST /clinic/checkin/scan` |

---

## 4. Facilities / Composting Staff (`facilities_staff`)

**Sidebar sections visible:** Main · Facility Operations

**Landing page:** `/dashboard/bmg`

### 4.1 Drum management

| # | Action | Route |
|---|---|---|
| 1 | Open **Drums** (with status counts: idle / processing / maintenance) | `GET /bmg/drums` |
| 2 | Filter / search (`q`, `status`) | same page |
| 3 | Click **"Add drum"** → fill code, name, location, capacity → **Save** | `GET /bmg/drums/create` → `POST /bmg/drums/store` |
| 4 | Click a drum row to open its detail (active batch + last 10 batches) | `GET /bmg/drums/{id}` |
| 5 | Click **"Edit"** → update → **Save** | `GET /bmg/drums/edit/{id}` → `POST /bmg/drums/update/{id}` |
| 6 | **Mark Idle** | `POST /bmg/drums/mark-idle/{id}` |
| 7 | **Mark Processing** (refuses if an active batch already exists) | `POST /bmg/drums/mark-processing/{id}` |
| 8 | **Mark Maintenance** | `POST /bmg/drums/mark-maintenance/{id}` |
| 9 | **Complete batch and idle** (one-click: close the active batch and return the drum to idle) | `POST /bmg/drums/complete-and-idle/{id}` |
| 10 | **Delete drum** (refuses if any batches exist) | `POST /bmg/drums/delete/{id}` |
| 11 | **Archive drum** *(admin only)* | `POST /bmg/drums/archive/{id}` |

### 4.2 Batch lifecycle (composting runs)

| # | Action | Route |
|---|---|---|
| 1 | Open **Batches** (filter by `status`) | `GET /bmg/batches` |
| 2 | Open a batch detail (tabbed: inputs / process logs / outputs) | `GET /bmg/batches/{id}` |
| 3 | Click **"Start batch on drum"** | `GET /bmg/batches/startOnDrum/{drumId}` |
| 4 | Pick waste category, input weight → **Save** (validates weight ≤ drum capacity; flips drum to `processing`) | `POST /bmg/batches/create` |
| 5 | **Mark batch completed** | `POST /bmg/batches/{id}/mark-completed` |
| 6 | **Cancel batch** | `POST /bmg/batches/{id}/cancel` |

### 4.3 Per-batch observations & data entry

| # | Action | Route |
|---|---|---|
| 1 | From a batch detail, click **"Add Input"** → enter weight → **Save** | `GET /bmg/batches/{id}/inputs/create` → `POST /bmg/batches/{id}/inputs/store` |
| 2 | Click **"Add Observation"** → enter temperature, moisture, free-text note → **Save** | `GET /bmg/batches/{id}/process-logs/create` → `POST /bmg/batches/{id}/process-logs/store` |
| 3 | Click **"Record Output"** → enter harvest weight, quality grade → **Save** (validates output ≤ input) | `GET /bmg/batches/{id}/output/create` → `POST /bmg/batches/{id}/output/store` |

### 4.4 Waste category taxonomy

| # | Action | Route |
|---|---|---|
| 1 | Open **Waste Categories** | `GET /bmg/categories` |
| 2 | Click **"Create"** → fill code, name, expected yield %, reference duration → **Save** | `GET /bmg/categories/create` → `POST /bmg/categories/store` |
| 3 | Click **"Edit"** → update → **Save** | `GET /bmg/categories/edit/{id}` → `POST /bmg/categories/update/{id}` |
| 4 | **Delete** *(admin only — refuses if the category is in use)* | `POST /bmg/categories/delete/{id}` |

### 4.5 BMG reports & analytics

| # | Action | Route |
|---|---|---|
| 1 | Open **BMG Reports** (yield by drum, duration by waste, monthly totals, drum utilization) | `GET /bmg/reports` |
| 2 | **Export CSV** — pick `yield-by-drum` / `duration-by-waste` / `monthly-totals` | `GET /bmg/reports/export-csv/{reportKey}` |
### 4.6 BMG Dashboard Workflow & Simulation Guide

| # | Action | Outcome |
|---|---|---|
| 1 | Open `/dashboard/bmg` and review drum status cards, active batches, and report shortcuts | Confirms operational status and what needs attention |
| 2 | Start a new batch if a drum is available | Creates a batch and flips the drum to `processing` |
| 3 | Add input weight and category | Captures waste intake and timestamps the cycle |
| 4 | Log process observations and monitor until completion | Keeps the batch status visible and auditable |
| 5 | Mark the batch complete and record output weight | Computes yield and prepares the drum for the next cycle |
| 6 | Review BMG reports to compare yield, duration, and utilization | Provides actionable insights for facilities planning |

> Simulation guide: use sample values such as `40 kg input` and `25 kg output` to demonstrate the full batch lifecycle without live composting activity.
---

## 5. Report Viewer (`report_viewer`)

**Sidebar sections visible:** Main · Facility Operations (read-only)

**Landing page:** `/dashboard/reports`

This role is read-only across the system. They can browse the same dashboards and reports that other roles see, but cannot mutate state.

| # | Action | Route |
|---|---|---|
| 1 | Open the cross-module **Reports** module picker | `GET /reports` |
| 2 | View **Clinic analytics** | `GET /reports/clinic` |
| 3 | View **Counselling analytics** | `GET /reports/counselling` |
| 4 | View **Inventory analytics** | `GET /reports/inventory` |
| 5 | View the **BMG Drum** list (read) | `GET /bmg/drums` |
| 6 | View a single drum (read) | `GET /bmg/drums/{id}` |
| 7 | View the **BMG Batch** list (read) | `GET /bmg/batches` |
| 8 | View a single batch (read) | `GET /bmg/batches/{id}` |
| 9 | View **Waste Categories** (read) | `GET /bmg/categories` |
| 10 | View **BMG Reports** dashboard | `GET /bmg/reports` |
| 11 | **Export BMG CSV** | `GET /bmg/reports/export-csv/{reportKey}` |
| 12 | View **Reorder Requests** (read) | `GET /inventory/reorders` |
| 13 | View a single reorder (read) | `GET /inventory/reorders/{id}` |

> All write operations (`POST /bmg/...`, `POST /admin/...`, `POST /clinic/...`, `POST /counselling/...`, `POST /inventory/...`) are blocked at the route filter level for `report_viewer`.

---

## 6. Employee (`employee`)

**Sidebar sections visible:** Main only (no role-specific section in the sidebar).

**Landing page:** `/dashboard/employee`

| # | Action | Route |
|---|---|---|
| 1 | Open the **Employee Portal** (reads your own `employees` row) | `GET /dashboard/employee` |
| 2 | Click your avatar (top-right) → **My Profile** | `GET /profile` |
| 3 | Update name / email / password → **Save** (min 8 chars if changing password) | `POST /profile/update` |
| 4 | Sign out (top-right user menu) | `GET /logout` |
| 5 | Walk up to the clinic kiosk and scan your RFID / QR to check in or admit yourself | `GET /iot/kiosk` → `POST /iot/scan` |
| 6 | View your own clinic/medical record history | `GET /dashboard/employee` / profile panels |
| 7 | If authorized, create a direct counselling referral for a student | `GET /clinic/referrals/create` → `POST /clinic/referrals/store` |

> The employee portal is a read-only landing for personal information. Employees can also use the clinic kiosk for self-admission and review their own medical record history. There is no employee-side "book appointment" UI — appointment booking is performed by the counsellor. Employees with referral permission can still initiate counselling referrals for students via `/clinic/referrals/create`.

---

## 7. Student (`student`)

**Sidebar sections visible:** Main · Student

**Landing page:** `/dashboard/student`

### 7.1 Self-service portal

| # | Action | Route |
|---|---|---|
| 1 | Open the **Student Portal** (shows upcoming appointments, queue status, and support options) | `GET /dashboard/student` |
| 2 | Scroll to **My Appointments** section | `dashboard/student#upcoming` (anchor on the same page) |
| 3 | Review the **Support Resources** section for referral updates and next steps | `dashboard/student` |
| 4 | Click avatar → **My Profile** | `GET /profile` |
| 5 | Update name / email / password → **Save** | `POST /profile/update` |

### 7.2 Walk-in via kiosk (primary path)

| # | Action | Route |
|---|---|---|
| 1 | Walk up to the clinic kiosk tablet | `GET /iot/kiosk` *(public)* |
| 2 | Scan your QR code (printed ID) or tap your RFID card; optionally select `chief_complaint`, `triage_priority`, `purpose` (clinic or counselling) | `POST /iot/scan` |
| 3 | If offline, the kiosk buffers scans and syncs when reconnected | `POST /iot/sync` |

### 7.3 Lobby display (passive viewing)

| # | Action | Route |
|---|---|---|
| 1 | Glance at the lobby TV to see your name / queue position | `GET /consultations/queue/display` *(public)* |
| 2 | TV auto-refreshes state | `GET /consultations/queue/state.json` *(public, every 1 s)* |

> **Note:** The student is the *subject* of clinical and support data, not the *actor* for support consultations. The counsellor and clinic staff enter and update those records; the student reads them on the portal.

---

## Cross-Module Flows

These end-to-end sequences involve more than one role and cross module boundaries.

### A. Clinic → Counselling Referral

```
1. clinic_staff at /clinic/consultations/{id}
   → POST /clinic/referrals/store
     • Validates student + reason + priority (routine / urgent / emergency)
     • Creates a `referrals` row with direction='clinic_to_counselling'
     • Auto-generates a QR code PNG at writable/bmg_qr_codes/{token}.svg
     • Broadcasts a notification to ALL counsellors
     • Returns to /clinic/consultations/{consultId} with success flash

2. counsellor at /counselling/referrals
   • Sees the pending referral
   → POST /counselling/referrals/accept/{id}
     • Sets referred_to = self, status='accepted'
     • Notifies the originating clinic staff
   → POST /counselling/referrals/decline/{id}
     • Sets status='declined'

### A2. Employee Referral to Counselling

```
1. employee at /clinic/referrals/create
   → GET /clinic/referrals/create
     • Opens the referral form with student search
     • Searches for a student by name, ID, QR, or RFID via `GET /clinic/students/search`
     • Selects the student and sets reason + priority
   → POST /clinic/referrals/store
     • Creates a `referrals` row with direct student source and direction='clinic_to_counselling'
     • Auto-generates a QR code PNG
     • Notifies all counsellors
     • Returns with success flash
2. counsellor at /counselling/referrals
   • Sees the pending referral
   → POST /counselling/referrals/accept/{id}
     • Sets referred_to = self, status='accepted'
     • Notifies the originating employee
   → POST /counselling/referrals/decline/{id}
     • Sets status='declined'

3. Optional: student or counsellor scans the QR
   → GET /referral/verify/{token}   (PUBLIC)
     • Marks qr_verified_at + qr_verified_by
   → GET /referral/qr/{id}   (downloads PNG; role: admin/clinic/counsellor)
```

### B. Student walk-in via kiosk → clinic queue

```
1. student at /iot/kiosk (no auth)
   → POST /iot/scan   (AJAX)
     • Identifies the student by QR or RFID
     • If the student has a counselling appointment today, marks it 'confirmed'
     • If purpose=clinic, creates a `consultations` row in 'in_progress'
     • If purpose=counselling, logs check-in only
   → Returns JSON to the kiosk UI

2. clinic_staff at /consultations/queue
   • Sees the new entry in real time (polls /consultations/queue/state.json every 1s)
   → POST /consultations/call-next   (auto-picks highest-priority waiting)
   → POST /consultations/start/{id}  (mark "in room")
   → Record vitals, diagnosis, treatment
   → POST /consultations/complete/{id}   (status=completed or follow_up)
```

### C. Counsellor books appointment → student notified → attendance tracked

```
1. counsellor at /counselling/appointments/create/{studentId}
   → POST /counselling/appointments/store
     • Validates the chosen slot
     • Runs SchedulingOptimizer::predictNoShowProbability()
     • Stores appointment with predicted risk %
     • Creates a student-targeted notification
     • Returns to /counselling/appointments/{id} with "Predicted No-Show Risk: NN.N%"

2. student logs in (or sees it on /dashboard/student)
   • Sees the upcoming appointment
   • Walks to the kiosk to confirm attendance → status='confirmed'

3. counsellor at /counselling
   → POST /counselling/appointments/start/{id}    (in-room)
   → POST /counselling/appointments/complete/{id} (saves session_notes; resets no-show counter)
   → POST /counselling/appointments/no-show/{id}  (3 in a row → welfare_alert fires)
```

### D. Support follow-up & referral

```
1. counsellor at /counselling/appointments/{id}
   → POST /counselling/appointments/complete/{id}
     • Records support notes and follow-up actions
     • Updates the appointment status and notifies the student
     • Keeps a visible history for future reference
```

### E. Facilities staff records a full batch lifecycle

```
1. facilities_staff at /bmg/drums
   → POST /bmg/drums/mark-processing/{id}    (or via "Start Batch")
   → GET  /bmg/batches/startOnDrum/{drumId}
   → POST /bmg/batches/create
     • drum_id, waste_category_id, input_weight_kg
     • Validates input_weight ≤ drum.capacity_kg
     • Creates the batch + first inputs row + flips drum to 'processing'
   → GET /bmg/batches/{id}   (view tabbed: inputs / process logs / outputs)

2. Throughout the cycle:
   → GET  /bmg/batches/{id}/inputs/create         → POST .../inputs/store
   → GET  /bmg/batches/{id}/process-logs/create   → POST .../store
     (logs temperature, moisture, free-text observation)

3. Decomposition complete:
   → POST /bmg/batches/{id}/mark-completed   OR
   → POST /bmg/drums/complete-and-idle/{drumId}
     (completes batch + flips drum to 'idle')

4. Record the harvest:
   → GET  /bmg/batches/{id}/output/create        → POST .../output/store
     • Validates output_weight_kg ≤ input_weight_kg
     • Calculates yield_percentage + duration_days on the batch
     • Re-flips drum to 'processing' if more inputs are added later
```

### F. Login security & audit trail (cross-cutting)

```
1. POST /login
   • Rate-limited (5 fails / 15 min) keyed by md5(lowercased email)
   • ALWAYS runs password_verify() even for unknown emails (timing equalization)
   • Generic "Invalid email or password" — no enumeration leak
   • On success: session_regenerate(true) and an audit_logs row "login_success"
   • On fail:   audit_logs row "login_failed" with reason enum

2. AuthFilter on every dashboard/admin/clinic/counselling/inventory/reports
   • Stashes intended URL on GET failure
   • RoleFilter on top of that for role-restricted routes

3. Every mutating controller method writes to audit_logs
   (action, module, entity_type, entity_id, old_values, new_values, ip, hash, previous_hash)
   • Hash-chain integrity checkable at /admin/audit/verify
   • Export of filtered logs at /admin/audit/export — the export itself is also audited
```

### G. Profile & session management (any role)

```
1. /profile  (top-right user menu → "My Profile")
   → POST /profile/update
     • Optional password change (min 8 chars; delegates to UserModel::setPassword)
     • Updates session first_name/last_name/full_name
     • Returns JSON for AJAX, redirect for native form

2. /logout  (top-right user menu → "Sign out" with confirmation)
   • Writes an audit_logs row "logout"
   • session_destroy() (data + cookie)
   • Redirects to /login
```

---

## CRUD Matrix by Role

Legend: **✓** = allowed, **◐** = read-only, **·** = not allowed

### Admin (`admin`)

| Entity | C | R | U | D | Notes |
|---|---|---|---|---|---|
| Users | ✓ | ✓ | ✓ | ✓ (3 modes) | Bulk ops; self-protection on toggle/delete |
| Roles | ✓ | ✓ | ✓ | · | Never hard-deleted; toggle permissions |
| Permissions | · | ✓ | · | · | Toggled via roles |
| Audit logs | · | ✓ | · | · | Export only |
| Students / Employees | ✓ | ✓ | ✓ | · | |
| Consultations | ✓ | ✓ | ✓ | ✓ | Complete + status updates |
| Treatments | ✓ | ✓ | · | · | |
| Referrals (out) | ✓ | ✓ | · | · | |
| Counselling appointments | ✓ | ✓ | ✓ | ✓ | All status transitions |
| Support notes | ✓ | ✓ | · | · | |
| Follow-up alerts | ✓ | ✓ | ✓ | · | |
| Availability | ✓ | ✓ | ✓ | · | |
| Medicines / batches | ✓ | ✓ | ✓ | · | |
| Reorder requests | ✓ | ✓ | ✓ | ✓ | Full state machine |
| BMG drums | ✓ | ✓ | ✓ | ✓ | Archive is admin-only |
| BMG batches | ✓ | ✓ | ✓ | ✓ | |
| BMG inputs / process logs / outputs | ✓ | ✓ | · | · | |
| Waste categories | ✓ | ✓ | ✓ | ✓ (if unused) | |
| Notifications | · | ✓ | ✓ | · | Mark read |
| Reports (cross-module) | · | ✓ + export | · | · | Read + CSV |

### Clinic Staff (`clinic_staff`)

| Entity | C | R | U | D |
|---|---|---|---|---|
| Users / Roles / Audit | · | · | · | · |
| Students | ✓ | ✓ | ✓ | · |
| Employees | ✓ | ✓ | ✓ | · |
| Consultations | ✓ | ✓ | ✓ | ✓ (complete) |
| Treatments | ✓ | ✓ | · | · |
| Referrals (clinic → counselling) | ✓ | ✓ | · | · |
| Medicines / batches | ✓ | ✓ | ✓ | · |
| Reorder requests | ✓ | ✓ | ✓ | ✓ (approve / order / receive / cancel) |
| Counselling appointments / support notes / follow-up alerts / availability | · | · | · | · |
| BMG modules | · | · | · | · |
| Reports / Audit | · | · | · | · |
| Own profile | · | ✓ | ✓ | · |
| Notifications | · | ✓ | ✓ | · |

### Counsellor (`counsellor`)

| Entity | C | R | U | D |
|---|---|---|---|---|
| Users / Roles / Audit | · | · | · | · |
| Students | · (via `?student_id=` query) | ✓ (via support form) | · | · |
| Consultations | · | · | · | · |
| Treatments | · | · | · | · |
| Referrals (incoming) | · | ✓ | ✓ (accept / decline) | · |
| Counselling appointments | ✓ | ✓ | ✓ | ✓ (cancel / no-show) |
| Support notes | ✓ | ✓ | · | · |
| Follow-up alerts | ✓ | ✓ | ✓ | · |
| Availability (own) | ✓ | ✓ | ✓ | ✓ (soft) |
| BMG modules / Medicines | · | · | · | · |
| Own profile | · | ✓ | ✓ | · |
| Notifications | · | ✓ | ✓ | · |

### Facilities Staff (`facilities_staff`)

| Entity | C | R | U | D |
|---|---|---|---|---|
| Users / Roles / Audit | · | · | · | · |
| Drums | ✓ | ✓ | ✓ | ✓ (only if no batches) |
| Batches | ✓ | ✓ | ✓ (mark-completed / cancel) | · |
| Inputs | ✓ | ✓ | · | · |
| Process logs | ✓ | ✓ | · | · |
| Outputs | ✓ | ✓ | · | · |
| Waste categories | ✓ | ✓ | ✓ | · (admin-only delete) |
| Reports (BMG) | · | ✓ + export | · | · |
| Own profile | · | ✓ | ✓ | · |
| Notifications | · | ✓ | ✓ | · |

### Report Viewer (`report_viewer`)

| Entity | C | R | U | D |
|---|---|---|---|---|
| All entities | · | ◐ (read-only across modules) | · | · |
| BMG drums / batches / categories | · | ✓ | · | · |
| BMG inputs / logs / outputs | · | ✓ (via batch show) | · | · |
| Reorder requests | · | ✓ | · | · |
| Reports (clinic / counselling / inventory / BMG) | · | ✓ + CSV | · | · |
| Own profile | · | ✓ | ✓ | · |
| Notifications | · | ✓ | ✓ | · |

### Employee (`employee`)

| Entity | C | R | U | D |
|---|---|---|---|---|
| All entities | · | · | · | · |
| Own profile | · | ✓ | ✓ | · |
| Own employee record | · | ✓ (on portal) | · | · |
| Referrals (clinic → counselling) | ✓ | ✓ | · | · |
| Notifications | · | ✓ | ✓ | · |
| Kiosk operations | ✓ (public) | · | · | · |

> Employees with referral permission can create student counselling referrals from `/clinic/referrals/create`.

### Student (`student`)

| Entity | C | R | U | D |
|---|---|---|---|---|
| All entities | · | · | · | · |
| Own profile | · | ✓ | ✓ | · |
| Own student record | · | ✓ (on portal) | · | · |
| Own appointments | · | ✓ (on portal) | · | · |
| Own consultations | · | ✓ (on portal) | · | · |
| Live queue position | · | ✓ | · | · |
| Notifications | · | ✓ | ✓ | · |
| Kiosk operations | ✓ (public) | · | · | · |

---

## Public (Unauthenticated) Endpoints

These routes are accessible **without** a SYNAPSE login. Most of them are intentional public surfaces (lobby TV, kiosk tablet, public QR verifier). All of them are rate-limited and audit-logged where appropriate.

| Route | Method | Purpose |
|---|---|---|
| `GET /` | `AuthController::showLogin` | Login page (redirects to `/dashboard` if already logged in) |
| `GET /login` | `AuthController::showLogin` | Login page |
| `POST /login` | `AuthController::attemptLogin` | Rate-limited; generic error; session-fixation defense |
| `GET /logout` | `AuthController::logout` | Sign out (technically auth-required to be useful) |
| `GET /testai` | `Home::testAi` | Developer-only — view last AI prediction rows |
| `GET /ui` | `UiController::showcase` | Developer-only — UI component reference |
| `GET /consultations/queue/display` | `Clinic\ConsultationController::display` | Lobby TV — first names only |
| `GET /consultations/queue/state.json` | `Clinic\ConsultationController::state` | AJAX refresh for TV + staff queue UI |
| `GET /iot/kiosk` | `Iot\KioskController::index` | Kiosk tablet |
| `POST /iot/scan` | `Iot\KioskController::processScan` | Kiosk scan handler (QR or RFID) |
| `POST /iot/sync` | `Iot\KioskController::syncBuffer` | Kiosk offline-buffer sync |
| `GET /referral/verify/{token}` | `Clinic\ReferralController::verifyQr/$1` | Public QR referral verifier |

> **CSRF caveat:** the CSRF filter excludes `login` and `logout` (see [`app/Config/Filters.php`](../app/Config/Filters.php)). All other POSTs require a valid CSRF token.

---

**End of workflows document.** This file lives at [`docs/WORKFLOWS.md`](../docs/WORKFLOWS.md). For the full controller inventory, see the appendix in this document; for the per-route filter list, see [`app/Config/Filters.php`](../app/Config/Filters.php) and [`app/Config/Routes.php`](../app/Config/Routes.php).
