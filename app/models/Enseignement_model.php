<?php

class Enseignement_model extends Model {

    public function __construct() {
        parent::__construct();
        $this->table = 'enseignements';
    }

    /**
     * Checks if an assignment is unique based on subject, class, and year.
     * A subject can only be assigned once to a class in a given academic year.
     * @param int $matiereId Subject's ID
     * @param int $classeId Class's ID
     * @param int $anneeId Academic Year's ID
     * @param int|null $currentAffectationId ID of the current assignment being updated (to exclude it from check).
     * @return bool True if unique, false otherwise.
     */
    public function checkUniqueness($matiereId, $classeId, $anneeId, $currentAffectationId = null) {
        $sql = "SELECT id FROM {$this->table}
                WHERE matiere_id = :matiere_id
                AND classe_id = :classe_id
                AND annee_id = :annee_id";

        $params = [
            'matiere_id' => $matiereId,
            'classe_id' => $classeId,
            'annee_id' => $anneeId
        ];

        if ($currentAffectationId !== null) {
            $sql .= " AND id != :current_id";
            $params['current_id'] = $currentAffectationId;
        }

        $stmt = $this->executeQuery($sql, $params);
        return $stmt ? ($stmt->fetch() === false) : false; // True if no record found (unique)
    }

    /**
     * Creates a new teaching assignment.
     * @param array $data Associative array with 'utilisateur_id', 'matiere_id', 'classe_id', 'annee_id'.
     * @return mixed Last insert ID or false on failure or if not unique (returns 'duplicate').
     */
    public function createAffectation($data) {
        if (empty($data['utilisateur_id']) || empty($data['matiere_id']) || empty($data['classe_id']) || empty($data['annee_id'])) {
            // error_log("Enseignement_model::createAffectation(): Missing required fields.");
            return false; // Or throw exception
        }

        // Corrected call to checkUniqueness: utilisateur_id is not part of this uniqueness constraint
        if (!$this->checkUniqueness($data['matiere_id'], $data['classe_id'], $data['annee_id'])) {
            // error_log("Enseignement_model::createAffectation(): This assignment (subject/class/year) already exists.");
            return 'duplicate'; // Special return for duplicate
        }

        // date_affectation has a DEFAULT CURRENT_TIMESTAMP
        return $this->insertRecord($data);
    }

    /**
     * Fetches a single assignment by its primary key 'id'.
     * @param int $id
     * @return mixed Assignment object or false.
     */
    public function getAffectationById($id) {
        $sql = "SELECT e.*, u.nom as enseignant_nom, m.nom as matiere_nom, c.nom as classe_nom, aa.libelle as annee_libelle
                FROM {$this->table} e
                JOIN utilisateurs u ON e.utilisateur_id = u.id
                JOIN matieres m ON e.matiere_id = m.id
                JOIN classes c ON e.classe_id = c.id
                JOIN annees_academiques aa ON e.annee_id = aa.id
                WHERE e.id = :id";
        $stmt = $this->executeQuery($sql, ['id' => $id]);
        return $stmt ? $stmt->fetch() : false;
    }

    /**
     * Retrieves assignments based on filters, with JOINs for readable names.
     * @param array $filters Associative array (e.g., ['utilisateur_id' => 1, 'annee_id' => 2]).
     * @return array Array of assignment objects.
     */
    public function getAffectations($filters = []) {
        $sql = "SELECT e.*, u.nom as enseignant_nom, m.nom as matiere_nom, c.nom as classe_nom, aa.libelle as annee_libelle
                FROM {$this->table} e
                LEFT JOIN utilisateurs u ON e.utilisateur_id = u.id
                LEFT JOIN matieres m ON e.matiere_id = m.id
                LEFT JOIN classes c ON e.classe_id = c.id
                LEFT JOIN annees_academiques aa ON e.annee_id = aa.id";

        $whereClauses = [];
        $params = [];

        if (!empty($filters)) {
            foreach ($filters as $key => $value) {
                if ($value !== null && $value !== '') { // Ensure filter value is meaningful
                    // Validate key against expected column names if necessary
                    $allowed_keys = ['e.utilisateur_id', 'e.matiere_id', 'e.classe_id', 'e.annee_id', 'u.nom', 'm.nom', 'c.nom', 'aa.libelle'];
                    // For simplicity, assuming direct mapping or pre-validated keys.
                    // A more robust filter would be specific, e.g., $filters['teacher_id'], $filters['subject_id']
                    $param_key = str_replace('.', '_', $key); // PDO param key from table.column
                    $whereClauses[] = "{$key} = :{$param_key}"; // Use actual column names for keys in $filters
                    $params[$param_key] = $value;
                }
            }
        }

        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }
        $sql .= " ORDER BY aa.date_debut DESC, u.nom, m.nom, c.nom"; // Example ordering

        $stmt = $this->executeQuery($sql, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Updates an existing teaching assignment.
     * @param int $id The ID of the assignment to update.
     * @param array $data Associative array of data to update.
     * @return bool|string True on success, false on failure, 'duplicate' if uniqueness check fails.
     */
    public function updateAffectation($id, $data) {
        if (empty($id) || empty($data)) {
            return false;
        }

        // Fetch current record to check if key fields for uniqueness are being changed
        $current = $this->getAffectationById($id);
        if (!$current) return false; // Record not found

        // $newUtilisateurId = $data['utilisateur_id'] ?? $current->utilisateur_id; // utilisateur_id is not part of this uniqueness constraint
        $newMatiereId = $data['matiere_id'] ?? $current->matiere_id;
        $newClasseId = $data['classe_id'] ?? $current->classe_id;
        $newAnneeId = $data['annee_id'] ?? $current->annee_id;

        // Check uniqueness only if any of the key fields (matiere, classe, annee) are actually changing
        $uniquenessFieldsChanged = ($newMatiereId != $current->matiere_id) ||
                                   ($newClasseId != $current->classe_id) ||
                                   ($newAnneeId != $current->annee_id);

        if ($uniquenessFieldsChanged) {
            // Corrected call to checkUniqueness
            if (!$this->checkUniqueness($newMatiereId, $newClasseId, $newAnneeId, $id)) {
                // error_log("Enseignement_model::updateAffectation(): This assignment combination (subject/class/year) already exists for another record.");
                return 'duplicate';
            }
        }

        return $this->updateRecord($id, $data);
    }

    /**
     * Deletes an assignment by its ID.
     * @param int $id
     * @return bool True on success, false on failure.
     */
    public function deleteAffectation($id) {
        return $this->deleteRecord($id);
    }

    /**
     * Get affectations for a specific teacher.
     * @param int $teacherId
     * @param int|null $anneeId Optional: filter by academic year
     * @return array
     */
    public function getAffectationsForTeacher($teacherId, $anneeId = null) {
        $filters = ['e.utilisateur_id' => $teacherId];
        if ($anneeId !== null) {
            $filters['e.annee_id'] = $anneeId;
        }
        return $this->getAffectations($filters);
    }

    /**
     * Get affectations for a specific class in a specific year.
     * @param int $classeId
     * @param int $anneeId
     * @return array
     */
    public function getAffectationsForClassInYear($classeId, $anneeId) {
        return $this->getAffectations(['e.classe_id' => $classeId, 'e.annee_id' => $anneeId]);
    }
}
?>
