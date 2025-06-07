-- sprint7_database.sql

CREATE TABLE IF NOT EXISTS matieres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL UNIQUE,
    code VARCHAR(20) NULL UNIQUE,
    description TEXT NULL,
    coefficient DECIMAL(5,2) DEFAULT 1.00,
    type_matiere VARCHAR(50) NULL COMMENT 'e.g., Fondamentale, Optionnelle, Atelier',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert some sample subjects
INSERT IGNORE INTO matieres (nom, code, type_matiere, coefficient) VALUES
('Mathématiques', 'MATH01', 'Fondamentale', 2.00),
('Physique-Chimie', 'PHYSCH01', 'Fondamentale', 1.50),
('Français', 'FRAN01', 'Fondamentale', 2.00),
('Anglais LV1', 'ANGLV1', 'Fondamentale', 1.00),
('Histoire-Géographie', 'HISTGEO01', 'Fondamentale', 1.00),
('Éducation Physique et Sportive', 'EPS01', 'Optionnelle', 0.50),
('Musique', 'MUS01', 'Atelier', 0.50),
('Philosophie', 'PHILO01', 'Fondamentale', 1.00),
('Sciences de la Vie et de la Terre', 'SVT01', 'Fondamentale', 1.50),
('Informatique', 'INFO01', 'Atelier', 1.00);

-- Formal definition for classes table
-- DROP TABLE IF EXISTS classes; -- Use with caution if re-running and need to clear data
CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL UNIQUE, -- e.g., "Seconde A", "Terminale C"
    niveau VARCHAR(100) NULL,        -- e.g., "Seconde", "Terminale", "CM2"
    cycle VARCHAR(100) NULL,         -- e.g., "Lycée", "Collège", "Primaire", "Maternelle"
    description TEXT NULL,
    salle_par_defaut VARCHAR(50) NULL, -- Default classroom
    capacite INT NULL,                 -- Optional: student capacity
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert some sample classes
INSERT IGNORE INTO classes (nom, niveau, cycle, salle_par_defaut, capacite) VALUES
('Sixième A', '6ème', 'Collège', 'S101', 30),
('Cinquième B', '5ème', 'Collège', 'S102', 30),
('Quatrième A', '4ème', 'Collège', 'S201', 28),
('Troisième C', '3ème', 'Collège', 'S202', 28),
('Seconde L', 'Seconde', 'Lycée', 'L10', 35),
('Première S1', 'Première', 'Lycée', 'L11', 33),
('Terminale A2', 'Terminale', 'Lycée', 'L20', 30),
('CM1 A', 'CM1', 'Primaire', 'P1A', 25),
('Moyenne Section', 'Moyenne Section', 'Maternelle', 'M02', 20);

-- Permissions for Matieres (Subjects)
INSERT IGNORE INTO permissions (nom, description, groupe) VALUES
('view_matieres', 'View list of subjects', 'Academic Management'),
('create_matiere', 'Create new subjects', 'Academic Management'),
('edit_matiere', 'Edit existing subjects', 'Academic Management'),
('delete_matiere', 'Delete subjects', 'Academic Management');

-- Permissions for Classes
INSERT IGNORE INTO permissions (nom, description, groupe) VALUES
('view_classes', 'View list of classes', 'Academic Management'),
('create_classe', 'Create new classes', 'Academic Management'),
('edit_classe', 'Edit existing classes', 'Academic Management'),
('delete_classe', 'Delete classes', 'Academic Management');

-- Permissions for Enseignements (Assignments)
INSERT IGNORE INTO permissions (nom, description, groupe) VALUES
('view_enseignements', 'View teaching assignments', 'Academic Management'),
('create_enseignement', 'Create new teaching assignments', 'Academic Management'),
('edit_enseignement', 'Edit existing teaching assignments', 'Academic Management'),
('delete_enseignement', 'Delete teaching assignments', 'Academic Management');

-- Assign these new permissions to 'Admin' role
SET @adminRoleId = (SELECT id FROM roles WHERE nom = 'Admin');
SET @superAdminRoleId = (SELECT id FROM roles WHERE nom = 'SuperAdmin');

-- Assign Matieres permissions to Admin
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT @adminRoleId, p.id FROM permissions p WHERE p.nom IN (
    'view_matieres', 'create_matiere', 'edit_matiere', 'delete_matiere'
);
-- Also ensure SuperAdmin has them (though the blanket grant in sprint5_database.sql should cover it if run after this)
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT @superAdminRoleId, p.id FROM permissions p WHERE p.nom IN (
    'view_matieres', 'create_matiere', 'edit_matiere', 'delete_matiere'
);

-- Assign Classes permissions to Admin
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT @adminRoleId, p.id FROM permissions p WHERE p.nom IN (
    'view_classes', 'create_classe', 'edit_classe', 'delete_classe'
);
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT @superAdminRoleId, p.id FROM permissions p WHERE p.nom IN (
    'view_classes', 'create_classe', 'edit_classe', 'delete_classe'
);

-- Assign Enseignements permissions to Admin
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT @adminRoleId, p.id FROM permissions p WHERE p.nom IN (
    'view_enseignements', 'create_enseignement', 'edit_enseignement', 'delete_enseignement'
);
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT @superAdminRoleId, p.id FROM permissions p WHERE p.nom IN (
    'view_enseignements', 'create_enseignement', 'edit_enseignement', 'delete_enseignement'
);

-- Note: If sprint5_database.sql (which contains the "assign ALL permissions to SuperAdmin" command)
-- is run *after* this sprint7_database.sql, then SuperAdmin will automatically get all these new permissions.
-- The explicit assignments to SuperAdmin here are for completeness if scripts are run out of order
-- or if the "assign all" command was missed. INSERT IGNORE makes them safe to re-run.

-- sprint7_database.sql END
-- Note: The 'enseignements' table (linking teachers, subjects, classes, years)
-- was defined in sprint3_database_updates.sql and depends on 'matieres' and 'classes'.
-- Ensure that script is compatible or that these definitions are sufficient.
