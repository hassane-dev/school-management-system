<?php

class Role_model extends Model {

    public function __construct() {
        parent::__construct();
        $this->table = 'roles';
    }

    /**
     * Create a new role.
     * @param array $data Role data, must include 'nom'.
     * @return mixed Last insert ID or false on failure.
     */
    public function create($data) {
        if (empty($data['nom'])) {
            // error_log("Role_model::create(): Role name ('nom') is required.");
            return false;
        }
        return $this->insertRecord($data);
    }

    /**
     * Read role(s).
     * @param int|null $id Role ID. If null, returns all roles.
     * @return mixed Role object, array of role objects, or false.
     */
    public function read($id = null) {
        return $this->selectRecords($id);
    }

    /**
     * Update a role by ID.
     * @param int $id Role ID.
     * @param array $data Data to update.
     * @return bool True on success (or if no rows affected but query was valid), false on failure.
     */
    public function update($id, $data) {
        if (empty($data['nom'])) {
             // error_log("Role_model::update(): Role name ('nom') is required for update.");
            return false; // Or handle more gracefully depending on requirements
        }
        return $this->updateRecord($id, $data);
    }

    /**
     * Delete a role by ID.
     * @param int $id Role ID.
     * @return bool True on success (row count > 0), false otherwise.
     */
    public function delete($id) {
        // Consider implications: what happens to users with this role?
        // The DB schema for utilisateurs has ON DELETE SET NULL for role_id.
        return $this->deleteRecord($id);
    }

    /**
     * Find a role by its name.
     * @param string $name Role name.
     * @return mixed Role object or false.
     */
    public function findByName($name) {
        $sql = "SELECT * FROM {$this->table} WHERE nom = :nom LIMIT 1";
        $stmt = $this->executeQuery($sql, ['nom' => $name]);
        return $stmt ? $stmt->fetch() : false;
    }
}
?>
