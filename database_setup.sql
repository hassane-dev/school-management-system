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

-- Confirmation message (as a comment, actual execution message depends on SQL client)
-- Script completed. Tables `parametres_generaux`, `parametres_ecole`, and `annees_academiques` are ready.
