<?php
namespace App\Models;
use App\Core\Model;
use PDO;

class ClasseMatiereEligibiliteModel extends Model {
    protected $table = 'classe_matiere_eligibilite';

    public function __construct() {
        parent::__construct();
    }

    public function getEligibleMatieresForClasse($classeId, $fetchFullObjects = false) {
        $bindings = [':classe_id' => $classeId];
        if ($fetchFullObjects) {
            $sql = "SELECT m.* FROM matieres m
                    JOIN {$this->table} cme ON m.id = cme.matiere_id
                    WHERE cme.classe_id = :classe_id ORDER BY m.nom";
        } else { // Fetch only matiere_ids
            $sql = "SELECT cme.matiere_id FROM {$this->table} cme
                    WHERE cme.classe_id = :classe_id";
        }
        $this->db->query($sql);
        $this->db->bind(':classe_id', $classeId, PDO::PARAM_INT);
        $results = $this->db->resultSet();

        if (!$fetchFullObjects) {
            return array_map(function($row) { return $row->matiere_id; }, $results);
        }
        return $results; // Array of matiere objects
    }

    public function getEligibleClassesForMatiere($matiereId, $fetchFullObjects = false) {
        $bindings = [':matiere_id' => $matiereId];
        if ($fetchFullObjects) {
            $sql = "SELECT c.* FROM classes c
                    JOIN {$this->table} cme ON c.id = cme.classe_id
                    WHERE cme.matiere_id = :matiere_id ORDER BY c.nom";
        } else { // Fetch only classe_ids
            $sql = "SELECT cme.classe_id FROM {$this->table} cme
                    WHERE cme.matiere_id = :matiere_id";
        }
        $this->db->query($sql);
        $this->db->bind(':matiere_id', $matiereId, PDO::PARAM_INT);
        $results = $this->db->resultSet();
        if (!$fetchFullObjects) {
            return array_map(function($row) { return $row->classe_id; }, $results);
        }
        return $results;
    }


    public function setMatiereEligibilityForClasse($classeId, $matiereId, $isEligible) {
        if ($isEligible) {
            $this->db->query("INSERT IGNORE INTO {$this->table} (classe_id, matiere_id)
                              VALUES (:classe_id, :matiere_id)");
            $this->db->bind(':classe_id', $classeId, PDO::PARAM_INT);
            $this->db->bind(':matiere_id', $matiereId, PDO::PARAM_INT);
            try {
                return $this->db->execute();
            } catch (\PDOException $e) {
                error_log("ClasseMatiereEligibiliteModel::setMatiereEligibilityForClasse (INSERT) Error: " . $e->getMessage());
                return false;
            }
        } else {
            $this->db->query("DELETE FROM {$this->table}
                              WHERE classe_id = :classe_id AND matiere_id = :matiere_id");
            $this->db->bind(':classe_id', $classeId, PDO::PARAM_INT);
            $this->db->bind(':matiere_id', $matiereId, PDO::PARAM_INT);
            try {
                return $this->db->execute();
            } catch (\PDOException $e) {
                error_log("ClasseMatiereEligibiliteModel::setMatiereEligibilityForClasse (DELETE) Error: " . $e->getMessage());
                return false;
            }
        }
    }

    public function syncEligibleMatieresForClasse($classeId, $matiereIdsArray = []) {
        // Start transaction if your DB class supports it and it's not handled by controller
        // $this->db->getPdo()->beginTransaction();
        try {
            // 1. Remove all existing eligibilities for this class
            $this->db->query("DELETE FROM {$this->table} WHERE classe_id = :classe_id");
            $this->db->bind(':classe_id', $classeId, PDO::PARAM_INT);
            $this->db->execute();

            // 2. Add new eligibilities from the array
            if (!empty($matiereIdsArray)) {
                // Ensure all matiereIds are integers
                $matiereIdsArray = array_map('intval', $matiereIdsArray);

                $sql = "INSERT INTO {$this->table} (classe_id, matiere_id) VALUES ";
                $valueTuples = [];
                $bindings = []; // Re-init bindings for this query

                $i = 0;
                foreach ($matiereIdsArray as $matiereId) {
                    if ($matiereId > 0) { // Basic validation for matiere_id
                        $valueTuples[] = "(:classe_id_$i, :matiere_id_$i)";
                        // It's important that placeholder names are unique for each value if not using ? placeholders
                        $bindings[":classe_id_$i"] = $classeId;
                        $bindings[":matiere_id_$i"] = $matiereId;
                        $i++;
                    }
                }
                if (!empty($valueTuples)) {
                    $sql .= implode(', ', $valueTuples);
                    $this->db->query($sql);
                    foreach ($bindings as $key => $value) {
                        // Bind type explicitly if necessary, though DB class handles it
                        $this->db->bind($key, $value, PDO::PARAM_INT);
                    }
                    if (!$this->db->execute()) {
                        // throw new \Exception("Failed to insert new eligibilities during sync.");
                        error_log("ClasseMatiereEligibiliteModel::syncEligibleMatieresForClasse - Failed to insert new eligibilities.");
                        // $this->db->getPdo()->rollBack();
                        return false;
                    }
                }
            }
            // $this->db->getPdo()->commit();
            return true;
        } catch (\PDOException $e) {
            // $this->db->getPdo()->rollBack();
            error_log("ClasseMatiereEligibiliteModel::syncEligibleMatieresForClasse Error: " . $e->getMessage());
            return false;
        }
    }
}
?>
