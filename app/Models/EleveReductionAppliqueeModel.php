<?php
namespace App\Models;
use App\Core\Model;

class EleveReductionAppliqueeModel extends Model {

    public function getAppliedReductionsForStudentYear($eleveId, $anneeAcademiqueId) {
        $sql = "SELECT era.*,
                       rt.nom_reduction, rt.description as reduction_description,
                       rt.pourcentage_reduction, rt.montant_fixe_reduction,
                       u.nom_complet as utilisateur_nom_complet
                FROM eleve_reductions_appliquees era
                JOIN reductions_types rt ON era.reduction_type_id = rt.id
                LEFT JOIN utilisateurs u ON era.applique_par_utilisateur_id = u.id
                WHERE era.eleve_id = :eleve_id AND era.annee_academique_id = :annee_id
                ORDER BY era.date_application DESC";

        $this->db->query($sql);
        $this->db->bind(':eleve_id', $eleveId);
        $this->db->bind(':annee_academique_id', $anneeAcademiqueId);
        return $this->db->resultSet();
    }

    public function getById($id) {
        $this->db->query("SELECT * FROM eleve_reductions_appliquees WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function applyReduction($data) {
        // Check if this specific reduction type is already applied for this student/year
        $this->db->query("SELECT id FROM eleve_reductions_appliquees
                          WHERE eleve_id = :eleve_id
                          AND annee_academique_id = :annee_academique_id
                          AND reduction_type_id = :reduction_type_id");
        $this->db->bind(':eleve_id', $data['eleve_id']);
        $this->db->bind(':annee_academique_id', $data['annee_academique_id']);
        $this->db->bind(':reduction_type_id', $data['reduction_type_id']);
        if ($this->db->single()) {
            return 'already_applied';
        }

        $sql = "INSERT INTO eleve_reductions_appliquees
                    (eleve_id, annee_academique_id, reduction_type_id, montant_calcule_reduction,
                     commentaire, date_application, applique_par_utilisateur_id)
                VALUES
                    (:eleve_id, :annee_academique_id, :reduction_type_id, :montant_calcule_reduction,
                     :commentaire, :date_application, :applique_par_utilisateur_id)";

        $this->db->query($sql);
        $this->db->bind(':eleve_id', $data['eleve_id']);
        $this->db->bind(':annee_academique_id', $data['annee_academique_id']);
        $this->db->bind(':reduction_type_id', $data['reduction_type_id']);
        $this->db->bind(':montant_calcule_reduction', $data['montant_calcule_reduction'] ?? 0); // This might be calculated based on type
        $this->db->bind(':commentaire', $data['commentaire'] ?? null);
        $this->db->bind(':date_application', $data['date_application'] ?? date('Y-m-d H:i:s'));
        $this->db->bind(':applique_par_utilisateur_id', $data['applique_par_utilisateur_id'] ?? null);

        try {
            if ($this->db->execute()) {
                return $this->db->lastInsertId();
            }
        } catch (\PDOException $e) {
            error_log("Error applying reduction: " . $e->getMessage());
            // Could check for $e->getCode() == '23000' for FK violations if necessary
            return false;
        }
        return false;
    }

    public function updateAppliedReduction($id, $data) {
        $existing = $this->getById($id);
        if (!$existing) return 'not_found';

        // Potentially re-check for duplicates if reduction_type_id changes (complex)
        // For now, assume only amount/commentary can be updated.
        $sql = "UPDATE eleve_reductions_appliquees SET
                    montant_calcule_reduction = :montant_calcule_reduction,
                    commentaire = :commentaire,
                    date_modification = NOW()
                WHERE id = :id";
        $this->db->query($sql);
        $this->db->bind(':id', $id);
        $this->db->bind(':montant_calcule_reduction', $data['montant_calcule_reduction'] ?? $existing->montant_calcule_reduction);
        $this->db->bind(':commentaire', $data['commentaire'] ?? $existing->commentaire);

        try {
            return $this->db->execute();
        } catch (\PDOException $e) {
            error_log("Error updating applied reduction " . $id . ": " . $e->getMessage());
            return false;
        }
    }

    public function removeReduction($id) {
        $this->db->query("DELETE FROM eleve_reductions_appliquees WHERE id = :id");
        $this->db->bind(':id', $id);
        try {
            return $this->db->execute();
        } catch (\PDOException $e) {
            error_log("Error removing applied reduction " . $id . ": " . $e->getMessage());
            return false;
        }
    }

    // Helper to calculate actual reduction amount based on type and a base amount
    // This might live in a service layer or here for convenience.
    public function calculateReductionAmount($reductionTypeId, $baseAmount) {
        $reductionTypeModel = new ReductionTypeModel(); // Potentially inject this dependency
        $type = $reductionTypeModel->getById($reductionTypeId);
        if (!$type) return 0;

        if (isset($type->pourcentage_reduction) && $type->pourcentage_reduction > 0) {
            return ($baseAmount * $type->pourcentage_reduction) / 100;
        } elseif (isset($type->montant_fixe_reduction) && $type->montant_fixe_reduction > 0) {
            return $type->montant_fixe_reduction;
        }
        return 0;
    }
}
?>
