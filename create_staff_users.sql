-- Get role IDs
SELECT id FROM roles WHERE name='admin' INTO @admin_role;
SELECT id FROM roles WHERE name='clinic_staff' INTO @clinic_role;
SELECT id FROM roles WHERE name='counsellor' INTO @counsellor_role;

-- Assign admin role to admin user if not already assigned
INSERT INTO user_roles (user_id, role_id, assigned_at) 
VALUES (1, @admin_role, NOW()) 
ON DUPLICATE KEY UPDATE assigned_at=NOW();

-- Create clinic staff user
INSERT INTO users (email, password_hash, first_name, last_name, is_active, email_verified_at, created_at, updated_at)
VALUES ('clinic@synapse.edu.ph', '$2y$12$3Nlrj3rFUeEiuzyqxFfRgecvHbTE1m9r54YvwVWZm6cdE0z21e2OK', 'Clinic', 'Staff', 1, NOW(), NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at=NOW();

SET @clinic_user_id = LAST_INSERT_ID();
INSERT INTO user_roles (user_id, role_id, assigned_at) 
SELECT @clinic_user_id, @clinic_role, NOW() 
WHERE NOT EXISTS (SELECT 1 FROM user_roles WHERE user_id=@clinic_user_id AND role_id=@clinic_role);

-- Create counselor user
INSERT INTO users (email, password_hash, first_name, last_name, is_active, email_verified_at, created_at, updated_at)
VALUES ('counsellor@synapse.edu.ph', '$2y$12$3Nlrj3rFUeEiuzyqxFfRgecvHbTE1m9r54YvwVWZm6cdE0z21e2OK', 'Counsellor', 'Staff', 1, NOW(), NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at=NOW();

SET @counsellor_user_id = LAST_INSERT_ID();
INSERT INTO user_roles (user_id, role_id, assigned_at) 
SELECT @counsellor_user_id, @counsellor_role, NOW() 
WHERE NOT EXISTS (SELECT 1 FROM user_roles WHERE user_id=@counsellor_user_id AND role_id=@counsellor_role);

SELECT 'Accounts created successfully' as status;
