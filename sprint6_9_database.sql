-- sprint6_9_database.sql

-- Table `eleves` (Students)
CREATE TABLE IF NOT EXISTS eleves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    matricule VARCHAR(20) NOT NULL UNIQUE COMMENT 'Student ID, can be auto-generated',
    nom_complet VARCHAR(255) NULL COMMENT 'Full name if not using separate prenom/nom_famille',
    prenom VARCHAR(100) NOT NULL,
    nom_famille VARCHAR(100) NOT NULL,
    date_naissance DATE NOT NULL,
    lieu_naissance VARCHAR(150) NOT NULL,
    sexe ENUM('M', 'F', 'Autre') NOT NULL COMMENT 'M=Masculin, F=Féminin, Autre=Other',
    nationalite VARCHAR(100) NULL,
    adresse TEXT NULL,
    telephone_mobile VARCHAR(25) NULL,
    email_eleve VARCHAR(255) NULL UNIQUE,
    nom_responsable_legal1 VARCHAR(150) NOT NULL COMMENT 'Parent/Guardian 1 Name',
    telephone_responsable_legal1 VARCHAR(25) NOT NULL,
    email_responsable_legal1 VARCHAR(255) NULL,
    profession_rl1 VARCHAR(100) NULL,
    nom_responsable_legal2 VARCHAR(150) NULL COMMENT 'Parent/Guardian 2 Name (Optional)',
    telephone_responsable_legal2 VARCHAR(25) NULL,
    email_responsable_legal2 VARCHAR(255) NULL, -- Added for Sprint 6.9 Step 1
    profession_rl2 VARCHAR(100) NULL, -- Added for Sprint 6.9 Step 1
    avatar_path VARCHAR(255) NULL COMMENT 'Path to student photo',
    statut_eleve VARCHAR(50) NOT NULL DEFAULT 'inscrit' COMMENT 'e.g., inscrit, ancien, transfere, radie, preinscrit',
    date_inscription_initiale DATE NOT NULL,
    date_radiation DATE NULL COMMENT 'Date of leaving/transfer',
    notes_medicales TEXT NULL,
    utilisateur_id INT NULL UNIQUE COMMENT 'Link to utilisateurs table if student has a user account', -- Added for Sprint 6.9 Step 1
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE SET NULL -- Added for Sprint 6.9 Step 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Student Information';

-- Table `affectations_eleves_classes` (Student Class Assignments)
CREATE TABLE IF NOT EXISTS affectations_eleves_classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    eleve_id INT NOT NULL,
    classe_id INT NOT NULL,
    annee_academique_id INT NOT NULL,
    date_affectation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, -- Changed from DATE to DATETIME
    statut_affectation VARCHAR(50) NOT NULL DEFAULT 'actif' COMMENT 'e.g., actif, termine, transfere_interne, inactive',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP, -- Added
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Added
    UNIQUE KEY uk_eleve_annee (eleve_id, annee_academique_id) COMMENT 'A student is in one class per academic year (active assignment)',
    FOREIGN KEY (eleve_id) REFERENCES eleves(id) ON DELETE CASCADE,
    FOREIGN KEY (classe_id) REFERENCES classes(id) ON DELETE RESTRICT,
    FOREIGN KEY (annee_academique_id) REFERENCES annees_academiques(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Assigns students to classes for an academic year';

-- Table `reductions_types` (Reduction Types)
CREATE TABLE IF NOT EXISTS reductions_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom_reduction VARCHAR(150) NOT NULL UNIQUE COMMENT 'e.g., Boursier, Orphelin, Fratrie',
    pourcentage_reduction DECIMAL(5,2) NULL COMMENT 'e.g., 10.00 for 10%. Used if montant_fixe is null.',
    montant_fixe_reduction DECIMAL(10,2) NULL COMMENT 'Used if pourcentage is null.',
    description TEXT NULL,
    conditions_application TEXT NULL, -- Added for Sprint 6.9 Step 1
    statut VARCHAR(20) NOT NULL DEFAULT 'actif' COMMENT 'actif, inactif', -- Added for Sprint 6.9 Step 1
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP, -- Added for Sprint 6.9 Step 1
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP -- Added for Sprint 6.9 Step 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Types of reductions applicable to fees';

-- Table `eleve_reductions_appliquees` (Applied Reductions to Student for a Year)
CREATE TABLE IF NOT EXISTS eleve_reductions_appliquees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    eleve_id INT NOT NULL,
    annee_academique_id INT NOT NULL,
    reduction_type_id INT NOT NULL,
    montant_calcule_reduction DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Actual calculated amount of reduction for this student (can be from % or fixed)', -- Added for Sprint 6.9 Step 1
    commentaire TEXT NULL, -- Renamed from notes for consistency
    date_application DATETIME DEFAULT CURRENT_TIMESTAMP, -- Changed from DATE to DATETIME
    applique_par_utilisateur_id INT NULL, -- Added for Sprint 6.9 Step 1
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP, -- Added for Sprint 6.9 Step 1
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Added for Sprint 6.9 Step 1
    UNIQUE KEY uk_eleve_annee_reduction (eleve_id, annee_academique_id, reduction_type_id),
    FOREIGN KEY (eleve_id) REFERENCES eleves(id) ON DELETE CASCADE,
    FOREIGN KEY (annee_academique_id) REFERENCES annees_academiques(id) ON DELETE CASCADE,
    FOREIGN KEY (reduction_type_id) REFERENCES reductions_types(id) ON DELETE RESTRICT,
    FOREIGN KEY (applique_par_utilisateur_id) REFERENCES utilisateurs(id) ON DELETE SET NULL -- Added for Sprint 6.9 Step 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Specific reduction types applied to a student for a year';

-- Table `mensualites` (Monthly Payments)
CREATE TABLE IF NOT EXISTS mensualites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    eleve_id INT NOT NULL,
    annee_academique_id INT NOT NULL,
    mois VARCHAR(20) NOT NULL COMMENT 'e.g., Octobre, Novembre or numeric 1-12 or specific like M1, M2',
    annee_concernee YEAR NOT NULL COMMENT 'The calendar year this month belongs to, e.g., 2023 for Octobre 2023',
    montant_attendu DECIMAL(10,2) NOT NULL,
    montant_reduction_applique DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Actual total reduction amount applied to this specific payment',
    montant_a_payer DECIMAL(10,2) GENERATED ALWAYS AS (montant_attendu - montant_reduction_applique) STORED COMMENT 'Net amount due after reduction', -- Added for Sprint 6.9 Step 1
    montant_paye DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    date_echeance DATE NULL COMMENT 'Due date for this installment', -- Renamed from date_limite_paiement
    date_dernier_paiement DATE NULL,
    statut_paiement ENUM('impaye', 'paye_partiel', 'paye_total', 'annule', 'exonere') NOT NULL DEFAULT 'impaye',
    commentaire TEXT NULL, -- Renamed from notes for consistency
    numero_recu_dernier_paiement VARCHAR(50) NULL UNIQUE, -- Renamed from recu_numero
    methode_dernier_paiement VARCHAR(50) NULL, -- Renamed from methode_paiement
    cree_par_utilisateur_id INT NULL COMMENT 'User who recorded/generated this',
    dernier_paiement_par_utilisateur_id INT NULL, -- Added for Sprint 6.9 Step 1
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP, -- Added for Sprint 6.9 Step 1
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Added for Sprint 6.9 Step 1
    UNIQUE KEY uk_eleve_annee_mois (eleve_id, annee_academique_id, mois, annee_concernee),
    FOREIGN KEY (eleve_id) REFERENCES eleves(id) ON DELETE CASCADE,
    FOREIGN KEY (annee_academique_id) REFERENCES annees_academiques(id) ON DELETE RESTRICT,
    FOREIGN KEY (cree_par_utilisateur_id) REFERENCES utilisateurs(id) ON DELETE SET NULL,
    FOREIGN KEY (dernier_paiement_par_utilisateur_id) REFERENCES utilisateurs(id) ON DELETE SET NULL -- Added for Sprint 6.9 Step 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Monthly fee payments for students';


-- SQL for Sprint 6.9 - New Permissions for Student Management and Accounting

-- Ensure roles 'Admin' and 'SuperAdmin' exist.
-- These are typically created in earlier migration scripts (e.g., sprint3_database.sql).
-- INSERT IGNORE INTO roles (nom, description, est_systeme) VALUES
-- ('Admin', 'Administrator with broad access, excluding super-user functions', 0),
-- ('SuperAdmin', 'Super Administrator with all privileges', 1);

-- Permissions for Eleves (Students)
INSERT IGNORE INTO permissions (nom, description, groupe) VALUES
('view_eleves', 'View list of students and their details', 'Student Management'),
('create_eleve', 'Create new student records', 'Student Management'),
('edit_eleve', 'Edit existing student records', 'Student Management'),
('delete_eleve', 'Delete student records', 'Student Management'),
('assign_eleve_classe', 'Assign students to classes for an academic year', 'Student Management'),
('import_export_eleves', 'Import or Export student data via CSV', 'Student Management'),
('reactivate_eleve_account', 'Reactivate student accounts (e.g., for new academic year)', 'Student Management');

-- Permissions for Comptabilite Eleve (Student Accounting)
INSERT IGNORE INTO permissions (nom, description, groupe) VALUES
('view_comptabilite_eleve', 'View student financial accounts and payment history', 'Student Accounting'),
('manage_comptabilite_eleve', 'Manage overall student accounting (e.g., generate monthly fees)', 'Student Accounting'),
('record_payment_eleve', 'Record payments made by students for fees', 'Student Accounting'),
('update_payment_status_eleve', 'Update status of student payments (e.g., to exempt, cancel)', 'Student Accounting'),
('apply_reduction_eleve', 'Apply or remove fee reductions for students', 'Student Accounting'),
('print_recu_eleve', 'Print payment receipts for students', 'Student Accounting'),
('generate_mensualites_eleve', 'Generate expected monthly payments for students', 'Student Accounting'),
('edit_mensualite_eleve', 'Edit details of a mensualite entry (e.g. amount due, notes)', 'Student Accounting'),
('remove_reduction_eleve', 'Explicit permission to remove an applied reduction (if different from apply_reduction_eleve for audit)', 'Student Accounting');


-- Permissions for Reduction Types (if managed via UI)
INSERT IGNORE INTO permissions (nom, description, groupe) VALUES
('view_reduction_types', 'View fee reduction types', 'Settings'),
('create_reduction_type', 'Create new fee reduction types', 'Settings'),
('edit_reduction_type', 'Edit existing fee reduction types', 'Settings'),
('delete_reduction_type', 'Delete fee reduction types', 'Settings');

-- Assign these new permissions to 'Admin' role
SET @adminRoleId = (SELECT id FROM roles WHERE nom = 'Admin');
SET @superAdminRoleId = (SELECT id FROM roles WHERE nom = 'SuperAdmin');

-- Check if role IDs were found before proceeding
-- SELECT @adminRoleId AS AdminRoleId, @superAdminRoleId AS SuperAdminRoleId; -- For verification, can be removed in production script

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT @adminRoleId, p.id FROM permissions p WHERE p.nom IN (
    'view_eleves', 'create_eleve', 'edit_eleve', 'delete_eleve', 'assign_eleve_classe', 'import_export_eleves', 'reactivate_eleve_account',
    'view_comptabilite_eleve', 'manage_comptabilite_eleve', 'record_payment_eleve', 'update_payment_status_eleve', 'apply_reduction_eleve', 'print_recu_eleve', 'generate_mensualites_eleve', 'edit_mensualite_eleve', 'remove_reduction_eleve',
    'view_reduction_types', 'create_reduction_type', 'edit_reduction_type', 'delete_reduction_type'
) AND @adminRoleId IS NOT NULL; -- Ensure role_id is not null

-- SuperAdmin gets all permissions.
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT @superAdminRoleId, p.id FROM permissions p WHERE p.nom IN (
    'view_eleves', 'create_eleve', 'edit_eleve', 'delete_eleve', 'assign_eleve_classe', 'import_export_eleves', 'reactivate_eleve_account',
    'view_comptabilite_eleve', 'manage_comptabilite_eleve', 'record_payment_eleve', 'update_payment_status_eleve', 'apply_reduction_eleve', 'print_recu_eleve', 'generate_mensualites_eleve', 'edit_mensualite_eleve', 'remove_reduction_eleve',
    'view_reduction_types', 'create_reduction_type', 'edit_reduction_type', 'delete_reduction_type'
) AND @superAdminRoleId IS NOT NULL; -- Ensure role_id is not null


SELECT 'Sprint 6.9 Permissions script executed and appended.' AS status;

-- End of sprint6_9_database.sql
-- Note: The CHECK constraint for reductions_types was omitted for broader MySQL/MariaDB version compatibility.
-- This logic (ensuring only percentage or fixed amount, not both) should be enforced at the application level.
-- Assumes `utilisateurs`, `classes`, `annees_academiques` tables exist from previous sprints.
-- The `montant_a_payer` in `mensualites` is now a generated column.
-- Some field names were updated for consistency (e.g. `commentaire` instead of `notes`, `date_echeance`, `numero_recu_dernier_paiement`).
-- Added missing `email_responsable_legal2`, `profession_rl2`, `utilisateur_id` to `eleves`.
-- Added `date_creation`, `date_modification` to `affectations_eleves_classes`, `reductions_types`, `eleve_reductions_appliquees`, `mensualites`.
-- Added `applique_par_utilisateur_id` to `eleve_reductions_appliquees`.
-- Added `dernier_paiement_par_utilisateur_id` to `mensualites`.
-- Updated `affectations_eleves_classes.date_affectation` to DATETIME.
-- Added `conditions_application` and `statut` to `reductions_types`.
-- Added `montant_calcule_reduction` to `eleve_reductions_appliquees`.
