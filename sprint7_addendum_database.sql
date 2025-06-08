-- sprint7_addendum_database.sql

-- Add the new 'type_academique' column to the 'matieres' table
ALTER TABLE matieres
ADD COLUMN IF NOT EXISTS type_academique VARCHAR(50) NULL DEFAULT NULL COMMENT 'e.g., Scientifique, Littéraire, Technique, Arts, Sportif'
AFTER type_matiere;

-- Update existing sample data with some academic types
UPDATE matieres SET type_academique = 'Scientifique' WHERE nom IN ('Mathématiques', 'Physique-Chimie', 'Sciences de la Vie et de la Terre', 'Informatique') AND type_academique IS NULL;
UPDATE matieres SET type_academique = 'Littéraire' WHERE nom IN ('Français', 'Anglais LV1', 'Histoire-Géographie', 'Philosophie') AND type_academique IS NULL;
UPDATE matieres SET type_academique = 'Arts' WHERE nom = 'Musique' AND type_academique IS NULL;
UPDATE matieres SET type_academique = 'Sportif' WHERE nom = 'Éducation Physique et Sportive' AND type_academique IS NULL;

-- Add an index on the new column for filtering
ALTER TABLE matieres ADD INDEX IF NOT EXISTS idx_type_academique_matieres (type_academique);


-- Create classe_matiere_eligibilite table
CREATE TABLE IF NOT EXISTS classe_matiere_eligibilite (
    classe_id INT NOT NULL,
    matiere_id INT NOT NULL,
    PRIMARY KEY (classe_id, matiere_id),
    FOREIGN KEY (classe_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (matiere_id) REFERENCES matieres(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Defines which subjects are eligible to be taught in a class';

-- Optional: Seed some initial eligibility data
INSERT IGNORE INTO classe_matiere_eligibilite (classe_id, matiere_id)
    SELECT c.id, m.id FROM classes c, matieres m WHERE c.nom = 'Seconde L' AND m.nom = 'Mathématiques';
INSERT IGNORE INTO classe_matiere_eligibilite (classe_id, matiere_id)
    SELECT c.id, m.id FROM classes c, matieres m WHERE c.nom = 'Seconde L' AND m.nom = 'Français';
INSERT IGNORE INTO classe_matiere_eligibilite (classe_id, matiere_id)
    SELECT c.id, m.id FROM classes c, matieres m WHERE c.nom = 'Première S1' AND m.nom = 'Physique-Chimie';
INSERT IGNORE INTO classe_matiere_eligibilite (classe_id, matiere_id)
    SELECT c.id, m.id FROM classes c, matieres m WHERE c.nom = 'Première S1' AND m.nom = 'Mathématiques';

-- New Permission for Managing Class-Subject Eligibility
INSERT IGNORE INTO permissions (nom, description, groupe) VALUES
('manage_classe_matiere_eligibilite', 'Manage which subjects are eligible for each class', 'Academic Management');

-- Assign this permission to Admin and SuperAdmin (using robust INSERT IGNORE ... SELECT)
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r, permissions p
WHERE r.nom = 'Admin' AND p.nom = 'manage_classe_matiere_eligibilite';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r, permissions p
WHERE r.nom = 'SuperAdmin' AND p.nom = 'manage_classe_matiere_eligibilite';


-- Curriculum Management Tables
CREATE TABLE IF NOT EXISTS programmes_scolaires (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(255) NOT NULL UNIQUE,
    annee_academique_id INT NOT NULL,
    description TEXT NULL,
    statut VARCHAR(50) NOT NULL DEFAULT 'brouillon' COMMENT 'e.g., brouillon, actif, archive',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (annee_academique_id) REFERENCES annees_academiques(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Curriculum headers or definitions';

CREATE TABLE IF NOT EXISTS programme_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    programme_id INT NOT NULL,
    matiere_id INT NOT NULL,
    notes_coefficient DECIMAL(5,2) NULL DEFAULT NULL,
    heures_par_semaine DECIMAL(4,1) NULL DEFAULT NULL,
    description_detail TEXT NULL,
    ordre INT DEFAULT 0,
    FOREIGN KEY (programme_id) REFERENCES programmes_scolaires(id) ON DELETE CASCADE,
    FOREIGN KEY (matiere_id) REFERENCES matieres(id) ON DELETE RESTRICT,
    UNIQUE KEY uk_programme_matiere (programme_id, matiere_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Details of subjects within each curriculum';

-- Sample Data for Curriculum
INSERT IGNORE INTO programmes_scolaires (nom, annee_academique_id, statut, description)
SELECT
    'Programme National 6ème 2023-2024',
    (SELECT id FROM annees_academiques WHERE libelle = '2023-2024' LIMIT 1),
    'actif',
    'Programme officiel pour les classes de 6ème pour l''année 2023-2024.'
WHERE EXISTS (SELECT id FROM annees_academiques WHERE libelle = '2023-2024');

INSERT IGNORE INTO programmes_scolaires (nom, annee_academique_id, statut, description)
SELECT
    'Programme Lycée Scientifique Interne 2023-2024',
    (SELECT id FROM annees_academiques WHERE libelle = '2023-2024' LIMIT 1),
    'brouillon',
    'Programme interne pour les filières scientifiques du lycée.'
WHERE EXISTS (SELECT id FROM annees_academiques WHERE libelle = '2023-2024');


-- Sample Details (ensure relevant programme_id and matiere_id exist)
INSERT IGNORE INTO programme_details (programme_id, matiere_id, notes_coefficient, heures_par_semaine, ordre)
SELECT
    (SELECT id FROM programmes_scolaires WHERE nom LIKE 'Programme National 6ème 2023-2024' LIMIT 1),
    (SELECT id FROM matieres WHERE nom = 'Mathématiques' LIMIT 1),
    2.5, 5.0, 1
WHERE EXISTS (SELECT id FROM programmes_scolaires WHERE nom LIKE 'Programme National 6ème 2023-2024') AND EXISTS (SELECT id FROM matieres WHERE nom = 'Mathématiques');

INSERT IGNORE INTO programme_details (programme_id, matiere_id, notes_coefficient, heures_par_semaine, ordre)
SELECT
    (SELECT id FROM programmes_scolaires WHERE nom LIKE 'Programme National 6ème 2023-2024' LIMIT 1),
    (SELECT id FROM matieres WHERE nom = 'Français' LIMIT 1),
    3.0, 6.0, 2
WHERE EXISTS (SELECT id FROM programmes_scolaires WHERE nom LIKE 'Programme National 6ème 2023-2024') AND EXISTS (SELECT id FROM matieres WHERE nom = 'Français');

INSERT IGNORE INTO programme_details (programme_id, matiere_id, notes_coefficient, heures_par_semaine, ordre)
SELECT
    (SELECT id FROM programmes_scolaires WHERE nom LIKE 'Programme National 6ème 2023-2024' LIMIT 1),
    (SELECT id FROM matieres WHERE nom = 'Histoire-Géographie' LIMIT 1),
    1.5, 3.0, 3
WHERE EXISTS (SELECT id FROM programmes_scolaires WHERE nom LIKE 'Programme National 6ème 2023-2024') AND EXISTS (SELECT id FROM matieres WHERE nom = 'Histoire-Géographie');

-- Permissions for Programmes Scolaires (Curriculum Management)
INSERT IGNORE INTO permissions (nom, description, groupe) VALUES
('view_programmes_scolaires', 'View list of curricula', 'Academic Management'),
('create_programme_scolaire', 'Create new curricula headers', 'Academic Management'),
('edit_programme_scolaire', 'Edit existing curricula headers', 'Academic Management'),
('delete_programme_scolaire', 'Delete curricula', 'Academic Management'),
('manage_programme_details', 'Manage subjects and their details within a curriculum', 'Academic Management');

-- Assign these new permissions to 'Admin' and 'SuperAdmin' roles
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r, permissions p
WHERE r.nom = 'Admin' AND p.nom IN (
    'view_programmes_scolaires',
    'create_programme_scolaire',
    'edit_programme_scolaire',
    'delete_programme_scolaire',
    'manage_programme_details'
);

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r, permissions p
WHERE r.nom = 'SuperAdmin' AND p.nom IN (
    'view_programmes_scolaires',
    'create_programme_scolaire',
    'edit_programme_scolaire',
    'delete_programme_scolaire',
    'manage_programme_details'
);
-- Note: SuperAdmin also gets all permissions from a separate statement in sprint5_database.sql if run sequentially.

-- End of sprint7_addendum_database.sql
-- MySQL 8.0+ features like ADD COLUMN IF NOT EXISTS and ADD INDEX IF NOT EXISTS are used.
-- For older versions, 'IF NOT EXISTS' should be removed, and script execution order managed carefully.
