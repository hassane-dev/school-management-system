<?php

class ParametresEcole_model extends Model {

    public function __construct() {
        parent::__construct();
        $this->table = 'parametres_ecole';
    }

    /**
     * Create a new school parameters record.
     * Similar to general settings, this table might often have only one record.
     */
    public function create($data) {
        $required_fields = ['nom_ecole', 'sigle', 'email', 'telephone', 'cycle_etude'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field])) {
                // error_log("Missing required field: $field for parametres_ecole creation");
                return false;
            }
        }
        // Ensure site_web is set, even if null, to match potential DB constraints or expectations
        $data['site_web'] = isset($data['site_web']) ? $data['site_web'] : null;

        return $this->insertRecord($data);
    }

    /**
     * Read school parameters.
     * If an ID is provided, it fetches that specific record.
     * If no ID, it attempts to fetch the first record.
     */
    public function read($id = null) {
        if ($id !== null) {
            return $this->selectRecords($id);
        } else {
            // Fetch the first record, assuming there's usually only one
            $result = $this->selectRecords(null, [], '', '1');
            return $result ? $result[0] : false;
        }
    }

    /**
     * Update school parameters by ID.
     */
    public function update($id, $data) {
        if (empty($id) || empty($data)) {
            return false;
        }
        return $this->updateRecord($id, $data);
    }

    /**
     * Delete school parameters record by ID.
     */
    public function delete($id) {
        if (empty($id)) {
            return false;
        }
        return $this->deleteRecord($id);
    }
}
?>
