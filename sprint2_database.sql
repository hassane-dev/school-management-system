-- sprint2_database.sql

-- Ensures this table always targets ID=1 for its single row of settings.
-- This simplifies application logic as it doesn't need to discover the row.
CREATE TABLE IF NOT EXISTS parametres_generaux (
    id INT NOT NULL PRIMARY KEY DEFAULT 1,
    nom_application VARCHAR(255) DEFAULT 'Modular School Management',
    langue_site_par_defaut VARCHAR(10) DEFAULT 'fr',
    theme_defaut VARCHAR(50) DEFAULT 'light',
    devise_monnaie VARCHAR(10) DEFAULT 'XOF',
    pays_par_defaut VARCHAR(100) DEFAULT 'Senegal',
    ville_par_defaut VARCHAR(100) DEFAULT 'Dakar',
    fuseau_horaire VARCHAR(50) DEFAULT 'Africa/Dakar',
    format_date VARCHAR(20) DEFAULT 'd/m/Y',
    format_heure VARCHAR(20) DEFAULT 'H:i',
    email_contact_general VARCHAR(255) DEFAULT 'contact@example.com'
    -- The CHECK (id = 1) constraint was removed for broader MySQL/MariaDB compatibility.
    -- Application logic will enforce the single-row-with-id-1 pattern.
);

-- Insert default settings row if it doesn't exist.
-- The IGNORE keyword ensures that if a row with ID=1 already exists, this statement won't cause an error.
INSERT IGNORE INTO parametres_generaux (id, nom_application, langue_site_par_defaut, theme_defaut, devise_monnaie, pays_par_defaut, ville_par_defaut, fuseau_horaire, format_date, format_heure, email_contact_general)
VALUES (
    1,
    'Gestion Scolaire Modulaire',
    'fr',
    'default',
    'XOF',
    'Sénégal',
    'Dakar',
    'Africa/Dakar',
    'd/m/Y',
    'H:i',
    'contact@example.com'
);

-- Table for Academic Years
CREATE TABLE IF NOT EXISTS annees_academiques (
    id INT AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(100) NOT NULL UNIQUE, -- e.g., "2023-2024"
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    active BOOLEAN NOT NULL DEFAULT 0,
    CONSTRAINT chk_dates CHECK (date_fin > date_debut) -- Ensure end date is after start date
);

-- Insert some sample academic years
INSERT IGNORE INTO annees_academiques (libelle, date_debut, date_fin, active) VALUES
('2022-2023', '2022-09-01', '2023-07-31', 0),
('2023-2024', '2023-09-01', '2024-07-31', 1), -- Example of an active year
('2024-2025', '2024-09-01', '2025-07-31', 0);

-- Table for School Parameters
CREATE TABLE IF NOT EXISTS parametres_ecole (
    id INT NOT NULL PRIMARY KEY DEFAULT 1, -- Enforce ID 1, only one row
    nom_ecole VARCHAR(255) DEFAULT 'Mon École Exemplaire',
    logo_path VARCHAR(255) NULL, -- Path to image file, relative to public/uploads/
    type_etablissement ENUM('public', 'prive', 'parapublic') DEFAULT 'prive',
    adresse_physique TEXT NULL,
    boite_postale VARCHAR(100) NULL,
    telephone_contact VARCHAR(50) NULL,
    email_contact VARCHAR(255) NULL,
    site_web VARCHAR(255) NULL
);

-- Insert default school settings row if it doesn't exist
INSERT IGNORE INTO parametres_ecole (id, nom_ecole, type_etablissement)
VALUES (1, 'Mon École Du Futur', 'prive');
