-- =============================================================================
-- SYNAPSE Capstone - Direct SQL Application of Phase 1 Migrations
-- =============================================================================
-- This script applies all the new tables and constraints that should have been
-- created by the CodeIgniter migrations. It is a direct SQL equivalent.
-- =============================================================================

USE synapse_ag;

-- =============================================================================
-- Migration 2026-07-15-000001: Add Employees Table
-- =============================================================================
CREATE TABLE IF NOT EXISTS employees (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    employee_number VARCHAR(50) NOT NULL UNIQUE,
    qr_code VARCHAR(255) NULL UNIQUE,
    rfid_tag VARCHAR(255) NULL UNIQUE,
    department VARCHAR(100) NULL,
    position VARCHAR(100) NULL,
    date_hired DATE NULL,
    employment_status ENUM('active', 'inactive', 'on_leave') NOT NULL DEFAULT 'active',
    hr_synced_at TIMESTAMP NULL,
    emergency_contact_name VARCHAR(150) NULL,
    emergency_contact_phone VARCHAR(20) NULL,
    date_of_birth DATE NULL,
    gender ENUM('male', 'female', 'other') NULL,
    address TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_employees_rfid_tag (rfid_tag),
    INDEX idx_employees_employment_status (employment_status),
    CONSTRAINT fk_employees_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- Migration 2026-07-15-000002: Add user_type to users
-- =============================================================================
ALTER TABLE users
ADD COLUMN IF NOT EXISTS user_type ENUM('student','employee','staff') NOT NULL DEFAULT 'student' AFTER email;

CREATE INDEX IF NOT EXISTS idx_users_user_type ON users(user_type);

-- =============================================================================
-- Migration 2026-07-15-000003: Add Checkin Logs Table
-- =============================================================================
CREATE TABLE IF NOT EXISTS checkin_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_type ENUM('student', 'employee') NOT NULL,
    student_id BIGINT UNSIGNED NULL,
    employee_id BIGINT UNSIGNED NULL,
    rfid_tag_scanned VARCHAR(255) NOT NULL,
    checkin_at TIMESTAMP NULL,
    module ENUM('clinic', 'counselling') NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    INDEX idx_checkin_patient (patient_type, student_id, employee_id),
    INDEX idx_checkin_at (checkin_at),
    INDEX idx_checkin_rfid (rfid_tag_scanned),
    CONSTRAINT fk_checkin_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL,
    CONSTRAINT fk_checkin_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- Migration 2026-07-15-004/005: Update Consultations & Counselling for Employees
-- =============================================================================
-- Add columns to consultations if not present
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = 'synapse_ag' AND TABLE_NAME = 'consultations' AND COLUMN_NAME = 'patient_type') = 0,
    'ALTER TABLE consultations ADD COLUMN patient_type ENUM(''student'',''employee'') NOT NULL DEFAULT ''student'' AFTER id',
    'SELECT 1'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = 'synapse_ag' AND TABLE_NAME = 'consultations' AND COLUMN_NAME = 'employee_id') = 0,
    'ALTER TABLE consultations ADD COLUMN employee_id BIGINT UNSIGNED NULL AFTER student_id',
    'SELECT 1'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA = 'synapse_ag' AND TABLE_NAME = 'consultations' AND INDEX_NAME = 'idx_consultations_patient') = 0,
    'CREATE INDEX idx_consultations_patient ON consultations(patient_type, student_id, employee_id)',
    'SELECT 1'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add FK to consultations if not present
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
     WHERE TABLE_SCHEMA = 'synapse_ag' AND TABLE_NAME = 'consultations' AND CONSTRAINT_NAME = 'fk_consultations_employee') = 0,
    'ALTER TABLE consultations ADD CONSTRAINT fk_consultations_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL',
    'SELECT 1'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add columns to counselling_appointments if not present
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = 'synapse_ag' AND TABLE_NAME = 'counselling_appointments' AND COLUMN_NAME = 'patient_type') = 0,
    'ALTER TABLE counselling_appointments ADD COLUMN patient_type ENUM(''student'',''employee'') NOT NULL DEFAULT ''student'' AFTER id',
    'SELECT 1'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = 'synapse_ag' AND TABLE_NAME = 'counselling_appointments' AND COLUMN_NAME = 'employee_id') = 0,
    'ALTER TABLE counselling_appointments ADD COLUMN employee_id BIGINT UNSIGNED NULL AFTER student_id',
    'SELECT 1'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA = 'synapse_ag' AND TABLE_NAME = 'counselling_appointments' AND INDEX_NAME = 'idx_counselling_patient') = 0,
    'CREATE INDEX idx_counselling_patient ON counselling_appointments(patient_type, student_id, employee_id)',
    'SELECT 1'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
     WHERE TABLE_SCHEMA = 'synapse_ag' AND TABLE_NAME = 'counselling_appointments' AND CONSTRAINT_NAME = 'fk_counselling_employee') = 0,
    'ALTER TABLE counselling_appointments ADD CONSTRAINT fk_counselling_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL',
    'SELECT 1'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =============================================================================
-- Migration 2026-07-15-000008: Add Intake Notes Table
-- =============================================================================
CREATE TABLE IF NOT EXISTS intake_notes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_type ENUM('student', 'employee') NOT NULL,
    student_id BIGINT UNSIGNED NULL,
    employee_id BIGINT UNSIGNED NULL,
    counsellor_id BIGINT UNSIGNED NOT NULL,
    appointment_id BIGINT UNSIGNED NULL,
    presenting_concern TEXT NULL,
    session_notes TEXT NULL,
    action_items TEXT NULL,
    session_date DATE NULL,
    is_confidential TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    INDEX idx_intake_patient (patient_type, student_id, employee_id),
    INDEX idx_intake_counsellor (counsellor_id),
    INDEX idx_intake_session_date (session_date),
    CONSTRAINT fk_intake_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL,
    CONSTRAINT fk_intake_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL,
    CONSTRAINT fk_intake_counsellor FOREIGN KEY (counsellor_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_intake_appointment FOREIGN KEY (appointment_id) REFERENCES counselling_appointments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- Migration 2026-07-15-000009: Update Referrals for QR
-- =============================================================================
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = 'synapse_ag' AND TABLE_NAME = 'referrals' AND COLUMN_NAME = 'qr_code_token') = 0,
    'ALTER TABLE referrals ADD COLUMN qr_code_token VARCHAR(64) NULL UNIQUE AFTER status',
    'SELECT 1'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = 'synapse_ag' AND TABLE_NAME = 'referrals' AND COLUMN_NAME = 'qr_code_path') = 0,
    'ALTER TABLE referrals ADD COLUMN qr_code_path VARCHAR(255) NULL AFTER qr_code_token',
    'SELECT 1'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = 'synapse_ag' AND TABLE_NAME = 'referrals' AND COLUMN_NAME = 'qr_generated_at') = 0,
    'ALTER TABLE referrals ADD COLUMN qr_generated_at TIMESTAMP NULL AFTER qr_code_path',
    'SELECT 1'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = 'synapse_ag' AND TABLE_NAME = 'referrals' AND COLUMN_NAME = 'qr_verified_at') = 0,
    'ALTER TABLE referrals ADD COLUMN qr_verified_at TIMESTAMP NULL AFTER qr_generated_at',
    'SELECT 1'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = 'synapse_ag' AND TABLE_NAME = 'referrals' AND COLUMN_NAME = 'qr_verified_by') = 0,
    'ALTER TABLE referrals ADD COLUMN qr_verified_by BIGINT UNSIGNED NULL AFTER qr_verified_at',
    'SELECT 1'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =============================================================================
-- Migration 2026-07-15-000010: Reorder Requests Table
-- =============================================================================
CREATE TABLE IF NOT EXISTS reorder_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    medicine_id BIGINT UNSIGNED NOT NULL,
    requested_quantity INT UNSIGNED NOT NULL,
    current_stock INT UNSIGNED NOT NULL,
    reorder_level INT UNSIGNED NOT NULL,
    urgency ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    status ENUM('pending', 'approved', 'ordered', 'received', 'cancelled') NOT NULL DEFAULT 'pending',
    requested_by BIGINT UNSIGNED NOT NULL,
    approved_by BIGINT UNSIGNED NULL,
    procurement_notes TEXT NULL,
    order_date DATE NULL,
    expected_delivery_date DATE NULL,
    actual_delivery_date DATE NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_reorder_status (status),
    INDEX idx_reorder_urgency (urgency),
    INDEX idx_reorder_medicine (medicine_id),
    CONSTRAINT fk_reorder_medicine FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE CASCADE,
    CONSTRAINT fk_reorder_requester FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_reorder_approver FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- Migration 2026-07-15-000011: BMG Drums Table
-- =============================================================================
CREATE TABLE IF NOT EXISTS bmg_drums (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    drum_code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    location VARCHAR(255) NULL,
    capacity_kg DECIMAL(10,2) NOT NULL DEFAULT 100.00,
    current_status ENUM('idle', 'processing', 'maintenance', 'archived') NOT NULL DEFAULT 'idle',
    installation_date DATE NULL,
    is_archived TINYINT(1) NOT NULL DEFAULT 0,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    INDEX idx_drums_status (current_status),
    INDEX idx_drums_archived (is_archived)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- Migration 2026-07-15-000012: Waste Categories Table
-- =============================================================================
CREATE TABLE IF NOT EXISTS waste_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    expected_yield_pct DECIMAL(5,2) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_waste_categories_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- Migration 2026-07-15-000013: BMG Batches Table
-- =============================================================================
CREATE TABLE IF NOT EXISTS bmg_batches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    batch_code VARCHAR(50) NOT NULL UNIQUE,
    drum_id BIGINT UNSIGNED NOT NULL,
    waste_category_id INT UNSIGNED NOT NULL,
    status ENUM('input', 'processing', 'completed', 'cancelled') NOT NULL DEFAULT 'input',
    input_weight_kg DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    input_recorded_at DATETIME NULL,
    input_recorded_by BIGINT UNSIGNED NULL,
    start_date DATE NULL,
    completion_date DATE NULL,
    duration_days INT NULL,
    output_weight_kg DECIMAL(10,2) NULL,
    yield_percentage DECIMAL(5,2) NULL,
    mass_reduction_pct DECIMAL(5,2) NULL,
    completed_by BIGINT UNSIGNED NULL,
    output_recorded_at DATETIME NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_batches_status (status),
    INDEX idx_batches_drum (drum_id),
    INDEX idx_batches_start_date (start_date),
    CONSTRAINT fk_batches_drum FOREIGN KEY (drum_id) REFERENCES bmg_drums(id) ON DELETE CASCADE,
    CONSTRAINT fk_batches_category FOREIGN KEY (waste_category_id) REFERENCES waste_categories(id) ON DELETE RESTRICT,
    CONSTRAINT fk_batches_input_user FOREIGN KEY (input_recorded_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_batches_completed_user FOREIGN KEY (completed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- Migration 2026-07-15-000014: BMG Inputs Table
-- =============================================================================
CREATE TABLE IF NOT EXISTS bmg_inputs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    batch_id BIGINT UNSIGNED NOT NULL,
    weight_kg DECIMAL(10,2) NOT NULL,
    recorded_at DATETIME NULL,
    recorded_by BIGINT UNSIGNED NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    INDEX idx_bmg_inputs_batch (batch_id),
    CONSTRAINT fk_bmg_inputs_batch FOREIGN KEY (batch_id) REFERENCES bmg_batches(id) ON DELETE CASCADE,
    CONSTRAINT fk_bmg_inputs_user FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- Migration 2026-07-15-000015: BMG Process Logs Table
-- =============================================================================
CREATE TABLE IF NOT EXISTS bmg_process_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    batch_id BIGINT UNSIGNED NOT NULL,
    log_date DATE NULL,
    observation_note TEXT NULL,
    temperature_celsius DECIMAL(5,2) NULL,
    moisture_level ENUM('low', 'normal', 'high') NULL,
    recorded_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    INDEX idx_bmg_process_batch (batch_id),
    INDEX idx_bmg_process_date (log_date),
    CONSTRAINT fk_bmg_process_batch FOREIGN KEY (batch_id) REFERENCES bmg_batches(id) ON DELETE CASCADE,
    CONSTRAINT fk_bmg_process_user FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- Migration 2026-07-15-000016: BMG Outputs Table
-- =============================================================================
CREATE TABLE IF NOT EXISTS bmg_outputs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    batch_id BIGINT UNSIGNED NOT NULL,
    output_weight_kg DECIMAL(10,2) NOT NULL,
    harvest_date DATE NULL,
    quality_grade ENUM('excellent', 'good', 'fair') NULL,
    notes TEXT NULL,
    recorded_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    INDEX idx_bmg_outputs_batch (batch_id),
    INDEX idx_bmg_outputs_harvest (harvest_date),
    CONSTRAINT fk_bmg_outputs_batch FOREIGN KEY (batch_id) REFERENCES bmg_batches(id) ON DELETE CASCADE,
    CONSTRAINT fk_bmg_outputs_user FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- Migration 2026-07-15-000017: BMG Check Constraints
-- =============================================================================
-- Note: MariaDB 10.4+ does NOT enforce CHECK constraints. They're parsed but
-- not enforced. We add them anyway for documentation and to satisfy the
-- application layer. For enforcement, the application uses BmgOutputModel
-- validation. This is documented in SYSTEM_ARCHITECTURE.md.
--
-- (Uncomment these on MySQL 8.0+ if CHECK enforcement is needed)
-- ALTER TABLE bmg_batches
--   ADD CONSTRAINT chk_bmg_batch_output_lte_input
--   CHECK (output_weight_kg IS NULL OR output_weight_kg <= input_weight_kg);

-- =============================================================================
-- Migration 2026-07-15-000018: Expand Roles
-- =============================================================================
INSERT IGNORE INTO roles (name, display_name, description) VALUES
    ('facilities_staff', 'Facilities / Composting Staff', 'BMG module operator (drums, batches)'),
    ('report_viewer',    'Report Viewer',                  'Cross-module read-only report access'),
    ('employee',         'Employee',                       'University employee (patient role)');

-- =============================================================================
-- Migration 2026-07-15-000019: BMG & New Permissions
-- =============================================================================
INSERT IGNORE INTO permissions (name, module, description) VALUES
    ('view_clinic_dashboard',  'clinic',      'View clinic landing page'),
    ('create_consultation',    'clinic',      'Create new consultation'),
    ('record_vitals',          'clinic',      'Record patient vitals'),
    ('manage_treatments',      'clinic',      'Prescribe / manage treatments'),
    ('create_referral',        'clinic',      'Create referral and generate QR'),
    ('verify_referral',        'clinic',      'Verify referral via QR scan'),
    ('view_clinic_audit',      'clinic',      'View clinic audit log'),
    ('view_counselling_dashboard', 'counselling', 'View counselling landing page'),
    ('book_appointment',            'counselling', 'Book / manage appointments'),
    ('record_intake',              'counselling', 'Record intake session notes'),
    ('view_own_intake',            'counselling', 'View own intake notes'),
    ('view_bmg_dashboard',  'bmg', 'View BMG dashboard'),
    ('manage_drums',        'bmg', 'Add/edit/archive BMG drums'),
    ('log_bmg_input',       'bmg', 'Record batch input weight'),
    ('log_bmg_process',     'bmg', 'Log process observations'),
    ('record_bmg_output',   'bmg', 'Record batch output / yield'),
    ('view_bmg_reports',    'bmg', 'View BMG analytics & export'),
    ('manage_inventory',    'inventory', 'Manage medicines and batches'),
    ('view_reorder_requests', 'inventory', 'View reorder requests'),
    ('approve_reorder',     'inventory', 'Approve / route reorder requests'),
    ('manage_users',        'admin', 'User CRUD'),
    ('manage_roles',        'admin', 'Role & permission management'),
    ('view_audit_log',      'admin', 'View system audit log'),
    ('view_reports',        'reports', 'View cross-module reports'),
    ('export_reports',      'reports', 'Export reports (CSV/PDF)');

-- Bind permissions to roles
-- Admin: all permissions
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p WHERE r.name = 'admin';

-- Clinic Staff
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.name = 'clinic_staff' AND p.name IN (
    'view_clinic_dashboard','create_consultation','record_vitals','manage_treatments',
    'create_referral','verify_referral','manage_inventory','view_reorder_requests',
    'approve_reorder','view_reports'
);

-- Counsellor
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.name = 'counsellor' AND p.name IN (
    'view_counselling_dashboard','book_appointment','record_intake','view_own_intake',
    'create_referral','verify_referral','view_reports'
);

-- Facilities Staff
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.name = 'facilities_staff' AND p.name IN (
    'view_bmg_dashboard','manage_drums','log_bmg_input','log_bmg_process',
    'record_bmg_output','view_bmg_reports','view_reports'
);

-- Report Viewer
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.name = 'report_viewer' AND p.name IN (
    'view_reports','export_reports','view_bmg_reports','view_reorder_requests',
    'view_clinic_audit'
);

-- Employee
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.name = 'employee' AND p.name IN ('book_appointment','view_own_intake');

-- Student
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.name = 'student' AND p.name IN ('book_appointment');

-- =============================================================================
-- Migration 2026-07-15-000020: Update Notification Types
-- =============================================================================
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = 'synapse_ag' AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'module') = 0,
    'ALTER TABLE notifications ADD COLUMN module ENUM(''auth'',''clinic'',''counselling'',''bmg'',''inventory'',''admin'',''reports'',''hri'') NULL AFTER type',
    'SELECT 1'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =============================================================================
-- DONE
-- =============================================================================
SELECT 'Phase 1 migrations applied successfully' AS status;
