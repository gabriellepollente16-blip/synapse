# SYNAPSE — System Architecture Documentation

> **A Unified Web-Based Platform for Campus Health, Counselling, and Biodegradable Waste Management**  
> An IoT- and QR/RFID-enabled system consolidating Foundation University's clinic operations, guidance counselling services, and Biodegradable Waste Management (BMG) composting tracking under a single, role-aware web application.

[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)](#tech-stack)
[![CodeIgniter](https://img.shields.io/badge/CodeIgniter-4.7-EF4223?logo=codeigniter&logoColor=white)](#tech-stack)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1?logo=mysql&logoColor=white)](#tech-stack)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

---

## Table of Contents

1. [Project Overview](#project-overview)
2. [Background & Rationale](#background--rationale)
3. [Objectives](#objectives)
4. [Project Structure](#project-structure)
5. [UI/UX Layout](#uiux-layout)
6. [Database Architecture](#database-architecture)
7. [Tech Stack](#tech-stack)
8. [Security & Compliance](#security--compliance)
9. [Module Breakdown](#module-breakdown)
10. [System Workflows](#system-workflows)
11. [Key Features](#key-features)
12. [Scope & Limitations](#scope--limitations)

---

## Project Overview

### What is SYNAPSE?

**SYNAPSE** is Foundation University's capstone project that addresses two structurally identical operational problems through a single, unified web platform:

1. **Health & Counselling Fragmentation** — Clinic and guidance counselling records are maintained separately with no shared data layer, appointments and referrals are tracked on paper, and medicine inventory is managed without procurement integration.

2. **BMG Composting Opacity** — The university fabricates multiple Biodegradable Waste Management (BMG) rotating drum composting units, but no digital system exists to record input weight, decomposition duration, or fertilizer yield.

### The Three Core Modules

SYNAPSE consolidates **three core modules** under one login, one audit trail, and one quality standard:

| Module | Domain | Primary Users |
|--------|--------|---------------|
| **Clinic Core** | Health services, medicine inventory, RFID check-in | Doctor, Nurse, Clinic Administrator |
| **Counselling Core** | Guidance counselling appointments, intake notes, QR referrals | Guidance Counsellor |
| **Facilities/Sustainability Core (BMG Tracking)** | Biodegradable waste input/process/output tracking, drum management | Facilities/Composting Staff |

### The Integration Philosophy

> All three modules share the **same integration philosophy**: replacing fragmented, informal institutional record-keeping with structured, centralized digital systems governed by one login, one RBAC, and one audit trail. However, **the Facilities/Sustainability module operates independently** of the Clinic and Counselling modules and does **not** exchange data with them. Integration is limited to shared authentication, role-based governance, and institutional audit logging.

### Stakeholders

- **Clinic Staff** (Doctor, Nurse, Clinic Administrator)
- **Guidance Counsellors**
- **Facilities/Composting Staff** (BMG operators)
- **System Administrators**
- **Report Viewers** (e.g., University Administration, Environmental Management Office)
- **Students and Employees** (HR-integrated user base)

---

## Background & Rationale

### Why a Unified Platform?

#### Regulatory Compliance

- **Republic Act No. 10173 (Data Privacy Act of 2012)** + **NPC Circular No. 2023-06**: Mandate organizational, physical, and technical safeguards for sensitive personal data, including health and counselling records. Healthcare data is classified as **sensitive personal information** requiring the highest level of protection.
- **Republic Act 9003 (Ecological Solid Waste Management Act of 2000)**: Mandates institutions to adopt systematic composting programs for biodegradable materials. However, only 245 of 11,637 Materials Recovery Facilities are operational nationwide as of 2021 (Commission on Audit, 2023).

#### Operational Efficiency

- On the health side: addition of employee records alongside student records, combined with cross-referrals between clinic and counselling staff, increases data volume and sensitivity.
- On the facilities side: deployment of multiple BMG units creates a management challenge that manual logbooks cannot scale to meet.

#### Data Governance

Hybrid cryptographic access control, combining **role-based authorization with application-layer encryption**, provides significantly stronger protection than RBAC alone (Chinnasamy & Deepalakshmi, 2022). SYNAPSE extends this governance discipline to its Facilities/Sustainability module, ensuring consistent accountability across all three modules even though their data domains remain separate.

#### Academic Contribution

SYNAPSE produces **real-world datasets across two otherwise unrelated institutional domains** — health service delivery and composting operations — generated under actual operating conditions. This addresses gaps left by prior lab-controlled and fragmented-system studies.

---

## Objectives

### General Objective

To develop SYNAPSE, a unified web-based platform that consolidates Foundation University's clinic, counselling, and facilities/composting operations under a single access-controlled system, supporting data-driven institutional decision-making across health services and sustainability operations.

### Specific Objectives

#### Clinic and Counselling Module

1. Develop clinic and counselling record modules under a **shared database** and **unified RBAC system**.
2. Implement a **QR-based referral verification** feature that generates a unique, scannable code for each referral or excuse slip, allowing clinic and counselling staff to instantly verify authenticity.
3. Implement an **RFID-based check-in** feature that allows clinic staff to scan a student's or employee's institutional ID to retrieve their record.
4. Implement a **medicine inventory module integrated with the procurement/purchasing process**, with automated reorder triggers based on stock thresholds and expiration tracking.
5. **Extend system scope to include employee health and counselling records**, with employee data gathered in coordination with the HR Department.

#### Facilities/Sustainability Module (BMG)

6. Develop a web-based system that allows authorized users to record the **daily input weight or volume of biodegradable waste** loaded into each BMG unit.
7. Implement a **process-tracking feature** that monitors and calculates the exact duration (in days) required for each batch to fully decompose into fertilizer.
8. Implement an **output-tracking feature** that records the final weight or volume of fertilizer produced per batch, accounting for expected yield loss during decomposition.
9. Design the system to support **waste categorization** (e.g., pure food waste, twigs and leaves, or mixed waste) to explain variance in decomposition duration across batches.
10. Create a **scalable dashboard architecture** that allows individual BMG units to be added, edited, or archived as the university's composting program expands or contracts.
11. Develop **reporting and analytics features** that present input, duration, and yield data in a format usable by the university's facilities or environmental management office.

#### System-Wide

12. Evaluate all three modules' usability and functionality through **User Acceptance Testing (UAT)** with representative end users, using the **ISO/IEC 25010 quality model** as a shared reference framework.

---

## Project Structure

### Directory Organization

```
synapse_ag/
├── app/                              # Application logic (MVC + Libraries)
│   ├── Common.php                   # Global application helpers
│   ├── Commands/                    # CLI commands
│   │   ├── TestAi.php
│   │   ├── TestCheckin.php
│   │   ├── TestDashboards.php
│   │   └── TestTamper.php
│   ├── Config/                      # Configuration files (28 config modules)
│   ├── Controllers/                 # HTTP request handlers
│   │   ├── AuthController.php       # Login, logout, session lifecycle, rate limiting
│   │   ├── DashboardController.php  # Role-aware landing pages & KPI tiles
│   │   ├── ProfileController.php    # Self-service account management
│   │   ├── NotificationController.php # Real-time alerts & polling
│   │   ├── UiController.php         # Component showcase for developers
│   │   ├── Admin/                   # System administration
│   │   │   ├── UserController.php
│   │   │   ├── RoleController.php
│   │   │   ├── AuditLogController.php
│   │   │   └── PermissionController.php
│   │   ├── Clinic/                  # Clinic operations
│   │   │   ├── StudentController.php
│   │   │   ├── EmployeeController.php  # NEW: Employee record management
│   │   │   ├── ConsultationController.php
│   │   │   ├── TreatmentController.php
│   │   │   ├── ReferralController.php  # QR code generation
│   │   │   ├── VitalsController.php
│   │   │   ├── CheckinController.php   # NEW: RFID-based check-in
│   │   │   └── ProcurementController.php # NEW: Reorder requests to procurement
│   │   ├── Counselling/             # Counselling operations
│   │   │   ├── AppointmentController.php
│   │   │   ├── IntakeController.php   # Intake session notes
│   │   │   └── ReferralController.php   # QR code generation
│   │   ├── Inventory/               # Medicine inventory
│   │   │   ├── MedicineController.php
│   │   │   ├── BatchController.php
│   │   │   ├── TransactionController.php
│   │   │   └── ReorderController.php   # NEW: Procurement integration
│   │   ├── Bmg/                     # NEW: BMG Composting Module
│   │   │   ├── DrumController.php        # BMG unit CRUD
│   │   │   ├── BatchController.php       # Batch lifecycle (input → output)
│   │   │   ├── InputController.php       # Input tracking
│   │   │   ├── ProcessController.php     # Process/duration tracking
│   │   │   ├── OutputController.php      # Output/yield tracking
│   │   │   ├── WasteCategoryController.php # Waste type management
│   │   │   └── BmgReportController.php   # BMG analytics & exports
│   │   ├── Iot/                     # IoT / Kiosk integration
│   │   │   ├── KioskController.php
│   │   │   └── OfflineBufferController.php
│   │   ├── Reports/                 # Cross-module analytics
│   │   │   ├── ClinicReportController.php
│   │   │   ├── CounselingReportController.php
│   │   │   ├── InventoryReportController.php
│   │   │   ├── BmgReportController.php   # NEW
│   │   │   └── ExportController.php
│   │   └── Hri/                     # NEW: HR Integration
│   │       └── EmployeeSyncController.php  # Pull employee data from HR
│   │
│   ├── Models/                      # Database models (~33 models)
│   │   ├── UserModel.php
│   │   ├── StudentModel.php
│   │   ├── EmployeeModel.php         # NEW: Employee identity (HR integration)
│   │   ├── ConsultationModel.php
│   │   ├── ConsultationVitalsModel.php
│   │   ├── CounsellingAppointmentModel.php
│   │   ├── CounsellorAvailabilityModel.php
│   │   ├── IntakeNoteModel.php       # Intake session notes
│   │   ├── MedicineModel.php
│   │   ├── MedicineBatchModel.php
│   │   ├── InventoryTransactionModel.php
│   │   ├── ReorderRequestModel.php   # NEW: Procurement integration
│   │   ├── ReferralModel.php        # With QR code field
│   │   ├── CheckinLogModel.php      # NEW: RFID check-in audit
│   │   ├── BmgDrumModel.php         # NEW: BMG unit master
│   │   ├── BmgBatchModel.php        # NEW: Batch lifecycle
│   │   ├── BmgInputModel.php        # NEW: Input tracking
│   │   ├── BmgProcessModel.php      # NEW: Process tracking
│   │   ├── BmgOutputModel.php       # NEW: Output tracking
│   │   ├── WasteCategoryModel.php   # NEW
│   │   ├── AuditLogModel.php        # Hash-chained
│   │   ├── NotificationModel.php
│   │   ├── OfflineCheckinBufferModel.php
│   │   ├── AiTriagePredictionModel.php
│   │   ├── AiInventoryForecastModel.php
│   │   ├── AiGeneratedSummaryModel.php
│   │   ├── EmergencyContactModel.php
│   │   ├── AllergyModel.php
│   │   ├── TreatmentModel.php
│   │   ├── RoleModel.php
│   │   ├── PermissionModel.php
│   │   ├── RolePermissionModel.php
│   │   ├── UserRoleModel.php
│   │   ├── ClinicStaffScheduleModel.php
│   │   └── SchedulingAnalyticsModel.php
│   │
│   ├── Views/                       # Presentation layer
│   │   ├── layouts/                 # Base templates (header, sidebar, footer)
│   │   ├── auth/                    # Login, password reset
│   │   ├── dashboard/               # Role-specific dashboards
│   │   ├── clinic/                  # Clinic UI
│   │   ├── counselling/             # Counselling UI
│   │   ├── inventory/               # Inventory UI
│   │   ├── bmg/                     # NEW: BMG UI
│   │   │   ├── dashboard.php        # Multi-drum overview
│   │   │   ├── drums/               # Drum management
│   │   │   ├── batches/             # Batch lifecycle views
│   │   │   └── reports/             # BMG reports
│   │   ├── admin/                   # Admin panel
│   │   ├── reports/                 # Cross-module reports
│   │   ├── errors/                  # 403, 404, 500, 503
│   │   └── components/              # Reusable UI components
│   │
│   ├── Libraries/                   # Business logic & domain algorithms
│   │   ├── AppExceptionHandler.php
│   │   ├── TriageAssistant.php
│   │   ├── InventoryForecaster.php
│   │   ├── SchedulingOptimizer.php
│   │   ├── ConflictDetector.php
│   │   ├── ReportSummarizer.php
│   │   ├── BmgYieldCalculator.php   # NEW: Yield % and mass reduction
│   │   ├── BmgDurationCalculator.php # NEW: Decomposition duration
│   │   └── QrCodeGenerator.php      # NEW: Referral QR codes
│   │
│   ├── Filters/                     # HTTP middleware
│   │   ├── AuthFilter.php
│   │   └── RoleFilter.php
│   │
│   ├── Helpers/                     # Autoloaded utility functions
│   │   ├── error_context_helper.php
│   │   ├── ui_pagination_helper.php
│   │   ├── search_highlight_helper.php
│   │   └── like_escape_helper.php
│   │
│   ├── Database/
│   │   ├── Migrations/              # Database version control
│   │   └── Seeds/                   # Dev/test data
│   │
│   └── Language/
│       └── en/                      # English language strings (i18n)
│
├── public/                          # Web-accessible directory
│   ├── index.php                    # Application entry point
│   ├── robots.txt
│   └── assets/
│       ├── css/                     # Stylesheets
│       ├── js/                      # Vanilla JS + Chart.js
│       ├── images/                  # Static images, logos, icons
│       ├── qr/                      # NEW: Generated QR code images
│       └── fonts/                   # Inter, Outfit, JetBrains Mono
│
├── writable/                        # Writable directory
│   ├── cache/                       # Application cache
│   ├── logs/                        # Error and activity logs
│   ├── session/                     # Session data storage
│   ├── uploads/                     # User-uploaded files
│   ├── debugbar/                    # CodeIgniter debugbar data
│   └── bmg_qr_codes/                # NEW: Generated QR codes for referrals
│
├── vendor/                          # Composer dependencies
│   ├── codeigniter4/                # CodeIgniter 4.7 framework
│   ├── phpunit/                     # Testing framework
│   ├── fakerphp/                    # Test data generation
│   ├── chillerlan/                  # NEW: QR code library
│   ├── picqer/                      # NEW: PHP barcode generator
│   └── [other dependencies]
│
├── tests/                           # Unit & integration tests
│   ├── _support/
│   ├── unit/
│   └── database/
│
├── composer.json                    # PHP package definitions
├── phpunit.dist.xml
├── spark                            # CodeIgniter CLI runner
├── .env
└── README.md
```

---

## UI/UX Layout

### High-Level UI Architecture

```
┌──────────────────────────────────────────────────────────────────┐
│                       SYNAPSE — Login Page                       │
│  ┌────────────────────────────────────────────────────────────┐  │
│  │  SYNAPSE — Foundation University                          │  │
│  │  "Campus Health, Counselling & Sustainability Platform"   │  │
│  │                                                             │  │
│  │  Email:    [_________________]                             │  │
│  │  Password: [_________________]                             │  │
│  │  2FA Code: [_________________]  (if enabled)               │  │
│  │                                                             │  │
│  │  [Login]    [Forgot Password]                              │  │
│  │                                                             │  │
│  │  © 2026 Foundation University                              │  │
│  └────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────┘
                              ↓ (upon login — role-based routing)
┌──────────────────────────────────────────────────────────────────┐
│ SYNAPSE  │  ☰ Menu  │  🔔 Notifications (3)  │  👤 Profile      │
├──────────┴───────────────────────────────────────────────────────┤
│ ┌──────┐ │  Role-Aware Dashboard (KPI Tiles)                     │
│ │ Logo │ │  ┌──────────────────────────────────────────────────┐  │
│ └──────┘ │  │  [Pending Consultations: 12]  [Patients: 45]    │  │
│          │  │  [Low-Stock Meds: 3]         [Referrals: 5]     │  │
│ Sidebar  │  │  [Active Batches: 4 BMG]    [Referrals: 5]         │  │
│ ┌──────┐ │  └──────────────────────────────────────────────────┘  │
│ │Menu  │ │                                                          │
│ │Items │ │  Module-Specific Quick Actions:                         │
│ └──────┘ │  • Clinic: New Consultation, View Queue                  │
│          │  • Counselling: Book Appointment, View Referrals         │
│ Role:    │  • BMG: Log New Input, Mark Batch Complete              │
│ [Clinic] │  • Admin: User Management, Audit Log                    │
│          │                                                          │
│          │  Charts: [Line] [Pie] [Bar] (Chart.js)                  │
│ Help │   │                                                          │
│ Logout   │  Recent Activity / Alerts                               │
│          │  • New referral from Dr. Smith                          │
│          │  • Aspirin stock low                                    │
│          │  • BMG Drum #3 batch completed (Yield: 32%)             │
└──────────┴──────────────────────────────────────────────────────┘
```

### Authentication & Role Routing Flow

```
Login Page
  ↓ Email + Password (+ optional 2FA)
  ↓ Validate credentials
  ↓ Role lookup
  ↓
  ├── admin             → /dashboard/admin
  ├── clinic_staff      → /dashboard/clinic
  ├── counsellor        → /dashboard/counsellor
  ├── facilities_staff  → /dashboard/bmg           (NEW)
  ├── report_viewer     → /dashboard/reports       (NEW)
  ├── employee          → /dashboard/employee      (NEW)
  └── student           → /dashboard/student
```

### Module-Specific UI

#### 1. **Clinic Core UI**

```
┌──────────────────────────────────────────────────────────────────┐
│ Clinic Dashboard                                                  │
├──────────────────────────────────────────────────────────────────┤
│ [Queue Display]    [Today's Consultations]   [Low-Stock Meds]     │
│   Now Serving: 5     Total: 12                  3 items            │
│   Next: 6                                                       │
│                                                                   │
│ ── RFID Check-In Tab ──                                          │
│  [Scan ID Card] → System retrieves student/employee record       │
│  ✓ John Doe (Student #2024-001) — Logged at 09:15 AM            │
│                                                                   │
│ ── New Consultation Tab ──                                       │
│  Patient: [Search ▼]    Chief Complaint: [____________]          │
│  Vitals:   BP [___] HR [___] Temp [___]                          │
│  AI Triage: [High] (confidence: 87%)                             │
│  Diagnosis: [____________]  Notes: [____________]                │
│                                                                   │
│ ── Referral Tab (QR-Generated) ──                                │
│  Create Referral to Counselling                                   │
│  [Generate QR Code] → Downloads PNG + links to referral record   │
└──────────────────────────────────────────────────────────────────┘
```

#### 2. **Counselling Core UI**

```
┌──────────────────────────────────────────────────────────────────┐
│ Counselling Dashboard                                             │
├──────────────────────────────────────────────────────────────────┤
│ [Today's Appointments]   [Pending Referrals]   [No-Shows]        │
│   8 appointments             5 pending            2 this week    │
│                                                                   │
│ ── Book Appointment ──                                            │
│  Student/Employee: [Search ▼]                                    │
│  Counsellor:      [Dr. Cruz ▼]                                   │
│  Date/Time:       [2026-07-12 14:00]                             │
│  Type:            [Initial / Follow-up]                          │
│  Send Reminder:   ☑ (24h before)                                 │
│                                                                   │
│ ── Intake Session Notes ──                                        │
│  Appointment: [2026-07-12 14:00 - John Doe ▼]                    │
│  Presenting Concern: [_________________________________]         │
│  Session Notes: [_________________________________]              │
│  Action Items:   [_________________________________]             │
│  [Save Notes]                                                     │
│                                                                   │
│ ── Generate Referral QR (to Clinic) ──                           │
│  [Generate QR] → Scannable code for clinic verification          │
└──────────────────────────────────────────────────────────────────┘
```

#### 3. **BMG (Biodegradable Waste Management) UI** — *NEW*

```
┌──────────────────────────────────────────────────────────────────┐
│ BMG Dashboard — Drums Overview                                    │
├──────────────────────────────────────────────────────────────────┤
│ [Total Drums: 6]  [Active Batches: 4]  [Idle: 2]                │
│                                                                   │
│ ┌────────────┐ ┌────────────┐ ┌────────────┐ ┌────────────┐    │
│ │  Drum #1   │ │  Drum #2   │ │  Drum #3   │ │  Drum #4   │    │
│ │  Status:   │ │  Status:   │ │  Status:   │ │  Status:   │    │
│ │  Processing│ │  Idle      │ │  Processing│ │  Idle      │    │
│ │  Day 12/21 │ │  —         │ │  Day 5/14  │ │  —         │    │
│ │  [Details] │ │  [Add Batch│ │  [Details] │ │  [Add Batch│    │
│ └────────────┘ └────────────┘ └────────────┘ └────────────┘    │
│                                                                   │
│ ── Input Tracking (New Batch) ──                                 │
│  Select Drum:  [Drum #1 ▼]                                       │
│  Waste Type:   [Food Waste ▼] / [Twigs & Leaves] / [Mixed]       │
│  Input Weight: [___] kg                                          │
│  Start Date:   [Auto: today]                                     │
│  [Save] → Drum #1 status = "Processing"                         │
│                                                                   │
│ ── Process Tracking (Mark as Completed) ──                       │
│  Active Batch: Drum #1, started 2026-06-30                       │
│  Completion Date: [2026-07-20]                                   │
│  [Mark Completed] → Computes Total Duration: 20 days            │
│                                                                   │
│ ── Output Tracking (Yield Analytics) ──                          │
│  Completed Batch: Drum #1 (input: 50 kg)                        │
│  Output Weight: [___] kg                                         │
│  [Save] → Computes Yield %: 32%                                  │
│  [BLOCKED] if output > input (validation rule)                   │
│                                                                   │
│ ── Analytics ──                                                  │
│  • Avg. duration by waste type                                   │
│  • Yield % by drum                                               │
│  • Total fertilizer produced (kg) this month                      │
│  • [Export PDF] [Export CSV]                                     │
└──────────────────────────────────────────────────────────────────┘
```

#### 4. **Admin Panel UI**

```
┌──────────────────────────────────────────────────────────────────┐
│ Admin Dashboard                                                   │
├──────────────────────────────────────────────────────────────────┤
│ [User Count]  [Active Sessions]  [Audit Entries]  [Drum Status] │
│   245 users      18 online         12,345 today     6 drums     │
│                                                                   │
│ ── User Management ──                                            │
│  Search: [____________]  Role: [All ▼]  Status: [Active ▼]      │
│  [+ New User] [Edit] [Deactivate] [Reset Password]               │
│                                                                   │
│ ── Role & Permissions ──                                         │
│  Roles: admin, clinic_staff, counsellor, facilities_staff,       │
│         report_viewer, employee, student                         │
│  Permission Matrix:  Module × Action grid                        │
│                                                                   │
│ ── Audit Log Viewer ──                                           │
│  Filter by: [User] [Module] [Action] [Date Range]                │
│  Each entry: Request ID, User, Action, Old → New values,         │
│              Hash chain integrity check                          │
└──────────────────────────────────────────────────────────────────┘
```

### Design System

- **Colors**: Minimal palette (primary blue, warning orange, success green, error red, plus an "earthy" green for BMG module)
- **Typography**: Inter (body) + Outfit (headings) + JetBrains Mono (code)
- **Components**:
  - Pill buttons, hairline borders, no heavy shadows
  - Status badges (Idle/Processing, Active/Completed)
  - Drum card widgets for BMG dashboard
  - QR code modals for referral verification
- **Accessibility**:
  - ARIA labels on all interactive elements
  - Keyboard navigation support
  - WCAG AA contrast compliance
  - Focus indicators

---

## Database Architecture

### Database Tables Overview (≈ 28+ tables)

The database is organized into **eight logical groups**:

1. **Auth & RBAC** (5 tables)
2. **User Identity** (Students + Employees) (2 tables)
3. **Clinic Operations** (5 tables)
4. **Counselling Operations** (3 tables)
5. **Medicine Inventory & Procurement** (4 tables)
6. **BMG Composting Module** (6 tables) — *NEW*
7. **Referrals & Notifications** (3 tables)
8. **Audit, AI, Analytics** (3 tables)

---

### 1. Authentication & Authorization (5 tables)

```sql
-- Users (unified for students, employees, staff)
users
├─ id (BIGINT, PK)
├─ email (VARCHAR, UNIQUE)
├─ password_hash (VARCHAR) -- bcrypt
├─ first_name, last_name, middle_name
├─ phone, avatar_url
├─ is_active (BOOLEAN)
├─ email_verified_at (TIMESTAMP)
├─ totp_secret (VARCHAR, Base32) -- 2FA
├─ two_factor_enabled (BOOLEAN)
├─ backup_codes (JSON)
├─ last_login_at (TIMESTAMP)
├─ user_type (ENUM: 'student', 'employee', 'staff') -- NEW
└─ created_at, updated_at

-- Roles
roles
├─ id (INT, PK)
├─ name (VARCHAR, UNIQUE) -- 'admin', 'clinic_staff', 'counsellor',
│                          -- 'facilities_staff', 'report_viewer',
│                          -- 'employee', 'student'  (7 roles)
├─ display_name (VARCHAR)
└─ description (TEXT)

-- Permissions (granular)
permissions
├─ id (INT, PK)
├─ name (VARCHAR, UNIQUE) -- e.g., 'view_bmg_dashboard', 'log_bmg_input'
├─ module (VARCHAR)       -- 'clinic', 'counselling', 'bmg', 'inventory', 'admin'
└─ description (TEXT)

-- Role-Permission junction
role_permissions
├─ role_id (INT, FK)
├─ permission_id (INT, FK)
└─ PRIMARY KEY: (role_id, permission_id)

-- User-Role junction (many-to-many)
user_roles
├─ user_id (BIGINT, FK)
├─ role_id (INT, FK)
└─ assigned_at (TIMESTAMP)
```

### 2. User Identity — Students + Employees (2 tables)

```sql
-- Students (existing)
students
├─ id (BIGINT, PK)
├─ user_id (BIGINT, FK → users.id, UNIQUE)
├─ student_number (VARCHAR, UNIQUE)
├─ qr_code (VARCHAR, UNIQUE)
├─ rfid_tag (VARCHAR, UNIQUE)         -- For RFID check-in
├─ course, year_level, section
├─ date_of_birth, gender, address
├─ consecutive_no_shows (TINYINT)
└─ created_at, updated_at

-- Employees (NEW — HR Integration)
employees
├─ id (BIGINT, PK)
├─ user_id (BIGINT, FK → users.id, UNIQUE)
├─ employee_number (VARCHAR, UNIQUE)
├─ qr_code (VARCHAR, UNIQUE, NULL)
├─ rfid_tag (VARCHAR, UNIQUE, NULL)    -- For RFID check-in
├─ department (VARCHAR)
├─ position (VARCHAR)
├─ date_hired (DATE)
├─ employment_status (ENUM: 'active', 'inactive', 'on_leave')
├─ hr_synced_at (TIMESTAMP)            -- Last sync with HR system
├─ emergency_contact_name, emergency_contact_phone
├─ date_of_birth, gender, address
└─ created_at, updated_at
```

### 3. Clinic Operations (5 tables)

```sql
-- Consultations (students + employees)
consultations
├─ id (BIGINT, PK)
├─ patient_type (ENUM: 'student', 'employee')    -- NEW
├─ student_id (BIGINT, FK → students.id, NULL)
├─ employee_id (BIGINT, FK → employees.id, NULL)  -- NEW
├─ clinic_staff_id (BIGINT, FK → users.id)
├─ consultation_date (DATETIME)
├─ queue_number (INT)
├─ triage_priority (ENUM: 'low','medium','high','urgent')
├─ chief_complaint (TEXT)
├─ diagnosis (TEXT)
├─ notes (TEXT)
├─ status (ENUM: 'pending','in_progress','completed')
└─ deleted_at

-- Vitals
consultation_vitals
├─ id, consultation_id, temperature, heart_rate,
├─ blood_pressure, respiratory_rate, oxygen_saturation
└─ recorded_at

-- Treatments
treatments
├─ id, consultation_id, medicine_batch_id, dosage,
├─ frequency, duration_days, instructions
└─ created_at

-- Allergies
allergies
├─ id, patient_type, student_id (NULL), employee_id (NULL),
├─ allergen, reaction, severity
└─ created_at

-- Emergency Contacts (polymorphic for both students/employees)
emergency_contacts
├─ id, owner_type, student_id (NULL), employee_id (NULL),
├─ name, relationship, phone
└─ created_at
```

### 4. Counselling Operations (4 tables)

```sql
-- Appointments
counselling_appointments
├─ id, patient_type, student_id (NULL), employee_id (NULL),
├─ counsellor_id, appointment_datetime, duration_minutes,
├─ status (ENUM: 'scheduled','completed','no_show','cancelled'),
├─ notes, reminder_sent (BOOLEAN)
└─ created_at, updated_at

-- Counsellor availability
counsellor_availability
├─ id, counsellor_id, day_of_week, start_time, end_time,
├─ is_available
└─ created_at

-- Intake session notes (free-text, counsellor-only)
intake_notes
├─ id, patient_type, student_id (NULL), employee_id (NULL),
├─ counsellor_id, appointment_id (FK, NULL),
├─ presenting_concern (TEXT),
├─ session_notes (TEXT),
├─ action_items (TEXT, NULL),
├─ session_date (DATE),
├─ is_confidential (BOOLEAN, DEFAULT TRUE)
└─ created_at, updated_at, deleted_at
```

### 5. Medicine Inventory & Procurement (4 tables)

```sql
-- Medicine master
medicines
├─ id, name, generic_name, description, dosage_form,
├─ unit_price, reorder_level, is_active
└─ created_at

-- Batches (FEFO)
medicine_batches
├─ id, medicine_id, batch_number, quantity_received,
├─ quantity_remaining, expiration_date, received_date,
├─ supplier, cost_per_unit
└─ created_at, deleted_at

-- Stock transactions
inventory_transactions
├─ id, medicine_batch_id, transaction_type (in/out/adjustment),
├─ quantity, reference_id, notes, recorded_by
└─ created_at

-- Reorder requests (NEW — procurement integration)
reorder_requests
├─ id, medicine_id, requested_quantity, current_stock,
├─ reorder_level, urgency (ENUM: 'low','medium','high','critical'),
├─ status (ENUM: 'pending','approved','ordered','received','cancelled'),
├─ requested_by (FK → users.id),
├─ approved_by (FK → users.id, NULL),
├─ procurement_notes (TEXT, NULL),
├─ order_date, expected_delivery_date, actual_delivery_date
└─ created_at, updated_at
```

### 6. **BMG (Biodegradable Waste Management) Module** — *NEW (6 tables)*

```sql
-- Waste categories
waste_categories
├─ id (INT, PK)
├─ code (VARCHAR, UNIQUE)  -- 'food_waste', 'twigs_leaves', 'mixed'
├─ name (VARCHAR)          -- "Food Scraps", "Twigs & Leaves", "Mixed Biodegradable"
├─ description (TEXT)
├─ expected_yield_pct (DECIMAL)  -- Reference yield for benchmarking
├─ is_active (BOOLEAN)
└─ created_at

-- BMG drums (rotating composting units)
bmg_drums
├─ id (BIGINT, PK)
├─ drum_code (VARCHAR, UNIQUE)  -- 'BMG-001', 'BMG-002'
├─ name (VARCHAR)               -- "Drum #1 - Main Campus"
├─ location (VARCHAR)           -- "Behind Cafeteria"
├─ capacity_kg (DECIMAL)        -- Maximum input capacity
├─ current_status (ENUM: 'idle','processing','maintenance','archived')
├─ installation_date (DATE)
├─ is_archived (BOOLEAN)
├─ notes (TEXT)
└─ created_at, updated_at, deleted_at

-- Batches (one batch = one load of waste in one drum)
bmg_batches
├─ id (BIGINT, PK)
├─ batch_code (VARCHAR, UNIQUE)  -- Auto-generated: 'BATCH-20260705-001'
├─ drum_id (BIGINT, FK → bmg_drums.id)
├─ waste_category_id (INT, FK → waste_categories.id)
├─ status (ENUM: 'input','processing','completed','cancelled')
├─ input_weight_kg (DECIMAL)
├─ input_recorded_at (DATETIME)
├─ input_recorded_by (BIGINT, FK → users.id)
├─ start_date (DATE)             -- Auto = input_recorded_at
├─ completion_date (DATE, NULL)
├─ duration_days (INT, NULL)     -- Computed: completion - start
├─ output_weight_kg (DECIMAL, NULL)
├─ yield_percentage (DECIMAL, NULL)   -- Computed: output/input * 100
├─ mass_reduction_pct (DECIMAL, NULL) -- Computed: 100 - yield
├─ completed_by (BIGINT, FK → users.id, NULL)
├─ output_recorded_at (DATETIME, NULL)
├─ notes (TEXT)
└─ created_at, updated_at
-- DB CONSTRAINT: CHECK (output_weight_kg <= input_weight_kg)

-- Input tracking (audit trail per input entry)
bmg_inputs
├─ id, batch_id, weight_kg, recorded_at, recorded_by
└─ notes
-- (Most batches have single input; this table allows partial inputs)

-- Process tracking (durations, observations)
bmg_process_logs
├─ id, batch_id, log_date, observation_note,
├─ temperature_celsius (DECIMAL, NULL),
├─ moisture_level (ENUM: 'low','normal','high', NULL)
└─ recorded_by, created_at

-- Output tracking (harvest events)
bmg_outputs
├─ id, batch_id, output_weight_kg, harvest_date,
├─ quality_grade (ENUM: 'excellent','good','fair', NULL),
├─ notes
└─ recorded_by, created_at
-- CONSTRAINT: CHECK (output_weight_kg <= batch.input_weight_kg)
```

### 7. Referrals, Check-ins & Notifications (3 tables)

```sql
-- Referrals (with QR code)
referrals
├─ id, source_patient_type, source_student_id (NULL), source_employee_id (NULL),
├─ from_module (ENUM: 'clinic','counselling'),
├─ to_module (ENUM: 'clinic','counselling'),
├─ referred_by, reason, status (pending/accepted/rejected/completed),
├─ assigned_to, qr_code_token (VARCHAR, UNIQUE),    -- NEW: Unique scannable token
├─ qr_code_path (VARCHAR),                          -- NEW: Path to PNG image
├─ qr_generated_at (TIMESTAMP),                     -- NEW
├─ qr_verified_at (TIMESTAMP, NULL),                -- NEW
├─ qr_verified_by (BIGINT, FK, NULL),               -- NEW
├─ created_at, updated_at
└─ deleted_at

-- RFID Check-in logs
checkin_logs
├─ id (BIGINT, PK)
├─ patient_type (ENUM: 'student','employee'),
├─ student_id (BIGINT, FK, NULL),
├─ employee_id (BIGINT, FK, NULL),
├─ rfid_tag_scanned (VARCHAR),  -- The scanned value
├─ checkin_at (TIMESTAMP),
├─ module (ENUM: 'clinic','counselling'),  -- Where they checked in
├─ notes (TEXT)
└─ INDEX: idx_checkin_patient, idx_checkin_at

-- Notifications
notifications
├─ id, recipient_id, type, title, message, reference_id,
├─ is_read, read_at, created_at
└─ Types: 'appointment_reminder','low_stock',
         'bmg_batch_complete','reorder_request','referral_received'
```

### 8. Audit, AI, Analytics (4 tables)

```sql
-- Hash-chained audit log (all 3 modules)
audit_logs
├─ id, user_id, action, module (clinic/counselling/bmg/...), entity_type,
├─ entity_id, old_values (JSON), new_values (JSON), request_id,
├─ ip_address, user_agent, status, hash, previous_hash, created_at
└─ INDEXES on user_id, entity_id, created_at, module

-- AI prediction tables (3 tables)
ai_triage_predictions
├─ id, consultation_id, predicted_priority, confidence_score, factors (JSON)
ai_inventory_forecasts
├─ id, medicine_id, forecast_date, predicted_consumption, predicted_stock_level
ai_generated_summaries
├─ id, entity_type, entity_id, summary_text, created_at
```

### Database Design Principles

1. **Polymorphic patient references**: `patient_type` ENUM + nullable FK to students/employees
2. **Soft deletes**: All sensitive tables use `deleted_at`
3. **Audit trail**: Every write is logged with hash-chaining across all 3 modules
4. **Database constraints**:
   - `CHECK (bmg_batches.output_weight_kg <= input_weight_kg)`
   - Foreign keys with `ON DELETE CASCADE` or `RESTRICT`
5. **Character set**: `utf8mb4_unicode_ci` (international support)
6. **Indexes**: All FKs, frequently filtered columns, date ranges

---

## Tech Stack

### Backend

| Layer | Technology | Version | Purpose |
|-------|-----------|---------|---------|
| **Language** | PHP | 8.2+ | Server-side logic |
| **Framework** | CodeIgniter | 4.7 | MVC, routing, ORM, filters |
| **Database** | MySQL / MariaDB | 8.0+ / 10.4+ | Persistent storage |
| **DB Driver** | MySQLi | Native | Database connectivity |
| **QR Library** | chillerlan/php-qrcode | Latest | QR code generation for referrals |
| **Barcode** | picqer/php-barcode-generator | Latest | Optional barcode support |

### Frontend

| Layer | Technology | Version | Purpose |
|-------|-----------|---------|---------|
| **Markup** | HTML5 | - | Semantic structure |
| **Styling** | CSS3 | - | Responsive, custom properties |
| **JavaScript** | Vanilla JS (ES6+) | - | Client interactivity |
| **Charts** | Chart.js | Latest (CDN) | Data visualization |
| **QR Scanner** | html5-qrcode | Latest (CDN) | Browser-based QR scanning |
| **Icons** | Font Awesome | 6.5 | Icon library (CDN) |
| **Fonts** | Inter, Outfit, JetBrains Mono | Latest | Self-served (Google Fonts CDN) |

### Infrastructure

| Layer | Technology | Purpose |
|-------|-----------|---------|
| **Web Server** | Apache 2.4 | HTTP server, `.htaccess` |
| **Stack** | XAMPP | Local dev (Apache + MySQL + PHP) |
| **Version Control** | Git | Source control |

### Testing

| Layer | Technology | Version | Purpose |
|-------|-----------|---------|---------|
| **Framework** | PHPUnit | 10.5.16 | Unit + integration tests |
| **Fake Data** | Faker | 1.9+ | Test data generation |
| **VFS** | vfsStream | 1.6+ | Filesystem mocking |

### Key Development Dependencies

- `codeigniter4/framework` ^4.7 — Core framework
- `fakerphp/faker` ^1.9 — Test data
- `mikey179/vfsstream` ^1.6 — Virtual filesystem
- `phpunit/phpunit` ^10.5.16 — Test runner
- `chillerlan/php-qrcode` ^4.0 — QR generation
- `picqer/php-barcode-generator` ^3.0 — Barcode support

### Bundled Libraries (via Composer)

- **PSR Suite** (`psr/*`): PSR-3 (logging), PSR-4 (autoloading), PSR-7 (HTTP)
- **Symfony Components**: YAML, error handlers, event dispatcher
- **Laminas**: Validation, encryption
- **Nikic PHP-Parser**: AST parsing

---

## Security & Compliance

### Compliance Frameworks

| Regulation | Scope | SYNAPSE Implementation |
|-----------|-------|--------------------------|
| **RA 10173 (Data Privacy Act of 2012)** | Sensitive personal data (health, counselling) | Hash-chained audit log, encryption, RBAC, encrypted backups |
| **NPC Circular 2023-06** | Organizational, physical, technical safeguards | Role-based access, audit trail, session security |
| **RA 9003 (Ecological Solid Waste Management Act)** | Composting program documentation | BMG module tracking, analytics, reporting |
| **ISO/IEC 25010** | Usability & functional quality | UAT evaluation framework |

### Authentication & Authorization

#### Login Flow

```
1. User submits email + password → AuthController::attemptLogin
2. Rate limit check (5 attempts / 15 min)
3. bcrypt verification: password_verify()
4. Session created:
   - logged_in = true
   - user_id, roles[], email
5. Optional 2FA (TOTP) check
6. Audit log entry: action='login', module='auth'
7. Redirect to role-specific dashboard
```

#### Two-Factor Authentication (2FA)

- **Standard**: RFC 6238 TOTP
- **Secret storage**: Base32 in `users.totp_secret`
- **Backup codes**: Hashed JSON array
- **App integration**: QR code for Google Authenticator/Authy

#### Role-Based Access Control (RBAC)

**Seven (7) roles:**

| Role | Description | Access |
|------|-------------|--------|
| `admin` | System administrator | All modules + audit logs |
| `clinic_staff` | Doctor, Nurse, Clinic Admin | Clinic, Inventory, Check-in |
| `counsellor` | Guidance Counsellor | Counselling, Referrals |
| `facilities_staff` | BMG operator | BMG module (drums, batches, inputs, outputs) |
| `report_viewer` | Mgmt office, environmental office | Cross-module reports only (read-only) |
| `employee` | University employee | View own records, book appointments |
| `student` | Student | View own records, book appointments |

**Permission Matrix (sample):**

| Permission | Admin | Clinic | Counsellor | Facilities | Report Viewer | Employee | Student |
|-----------|:-----:|:------:|:----------:|:----------:|:-------------:|:--------:|:-------:|
| view_bmg_dashboard | ✓ | ✗ | ✗ | ✓ | ✓ | ✗ | ✗ |
| log_bmg_input | ✗ | ✗ | ✗ | ✓ | ✗ | ✗ | ✗ |
| view_consultation | ✓ | ✓ | ✓ | ✗ | ✓ | (own) | (own) |
| create_referral | ✓ | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ |
| view_audit_log | ✓ | ✗ | ✗ | ✗ | ✗ | ✗ | ✗ |
| book_appointment | ✓ | ✗ | ✗ | ✗ | ✗ | ✓ | ✓ |

#### Session Management

- **Driver**: Database-backed (server-controlled, not client-side)
- **Cookies**: `Secure`, `HttpOnly`, `SameSite=Strict`
- **Regeneration**: On login (prevents session fixation)
- **Timeout**: 30 min inactivity

### Security Features

#### 1. CSRF Protection

```php
// app/Config/Security.php
$csrfProtection = 'cookie';
$tokenRandomize = true;
$expires = 7200; // 2 hours
$regenerate = true;

// In all forms: <?= csrf_field() ?>
// In AJAX: X-CSRF-TOKEN header
```

#### 2. Input Validation & Sanitization

- Validation rules: required, valid_email, is_unique, min_length, strong_password
- Sanitization: htmlspecialchars, like_escape_helper, MIME type validation

#### 3. Password Security

- **Algorithm**: bcrypt (PHP `password_hash`, cost ≥ 10)
- **Requirements**: Min 8 chars, mixed case, numbers, special chars
- **Verification**: `password_verify()` constant-time comparison

#### 4. Encryption

- **Driver**: openssl
- **Cipher**: AES-256-CBC
- **Key**: Stored in `.env` as `ENCRYPTION_KEY`
- **Encrypted fields**: TOTP secret, backup codes, sensitive PII

#### 5. Audit Logging (Hash-Chained)

- Every action across **all 3 modules** logged
- Records: user_id, action, module, entity_type, entity_id
- old_values (JSON) → new_values (JSON)
- Request ID for multi-step tracing
- Hash = SHA-256(`previous_hash + action + timestamp`) — tamper-evident
- Independent logging even though BMG module data is separate

#### 6. SQL Injection Prevention

- All queries use Query Builder (parameterized) or prepared statements
- LIKE clauses use `like_escape_helper`

#### 7. XSS Prevention

- Auto-escaping in views: `<?= $variable ?>`
- CSP headers: `default-src 'self'`
- Input sanitization at controller level

#### 8. Rate Limiting

- Login: 5 attempts / 15 min per IP
- API: 100 req / min per user/IP
- Form submissions: 3 / min per user

#### 9. Security Headers

```
X-Content-Type-Options: nosniff
X-Frame-Options: SAMEORIGIN
X-XSS-Protection: 1; mode=block
Strict-Transport-Security: max-age=31536000; includeSubDomains
Content-Security-Policy: default-src 'self'; script-src 'self' cdn.jsdelivr.net
```

#### 10. File Upload Security

- Whitelist: image/jpeg, image/png only
- Max size: 5MB
- Stored outside webroot: `writable/uploads/`
- Random hash filenames
- DB constraint on BMG outputs: `CHECK (output_weight_kg <= input_weight_kg)`

### Database Constraints (Enforced at DB Layer)

```sql
-- BMG Output validity (scientific impossibility guard)
ALTER TABLE bmg_batches
  ADD CONSTRAINT chk_output_lte_input
  CHECK (output_weight_kg IS NULL OR output_weight_kg <= input_weight_kg);

-- Audit log immutability (no updates, only inserts)
REVOKE UPDATE, DELETE ON audit_logs FROM 'synapse_app'@'localhost';
```

---

## Module Breakdown

### Module Independence Statement

> **The Facilities/Sustainability (BMG) module operates independently** of the Clinic and Counselling modules and does not exchange data with them. Integration is limited to **shared login, RBAC governance, and institutional audit logging**.

This is a critical architectural principle: BMG, Clinic, and Counselling share the platform, but their data domains are completely separate.

---

### 1. Authentication Module (AuthController.php)

**Responsibility**: Login, logout, session, rate limiting, 2FA

**Endpoints**:
- `GET /login` — Show login
- `POST /login` — Process login (+ optional 2FA)
- `GET /logout` — Destroy session

**Security**: bcrypt, rate limiting, session regeneration, audit logging

---

### 2. Dashboard Module (DashboardController.php)

**Responsibility**: Role-aware landing page with KPI tiles

**Endpoints (role-routed)**:
- `GET /dashboard/admin` — System health, user stats
- `GET /dashboard/clinic` — Queue, patients, low-stock alerts
- `GET /dashboard/counsellor` — Appointment pipeline, screening backlog
- `GET /dashboard/bmg` — **NEW**: Drum status, active batches, recent yields
- `GET /dashboard/reports` — **NEW**: Cross-module report viewer
- `GET /dashboard/employee` — **NEW**: My appointments, records
- `GET /dashboard/student` — My appointments

---

### 3. Clinic Core Module (Clinic/* Controllers)

**Responsibility**: Patient consultations, vitals, treatments, queue, RFID check-in, QR referrals

**Sub-modules**:

#### 3a. Student & Employee Records
- `StudentController` — Manage student patients
- `EmployeeController` — **NEW**: Manage employee patients (HR-synced)

#### 3b. RFID Check-In (NEW)
- `CheckinController` — Scan RFID, retrieve record, log timestamp
- Supports both student RFID and employee RFID tags

#### 3c. Consultation Queue
- `ConsultationController` — Queue display, call next, start/skip/complete
- AI triage prediction (TriageAssistant library)

#### 3d. Treatments
- `TreatmentController` — Prescribe from available batches, log transactions

#### 3e. QR Referral Generation (NEW)
- `ReferralController` — Create referral, auto-generate QR code (PNG)
- QR contains unique token + URL to verify
- Receiving party scans via `html5-qrcode` library

#### 3f. Procurement Integration (NEW)
- `ProcurementController` — Auto-routes reorder requests to procurement personnel

---

### 4. Counselling Core Module (Counselling/* Controllers)

**Responsibility**: Appointments, intake session notes, QR referrals

**Sub-modules**:

#### 4a. Appointments
- `AppointmentController` — Book, view, reschedule, track no-shows

#### 4b. Intake Notes
- `IntakeController` — Record and manage counsellor intake session notes
- Free-text fields: presenting concern, session notes, action items
- Marked confidential; access restricted to assigned counsellor and admins

#### 4c. QR Referral (NEW)
- `ReferralController` — Generate QR for clinic referrals

---

### 5. Medicine Inventory Module (Inventory/* Controllers)

**Responsibility**: Medicine management, batch tracking, procurement integration

**Sub-modules**:

#### 5a. Medicine Master
- `MedicineController` — CRUD, reorder levels

#### 5b. Batch Management
- `BatchController` — FEFO tracking, expiration monitoring

#### 5c. Stock Transactions
- `TransactionController` — In/out/adjustment logs

#### 5d. Reorder Requests (NEW)
- `ReorderController` — Auto-trigger reorder when stock < reorder level
- Status workflow: pending → approved → ordered → received
- Routes to procurement personnel

---

### 6. **BMG (Biodegradable Waste Management) Module** — *NEW (Bmg/* Controllers)*

**Responsibility**: Input/process/output tracking for composting drums

#### 6a. Drum Management
- `DrumController` — CRUD BMG units (drum_code, location, capacity, status)
- Archive drums when decommissioned
- `getList` — Scalable dashboard

#### 6b. Batch Lifecycle
- `BatchController` — Manages the full batch lifecycle:
  1. **Input**: `startBatch()` — record waste weight + category, set status='input'
  2. **Process**: `markProcessing()` — auto-set on first input, status='processing'
  3. **Complete**: `markCompleted()` — set completion_date, compute `duration_days`
  4. **Output**: `recordOutput()` — set output_weight, compute yield %

#### 6c. Input Tracking
- `InputController` — Record daily input weight (kg) per batch
- Validates against drum capacity

#### 6d. Process Tracking
- `ProcessController` — Log observations, monitor duration
- Computes `duration_days` automatically on completion

#### 6e. Output Tracking
- `OutputController` — Record final fertilizer weight
- Computes `yield_percentage` and `mass_reduction_pct`
- DB-level CHECK constraint: `output_weight <= input_weight`
- Application-level validation as defense-in-depth

#### 6f. Waste Categorization
- `WasteCategoryController` — Manage waste types (food, twigs/leaves, mixed)
- Supports comparative analysis across categories

#### 6g. BMG Reports
- `BmgReportController` — Analytics, PDF/CSV export
- Reports: avg duration by waste type, yield % by drum, total fertilizer

**Libraries**:
- `BmgYieldCalculator` — Computes yield % and mass reduction
- `BmgDurationCalculator` — Computes decomposition duration

---

### 7. Admin Module (Admin/* Controllers)

**Responsibility**: Users, roles, permissions, audit logs, system health

**Sub-modules**:

#### 7a. User Management
- `UserController` — CRUD users (students, employees, staff)

#### 7b. Role & Permissions
- `RoleController`, `PermissionController` — Manage 7 roles, granular permissions

#### 7c. Audit Log Viewer
- `AuditLogController` — Searchable, filterable log
- Hash chain verification (detect tampering)
- Covers all 3 modules

---

### 8. IoT Module (Iot/* Controllers)

**Responsibility**: QR/RFID scanning, offline buffer

**Sub-modules**:

#### 8a. Kiosk
- `KioskController` — QR/RFID scan handler, match to user

#### 8b. Offline Buffer
- `OfflineBufferController` — Queue scans when offline, sync when online

---

### 9. Reports Module (Reports/* Controllers)

**Responsibility**: Cross-module analytics, exports

**Sub-modules**:

#### 9a. Clinic Reports
- Consultation volume, wait times, diagnosis frequency, AI accuracy

#### 9b. Counselling Reports
- Appointment utilization, no-show rates, intake session volume

#### 9c. Inventory Reports
- Stock levels, expiration, cost analysis

#### 9d. BMG Reports (NEW)
- Avg. duration by waste type
- Yield % by drum
- Total fertilizer produced
- PDF/CSV export

---

### 10. Profile Module (ProfileController.php)

**Responsibility**: Self-service account management

**Endpoints**:
- `GET /profile` — View profile
- `POST /profile/update` — Update info
- `POST /profile/change-password`
- `GET /profile/security` — 2FA settings, sessions

---

### 11. Notification Module (NotificationController.php)

**Responsibility**: Real-time alerts, polling endpoint

**Notification types**:
- Appointment reminders
- Low-stock alerts
- Reorder request status updates
- **NEW**: BMG batch completion alerts
- Referral received / accepted

---

## System Workflows

### Clinic & Counselling Workflow

```
1. CHECK-IN (RFID)
   └─ Staff scans ID → System retrieves student/employee record
   └─ Logs timestamp in checkin_logs

2. APPOINTMENT SCHEDULING
   └─ Student/Employee books via web → System sends 24h reminder
   └─ No-shows logged for follow-up

3. REFERRAL VERIFICATION (QR)
   └─ Clinic/Counselling creates referral → System generates unique QR
   └─ Receiving party scans QR → Instant verification
   └─ Replaces unverifiable paper slips

4. MEDICINE INVENTORY
   └─ Staff logs stock levels → System monitors thresholds
   └─ Auto-triggers reorder when stock < reorder_level
   └─ Routes to procurement personnel
```

### BMG Workflow (Input → Process → Output)

```
1. INPUT TRACKING
   └─ Staff weighs waste on physical scale
   └─ Selects drum + waste category
   └─ Enters weight (kg) in system
   └─ System: timestamps start_date, updates drum status to "Processing"

2. PROCESS TRACKING
   └─ Staff monitors physical drum
   └─ When waste is decomposed: clicks "Mark as Completed"
   └─ System: captures completion_date
   └─ System: computes duration_days = completion - start
   └─ System: resets drum status to "Idle"

3. OUTPUT TRACKING (Yield Analytics)
   └─ Staff weighs final fertilizer
   └─ Enters output weight in system
   └─ System: computes yield_percentage = (output / input) * 100
   └─ System: computes mass_reduction_pct = 100 - yield
   └─ System: BLOCKS submission if output > input
   └─ Database: CHECK constraint enforces validation at DB layer
```

---

## Key Features

### Clinic & Counselling Features

1. **Unified Patient Records** — Students and employees, single source of truth
2. **HR Integration** — Employee data synced from HR Department
3. **RFID Check-In** — Instant record retrieval via institutional ID
4. **QR Referral Verification** — Unique scannable code per referral
5. **Medicine Inventory (FEFO)** — First-Expired, First-Out tracking
6. **Procurement Integration** — Auto-reorder triggers, status workflow
7. **Confidential Intake Notes** — Encrypted, counsellor-only session documentation
8. **Bidirectional Referrals** — Clinic ↔ Counselling with SLA

### BMG (Sustainability) Features

10. **Multi-Drum Scalability** — Add/edit/archive drums as program grows
11. **Input Tracking** — Daily weight logging per batch
12. **Process Tracking** — Auto-calculated duration (days)
13. **Output Tracking** — Yield % and mass reduction analytics
14. **Waste Categorization** — Food / Twigs & Leaves / Mixed
15. **Validation Rules** — DB-level CHECK constraint blocks invalid outputs
16. **Comparative Analytics** — Compare new compact drums vs. old baselines
17. **PDF/CSV Export** — Reports for facilities/environmental office

### System-Wide Features

18. **Single Sign-On** — One login for all 3 modules (but independent data)
19. **Unified RBAC** — 7 roles, granular permissions, route-level enforcement
20. **Hash-Chained Audit Log** — Tamper-evident, covers all 3 modules
21. **AI Assistance** — Triage prediction, inventory forecasting, summary generation
22. **ISO/IEC 25010 UAT** — Standardized quality evaluation

---

## Scope & Limitations

### Scope

#### Clinic Module
- Digitized patient records for **students and employees**
- QR-based referral/excuse slip verification
- RFID-based check-in
- Medicine inventory + procurement integration (reorder, expiration, alerts)
- Basic clinical encounter logging

#### Counselling Module
- Digitized counselling appointment scheduling (automated reminders, no-show tracking)
- Bidirectional referral system (clinic ↔ counselling)
- Confidential, partitioned, encrypted intake session notes (counsellor-only access)

#### Employee Scope (HR Integration)
- Extension to university employees (in addition to students)
- Employee data gathered in coordination with HR Department

#### Facilities/Sustainability Module (BMG)
- **Input tracking**: daily weight/volume per BMG unit
- **Process tracking**: decomposition duration in days
- **Output tracking**: fertilizer weight, yield %, mass reduction %
- **Waste categorization**: food / twigs & leaves / mixed
- **Multi-unit dashboard**: scalable, add/edit/archive drums
- **Reporting**: input, duration, yield analytics, PDF/CSV export

#### System-Wide
- **RBAC**: 7 roles (System Admin, Clinic Staff, Counsellor, Facilities Staff, Report Viewer, Employee, Student)
- **Audit trail**: write-once log covering all 3 modules
- **UAT evaluation**: ISO/IEC 25010 quality model

### Limitations

#### Clinic & Counselling
- ❌ Excludes: e-prescriptions, medicine dispensing, telemedicine, billing, national health DB integration
- ❌ Excludes clinical psychiatric screening, psychological diagnosis, and standardized mental health assessment instruments (e.g., PHQ-9, GAD-7); counsellors document concerns and actions via free-text intake notes only
- ⚠️ Hardware: Development team provides QR/RFID hardware; **university provides RFID-enabled ID cards**
- ⚠️ Procurement: tracks stock and triggers reorders, but does **not** process purchase transactions/payments

#### Facilities/Sustainability (BMG)
- ❌ Does **not** include BMG fabrication/construction/mechanical operation
- ❌ Does **not** include automated sensors or IoT hardware for composting data
- ❌ All BMG data (input, dates, output) entered **manually** by staff
- ❌ Does **not** perform scientific interpretation (e.g., optimal waste ratios)
- ❌ Does **not** manage waste collection logistics or transportation
- ✅ Uses existing physical scale at each BMG unit location

#### System-Wide
- ⚠️ **Module independence**: BMG operates independently of Clinic/Counselling — no data exchange
  - Integration limited to: shared login, RBAC, institutional audit log
- ⚠️ **Operating environment**: Limited to Foundation University; generalization to other institutions outside scope
- ⚠️ **Connectivity**: System depends on university server/internet; downtime interrupts data entry
  - Mitigation: locally cached entries sync upon reconnection

---

## Stakeholder Summary

| Stakeholder | Benefit |
|-------------|---------|
| **Clinic Staff** (Doctor, Nurse, Admin) | Consolidated patient records, RFID check-in, QR referrals, automated reorder |
| **Guidance Counsellors** | Encrypted intake notes, defined clinic referral bridge, appointment management |
| **Facilities/Composting Staff** | Simple structured interface for logging input/output, no more paper logbooks |
| **System Administrators** | Centralized user/role management, cross-module audit log |
| **Report Viewers** (Mgmt, Environmental Office) | Real-time dashboards, PDF/CSV exports, data-driven decisions |
| **University Administration** | Compliance reporting (Data Privacy Act + RA 9003), resource allocation |
| **Students & Employees** | Single point of access for appointments, referrals, reminders |
| **Future Researchers** | Real-world datasets across two domains (health + sustainability) |
| **Local Government / Community** | Methodology reference for other schools, LGUs, small institutions |

---

## Regulatory Compliance Summary

| Regulation | Compliance Mechanism |
|-----------|---------------------|
| **RA 10173 (Data Privacy Act 2012)** | Hash-chained audit log, encryption, RBAC, session security |
| **NPC Circular 2023-06** | Technical/organizational safeguards, breach logging |
| **RA 9003 (Ecological Solid Waste Mgmt Act 2000)** | BMG input/process/output tracking, analytics, reporting |
| **WHO Global Strategy on Digital Health** | Integrated digital health record system |
| **ISO/IEC 25010** | UAT framework for usability + functional quality |

---

## Summary

**SYNAPSE** is Foundation University's capstone project — a unified, role-aware, web-based platform that consolidates **three institutional services** under one login and one quality standard:

1. **Clinic Core** — health services, RFID check-in, QR referrals, medicine inventory
2. **Counselling Core** — appointments, confidential intake notes, QR referrals
3. **BMG Core** — biodegradable waste input/process/output tracking, yield analytics

All three modules share **authentication, RBAC, and audit logging** but maintain **independent data domains**. The system serves Foundation University's complete population (students + employees), complies with the **Data Privacy Act of 2012** and **RA 9003**, and is evaluated under the **ISO/IEC 25010** quality model.

The architecture is built on **CodeIgniter 4.7** (PHP 8.2+), **MySQL 8.0+**, vanilla JS, and **Chart.js** — prioritizing security, operational efficiency, and real-world data contribution for future research on institutional digital health and waste management systems in Philippine higher education.

---

**Document Version**: 2.0  
**Last Updated**: 2026-07-11  
**Maintained By**: SYNAPSE Development Team  
**Institution**: Foundation University
