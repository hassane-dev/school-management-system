-- SQL script for Sprint 3 database updates

-- IMPORTANT PRELIMINARY STEP (Manual Check Recommended):
-- The 'utilisateurs' table currently has a 'role_id' column. This column needs to be removed
-- as user roles will now be managed by the new 'accreditations' table.
-- Before running the `DROP COLUMN` command, ensure you have a backup if necessary.
-- If the column `role_id` or its foreign key constraint does not exist, the following commands might error.
-- You might need to identify the specific foreign key constraint name for `utilisateurs.role_id` if it's not a common one.
-- Example steps to try and remove a foreign key (names might vary):
-- ALTER TABLE `utilisateurs` DROP FOREIGN KEY `utilisateurs_ibfk_1`; -- Replace 'utilisateurs_ibfk_1' with actual FK name if different
-- ALTER TABLE `utilisateurs` DROP INDEX `role_id`; -- If an index exists on role_id (common for FKs)

-- Then, drop the column:
-- For MySQL 8.0+ you can use: ALTER TABLE `utilisateurs` DROP COLUMN IF EXISTS `role_id`;
-- For older versions, the command below will error if the column doesn't exist.
ALTER TABLE `utilisateurs` DROP COLUMN `role_id`;
-- Note: Data from the old `role_id` column should be migrated to the `accreditations` table manually or via a separate script.


-- Table: accreditations (links users to roles, many-to-many)
CREATE TABLE IF NOT EXISTS `accreditations` (
  `utilisateur_id` INT,
  `role_id` INT,
  `date_accreditation` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`utilisateur_id`, `role_id`),
  FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Placeholder for matieres table (Subjects)
-- This table is assumed to exist or is created here for foreign key integrity.
CREATE TABLE IF NOT EXISTS `matieres` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nom` VARCHAR(255) NOT NULL UNIQUE,
    -- other fields like code, description etc.
    `date_creation` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Placeholder for classes table
-- This table is assumed to exist or is created here for foreign key integrity.
CREATE TABLE IF NOT EXISTS `classes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nom` VARCHAR(255) NOT NULL UNIQUE, -- e.g., "Seconde A", "Terminale C"
    `niveau` VARCHAR(100), -- e.g., "Seconde", "Terminale"
    -- other fields like capacity, school_cycle_id etc.
    `date_creation` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert some sample data into matieres and classes for testing if needed
INSERT IGNORE INTO `matieres` (nom) VALUES ('Mathématiques'), ('Physique'), ('Français'), ('Philosophie'), ('Anglais');
INSERT IGNORE INTO `classes` (nom, niveau) VALUES
('Seconde L', 'Seconde'),
('Seconde S', 'Seconde'),
('Première L1', 'Première'),
('Première L2', 'Première'),
('Première S1', 'Première'),
('Première S2', 'Première'),
('Terminale L1', 'Terminale'),
('Terminale L2', 'Terminale'),
('Terminale S1', 'Terminale'),
('Terminale S2', 'Terminale');


-- Table: enseignements (links teachers to subjects in classes for specific academic years)
CREATE TABLE IF NOT EXISTS `enseignements` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `utilisateur_id` INT NOT NULL COMMENT 'References the teacher from utilisateurs table',
  `matiere_id` INT NOT NULL,
  `classe_id` INT NOT NULL,
  `annee_id` INT NOT NULL COMMENT 'References annees_academiques table',
  `date_affectation` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  FOREIGN KEY (`matiere_id`) REFERENCES `matieres`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  FOREIGN KEY (`classe_id`) REFERENCES `classes`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  FOREIGN KEY (`annee_id`) REFERENCES `annees_academiques`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  UNIQUE KEY `unique_enseignement` (`utilisateur_id`, `matiere_id`, `classe_id`, `annee_id`)
  -- A teacher teaching the same subject in the same class in the same year should be unique.
  -- Changed unique key to include utilisateur_id. A subject might be taught by different teachers in different classes/years,
  -- but one teacher typically doesn't get assigned the *exact* same class+subject+year combo multiple times.
  -- The original unique key was (`matiere_id`, `classe_id`, `annee_id`), which implies a subject can only be taught once
  -- in a class for a year, regardless of teacher. This might be too restrictive if co-teaching is allowed or if a teacher changes mid-year.
  -- The new unique key (`utilisateur_id`, `matiere_id`, `classe_id`, `annee_id`) seems more appropriate.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Confirmation message (as a comment, actual execution message depends on SQL client)
-- Script completed.
-- - `utilisateurs` table: `role_id` column removal was attempted (manual verification advised).
-- - `accreditations` table created.
-- - `matieres` and `classes` tables (placeholders) created with sample data.
-- - `enseignements` table created.
-- Ensure all referenced tables (utilisateurs, roles, annees_academiques) exist and are populated as needed.
