-- sprint5_database.sql
-- This script assumes that sprint3_database.sql (defining permissions, roles, user_roles, utilisateurs tables)
-- and sprint4_database.sql (enhancing utilisateurs table) have been executed,
-- or that the necessary tables (utilisateurs, roles, permissions, role_permissions, user_roles, annees_academiques) exist.

-- Set SQL variables for the SuperAdmin user
SET @superAdminEmail = 'hasmixione@gmail.com';
SET @superAdminNom = 'Super Administrator (Hasmix)';
-- Password: H@s7511mat9611
-- BCRYPT Hash for 'H@s7511mat9611': $2y$10$KAGENdM9bU6ExLhymTkmEOALgmTfE1z7E6N3oBs42L9yDHIiWdCFe
SET @superAdminPasswordHash = '$2y$10$KAGENdM9bU6ExLhymTkmEOALgmTfE1z7E6N3oBs42L9yDHIiWdCFe';
SET @superAdminLang = 'fr'; -- Default language

-- Create or Update the SuperAdmin user in 'utilisateurs' table
-- Assumes 'email' column has a UNIQUE constraint for ON DUPLICATE KEY UPDATE to work as expected.
INSERT INTO utilisateurs (nom, email, mot_de_passe, statut_compte, langue_preferee, date_creation, date_modification)
VALUES (@superAdminNom, @superAdminEmail, @superAdminPasswordHash, 'actif', @superAdminLang, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    mot_de_passe = VALUES(mot_de_passe),
    nom = VALUES(nom),
    statut_compte = 'actif',            -- Ensure account is active
    langue_preferee = VALUES(langue_preferee),
    date_modification = NOW();

-- Define crucial SuperAdmin and other role-dashboard permissions (using INSERT IGNORE)
-- Ensures these permissions exist in the `permissions` table.
INSERT IGNORE INTO permissions (nom, description, groupe) VALUES
('access_superadmin_interface', 'Access the main SuperAdmin dashboard area', 'SuperAdmin'),
('access_license_management_interface', 'Access and manage software license keys', 'SuperAdmin'),
('access_enseignant_dashboard', 'Access Teacher Dashboard', 'Enseignant'),
('access_etudiant_dashboard', 'Access Student Dashboard', 'Etudiant'),
('access_parent_dashboard', 'Access Parent Dashboard', 'Parent');
-- Note: 'view_admin_dashboard' for regular Admin should exist from sprint3_database.sql seeding.
-- Other permissions like 'manage_general_settings', etc., are also assumed to be seeded by sprint3_database.sql.

-- Assign 'SuperAdmin' role to this user (globally, annee_academique_id is NULL)
-- This uses INSERT IGNORE to avoid error if the assignment already exists.
-- It relies on the unique key uk_user_role_year (utilisateur_id, role_id, annee_academique_id) on user_roles table.
INSERT IGNORE INTO user_roles (utilisateur_id, role_id, annee_academique_id)
SELECT
    (SELECT id FROM utilisateurs WHERE email = @superAdminEmail),
    (SELECT id FROM roles WHERE nom = 'SuperAdmin'),
    NULL;

-- Assign ALL permissions in the permissions table to the 'SuperAdmin' role.
-- This is the most robust way to ensure SuperAdmin has full capabilities.
-- INSERT IGNORE handles cases where some permissions might have been individually assigned before.
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT
    (SELECT id FROM roles WHERE nom = 'SuperAdmin'),
    p.id
FROM permissions p;


-- Assign specific dashboard access permissions to their respective roles
-- (view_admin_dashboard for Admin role should have been assigned in sprint3_database.sql or by the general SuperAdmin assignment)
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
JOIN permissions p ON
    (r.nom = 'Enseignant' AND p.nom = 'access_enseignant_dashboard') OR
    (r.nom = 'Etudiant' AND p.nom = 'access_etudiant_dashboard') OR
    (r.nom = 'Parent' AND p.nom = 'access_parent_dashboard');
-- Ensure Admin role has view_admin_dashboard if not covered by a broader assignment
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT (SELECT id FROM roles WHERE nom = 'Admin'), (SELECT id FROM permissions WHERE nom = 'view_admin_dashboard');


-- End of SuperAdmin user and core role-permission setup in sprint5_database.sql
-- Note: Ensure that the 'SuperAdmin' and other necessary roles ('Admin', 'Enseignant', 'Etudiant', 'Parent')
-- themselves exist (should be seeded in sprint3_database.sql).
-- This script focuses on the SuperAdmin user account, its direct role assignment,
-- ensuring critical permissions exist, and then assigning all permissions to SuperAdmin role,
-- plus dashboard permissions for other key roles.
-- The `ALTER TABLE utilisateurs DROP COLUMN IF EXISTS role_id;` was in sprint3_database.sql and should run once.
-- If re-running all SQL from scratch, ensure sprint3_database.sql is run before this.
