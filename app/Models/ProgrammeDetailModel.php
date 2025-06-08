<?php
namespace App\Models;
use App\Core\Model;
use PDO;

class ProgrammeDetailModel extends Model {
    protected $table = 'programme_details';

    public function __construct() {
        parent::__construct();
    }

    public function getDetailsForProgramme($programmeId, $options = []) {
        $sql = "SELECT pd.*, m.nom as matiere_nom, m.code as matiere_code, m.type_academique as matiere_type_academique
                FROM {$this->table} pd
                JOIN matieres m ON pd.matiere_id = m.id
                WHERE pd.programme_id = :programme_id";

        $orderBy = $options['orderBy'] ?? 'pd.ordre, m.nom';
        $orderDir = strtoupper($options['orderDir'] ?? 'ASC');
        if (!in_array($orderDir, ['ASC', 'DESC'])) {
            $orderDir = 'ASC';
        }
        // Basic validation for orderBy
        $allowedOrderByColumns = ['pd.ordre', 'm.nom', 'm.code', 'pd.notes_coefficient', 'pd.heures_par_semaine'];
         $orderByColumns = explode(',', $orderBy);
        $safeOrderBy = [];
        foreach($orderByColumns as $col) {
            $col = trim($col);
            if (in_array($col, $allowedOrderByColumns)) {
                $safeOrderBy[] = $col;
            }
        }
        if (empty($safeOrderBy)) {
            $orderBy = 'pd.ordre, m.nom'; // Fallback
        } else {
            $orderBy = implode(', ', $safeOrderBy);
        }

        $sql .= " ORDER BY " . $orderBy . " " . $orderDir;

        $this->db->query($sql);
        $this->db->bind(':programme_id', $programmeId, PDO::PARAM_INT);
        return $this->db->resultSet();
    }

    public function getById($detailId) {
        $this->db->query("SELECT pd.*, m.nom as matiere_nom, m.code as matiere_code
                          FROM {$this->table} pd
                          JOIN matieres m ON pd.matiere_id = m.id
                          WHERE pd.id = :id");
        $this->db->bind(':id', $detailId, PDO::PARAM_INT);
        return $this->db->single();
    }

    public function addMatiereToProgramme($data) {
        if (empty($data['programme_id']) || empty($data['matiere_id'])) {
            error_log("ProgrammeDetailModel::addMatiereToProgramme - Missing programme_id or matiere_id.");
            return false;
        }
        $this->db->query("INSERT INTO {$this->table}
                          (programme_id, matiere_id, notes_coefficient, heures_par_semaine, description_detail, ordre)
                          VALUES (:programme_id, :matiere_id, :notes_coefficient, :heures_par_semaine, :description_detail, :ordre)");
        $this->db->bind(':programme_id', $data['programme_id'], PDO::PARAM_INT);
        $this->db->bind(':matiere_id', $data['matiere_id'], PDO::PARAM_INT);
        $this->db->bind(':notes_coefficient', (isset($data['notes_coefficient']) && $data['notes_coefficient'] !== '') ? $data['notes_coefficient'] : null, PDO::PARAM_STR);
        $this->db->bind(':heures_par_semaine', (isset($data['heures_par_semaine']) && $data['heures_par_semaine'] !== '') ? $data['heures_par_semaine'] : null, PDO::PARAM_STR);
        $this->db->bind(':description_detail', $data['description_detail'] ?? null);
        $this->db->bind(':ordre', (isset($data['ordre']) && $data['ordre'] !== '') ? (int)$data['ordre'] : 0, PDO::PARAM_INT);

        try {
            if ($this->db->execute()) {
                return $this->db->lastInsertId();
            }
        } catch (\PDOException $e) {
            error_log("ProgrammeDetailModel::addMatiereToProgramme Error: " . $e->getMessage());
            if ($this->db->getErrorCode() == '23000' && strpos($this->db->getErrorInfo() ?? '', 'uk_programme_matiere') !== false) {
                return 'duplicate_matiere_in_programme';
            }
        }
        return false;
    }

    public function updateMatiereInProgramme($detailId, $data) {
         if (empty($data['matiere_id'])) { // matiere_id is part of UNIQUE key, cannot be empty
            error_log("ProgrammeDetailModel::updateMatiereInProgramme - Missing matiere_id for detail ID $detailId.");
            return false;
        }
        $this->db->query("UPDATE {$this->table} SET
                          matiere_id = :matiere_id, notes_coefficient = :notes_coefficient,
                          heures_par_semaine = :heures_par_semaine, description_detail = :description_detail, ordre = :ordre
                          WHERE id = :detail_id");
        $this->db->bind(':detail_id', $detailId, PDO::PARAM_INT);
        $this->db->bind(':matiere_id', $data['matiere_id'], PDO::PARAM_INT);
        $this->db->bind(':notes_coefficient', (isset($data['notes_coefficient']) && $data['notes_coefficient'] !== '') ? $data['notes_coefficient'] : null, PDO::PARAM_STR);
        $this->db->bind(':heures_par_semaine', (isset($data['heures_par_semaine']) && $data['heures_par_semaine'] !== '') ? $data['heures_par_semaine'] : null, PDO::PARAM_STR);
        $this->db->bind(':description_detail', $data['description_detail'] ?? null);
        $this->db->bind(':ordre', (isset($data['ordre']) && $data['ordre'] !== '') ? (int)$data['ordre'] : 0, PDO::PARAM_INT);

        try {
            return $this->db->execute();
        } catch (\PDOException $e) {
            error_log("ProgrammeDetailModel::updateMatiereInProgramme Error: " . $e->getMessage());
            if ($this->db->getErrorCode() == '23000' && strpos($this->db->getErrorInfo() ?? '', 'uk_programme_matiere') !== false) {
                 return 'duplicate_matiere_in_programme';
            }
        }
        return false;
    }

    public function removeMatiereFromProgramme($detailId) {
        $this->db->query("DELETE FROM {$this->table} WHERE id = :detail_id");
        $this->db->bind(':detail_id', $detailId, PDO::PARAM_INT);
        try {
            return $this->db->execute();
        } catch (\PDOException $e) {
            error_log("ProgrammeDetailModel::removeMatiereFromProgramme Error: " . $e->getMessage());
            return false;
        }
    }

    public function removeAllMatieresFromProgramme($programmeId) {
        $this->db->query("DELETE FROM {$this->table} WHERE programme_id = :programme_id");
        $this->db->bind(':programme_id', $programmeId, PDO::PARAM_INT);
         try {
            return $this->db->execute();
        } catch (\PDOException $e) {
            error_log("ProgrammeDetailModel::removeAllMatieresFromProgramme Error: " . $e->getMessage());
            return false;
        }
    }

    public function syncMatieresForProgramme($programmeId, $matieresDataArray = []) {
        // $this->db->getPdo()->beginTransaction(); // Requires getPdo() in Database.php and careful error handling
        try {
            $this->removeAllMatieresFromProgramme($programmeId);

            if (empty($matieresDataArray)) {
                // $this->db->getPdo()->commit();
                return true;
            }

            $sql = "INSERT INTO {$this->table}
                    (programme_id, matiere_id, notes_coefficient, heures_par_semaine, description_detail, ordre) VALUES ";
            $valueTuples = [];
            $bindings = [];
            $i = 0;
            foreach ($matieresDataArray as $data) {
                if (empty($data['matiere_id'])) continue;

                $valueTuples[] = "(:prog_id_$i, :mat_id_$i, :coeff_$i, :heures_$i, :desc_$i, :ordre_$i)";
                $bindings[":prog_id_$i"] = $programmeId;
                $bindings[":mat_id_$i"] = $data['matiere_id'];
                $bindings[":coeff_$i"] = (isset($data['notes_coefficient']) && $data['notes_coefficient'] !== '') ? $data['notes_coefficient'] : null;
                $bindings[":heures_$i"] = (isset($data['heures_par_semaine']) && $data['heures_par_semaine'] !== '') ? $data['heures_par_semaine'] : null;
                $bindings[":desc_$i"] = $data['description_detail'] ?? null;
                $bindings[":ordre_$i"] = (isset($data['ordre']) && $data['ordre'] !== '') ? (int)$data['ordre'] : ($i + 1) * 10;
                $i++;
            }

            if (empty($valueTuples)) {
                // $this->db->getPdo()->commit();
                return true;
            }

            $sql .= implode(', ', $valueTuples);
            $this->db->query($sql);
            foreach ($bindings as $key => $value) {
                $this->db->bind($key, $value); // Type detection in bind method
            }

            if ($this->db->execute()) {
                // $this->db->getPdo()->commit();
                return true;
            } else {
                // $this->db->getPdo()->rollBack();
                // Check for unique key violation (programme_id, matiere_id)
                if ($this->db->getErrorCode() == '23000' && strpos($this->db->getErrorInfo() ?? '', 'uk_programme_matiere') !== false) {
                    return 'duplicate_matiere_in_programme_array';
                }
                return false;
            }
        } catch (\PDOException $e) {
            // $this->db->getPdo()->rollBack();
            error_log("ProgrammeDetailModel::syncMatieresForProgramme PDOException: " . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            // $this->db->getPdo()->rollBack();
            error_log("ProgrammeDetailModel::syncMatieresForProgramme Exception: " . $e->getMessage());
            return false;
        }
    }
}
?>
