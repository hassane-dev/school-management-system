<?php
namespace App\Models;
use App\Core\Model;
use PDO;

class MatiereModel extends Model {
    protected $table = 'matieres'; // Define table name

    public function __construct() {
        parent::__construct();
    }

    public function getAll($filters = [], $options = []) {
        $sql = "SELECT * FROM {$this->table}";
        $bindings = [];
        $whereClauses = [];

        if (!empty($filters['search'])) {
            $whereClauses[] = "(nom LIKE :search OR code LIKE :search)";
            $bindings[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['type_matiere'])) { // Original type filter
            $whereClauses[] = "type_matiere = :type_matiere";
            $bindings[':type_matiere'] = $filters['type_matiere'];
        }
        if (!empty($filters['type_academique'])) { // New academic type filter
            $whereClauses[] = "type_academique = :type_academique";
            $bindings[':type_academique'] = $filters['type_academique'];
        }

        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }

        $orderBy = $options['orderBy'] ?? 'nom';
        $orderDir = $options['orderDir'] ?? 'ASC';
        // Validate orderBy against a list of allowed columns
        $allowedOrderBy = ['nom', 'code', 'type_matiere', 'type_academique', 'coefficient'];
        if (!in_array($orderBy, $allowedOrderBy)) {
            $orderBy = 'nom'; // Fallback to default
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
            $whereClauses[] = "(nom LIKE :search OR code LIKE :search)";
            $bindings[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['type_matiere'])) {
            $whereClauses[] = "type_matiere = :type_matiere";
            $bindings[':type_matiere'] = $filters['type_matiere'];
        }
        if (!empty($filters['type_academique'])) { // New academic type filter
            $whereClauses[] = "type_academique = :type_academique";
            $bindings[':type_academique'] = $filters['type_academique'];
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

    public function findByCode($code, $excludeId = null) {
        $sql = "SELECT * FROM {$this->table} WHERE code = :code";
        $bindings = [':code' => $code];
        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $bindings[':exclude_id'] = $excludeId;
        }
        $this->db->query($sql);
        foreach($bindings as $key => $value) { $this->db->bind($key, $value); }
        return $this->db->single();
    }

    public function findByName($nom, $excludeId = null) {
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
        $this->db->query("INSERT INTO {$this->table} (nom, code, description, coefficient, type_matiere, type_academique)
                          VALUES (:nom, :code, :description, :coefficient, :type_matiere, :type_academique)");
        $this->db->bind(':nom', $data['nom']);
        $this->db->bind(':code', $data['code'] ?? null);
        $this->db->bind(':description', $data['description'] ?? null);
        $this->db->bind(':coefficient', $data['coefficient'] ?? 1.00, PDO::PARAM_STR);
        $this->db->bind(':type_matiere', $data['type_matiere'] ?? null);
        $this->db->bind(':type_academique', $data['type_academique'] ?? null); // New field

        try {
            if ($this->db->execute()) {
                return $this->db->lastInsertId();
            }
        } catch (\PDOException $e) {
            error_log("MatiereModel::create Error: " . $e->getMessage());
            if ($this->db->getErrorCode() == '23000') {
                $errorInfo = $this->db->getErrorInfo();
                if (strpos($errorInfo ?? '', 'Duplicate entry') !== false) {
                    if (strpos($errorInfo ?? '', "'nom'") !== false) return 'duplicate_nom';
                    if (strpos($errorInfo ?? '', "'code'") !== false) return 'duplicate_code';
                }
            }
        }
        return false;
    }

    public function update($id, $data) {
        $this->db->query("UPDATE {$this->table} SET nom = :nom, code = :code, description = :description,
                          coefficient = :coefficient, type_matiere = :type_matiere, type_academique = :type_academique
                          WHERE id = :id");
        $this->db->bind(':id', $id, PDO::PARAM_INT);
        $this->db->bind(':nom', $data['nom']);
        $this->db->bind(':code', $data['code'] ?? null);
        $this->db->bind(':description', $data['description'] ?? null);
        $this->db->bind(':coefficient', $data['coefficient'] ?? 1.00, PDO::PARAM_STR);
        $this->db->bind(':type_matiere', $data['type_matiere'] ?? null);
        $this->db->bind(':type_academique', $data['type_academique'] ?? null); // New field

        try {
            return $this->db->execute();
        } catch (\PDOException $e) {
            error_log("MatiereModel::update Error: " . $e->getMessage());
             if ($this->db->getErrorCode() == '23000') {
                $errorInfo = $this->db->getErrorInfo();
                if (strpos($errorInfo ?? '', 'Duplicate entry') !== false) {
                    if (strpos($errorInfo ?? '', "'nom'") !== false) return 'duplicate_nom';
                    if (strpos($errorInfo ?? '', "'code'") !== false) return 'duplicate_code';
                }
            }
        }
        return false;
    }

    public function delete($id) {
        if (class_exists('App\Models\EnseignementModel')) {
            try {
                $enseignementModel = new \App\Models\EnseignementModel();
                $usageFilters = ['e.matiere_id' => $id];
                $assignments = $enseignementModel->getAffectations($usageFilters, ['limit' => 1]);
                if (!empty($assignments)) {
                    return 'in_use';
                }
            } catch (\Exception $e) {
                error_log("MatiereModel::delete - Error checking EnseignementModel: " . $e->getMessage());
            }
        } else {
            error_log("MatiereModel::delete Warning: EnseignementModel not available for 'in_use' check. Relying on DB constraints.");
        }

        $this->db->query("DELETE FROM {$this->table} WHERE id = :id");
        $this->db->bind(':id', $id, PDO::PARAM_INT);
        try {
            return $this->db->execute();
        } catch (\PDOException $e) {
            error_log("MatiereModel::delete Error: " . $e->getMessage());
             if ($this->db->getErrorCode() == '23000') {
                return 'in_use';
            }
            return false;
        }
    }

    public function getDistinctTypes() { // For original 'type_matiere'
        $this->db->query("SELECT DISTINCT type_matiere FROM {$this->table} WHERE type_matiere IS NOT NULL AND type_matiere != '' ORDER BY type_matiere");
        return $this->db->resultSet();
    }

    // New method for 'type_academique'
    public function getDistinctTypesAcademiques() {
        $this->db->query("SELECT DISTINCT type_academique FROM {$this->table} WHERE type_academique IS NOT NULL AND type_academique != '' ORDER BY type_academique");
        return $this->db->resultSet();
    }
}
?>
