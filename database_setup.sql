-- SQL script for database setup

-- Table: parametres_generaux
CREATE TABLE IF NOT EXISTS `parametres_generaux` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `langue_defaut` VARCHAR(10) NOT NULL,
  `theme` VARCHAR(255) NOT NULL,
  `devise_monnaie` VARCHAR(10) NOT NULL,
  `pays` VARCHAR(255) NOT NULL,
  `region` VARCHAR(255) NOT NULL,
  `ville` VARCHAR(255) NOT NULL,
  `quartier` VARCHAR(255) NOT NULL,
  `nombre_langues` INT NOT NULL,
  `langue_1` VARCHAR(10) NOT NULL,
  `langue_2` VARCHAR(10) NULL,
  `langue_3` VARCHAR(10) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: parametres_ecole
CREATE TABLE IF NOT EXISTS `parametres_ecole` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nom_ecole` VARCHAR(255) NOT NULL,
  `sigle` VARCHAR(50) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `telephone` VARCHAR(50) NOT NULL,
  `site_web` VARCHAR(255) NULL,
  `cycle_etude` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: annees_academiques
CREATE TABLE IF NOT EXISTS `annees_academiques` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `libelle` VARCHAR(255) NOT NULL,
  `date_debut` DATE NOT NULL,
  `date_fin` DATE NOT NULL,
  `active` BOOLEAN DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `licences` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `cle_licence` VARCHAR(255) NOT NULL UNIQUE COMMENT 'License key string',
  `duree_jours` INT NOT NULL COMMENT 'Duration in days (e.g., 90, 180, 365)',
  `date_creation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When the license was generated',
  `date_activation` DATETIME NULL COMMENT 'When the license was first activated',
  `date_expiration` DATETIME NULL COMMENT 'Calculated: date_activation + duree_jours',
  `hachage_machine` VARCHAR(255) NULL COMMENT 'Machine hash or identifier',
  `est_active` BOOLEAN NOT NULL DEFAULT 0 COMMENT '0 = inactive, 1 = active',
  `utilisee_par_instance_id` VARCHAR(255) NULL COMMENT 'Identifier for the school instance using it (e.g., UUID or domain name)',
  `notes` TEXT NULL COMMENT 'Optional notes by SuperAdmin about this license'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Confirmation message (as a comment, actual execution message depends on SQL client)
-- Script completed. Tables `parametres_generaux`, `parametres_ecole`, and `annees_academiques` are ready.
