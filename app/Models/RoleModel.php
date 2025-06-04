<?php
namespace App\Models;
use App\Core\Model;
use PDO;

class RoleModel extends Model {
    public function getAll() {
        $this->db->query("SELECT * FROM roles ORDER BY nom");
        return $this->db->resultSet();
    }

    public function getById($id) {
        $this->db->query("SELECT * FROM roles WHERE id = :id");
        $this->db->bind(':id', $id, PDO::PARAM_INT);
        return $this->db->single();
    }

    public function getByName($name) {
        $this->db->query("SELECT * FROM roles WHERE nom = :nom");
        $this->db->bind(':nom', $name);
        return $this->db->single();
    }

    public function create($data) {
        if (empty($data['nom'])) {
            error_log('RoleModel::create: "nom" is required.');
            return false;
        }
        $this->db->query("INSERT INTO roles (nom, description, est_systeme) VALUES (:nom, :description, :est_systeme)");
        $this->db->bind(':nom', $data['nom']);
        $this->db->bind(':description', $data['description'] ?? null);
        $this->db->bind(':est_systeme', isset($data['est_systeme']) ? (int)(bool)$data['est_systeme'] : 0, PDO::PARAM_INT);

        try {
            if ($this->db->execute()) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch (\PDOException $e) {
            error_log("RoleModel::create Error: " . $e->getMessage());
            if ($e->getCode() == '23000') { // Integrity constraint violation (e.g. unique key for nom)
                return 'duplicate_nom';
            }
            return false;
        }
    }

    public function update($id, $data) {
        $role = $this->getById($id);
        if (!$role) {
            return false; // Role not found
        }
        // Prevent changing 'nom' or 'est_systeme' status of system roles
        if ($role->est_systeme) {
            if (isset($data['nom']) && $data['nom'] !== $role->nom) {
                 error_log("RoleModel::update Error: Cannot change name of a system role (ID: $id).");
                return 'error_system_role_name_change';
            }
             if (isset($data['est_systeme']) && (bool)$data['est_systeme'] != (bool)$role->est_systeme) {
                error_log("RoleModel::update Error: Cannot change system status of a system role (ID: $id).");
                return 'error_system_role_status_change';
            }
        }


        $sql = "UPDATE roles SET nom = :nom, description = :description";
        // Only allow updating est_systeme if it's currently NOT a system role
        // (i.e., cannot make an existing role a system role, or a system role not a system role via this method)
        // This logic is better handled by disallowing 'est_systeme' in $data for updates or checking its value.
        // For simplicity, we don't update 'est_systeme' here. It's set at creation.
        $sql .= " WHERE id = :id";
        if ($role->est_systeme) { // If it's a system role, don't allow changing its name
             $sql .= " AND nom = :current_nom_for_system_role"; // Ensures name doesn't change
        }


        $this->db->query($sql);
        $this->db->bind(':id', $id, PDO::PARAM_INT);
        $this->db->bind(':nom', $data['nom']);
        $this->db->bind(':description', $data['description'] ?? null);
        if ($role->est_systeme) {
             $this->db->bind(':current_nom_for_system_role', $role->nom);
        }

        try {
            return $this->db->execute();
        } catch (\PDOException $e) {
            error_log("RoleModel::update Error: " . $e->getMessage());
             if ($e->getCode() == '23000') {
                return 'duplicate_nom';
            }
            return false;
        }
    }

    public function delete($id) {
        $role = $this->getById($id);
        if ($role && $role->est_systeme) {
            error_log("RoleModel::delete Error: Cannot delete system role (ID: $id).");
            return 'error_system_role'; // Custom error code for system role
        }

        $this->db->query("DELETE FROM roles WHERE id = :id AND est_systeme = 0");
        $this->db->bind(':id', $id, PDO::PARAM_INT);

        try {
            return $this->db->execute();
        } catch (\PDOException $e) {
            // Handle foreign key constraints if role is in use (e.g., in role_permissions, user_roles)
            // The DB schema uses ON DELETE CASCADE for role_permissions and user_roles, so this should be okay.
            error_log("RoleModel::delete Error: " . $e->getMessage());
            if ($e->getCode() == '23000') {
                return 'error_in_use'; // Role is linked in other tables not set to cascade (should not happen with current schema)
            }
            return false;
        }
    }
}
?>
