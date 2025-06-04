<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class AnneeAcademiqueModel extends Model {

    public function getAll() {
        $this->db->query("SELECT * FROM annees_academiques ORDER BY date_debut DESC");
        return $this->db->resultSet();
    }

    public function getById($id) {
        $this->db->query("SELECT * FROM annees_academiques WHERE id = :id");
        $this->db->bind(':id', $id, PDO::PARAM_INT);
        return $this->db->single();
    }

    public function findByLibelle($libelle, $excludeId = null) {
        $sql = "SELECT * FROM annees_academiques WHERE libelle = :libelle";
        $params = [':libelle' => $libelle];
        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }
        $this->db->query($sql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        return $this->db->single();
    }

    public function create($data) {
        // 'active' will be handled by activate method if needed after creation
        $this->db->query("INSERT INTO annees_academiques (libelle, date_debut, date_fin, active) VALUES (:libelle, :date_debut, :date_fin, :active)");
        $this->db->bind(':libelle', $data['libelle']);
        $this->db->bind(':date_debut', $data['date_debut']);
        $this->db->bind(':date_fin', $data['date_fin']);
        $this->db->bind(':active', $data['active'] ?? 0, PDO::PARAM_INT); // Default to inactive

        try {
            if ($this->db->execute()) {
                return $this->db->lastInsertId(); // Return ID if successful
            }
            return false;
        } catch (\PDOException $e) {
            error_log("AnneeAcademiqueModel::create Error: " . $e->getMessage());
            // Check for unique constraint violation for libelle
            if ($e->getCode() == '23000') { // SQLSTATE 23000: Integrity constraint violation
                 // Could check $e->errorInfo[1] for specific driver error code for duplicate key
                return 'duplicate_libelle';
            }
            return false;
        }
    }

    public function update($id, $data) {
        $this->db->query("UPDATE annees_academiques SET libelle = :libelle, date_debut = :date_debut, date_fin = :date_fin WHERE id = :id");
        $this->db->bind(':id', $id, PDO::PARAM_INT);
        $this->db->bind(':libelle', $data['libelle']);
        $this->db->bind(':date_debut', $data['date_debut']);
        $this->db->bind(':date_fin', $data['date_fin']);
        // Note: 'active' status is not updated here; use activate() method for that.

        try {
            return $this->db->execute();
        } catch (\PDOException $e) {
            error_log("AnneeAcademiqueModel::update Error: " . $e->getMessage());
             if ($e->getCode() == '23000') {
                return 'duplicate_libelle';
            }
            return false;
        }
    }

    public function delete($id) {
        // Ensure the year is not active before deleting
        $year = $this->getById($id);
        if ($year && $year->active) {
            // error_log("AnneeAcademiqueModel::delete Error: Cannot delete an active academic year.");
            return 'error_active'; // Custom error code for active year
        }

        $this->db->query("DELETE FROM annees_academiques WHERE id = :id");
        $this->db->bind(':id', $id, PDO::PARAM_INT);

        try {
            return $this->db->execute();
        } catch (\PDOException $e) {
            // Handle potential foreign key constraint errors if this year is linked
            error_log("AnneeAcademiqueModel::delete Error: " . $e->getMessage());
            if ($e->getCode() == '23000') { // Integrity constraint violation
                return 'error_linked'; // Custom error for linked records
            }
            return false;
        }
    }

    public function activate($id) {
        // Deactivate all other years first
        $this->db->query("UPDATE annees_academiques SET active = 0");
        if (!$this->db->execute()) {
            error_log("AnneeAcademiqueModel::activate Error: Failed to deactivate other years.");
            return false; // Failed to deactivate others
        }

        // Then activate the selected one
        if ($id > 0) { // Ensure $id is valid before trying to activate
            $this->db->query("UPDATE annees_academiques SET active = 1 WHERE id = :id");
            $this->db->bind(':id', $id, PDO::PARAM_INT);
            if (!$this->db->execute()) {
                 error_log("AnneeAcademiqueModel::activate Error: Failed to activate year ID " . $id);
                return false; // Failed to activate target year
            }
        }
        // If $id was 0 (or invalid), all years are now deactivated.
        return true;
    }

    public function getActiveYear() {
        $this->db->query("SELECT * FROM annees_academiques WHERE active = 1 LIMIT 1");
        return $this->db->single();
    }
}
?>
