<?php
namespace App\Models;
use App\Core\Model;

class AffectationEleveClasseModel extends Model {

    public function assignStudentToClass($eleveId, $classeId, $anneeAcademiqueId, $dateAffectation = null, $statut = 'actif') {
        $dateAffectation = $dateAffectation ?? date('Y-m-d H:i:s');

        // Ensure a student is only actively assigned to one class per academic year.
        // First, deactivate any existing 'actif' assignments for this student in this academic year.
        $this->db->query("UPDATE affectations_eleves_classes
                          SET statut_affectation = 'inactive', date_modification = NOW()
                          WHERE eleve_id = :eleve_id
                          AND annee_academique_id = :annee_id
                          AND statut_affectation = 'actif'");
        $this->db->bind(':eleve_id', $eleveId);
        $this->db->bind(':annee_id', $anneeAcademiqueId);
        $this->db->execute(); // Execute this update first

        // Then, insert the new assignment or update if a record (now inactive or for a different class) already exists.
        // Using INSERT ... ON DUPLICATE KEY UPDATE for robustness.
        // The unique key 'uk_eleve_annee' (eleve_id, annee_academique_id) is crucial here.
        // If uk_eleve_annee is just on (eleve_id, annee_academique_id), this logic is fine.
        // If the primary key is (eleve_id, classe_id, annee_academique_id) then it's more complex.
        // Assuming a simple (eleve_id, annee_academique_id) unique key for "one class per year" rule.
        // The SQL for table creation was: UNIQUE KEY `uk_eleve_annee` (`eleve_id`,`annee_academique_id`)

        $this->db->query("INSERT INTO affectations_eleves_classes
                            (eleve_id, classe_id, annee_academique_id, date_affectation, statut_affectation, date_creation)
                          VALUES
                            (:eleve_id, :classe_id, :annee_id, :date_affectation, :statut, NOW())
                          ON DUPLICATE KEY UPDATE
                            classe_id = VALUES(classe_id),
                            date_affectation = VALUES(date_affectation),
                            statut_affectation = VALUES(statut_affectation),
                            date_modification = NOW()");

        $this->db->bind(':eleve_id', $eleveId);
        $this->db->bind(':classe_id', $classeId);
        $this->db->bind(':annee_id', $anneeAcademiqueId);
        $this->db->bind(':date_affectation', $dateAffectation);
        $this->db->bind(':statut', $statut);

        try {
            return $this->db->execute();
        } catch (\PDOException $e) {
            error_log("Error assigning student to class: " . $e->getMessage());
            return false;
        }
    }

    public function getCurrentAssignmentForStudent($eleveId, $anneeAcademiqueId) {
        $this->db->query("SELECT aec.*, c.nom as classe_nom, aa.libelle as annee_libelle
                          FROM affectations_eleves_classes aec
                          JOIN classes c ON aec.classe_id = c.id
                          JOIN annees_academiques aa ON aec.annee_academique_id = aa.id
                          WHERE aec.eleve_id = :eleve_id
                          AND aec.annee_academique_id = :annee_id
                          AND aec.statut_affectation = 'actif'
                          ORDER BY aec.date_affectation DESC
                          LIMIT 1");
        $this->db->bind(':eleve_id', $eleveId);
        $this->db->bind(':annee_id', $anneeAcademiqueId);
        return $this->db->single();
    }

    public function getStudentsInClass($classeId, $anneeAcademiqueId, $options = []) {
        $sql = "SELECT e.*, aec.id as affectation_id, aec.date_affectation, aec.statut_affectation
                FROM eleves e
                JOIN affectations_eleves_classes aec ON e.id = aec.eleve_id
                WHERE aec.classe_id = :classe_id
                AND aec.annee_academique_id = :annee_id
                AND aec.statut_affectation = 'actif'";

        $orderBy = $options['orderBy'] ?? 'e.nom_famille, e.prenom';
        $sql .= " ORDER BY " . $orderBy;

        // Add pagination if needed
        if (isset($options['limit'])) {
            $sql .= " LIMIT " . (int)$options['limit'] . (isset($options['offset']) ? " OFFSET " . (int)$options['offset'] : "");
        }

        $this->db->query($sql);
        $this->db->bind(':classe_id', $classeId);
        $this->db->bind(':annee_id', $anneeAcademiqueId);
        return $this->db->resultSet();
    }

    public function getAssignmentHistoryForStudent($eleveId, $options = []) {
        $sql = "SELECT aec.*, c.nom as classe_nom, aa.libelle as annee_academique_libelle
                FROM affectations_eleves_classes aec
                JOIN classes c ON aec.classe_id = c.id
                JOIN annees_academiques aa ON aec.annee_academique_id = aa.id
                WHERE aec.eleve_id = :eleve_id";

        $orderBy = $options['orderBy'] ?? 'aec.annee_academique_id DESC, aec.date_affectation DESC';
        $sql .= " ORDER BY " . $orderBy;

        $this->db->query($sql);
        $this->db->bind(':eleve_id', $eleveId);
        return $this->db->resultSet();
    }

    public function updateAssignmentStatus($assignmentId, $newStatus) {
        $this->db->query("UPDATE affectations_eleves_classes
                          SET statut_affectation = :new_status, date_modification = NOW()
                          WHERE id = :assignment_id");
        $this->db->bind(':new_status', $newStatus);
        $this->db->bind(':assignment_id', $assignmentId);
        return $this->db->execute();
    }

    public function getById($assignmentId) {
        $this->db->query("SELECT * FROM affectations_eleves_classes WHERE id = :assignment_id");
        $this->db->bind(':assignment_id', $assignmentId);
        return $this->db->single();
    }

    public function removeStudentFromClassForYear($eleveId, $anneeAcademiqueId) {
         // This typically means setting the assignment to inactive or deleting it.
         // Setting to inactive is often safer for historical data.
        $this->db->query("UPDATE affectations_eleves_classes
                          SET statut_affectation = 'inactive', date_modification = NOW()
                          WHERE eleve_id = :eleve_id AND annee_academique_id = :annee_id AND statut_affectation = 'actif'");
        $this->db->bind(':eleve_id', $eleveId);
        $this->db->bind(':annee_id', $anneeAcademiqueId);
        return $this->db->execute();
    }
}
?>
