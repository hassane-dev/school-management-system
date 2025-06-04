<?php
namespace App\Models;
use App\Core\Model;
use PDO; // For binding types

class UtilisateurModel extends Model {
    protected $table = 'utilisateurs'; // Define table name for potential use in base Model

    public function __construct() {
        parent::__construct();
    }

    public function getById($id) {
        // Selects all columns from utilisateurs table.
        // Consider selecting specific columns if not all are needed, for minor performance gain.
        $this->db->query("SELECT u.* FROM {$this->table} u WHERE u.id = :id");
        $this->db->bind(':id', $id, PDO::PARAM_INT);
        $user = $this->db->single();
        return $user;
    }

    public function findByEmail($email) {
        $this->db->query("SELECT * FROM {$this->table} WHERE email = :email");
        $this->db->bind(':email', $email);
        return $this->db->single();
    }

    public function isEmailUnique($email, $currentUserId = null): bool {
        $sql = "SELECT id FROM {$this->table} WHERE email = :email";
        $bindings = [':email' => $email];
        if ($currentUserId !== null) {
            $sql .= " AND id != :currentUserId";
            $bindings[':currentUserId'] = $currentUserId;
        }
        $this->db->query($sql);
        foreach($bindings as $key => $value) {
            $this->db->bind($key, $value);
        }
        $this->db->execute(); // Must execute before rowCount
        return $this->db->rowCount() === 0;
    }

    public function getAll($filters = [], $options = []) {
        // Base query - select specific, necessary columns to avoid fetching sensitive data like password hash unless needed
        $sql = "SELECT u.id, u.nom, u.email, u.statut_compte, u.date_derniere_connexion, u.date_creation, u.langue_preferee FROM {$this->table} u";
        $whereClauses = [];
        $bindings = [];

        if (!empty($filters['search'])) {
            $whereClauses[] = "(u.nom LIKE :search OR u.email LIKE :search)";
            $bindings[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['statut_compte'])) {
            $whereClauses[] = "u.statut_compte = :statut_compte";
            $bindings[':statut_compte'] = $filters['statut_compte'];
        }
        // Add more filters here as needed, e.g., for roles (would require JOIN)

        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }

        $orderBy = $options['orderBy'] ?? 'u.nom'; // Default order
        $orderDir = $options['orderDir'] ?? 'ASC';
        // Validate orderBy against a list of allowed columns to prevent SQL injection if $orderBy comes from user input
        $allowedOrderBy = ['u.id', 'u.nom', 'u.email', 'u.statut_compte', 'u.date_creation', 'u.date_derniere_connexion'];
        if (!in_array($orderBy, $allowedOrderBy)) {
            $orderBy = 'u.nom'; // Fallback to default
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
        $sql = "SELECT COUNT(u.id) as total FROM {$this->table} u";
        $whereClauses = [];
        $bindings = [];

        if (!empty($filters['search'])) {
           $whereClauses[] = "(u.nom LIKE :search OR u.email LIKE :search)";
           $bindings[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['statut_compte'])) {
           $whereClauses[] = "u.statut_compte = :statut_compte";
           $bindings[':statut_compte'] = $filters['statut_compte'];
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

    public function create($data) {
        // Password should be pre-hashed by the controller/service layer
        if (empty($data['nom']) || empty($data['email']) || empty($data['mot_de_passe'])) {
            error_log("UtilisateurModel::create - Missing required data: nom, email, or mot_de_passe.");
            return false;
        }

        $sql = "INSERT INTO {$this->table} (nom, email, mot_de_passe, langue_preferee, statut_compte, date_creation, date_modification)
                VALUES (:nom, :email, :mot_de_passe, :langue_preferee, :statut_compte, NOW(), NOW())";
        $this->db->query($sql);
        $this->db->bind(':nom', $data['nom']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':mot_de_passe', $data['mot_de_passe']);
        $this->db->bind(':langue_preferee', $data['langue_preferee'] ?? (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'fr'));
        $this->db->bind(':statut_compte', $data['statut_compte'] ?? 'actif');

        try {
            if ($this->db->execute()) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch (\PDOException $e) {
            error_log("UtilisateurModel::create Error: " . $e->getMessage() . " | Data: " . json_encode($data));
            if ($e->getCode() == '23000') { // Integrity constraint violation
                return 'duplicate_email'; // Or more generic 'error_duplicate'
            }
            return false;
        }
    }

    public function update($id, $data) {
        $setParts = [];
        $bindings = [':id' => $id];
        $allowedFields = ['nom', 'email', 'langue_preferee', 'statut_compte']; // Fields allowed for direct update

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $setParts[] = "$field = :$field";
                $bindings[":$field"] = $data[$field];
            }
        }

        if (empty($setParts)) return true; // Nothing to update or only non-allowed fields were passed

        // date_modification is updated automatically by DB (ON UPDATE CURRENT_TIMESTAMP)
        $this->db->query("UPDATE {$this->table} SET " . implode(', ', $setParts) . " WHERE id = :id");
        foreach($bindings as $key => $value) {
            $this->db->bind($key, $value);
        }

        try {
            return $this->db->execute();
        } catch (\PDOException $e) {
            error_log("UtilisateurModel::update Error: " . $e->getMessage() . " | ID: $id | Data: " . json_encode($data));
             if ($e->getCode() == '23000') { // Integrity constraint violation
                return 'duplicate_email';
            }
            return false;
        }
    }

    public function updatePassword($id, $hashedPassword) {
        $this->db->query("UPDATE {$this->table} SET mot_de_passe = :mot_de_passe WHERE id = :id");
        $this->db->bind(':id', $id, PDO::PARAM_INT);
        $this->db->bind(':mot_de_passe', $hashedPassword);
        return $this->db->execute();
    }

    public function updateStatus($id, $status) {
        // Validate status against allowed values if necessary
        $allowedStatus = ['actif', 'inactif', 'suspendu', 'en_attente_validation', 'supprime'];
        if (!in_array($status, $allowedStatus)) {
            error_log("UtilisateurModel::updateStatus - Invalid status value: $status");
            return false;
        }
        $this->db->query("UPDATE {$this->table} SET statut_compte = :statut_compte WHERE id = :id");
        $this->db->bind(':id', $id, PDO::PARAM_INT);
        $this->db->bind(':statut_compte', $status);
        return $this->db->execute();
    }

    public function updateLastLogin($id) {
        $this->db->query("UPDATE {$this->table} SET date_derniere_connexion = NOW() WHERE id = :id");
        $this->db->bind(':id', $id, PDO::PARAM_INT);
        return $this->db->execute();
    }

    public function delete($id) {
        // For hard delete: (Ensure ON DELETE CASCADE is set for related user_roles etc. or handle manually)
        $this->db->query("DELETE FROM {$this->table} WHERE id = :id");
        $this->db->bind(':id', $id, PDO::PARAM_INT);
        // Consider implications: user_roles has ON DELETE CASCADE, so associated roles will be removed.
        // If other tables link to utilisateurs, ensure appropriate FK constraints (ON DELETE SET NULL, CASCADE, or RESTRICT).
        try {
            return $this->db->execute();
        } catch (\PDOException $e) {
            error_log("UtilisateurModel::delete Error: " . $e->getMessage() . " | ID: $id");
            // Check for FK constraint violation if not handled by CASCADE
            if ($e->getCode() == '23000') {
                return 'error_linked'; // User is linked in other tables that don't cascade/set null.
            }
            return false;
        }
    }
}
?>
