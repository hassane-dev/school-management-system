<?php

class Utilisateur_model extends Model {

    private $accreditationModel;

    public function __construct() {
        parent::__construct();
        $this->table = 'utilisateurs';
        // It's better to inject dependencies, but for this structure, lazy load or direct instantiation.
        // $this->accreditationModel = new Accreditation_model(); // Autoloader should handle this.
    }

    // Lazy load AccreditationModel to avoid issues if it's not always needed
    private function getAccreditationModel() {
        if ($this->accreditationModel === null) {
            if (!class_exists('Accreditation_model')) {
                // Basic attempt to load if autoloader somehow missed it or not configured for this context
                $modelPath = __DIR__ . '/Accreditation_model.php';
                if (file_exists($modelPath)) {
                    require_once $modelPath;
                } else {
                    throw new Exception("Accreditation_model file not found at {$modelPath}");
                }
            }
            $this->accreditationModel = new Accreditation_model();
        }
        return $this->accreditationModel;
    }


    /**
     * Create a new user. Hashes the password. Role is no longer handled here.
     * @param array $data User data including 'mot_de_passe', 'nom', 'email'.
     * @return mixed Last insert ID or false on failure
     */
    public function create($data) {
        if (empty($data['email']) || empty($data['mot_de_passe']) || empty($data['nom'])) {
            // error_log("Utilisateur_model::create(): Missing required fields (email, mot_de_passe, nom).");
            return false;
        }

        // Password hashing
        $data['mot_de_passe'] = password_hash($data['mot_de_passe'], PASSWORD_DEFAULT);

        // Ensure all expected fields are present, even if null, to match table structure
        $data['langue_preferee'] = $data['langue_preferee'] ?? 'fr';
        // date_creation has a DEFAULT CURRENT_TIMESTAMP
        // role_id is removed

        // Fields for insertRecord should only be those present in the 'utilisateurs' table
        $userData = [
            'nom' => $data['nom'],
            'email' => $data['email'],
            'mot_de_passe' => $data['mot_de_passe'],
            'langue_preferee' => $data['langue_preferee'],
        ];

        return $this->insertRecord($userData);
    }

    /**
     * Read user(s).
     * @param int|null $id User ID. If null, returns all users.
     * @return mixed User object, array of user objects, or false
     */
    public function read($id = null) {
        // role_id is no longer in this table, so no need to fetch it directly here.
        // If role info is needed with user, use getRoles() or a dedicated method.
        return $this->selectRecords($id);
    }

    /**
     * Update user data. Optionally hashes password if provided. Role is not handled here.
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

        // Remove role_id if it's part of $data, as it's not in the table anymore
        if (isset($data['role_id'])) {
            unset($data['role_id']);
        }

        return $this->updateRecord($id, $data);
    }

    /**
     * Delete a user by ID. Also removes accreditations via DB cascade or manually.
     * @param int $id User ID
     * @return bool True on success, false on failure
     */
    public function delete($id) {
        // Accreditations are set to ON DELETE CASCADE, so they should be removed automatically by the DB.
        // If not, or for extra safety:
        // $this->getAccreditationModel()->removeAllForUser($id);
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
            return false;
        }
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        return $this->updateRecord($userId, ['mot_de_passe' => $hashedPassword]);
    }

    // --- New/Updated methods for accreditations ---

    /**
     * Adds an accreditation (role) to a user.
     * @param int $userId
     * @param int $roleId
     * @return bool True on success, false on failure.
     */
    public function addAccreditation($userId, $roleId) {
        return $this->getAccreditationModel()->create(['utilisateur_id' => $userId, 'role_id' => $roleId]);
    }

    /**
     * Removes an accreditation (role) from a user.
     * @param int $userId
     * @param int $roleId
     * @return bool True on success, false on failure.
     */
    public function removeAccreditation($userId, $roleId) {
        return $this->getAccreditationModel()->delete($userId, $roleId);
    }

    /**
     * Gets all roles for a specific user.
     * @param int $userId
     * @return array Array of role objects.
     */
    public function getRoles($userId) {
        return $this->getAccreditationModel()->getRolesForUser($userId);
    }

    /**
     * Gets all role names for a specific user.
     * @param int $userId
     * @return array Array of role names.
     */
    public function getRoleNames($userId) {
        return $this->getAccreditationModel()->getRoleNamesForUser($userId);
    }


    /**
     * Fetches users who have specific "teacher" roles.
     * @param array $teacherRoleNames Array of role names considered as teacher roles.
     * @return array Array of user objects.
     */
    public function getAccreditedTeachers($teacherRoleNames = ['enseignant', 'professeur principal']) {
        if (empty($teacherRoleNames)) {
            return [];
        }
        // Create placeholders for IN clause
        $placeholders = implode(',', array_fill(0, count($teacherRoleNames), '?'));

        $sql = "SELECT u.* FROM {$this->table} u
                JOIN accreditations acc ON u.id = acc.utilisateur_id
                JOIN roles r ON acc.role_id = r.id
                WHERE r.nom IN ({$placeholders})
                GROUP BY u.id ORDER BY u.nom"; // Group by to avoid duplicates if user has multiple teacher roles

        $stmt = $this->executeQuery($sql, $teacherRoleNames);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Get all users with their role names (now potentially multiple roles).
     * This is more complex due to multiple roles. Returning a list of users,
     * where each user object has an added property `roles_list` (array of role names).
     * @return array Array of user objects, each with a 'roles_list' property.
     */
    public function getAllUsersWithRoles() {
        $users = $this->read(); // Get all users
        if (!$users) {
            return [];
        }
        foreach ($users as $user) {
            $user->roles_list = $this->getRoleNames($user->id); // Add role names list to each user
        }
        return $users;
    }
}
?>
