-- sprint4_database.sql

-- Ensure the utilisateurs table has all necessary columns for Sprint 4 User Management.
-- Some columns like nom, email, mot_de_passe should ideally exist from initial user setup (e.g., Sprint 3 for Auth).
-- This script focuses on adding new columns or ensuring they exist as per Sprint 4 requirements.

-- Add 'statut_compte' column
ALTER TABLE utilisateurs
ADD COLUMN IF NOT EXISTS statut_compte VARCHAR(20) NOT NULL DEFAULT 'actif' COMMENT 'e.g., actif, inactif, suspendu, en_attente_validation';

-- Add 'date_derniere_connexion' column
ALTER TABLE utilisateurs
ADD COLUMN IF NOT EXISTS date_derniere_connexion DATETIME NULL;

-- Add 'date_creation' column if it doesn't exist (some DBs add this automatically)
-- Forcing it to ensure consistency. If it exists, this might need adjustment depending on DB engine.
-- A better approach for existing tables is to check information_schema before altering.
-- For this script, assuming it might not exist or needs to match this definition.
ALTER TABLE utilisateurs
ADD COLUMN IF NOT EXISTS date_creation DATETIME DEFAULT CURRENT_TIMESTAMP;

-- Add 'date_modification' column
ALTER TABLE utilisateurs
ADD COLUMN IF NOT EXISTS date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Add 'langue_preferee' column
ALTER TABLE utilisateurs
ADD COLUMN IF NOT EXISTS langue_preferee VARCHAR(10) DEFAULT 'fr';


-- Update existing users to have a default langue_preferee if it's NULL and the column was just added.
-- This is more of a data migration step, run once if needed.
-- UPDATE utilisateurs SET langue_preferee = 'fr' WHERE langue_preferee IS NULL;

-- Ensure email is unique if not already set (it should be from previous sprints)
-- ALTER TABLE utilisateurs ADD UNIQUE INDEX IF NOT EXISTS uk_email (email); -- 'IF NOT EXISTS' for ADD INDEX is DB specific.
-- Standard way: ALTER TABLE utilisateurs ADD CONSTRAINT uk_email UNIQUE (email); (will error if exists)
-- We assume 'email' column has a UNIQUE constraint from its initial creation.

-- Add index on statut_compte for filtering
-- Check if index exists before adding to avoid errors if script is run multiple times.
-- This is a simplified add; a robust migration would check information_schema.
-- For now, we assume it might not exist. If it does, this specific statement might error harmlessly or need adjustment.
-- A common way to handle this in scripts is to ignore errors for ADD INDEX if it's just for optimization.
-- ALTER TABLE utilisateurs ADD INDEX idx_statut_compte (statut_compte);
-- More compatible way to attempt adding index (will error if index or column with same name exists but generally safe for new index):
-- CREATE INDEX idx_statut_compte ON utilisateurs(statut_compte); -- This syntax is for some DBs, MySQL uses ADD INDEX.
-- Sticking to MySQL's ADD INDEX and noting it might error if it exists.

-- Note: The following is for MySQL. Other DBs might need different syntax for adding index if not exists.
-- A simple ADD INDEX is usually fine; if it exists, it might warn or error but not break subsequent statements usually.
ALTER TABLE utilisateurs ADD INDEX idx_statut_compte_utilisateurs (statut_compte);
-- Renamed index to be more specific to avoid potential name clashes if `idx_statut_compte` was too generic

-- Reminder: The 'utilisateurs' table should already have 'id' (PK), 'nom', 'email', 'mot_de_passe'.
-- The `role_id` column should have been removed as per sprint3_database.sql.

-- End of sprint4_database.sql
-- Further data migrations (like populating langue_preferee for existing users)
-- would typically be separate or handled by application logic upon user profile update.
