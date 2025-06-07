<?php
namespace App\Models;
use App\Core\Model;
use PDO;

class ClasseModel extends Model {
    protected $table = 'classes'; // Define table name

    public function __construct() {
        parent::__construct();
    }

    public function getAll($filters = [], $options = []) {
        $sql = "SELECT * FROM {$this->table}";
        $bindings = [];
        $whereClauses = [];

        if (!empty($filters['search'])) {
            $whereClauses[] = "(nom LIKE :search OR niveau LIKE :search OR cycle LIKE :search)";
            $bindings[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['niveau'])) {
            $whereClauses[] = "niveau = :niveau";
            $bindings[':niveau'] = $filters['niveau'];
        }
        if (!empty($filters['cycle'])) {
            $whereClauses[] = "cycle = :cycle";
            $bindings[':cycle'] = $filters['cycle'];
        }

        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }

        $orderBy = $options['orderBy'] ?? 'cycle, niveau, nom';
        $orderDir = $options['orderDir'] ?? 'ASC';

        // Basic validation for orderBy to prevent injection with complex column names
        $allowedOrderByColumns = ['nom', 'niveau', 'cycle', 'salle_par_defaut', 'capacite'];
        $orderByColumns = explode(',', $orderBy);
        $safeOrderBy = [];
        foreach($orderByColumns as $col) {
            $col = trim($col);
            if (in_array($col, $allowedOrderByColumns)) {
                $safeOrderBy[] = $col;
            }
        }
        if (empty($safeOrderBy)) { // Fallback if no valid columns found
            $orderBy = 'cycle, niveau, nom';
        } else {
            $orderBy = implode(', ', $safeOrderBy);
        }

        $sql .= " ORDER BY " . $orderBy . " " . (strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC');


        if (isset($options['limit']) && is_numeric($options['limit'])) {
            $sql .= " LIMIT " . (int)$options['limit'];
            if (isset($options['offset']) && is_numeric($options['offset'])) {
                $sql .= " OFFSET " . (int)$options['offset'];
            }
        }
        $this->db->query($sql);
        foreach ($bindings as $key => $value) {
            $this->db->bind($key, $value);
        }
        return $this->db->resultSet();
    }

    public function getTotalCount($filters = []) {
        $sql = "SELECT COUNT(id) as total FROM {$this->table}";
        $bindings = [];
        $whereClauses = [];
        if (!empty($filters['search'])) {
            $whereClauses[] = "(nom LIKE :search OR niveau LIKE :search OR cycle LIKE :search)";
            $bindings[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['niveau'])) {
            $whereClauses[] = "niveau = :niveau";
            $bindings[':niveau'] = $filters['niveau'];
        }
        if (!empty($filters['cycle'])) {
            $whereClauses[] = "cycle = :cycle";
            $bindings[':cycle'] = $filters['cycle'];
        }
        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }
        $this->db->query($sql);
        foreach ($bindings as $key => $value) {
            $this->db->bind($key, $value);
        }
        $result = $this->db->single();
        return $result ? (int)$result->total : 0;
    }

    public function getById($id) {
        $this->db->query("SELECT * FROM {$this->table} WHERE id = :id");
        $this->db->bind(':id', $id, PDO::PARAM_INT);
        return $this->db->single();
    }

    public function findByNom($nom, $excludeId = null) {
        $sql = "SELECT * FROM {$this->table} WHERE nom = :nom";
        $bindings = [':nom' => $nom];
        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $bindings[':exclude_id'] = $excludeId;
        }
        $this->db->query($sql);
        foreach($bindings as $key => $value) { $this->db->bind($key, $value); }
        return $this->db->single();
    }

    public function create($data) {
        $this->db->query("INSERT INTO {$this->table} (nom, niveau, cycle, description, salle_par_defaut, capacite)
                          VALUES (:nom, :niveau, :cycle, :description, :salle_par_defaut, :capacite)");
        $this->db->bind(':nom', $data['nom']);
        $this->db->bind(':niveau', $data['niveau'] ?? null);
        $this->db->bind(':cycle', $data['cycle'] ?? null);
        $this->db->bind(':description', $data['description'] ?? null);
        $this->db->bind(':salle_par_defaut', $data['salle_par_defaut'] ?? null);
        $this->db->bind(':capacite', isset($data['capacite']) && $data['capacite'] !== '' ? (int)$data['capacite'] : null,
                        (isset($data['capacite']) && $data['capacite'] !== '') ? PDO::PARAM_INT : PDO::PARAM_NULL);

        try {
            if ($this->db->execute()) {
                return $this->db->lastInsertId();
            }
        } catch (\PDOException $e) {
            error_log("ClasseModel::create Error: " . $e->getMessage());
            if ($this->db->getErrorCode() == '23000' && strpos($this->db->getErrorInfo() ?? '', 'Duplicate entry') !== false && strpos($this->db->getErrorInfo() ?? '', "'nom'") !== false) {
                return 'duplicate_nom';
            }
        }
        return false;
    }

    public function update($id, $data) {
        $this->db->query("UPDATE {$this->table} SET nom = :nom, niveau = :niveau, cycle = :cycle, description = :description,
                          salle_par_defaut = :salle_par_defaut, capacite = :capacite
                          WHERE id = :id");
        $this->db->bind(':id', $id, PDO::PARAM_INT);
        $this->db->bind(':nom', $data['nom']);
        $this->db->bind(':niveau', $data['niveau'] ?? null);
        $this->db->bind(':cycle', $data['cycle'] ?? null);
        $this->db->bind(':description', $data['description'] ?? null);
        $this->db->bind(':salle_par_defaut', $data['salle_par_defaut'] ?? null);
        $this->db->bind(':capacite', isset($data['capacite']) && $data['capacite'] !== '' ? (int)$data['capacite'] : null,
                        (isset($data['capacite']) && $data['capacite'] !== '') ? PDO::PARAM_INT : PDO::PARAM_NULL);

        try {
            return $this->db->execute();
        } catch (\PDOException $e) {
            error_log("ClasseModel::update Error: " . $e->getMessage());
            if ($this->db->getErrorCode() == '23000' && strpos($this->db->getErrorInfo() ?? '', 'Duplicate entry') !== false && strpos($this->db->getErrorInfo() ?? '', "'nom'") !== false) {
                return 'duplicate_nom';
            }
        }
        return false;
    }

    public function delete($id) {
        // Check usage in 'enseignements'
        if (class_exists('App\Models\EnseignementModel')) {
             try {
                $enseignementModel = new \App\Models\EnseignementModel();
                $assignments = $enseignementModel->getAffectations(['e.classe_id' => $id], ['limit' => 1]);
                if (!empty($assignments)) {
                    return 'in_use_enseignement';
                }
            } catch (\Exception $e) {
                error_log("ClasseModel::delete - Error checking EnseignementModel: " . $e->getMessage());
            }
        }
        // Placeholder for checking student affectations (Sprint 6)
        // if (class_exists('App\Models\AffectationEtudiantModel')) { ... }


        $this->db->query("DELETE FROM {$this->table} WHERE id = :id");
        $this->db->bind(':id', $id, PDO::PARAM_INT);
        try {
            return $this->db->execute();
        } catch (\PDOException $e) {
            error_log("ClasseModel::delete Error: " . $e->getMessage());
            if ($this->db->getErrorCode() == '23000') { // Integrity constraint (foreign key)
                return 'in_use'; // Generic in_use if other checks fail or not present
            }
            return false;
        }
    }

    public function getDistinctNiveaux() {
        $this->db->query("SELECT DISTINCT niveau FROM {$this->table} WHERE niveau IS NOT NULL AND niveau != '' ORDER BY niveau");
        return $this->db->resultSet();
    }

    public function getDistinctCycles() {
        $this->db->query("SELECT DISTINCT cycle FROM {$this->table} WHERE cycle IS NOT NULL AND cycle != '' ORDER BY cycle");
        return $this->db->resultSet();
    }
}
?>
