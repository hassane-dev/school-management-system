<?php

class Permission_model extends Model {

    public function __construct() {
        parent::__construct();
        $this->table = 'permissions';
    }

    /**
     * Create a new permission.
     * @param array $data Permission data, must include 'nom'. 'description' is optional.
     * @return mixed Last insert ID or false on failure.
     */
    public function create($data) {
        if (empty($data['nom'])) {
            // error_log("Permission_model::create(): Permission name ('nom') is required.");
            return false;
        }
        // Ensure description is set, even if null, to match table structure
        $data['description'] = $data['description'] ?? null;
        return $this->insertRecord($data);
    }

    /**
     * Read permission(s).
     * @param int|null $id Permission ID. If null, returns all permissions.
     * @return mixed Permission object, array of permission objects, or false.
     */
    public function read($id = null) {
        return $this->selectRecords($id);
    }

    /**
     * Update a permission by ID.
     * @param int $id Permission ID.
     * @param array $data Data to update.
     * @return bool True on success, false on failure.
     */
    public function update($id, $data) {
         if (empty($id) || empty($data) || (isset($data['nom']) && empty($data['nom']))) {
            // error_log("Permission_model::update(): ID and data (with non-empty name if provided) are required.");
            return false;
        }
        return $this->updateRecord($id, $data);
    }

    /**
     * Delete a permission by ID.
     * @param int $id Permission ID.
     * @return bool True on success, false on failure.
     */
    public function delete($id) {
        // Consider implications: this will also affect role_permissions table due to CASCADE.
        return $this->deleteRecord($id);
    }

    /**
     * Find a permission by its name.
     * @param string $name Permission name.
     * @return mixed Permission object or false.
     */
    public function findByName($name) {
        $sql = "SELECT * FROM {$this->table} WHERE nom = :nom LIMIT 1";
        $stmt = $this->executeQuery($sql, ['nom' => $name]);
        return $stmt ? $stmt->fetch() : false;
    }
}
?>
