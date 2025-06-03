<?php

class ParametresGeneraux_model extends Model {

    public function __construct() {
        parent::__construct(); // Ensure parent constructor is called
        $this->table = 'parametres_generaux';
    }

    /**
     * Create a new general settings record.
     * Assumes only one record, so it might be an update or create.
     * For simplicity, this create will attempt to insert.
     * A more robust solution might check if a record exists and then update.
     * Or, rely on the application logic to only call create once.
     * For now, we allow multiple records, though typically this table has one.
     */
    public function create($data) {
        // Validate required fields (example)
        $required_fields = ['langue_defaut', 'theme', 'devise_monnaie', 'pays', 'region', 'ville', 'quartier', 'nombre_langues', 'langue_1'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field])) {
                // error_log("Missing required field: $field for parametres_generaux creation");
                return false; // Or throw an exception
            }
        }
        return $this->insertRecord($data);
    }

    /**
     * Read general settings.
     * Typically, there's only one row in this table.
     * If an ID is provided, it fetches that specific record.
     * If no ID, it attempts to fetch the first record (most relevant for this table).
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
     * Update general settings by ID.
     * (Usually, this table has only one record, so ID might be known or fetched first)
     */
    public function update($id, $data) {
        if (empty($id) || empty($data)) {
            return false;
        }
        return $this->updateRecord($id, $data);
    }

    /**
     * Delete a general settings record by ID.
     * (Be cautious with this, as there's usually only one record)
     */
    public function delete($id) {
        if (empty($id)) {
            return false;
        }
        return $this->deleteRecord($id);
    }
}
?>
