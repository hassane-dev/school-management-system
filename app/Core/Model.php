<?php
namespace App\Core;

// The Database class should be in the same namespace or imported with 'use'.
// Since it's in App\Core, it's accessible directly.

class Model {
    protected $db;

    public function __construct() {
        // Instantiate the Database class to get a PDO connection wrapper
        $this->db = new Database();
    }

    /**
     * A generic method to find a record by ID from a given table.
     * Note: Child models should specify their table name.
     * This is an example; more sophisticated query building might be in child models or a query builder.
     *
     * @param string $table The name of the table.
     * @param int $id The ID of the record to find.
     * @return mixed The record object or false if not found.
     */
    public function findById($table, $id) {
        $this->db->query("SELECT * FROM {$table} WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }
}
?>
