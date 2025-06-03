<?php

class AnneesAcademiques_model extends Model {

    public function __construct() {
        parent::__construct();
        $this->table = 'annees_academiques';
    }

    /**
     * Create a new academic year record.
     */
    public function create($data) {
        $required_fields = ['libelle', 'date_debut', 'date_fin'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field])) {
                // error_log("Missing required field: $field for annees_academiques creation");
                return false;
            }
        }
        // Ensure 'active' field has a default if not provided
        $data['active'] = isset($data['active']) ? $data['active'] : 0;

        return $this->insertRecord($data);
    }

    /**
     * Read academic year(s).
     * If ID is null, returns all academic years.
     * If ID is provided, returns a single academic year.
     */
    public function read($id = null) {
        return $this->selectRecords($id);
    }

    /**
     * Update an academic year record by ID.
     */
    public function update($id, $data) {
        if (empty($id) || empty($data)) {
            return false;
        }
        return $this->updateRecord($id, $data);
    }

    /**
     * Delete an academic year record by ID.
     */
    public function delete($id) {
        if (empty($id)) {
            return false;
        }
        return $this->deleteRecord($id);
    }

    /**
     * Activate an academic year.
     * This might involve deactivating other academic years if only one can be active.
     * For now, it just activates the specified one.
     * A more complex logic could be:
     * $this->db->beginTransaction();
     * $this->executeQuery("UPDATE {$this->table} SET active = 0 WHERE active = 1");
     * $this->updateRecord($id, ['active' => 1]);
     * $this->db->commit();
     */
    public function activer($id) {
        if (empty($id)) {
            return false;
        }
        // Optional: Deactivate all others first
        // $this->executeQuery("UPDATE {$this->table} SET active = 0 WHERE id != :id", ['id' => $id]);
        // For now, a simpler approach: ensure all others are inactive before activating a new one if that's a rule.
        // The task implies a simpler activer/desactiver for now.
        return $this->updateRecord($id, ['active' => 1]);
    }

    /**
     * Deactivate an academic year.
     */
    public function desactiver($id) {
        if (empty($id)) {
            return false;
        }
        return $this->updateRecord($id, ['active' => 0]);
    }

    /**
     * Get the currently active academic year.
     * Assumes only one academic year can be active at a time.
     */
    public function getActiveYear() {
        $sql = "SELECT * FROM {$this->table} WHERE active = 1 LIMIT 1";
        $stmt = $this->executeQuery($sql);
        return $stmt ? $stmt->fetch() : false;
    }
}
?>
