<?php

class Accreditation_model extends Model {

    public function __construct() {
        parent::__construct();
        $this->table = 'accreditations'; // Primary table for this model
    }

    /**
     * Creates a new accreditation (links a user to a role).
     * @param array $data Associative array with 'utilisateur_id' and 'role_id'.
     * @return mixed The last insert ID (though composite PK, so less relevant) or true on success, false on failure.
     */
    public function create($data) {
        if (empty($data['utilisateur_id']) || empty($data['role_id'])) {
            // error_log("Accreditation_model::create(): utilisateur_id and role_id are required.");
            return false;
        }

        // Check for existing record to prevent duplicate entry errors if not handled by DB constraints gracefully
        $exists_sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE utilisateur_id = :utilisateur_id AND role_id = :role_id";
        $stmt_check = $this->executeQuery($exists_sql, ['utilisateur_id' => $data['utilisateur_id'], 'role_id' => $data['role_id']]);
        $result = $stmt_check->fetch();

        if ($result && $result->count > 0) {
            return true; // Record already exists, consider it a success.
        }

        // date_accreditation has a DEFAULT CURRENT_TIMESTAMP in schema
        $sql = "INSERT INTO {$this->table} (utilisateur_id, role_id) VALUES (:utilisateur_id, :role_id)";
        $stmt = $this->executeQuery($sql, [
            'utilisateur_id' => $data['utilisateur_id'],
            'role_id' => $data['role_id']
        ]);

        // For INSERT on table with composite PK, lastInsertId might not be what you expect or needed.
        // Success is indicated by the statement executing without error.
        return $stmt !== false;
    }

    /**
     * Deletes an accreditation (unlinks a user from a role).
     * @param int $utilisateur_id
     * @param int $role_id
     * @return bool True on success (if rows were affected), false otherwise.
     */
    public function delete($utilisateur_id, $role_id) {
        if (empty($utilisateur_id) || empty($role_id)) {
            // error_log("Accreditation_model::delete(): utilisateur_id and role_id are required.");
            return false;
        }
        $sql = "DELETE FROM {$this->table} WHERE utilisateur_id = :utilisateur_id AND role_id = :role_id";
        $stmt = $this->executeQuery($sql, [
            'utilisateur_id' => $utilisateur_id,
            'role_id' => $role_id
        ]);
        return $stmt ? $stmt->rowCount() > 0 : false;
    }

    /**
     * Fetches all role objects for a given user ID.
     * @param int $userId
     * @return array Array of role objects (id, nom).
     */
    public function getRolesForUser($userId) {
        if (empty($userId)) {
            return [];
        }
        $sql = "SELECT r.id, r.nom FROM roles r
                JOIN {$this->table} a ON r.id = a.role_id
                WHERE a.utilisateur_id = :userId";
        $stmt = $this->executeQuery($sql, ['userId' => $userId]);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_OBJ) : [];
    }

    /**
     * Fetches all role names for a given user ID.
     * @param int $userId
     * @return array Array of role names.
     */
    public function getRoleNamesForUser($userId) {
        if (empty($userId)) {
            return [];
        }
        $sql = "SELECT r.nom FROM roles r
                JOIN {$this->table} a ON r.id = a.role_id
                WHERE a.utilisateur_id = :userId";
        $stmt = $this->executeQuery($sql, ['userId' => $userId]);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN, 0) : [];
    }


    /**
     * Fetches all user objects for a given role ID.
     * @param int $roleId
     * @return array Array of user objects (id, nom, email).
     */
    public function getUsersForRole($roleId) {
        if (empty($roleId)) {
            return [];
        }
        $sql = "SELECT u.id, u.nom, u.email FROM utilisateurs u
                JOIN {$this->table} a ON u.id = a.utilisateur_id
                WHERE a.role_id = :roleId";
        $stmt = $this->executeQuery($sql, ['roleId' => $roleId]);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_OBJ) : [];
    }

    /**
     * Checks if a user has a specific role.
     * @param int $userId
     * @param int|string $roleIdentifier Role ID (int) or Role Name (string).
     * @return bool True if the user has the role, false otherwise.
     */
    public function userHasRole($userId, $roleIdentifier) {
        if (empty($userId) || empty($roleIdentifier)) {
            return false;
        }

        $params = ['userId' => $userId];
        if (is_numeric($roleIdentifier)) {
            $sql = "SELECT COUNT(*) as count FROM {$this->table}
                    WHERE utilisateur_id = :userId AND role_id = :roleId";
            $params['roleId'] = $roleIdentifier;
        } else {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} a
                    JOIN roles r ON a.role_id = r.id
                    WHERE a.utilisateur_id = :userId AND r.nom = :roleName";
            $params['roleName'] = $roleIdentifier;
        }

        $stmt = $this->executeQuery($sql, $params);
        $result = $stmt ? $stmt->fetch() : null;

        return $result && $result->count > 0;
    }

    /**
     * Removes all accreditations for a specific user.
     * Useful when deleting a user or completely re-assigning roles.
     * @param int $userId
     * @return bool True on success, false on failure.
     */
    public function removeAllForUser($userId) {
        if (empty($userId)) return false;
        $sql = "DELETE FROM {$this->table} WHERE utilisateur_id = :userId";
        $stmt = $this->executeQuery($sql, ['userId' => $userId]);
        return $stmt !== false; // Returns true if query executed, even if 0 rows affected
    }

    /**
     * Removes all accreditations for a specific role.
     * Useful when deleting a role.
     * @param int $roleId
     * @return bool True on success, false on failure.
     */
    public function removeAllForRole($roleId) {
        if (empty($roleId)) return false;
        $sql = "DELETE FROM {$this->table} WHERE role_id = :roleId";
        $stmt = $this->executeQuery($sql, ['roleId' => $roleId]);
        return $stmt !== false;
    }
}
?>
