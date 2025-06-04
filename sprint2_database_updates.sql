-- SQL script for Sprint 2 database updates

-- Table: roles
CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nom` VARCHAR(255) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: utilisateurs
CREATE TABLE IF NOT EXISTS `utilisateurs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nom` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `mot_de_passe` VARCHAR(255) NOT NULL,
  `langue_preferee` VARCHAR(5) DEFAULT 'fr',
  `role_id` INT,
  `date_creation` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: permissions
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nom` VARCHAR(255) NOT NULL UNIQUE,
  `description` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: role_permissions (junction table)
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `role_id` INT,
  `permission_id` INT,
  PRIMARY KEY (`role_id`, `permission_id`),
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default roles
INSERT INTO `roles` (`nom`) VALUES
('admin'),          -- Full access
('enseignant'),     -- Teacher-specific access
('parent_eleve'),   -- Parent/student access
('comptable'),      -- Accountant access
('super_admin');    -- Super administrator (system owner)

-- Example permissions (can be expanded significantly)
INSERT INTO `permissions` (`nom`, `description`) VALUES
('manage_users', 'Create, edit, delete users'),
('manage_roles', 'Create, edit, delete roles and assign permissions'),
('manage_settings', 'Access and modify all system settings'),
('view_settings_general', 'View general settings'),
('edit_settings_general', 'Edit general settings'),
('view_settings_school', 'View school settings'),
('edit_settings_school', 'Edit school settings'),
('manage_academic_years', 'Manage academic years (CRUD, activate/deactivate)'),
('view_reports', 'View system reports'),
('manage_finances', 'Manage financial records and accounting'),
('access_teacher_dashboard', 'Access teacher-specific dashboard and tools'),
('access_parent_dashboard', 'Access parent/student specific dashboard and information');

-- Assign all permissions to super_admin role (example)
-- In a real app, you would get IDs dynamically or have a seeder script
-- Assuming super_admin role_id is 5 and permission IDs are 1 through N
-- This is illustrative; a proper seeder would be more robust.
-- For now, this is better handled by application logic or a dedicated seeder script
-- after IDs are known. Example:
-- INSERT INTO `role_permissions` (`role_id`, `permission_id`)
-- SELECT r.id, p.id FROM roles r, permissions p WHERE r.nom = 'super_admin';

-- Admin role permissions (example subset)
-- INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
-- ((SELECT id FROM roles WHERE nom = 'admin'), (SELECT id FROM permissions WHERE nom = 'manage_users')),
-- ((SELECT id FROM roles WHERE nom = 'admin'), (SELECT id FROM permissions WHERE nom = 'manage_settings'));


-- Confirmation message (as a comment, actual execution message depends on SQL client)
-- Script completed. Tables `utilisateurs`, `roles`, `permissions`, and `role_permissions` are ready.
-- Default roles 'admin', 'enseignant', 'parent_eleve', 'comptable', 'super_admin' inserted.
-- Example permissions inserted.
-- Note: Assigning permissions to roles is typically done via application logic or a more sophisticated seeder.
