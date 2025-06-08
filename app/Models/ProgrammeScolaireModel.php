<?php
namespace App\Models;
use App\Core\Model;
use PDO;

class ProgrammeScolaireModel extends Model {
    protected $table = 'programmes_scolaires';

    public function __construct() {
        parent::__construct();
    }

    public function getAll($filters = [], $options = []) {
        $sql = "SELECT ps.*, aa.libelle as annee_academique_libelle
                FROM {$this->table} ps
                JOIN annees_academiques aa ON ps.annee_academique_id = aa.id";
        $bindings = [];
        $whereClauses = [];

        if (!empty($filters['search'])) {
            $whereClauses[] = "(ps.nom LIKE :search OR ps.description LIKE :search)";
            $bindings[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['annee_academique_id'])) {
            $whereClauses[] = "ps.annee_academique_id = :annee_id";
            $bindings[':annee_id'] = (int)$filters['annee_academique_id'];
        }
        if (!empty($filters['statut'])) {
            $whereClauses[] = "ps.statut = :statut";
            $bindings[':statut'] = $filters['statut'];
        }

        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }

        $orderBy = $options['orderBy'] ?? 'aa.date_debut DESC, ps.nom';
        $orderDir = strtoupper($options['orderDir'] ?? 'ASC');
        if (!in_array($orderDir, ['ASC', 'DESC'])) {
            $orderDir = 'ASC';
        }
        // Basic validation for orderBy to prevent injection with complex column names
        $allowedOrderByColumns = ['ps.nom', 'aa.date_debut', 'ps.statut', 'ps.date_creation'];
        $orderByColumns = explode(',', $orderBy);
        $safeOrderBy = [];
        foreach($orderByColumns as $col) {
            $col = trim($col);
            if (in_array($col, $allowedOrderByColumns)) {
                $safeOrderBy[] = $col;
            }
        }
        if (empty($safeOrderBy)) {
            $orderBy = 'aa.date_debut DESC, ps.nom'; // Fallback
        } else {
            $orderBy = implode(', ', $safeOrderBy);
        }

        $sql .= " ORDER BY " . $orderBy . " " . $orderDir;

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
        $sql = "SELECT COUNT(ps.id) as total
                FROM {$this->table} ps
                JOIN annees_academiques aa ON ps.annee_academique_id = aa.id";
        $bindings = [];
        $whereClauses = [];

        if (!empty($filters['search'])) {
           $whereClauses[] = "(ps.nom LIKE :search OR ps.description LIKE :search)";
           $bindings[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['annee_academique_id'])) {
           $whereClauses[] = "ps.annee_academique_id = :annee_id";
           $bindings[':annee_id'] = (int)$filters['annee_academique_id'];
        }
        if (!empty($filters['statut'])) {
           $whereClauses[] = "ps.statut = :statut";
           $bindings[':statut'] = $filters['statut'];
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
        $this->db->query("SELECT ps.*, aa.libelle as annee_academique_libelle
                          FROM {$this->table} ps
                          LEFT JOIN annees_academiques aa ON ps.annee_academique_id = aa.id
                          WHERE ps.id = :id");
        $this->db->bind(':id', $id, PDO::PARAM_INT);
        return $this->db->single();
    }

    public function findByNomAndYear($nom, $anneeAcademiqueId, $excludeId = null) {
        $sql = "SELECT * FROM {$this->table}
                WHERE nom = :nom AND annee_academique_id = :annee_id";
        $bindings = [':nom' => $nom, ':annee_id' => $anneeAcademiqueId];
        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $bindings[':exclude_id'] = $excludeId;
        }
        $this->db->query($sql);
        foreach($bindings as $key => $value) { $this->db->bind($key, $value); }
        return $this->db->single();
    }

    public function getByYear($anneeId) {
        $this->db->query("SELECT * FROM {$this->table} WHERE annee_academique_id = :annee_id ORDER BY nom");
        $this->db->bind(':annee_id', $anneeId, PDO::PARAM_INT);
        return $this->db->resultSet();
    }

    public function findByStatus($status) { // Consider renaming to getByStatus for consistency
        $this->db->query("SELECT * FROM {$this->table} WHERE statut = :statut ORDER BY nom");
        $this->db->bind(':statut', $status);
        return $this->db->resultSet();
    }

    public function create($data) {
        // nom and annee_academique_id are required by DB constraint NOT NULL
        if (empty($data['nom']) || empty($data['annee_academique_id'])) {
            error_log("ProgrammeScolaireModel::create - Missing required fields: nom or annee_academique_id");
            return false;
        }
        $this->db->query("INSERT INTO {$this->table} (nom, annee_academique_id, description, statut)
                          VALUES (:nom, :annee_academique_id, :description, :statut)");
        $this->db->bind(':nom', $data['nom']);
        $this->db->bind(':annee_academique_id', $data['annee_academique_id'], PDO::PARAM_INT);
        $this->db->bind(':description', $data['description'] ?? null);
        $this->db->bind(':statut', $data['statut'] ?? 'brouillon');

        try {
            if ($this->db->execute()) {
                return $this->db->lastInsertId();
            }
        } catch (\PDOException $e) {
            error_log("ProgrammeScolaireModel::create Error: " . $e->getMessage());
            if ($this->db->getErrorCode() == '23000' && strpos($this->db->getErrorInfo() ?? '', 'Duplicate entry') !== false && strpos($this->db->getErrorInfo() ?? '', "'nom'") !== false) {
                return 'duplicate_nom';
            }
        }
        return false;
    }

    public function update($id, $data) {
        if (empty($data['nom']) || empty($data['annee_academique_id'])) {
            error_log("ProgrammeScolaireModel::update - Missing required fields: nom or annee_academique_id for ID $id");
            return false;
        }
        $this->db->query("UPDATE {$this->table} SET nom = :nom, annee_academique_id = :annee_academique_id,
                          description = :description, statut = :statut
                          WHERE id = :id");
        $this->db->bind(':id', $id, PDO::PARAM_INT);
        $this->db->bind(':nom', $data['nom']);
        $this->db->bind(':annee_academique_id', $data['annee_academique_id'], PDO::PARAM_INT);
        $this->db->bind(':description', $data['description'] ?? null);
        $this->db->bind(':statut', $data['statut'] ?? 'brouillon');

        try {
            return $this->db->execute();
        } catch (\PDOException $e) {
            error_log("ProgrammeScolaireModel::update Error: " . $e->getMessage());
            if ($this->db->getErrorCode() == '23000' && strpos($this->db->getErrorInfo() ?? '', 'Duplicate entry') !== false && strpos($this->db->getErrorInfo() ?? '', "'nom'") !== false) {
                 return 'duplicate_nom';
            }
        }
        return false;
    }

    public function delete($id) {
        $this->db->query("DELETE FROM {$this->table} WHERE id = :id");
        $this->db->bind(':id', $id, PDO::PARAM_INT);
        try {
            return $this->db->execute(); // ON DELETE CASCADE for programme_details will handle details
        } catch (\PDOException $e) {
            error_log("ProgrammeScolaireModel::delete Error: " . $e->getMessage());
            return false;
        }
    }

    public function getDistinctStatuts() {
        $this->db->query("SELECT DISTINCT statut FROM {$this->table} WHERE statut IS NOT NULL AND statut != '' ORDER BY statut");
        return $this->db->resultSet();
    }
}
?>
