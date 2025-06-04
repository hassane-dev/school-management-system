<?php

class Matiere_model extends Model {
    public function __construct() {
        parent::__construct();
        $this->table = 'matieres';
    }

    public function read($id = null) {
        return $this->selectRecords($id);
    }

    public function findByName($name) {
        $sql = "SELECT * FROM {$this->table} WHERE nom = :nom LIMIT 1";
        $stmt = $this->executeQuery($sql, ['nom' => $name]);
        return $stmt ? $stmt->fetch() : false;
    }

    public function create($data) {
        if (empty($data['nom'])) return false;
        return $this->insertRecord($data);
    }
}
?>
