-- sprint3_database.sql

-- Table: permissions
CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL UNIQUE, -- e.g., 'manage_users', 'edit_settings'
    description TEXT NULL,
    groupe VARCHAR(50) NULL -- For grouping permissions in UI, e.g., 'Settings', 'Users'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: roles
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL UNIQUE, -- e.g., 'SuperAdmin', 'Admin', 'Enseignant'
    description TEXT NULL,
    est_systeme BOOLEAN NOT NULL DEFAULT 0 -- 1 for system roles like SuperAdmin that cannot be deleted
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: role_permissions (Pivot for Roles-Permissions)
CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: user_roles (Pivot for Users-Roles with optional academic year context)
CREATE TABLE IF NOT EXISTS user_roles (
    id INT AUTO_INCREMENT PRIMARY KEY, -- Surrogate key
    utilisateur_id INT NOT NULL,
    role_id INT NOT NULL,
    annee_academique_id INT NULL, -- NULL for global roles, or specific year context
    UNIQUE KEY uk_user_role_year (utilisateur_id, role_id, annee_academique_id), -- Ensures combination is unique
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (annee_academique_id) REFERENCES annees_academiques(id) ON DELETE SET NULL ON UPDATE CASCADE -- If year deleted, role becomes global or could be deleted based on policy
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Seed Permissions (Static Predefined)
INSERT IGNORE INTO permissions (nom, description, groupe) VALUES
('view_admin_dashboard', 'View Admin Dashboard', 'Admin'),
-- General Settings
('manage_general_settings', 'Manage General Application Settings', 'Settings'),
-- Academic Years
('view_academic_years', 'View Academic Years List', 'Settings'),
('create_academic_year', 'Create New Academic Years', 'Settings'),
('edit_academic_year', 'Edit Existing Academic Years', 'Settings'),
('delete_academic_year', 'Delete Academic Years', 'Settings'),
('activate_academic_year', 'Activate Academic Years', 'Settings'),
-- School Settings
('manage_school_settings', 'Manage School Specific Settings', 'Settings'),
-- Roles & Permissions (Sprint 3 specific)
('view_roles', 'View Roles List', 'Users & Roles'),
('create_role', 'Create New Roles', 'Users & Roles'),
('edit_role', 'Edit Existing Roles (name, description)', 'Users & Roles'),
('assign_permissions_to_role', 'Assign/Revoke Permissions for Roles', 'Users & Roles'),
('delete_role', 'Delete Roles (non-system)', 'Users & Roles'),
-- User Management
('view_users', 'View Users List', 'Users & Roles'),
('create_user', 'Create New Users', 'Users & Roles'),
('edit_user', 'Edit Existing Users', 'Users & Roles'),
('delete_user', 'Delete Users', 'Users & Roles'),
('assign_roles_to_user', 'Assign/Revoke Roles for Users (global or year-specific)', 'Users & Roles'),
('reactivate_user_account', 'Reactivate User Accounts Annually', 'Users & Roles'),
-- SuperAdmin specific
('access_superadmin_interface', 'Access Special SuperAdmin Interfaces', 'Admin'),
-- Sprint 3 - Teacher & Assignment Management Permissions (Ensure these are in the list)
('manage_teachers_accreditations', 'Manage teacher accreditations (assign roles)', 'Teachers'),
('view_teachers_list', 'View list of teachers', 'Teachers'),
('manage_enseignements', 'Manage teaching assignments (CRUD)', 'Assignments');


-- Seed Roles
INSERT IGNORE INTO roles (nom, description, est_systeme) VALUES
('SuperAdmin', 'Full access to all system features and settings.', 1),
('Admin', 'Administrator with broad access, but less than SuperAdmin.', 0),
('Enseignant', 'Teacher role with access to teaching-related features.', 0),
('Etudiant', 'Student role with access to student-specific information.', 0),
('Parent', 'Parent role with access to information about their children.', 0);


-- Assign ALL defined permissions to SuperAdmin role
-- This statement robustly assigns all permissions currently in the permissions table to the SuperAdmin role.
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT
    (SELECT id FROM roles WHERE nom = 'SuperAdmin'),
    p.id
FROM permissions p;

-- Example: Assign a subset of permissions to the Admin role
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT
    (SELECT id FROM roles WHERE nom = 'Admin'),
    p.id
FROM permissions p
WHERE p.nom IN (
    'view_admin_dashboard',
    'manage_general_settings',
    'view_academic_years',
    'create_academic_year',
    'edit_academic_year',
    'delete_academic_year',
    'activate_academic_year',
    'manage_school_settings',
    'view_roles',
    'view_users',
    'manage_teachers_accreditations',
    'view_teachers_list',
    'manage_enseignements'
);


-- Final check: Remove role_id from utilisateurs table if it exists
-- This was part of the transition from a single role per user to multiple roles via user_roles.
-- This statement is safe for MySQL 8.0+; for older versions, if the column doesn't exist, it will error.
-- In such cases, manual removal or a conditional drop in a migration script is advised.
ALTER TABLE utilisateurs DROP COLUMN IF EXISTS role_id;


-- Script completed. Tables for RBAC created and seeded.
-- SuperAdmin role has all permissions. Admin role has a subset.
-- The `utilisateurs.role_id` column is removed.
