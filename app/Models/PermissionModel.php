<?php
namespace App\Models;
use App\Core\Model;
use PDO;

class PermissionModel extends Model {
    public function getAll() {
        $this->db->query("SELECT * FROM permissions ORDER BY groupe, nom");
        return $this->db->resultSet();
    }

    public function getById($id) {
        $this->db->query("SELECT * FROM permissions WHERE id = :id");
        $this->db->bind(':id', $id, PDO::PARAM_INT);
        return $this->db->single();
    }

    public function getByName($name) {
        $this->db->query("SELECT * FROM permissions WHERE nom = :nom");
        $this->db->bind(':nom', $name);
        return $this->db->single();
    }

    /**
     * Creates a new permission. Generally not used if permissions are predefined.
     * @param array $data Associative array with 'nom', 'description', 'groupe'.
     * @return bool True on success, false on failure.
     */
    public function create($data) {
        if (empty($data['nom'])) {
            error_log('PermissionModel::create: "nom" is required.');
            return false;
        }
        $this->db->query("INSERT INTO permissions (nom, description, groupe) VALUES (:nom, :description, :groupe)");
        $this->db->bind(':nom', $data['nom']);
        $this->db->bind(':description', $data['description'] ?? null);
        $this->db->bind(':groupe', $data['groupe'] ?? null);

        try {
            return $this->db->execute();
        } catch (\PDOException $e) {
            error_log("PermissionModel::create Error: " . $e->getMessage());
            if ($e->getCode() == '23000') { // Integrity constraint violation (e.g. unique key)
                return 'duplicate_nom';
            }
            return false;
        }
    }

    /**
     * Updates a permission. Generally not used.
     * @param int $id Permission ID.
     * @param array $data Associative array with 'nom', 'description', 'groupe'.
     * @return bool True on success, false on failure.
     */
    public function update($id, $data) {
        if (empty($id) || empty($data['nom'])) {
             error_log('PermissionModel::update: "id" and "nom" are required.');
            return false;
        }
        $this->db->query("UPDATE permissions SET nom = :nom, description = :description, groupe = :groupe WHERE id = :id");
        $this->db->bind(':id', $id, PDO::PARAM_INT);
        $this->db->bind(':nom', $data['nom']);
        $this->db->bind(':description', $data['description'] ?? null);
        $this->db->bind(':groupe', $data['groupe'] ?? null);

        try {
            return $this->db->execute();
        } catch (\PDOException $e) {
            error_log("PermissionModel::update Error: " . $e->getMessage());
            if ($e->getCode() == '23000') {
                return 'duplicate_nom';
            }
            return false;
        }
    }

    /**
     * Deletes a permission. Generally not used. Cascade delete in DB will affect role_permissions.
     * @param int $id Permission ID.
     * @return bool True on success, false on failure.
     */
    public function delete($id) {
        if (empty($id)) {
            error_log('PermissionModel::delete: "id" is required.');
            return false;
        }
        $this->db->query("DELETE FROM permissions WHERE id = :id");
        $this->db->bind(':id', $id, PDO::PARAM_INT);

        try {
            return $this->db->execute();
        } catch (\PDOException $e) {
            error_log("PermissionModel::delete Error: " . $e->getMessage());
            // Could be a foreign key constraint issue if not set to cascade (but it is in this schema)
            return false;
        }
    }
}
?>
