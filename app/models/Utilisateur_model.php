<?php

class Utilisateur_model extends Model {

    public function __construct() {
        parent::__construct();
        $this->table = 'utilisateurs';
    }

    /**
     * Create a new user. Hashes the password.
     * @param array $data User data including 'mot_de_passe'
     * @return mixed Last insert ID or false on failure
     */
    public function create($data) {
        if (empty($data['email']) || empty($data['mot_de_passe']) || empty($data['nom']) || !isset($data['role_id'])) {
            // error_log("Utilisateur_model::create(): Missing required fields.");
            return false;
        }

        if (isset($data['mot_de_passe'])) {
            $data['mot_de_passe'] = password_hash($data['mot_de_passe'], PASSWORD_DEFAULT);
        }

        // Ensure all expected fields are present, even if null, to match table structure
        $data['langue_preferee'] = $data['langue_preferee'] ?? 'fr';
        // date_creation has a DEFAULT CURRENT_TIMESTAMP

        return $this->insertRecord($data);
    }

    /**
     * Read user(s).
     * @param int|null $id User ID. If null, returns all users.
     * @return mixed User object, array of user objects, or false
     */
    public function read($id = null) {
        return $this->selectRecords($id);
    }

    /**
     * Update user data. Optionally hashes password if provided.
     * @param int $id User ID
     * @param array $data Data to update. If 'mot_de_passe' is present, it will be hashed.
     * @return bool True on success, false on failure
     */
    public function update($id, $data) {
        if (isset($data['mot_de_passe']) && !empty($data['mot_de_passe'])) {
            $data['mot_de_passe'] = password_hash($data['mot_de_passe'], PASSWORD_DEFAULT);
        } elseif (isset($data['mot_de_passe']) && empty($data['mot_de_passe'])) {
            unset($data['mot_de_passe']); // Do not update password if empty string is passed
        }
        return $this->updateRecord($id, $data);
    }

    /**
     * Delete a user by ID.
     * @param int $id User ID
     * @return bool True on success, false on failure
     */
    public function delete($id) {
        return $this->deleteRecord($id);
    }

    /**
     * Find a user by email.
     * @param string $email
     * @return mixed User object or false
     */
    public function findByEmail($email) {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1";
        $stmt = $this->executeQuery($sql, ['email' => $email]);
        return $stmt ? $stmt->fetch() : false;
    }

    /**
     * Verify user's password.
     * @param mixed $identifier User ID or email
     * @param string $password Plain text password
     * @return bool True if password matches, false otherwise
     */
    public function verifyPassword($identifier, $password) {
        $user = is_numeric($identifier) ? $this->read($identifier) : $this->findByEmail($identifier);
        if ($user && isset($user->mot_de_passe)) {
            return password_verify($password, $user->mot_de_passe);
        }
        return false;
    }

    /**
     * Update a user's password.
     * @param int $userId User ID
     * @param string $newPassword New plain text password
     * @return bool True on success, false on failure
     */
    public function updatePassword($userId, $newPassword) {
        if (empty($newPassword)) {
            // error_log("Utilisateur_model::updatePassword(): New password cannot be empty.");
            return false;
        }
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        return $this->updateRecord($userId, ['mot_de_passe' => $hashedPassword]);
    }

    /**
     * Get user with their role name.
     * @param int $id User ID
     * @return mixed Object with user data and role_nom, or false
     */
    public function getUserWithRole($id) {
        $sql = "SELECT u.*, r.nom as role_nom FROM {$this->table} u
                LEFT JOIN roles r ON u.role_id = r.id
                WHERE u.id = :id";
        $stmt = $this->executeQuery($sql, ['id' => $id]);
        return $stmt ? $stmt->fetch() : false;
    }

    /**
     * Get all users with their role names.
     * @return mixed Array of user objects with role_nom, or false
     */
    public function getAllUsersWithRoles() {
        $sql = "SELECT u.*, r.nom as role_nom FROM {$this->table} u
                LEFT JOIN roles r ON u.role_id = r.id";
        $stmt = $this->executeQuery($sql);
        return $stmt ? $stmt->fetchAll() : false;
    }
}
?>
