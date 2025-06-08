<?php
namespace App\Models;
use App\Core\Model;

class ReductionTypeModel extends Model {

    public function getAll($options = []) {
        $sql = "SELECT * FROM reductions_types";

        $orderBy = $options['orderBy'] ?? 'nom_reduction';
        $orderDir = $options['orderDir'] ?? 'ASC';
        $sql .= " ORDER BY " . $orderBy . " " . $orderDir;

        if (isset($options['limit'])) {
            $sql .= " LIMIT " . (int)$options['limit'] . (isset($options['offset']) ? " OFFSET " . (int)$options['offset'] : "");
        }
        $this->db->query($sql);
        return $this->db->resultSet();
    }

    public function getTotalCount() {
        $this->db->query("SELECT COUNT(id) as total FROM reductions_types");
        $result = $this->db->single();
        return $result ? (int)$result->total : 0;
    }

    public function getById($id) {
        $this->db->query("SELECT * FROM reductions_types WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function findByName($name) {
        $this->db->query("SELECT * FROM reductions_types WHERE nom_reduction = :nom_reduction");
        $this->db->bind(':nom_reduction', $name);
        return $this->db->single();
    }

    public function create($data) {
        if ($this->findByName($data['nom_reduction'])) {
            return 'duplicate_name';
        }

        $sql = "INSERT INTO reductions_types (nom_reduction, description, pourcentage_reduction, montant_fixe_reduction, conditions_application, statut)
                VALUES (:nom_reduction, :description, :pourcentage_reduction, :montant_fixe_reduction, :conditions_application, :statut)";
        $this->db->query($sql);
        $this->db->bind(':nom_reduction', $data['nom_reduction']);
        $this->db->bind(':description', $data['description'] ?? null);
        $this->db->bind(':pourcentage_reduction', $data['pourcentage_reduction'] ?? null);
        $this->db->bind(':montant_fixe_reduction', $data['montant_fixe_reduction'] ?? null);
        $this->db->bind(':conditions_application', $data['conditions_application'] ?? null);
        $this->db->bind(':statut', $data['statut'] ?? 'actif');

        try {
            if ($this->db->execute()) {
                return $this->db->lastInsertId();
            }
        } catch (\PDOException $e) {
            if ($e->getCode() == '23000') { // Integrity constraint (e.g. unique name - though checked above)
                return 'duplicate_name'; // Or a more generic error
            }
            error_log("Error creating reduction type: " . $e->getMessage());
            return false;
        }
        return false;
    }

    public function update($id, $data) {
        $existing = $this->getById($id);
        if (!$existing) return 'not_found';

        if (isset($data['nom_reduction']) && $data['nom_reduction'] !== $existing->nom_reduction) {
            if ($this->findByName($data['nom_reduction'])) {
                return 'duplicate_name';
            }
        }

        $sql = "UPDATE reductions_types SET
                    nom_reduction = :nom_reduction,
                    description = :description,
                    pourcentage_reduction = :pourcentage_reduction,
                    montant_fixe_reduction = :montant_fixe_reduction,
                    conditions_application = :conditions_application,
                    statut = :statut,
                    date_modification = NOW()
                WHERE id = :id";
        $this->db->query($sql);
        $this->db->bind(':id', $id);
        $this->db->bind(':nom_reduction', $data['nom_reduction'] ?? $existing->nom_reduction);
        $this->db->bind(':description', $data['description'] ?? $existing->description);
        $this->db->bind(':pourcentage_reduction', $data['pourcentage_reduction'] ?? $existing->pourcentage_reduction);
        $this->db->bind(':montant_fixe_reduction', $data['montant_fixe_reduction'] ?? $existing->montant_fixe_reduction);
        $this->db->bind(':conditions_application', $data['conditions_application'] ?? $existing->conditions_application);
        $this->db->bind(':statut', $data['statut'] ?? $existing->statut);

        try {
            return $this->db->execute();
        } catch (\PDOException $e) {
            if ($e->getCode() == '23000') {
                return 'duplicate_name';
            }
            error_log("Error updating reduction type " . $id . ": " . $e->getMessage());
            return false;
        }
    }

    public function delete($id) {
        // Check for dependencies in eleve_reductions_appliquees before deleting
        $this->db->query("SELECT COUNT(id) as count FROM eleve_reductions_appliquees WHERE reduction_type_id = :id");
        $this->db->bind(':id', $id);
        $result = $this->db->single();
        if ($result && $result->count > 0) {
            return 'in_use'; // Cannot delete, it's being used
        }

        $this->db->query("DELETE FROM reductions_types WHERE id = :id");
        $this->db->bind(':id', $id);
        try {
            return $this->db->execute();
        } catch (\PDOException $e) {
            error_log("Error deleting reduction type " . $id . ": " . $e->getMessage());
            return false;
        }
    }

    public function getStatuts() {
        return ['actif', 'inactif'];
    }
}
?>
