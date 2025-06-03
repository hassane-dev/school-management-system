<?php

class Model {
    protected $db;
    protected $table; // To be set by child models

    // TODO: Replace with actual database credentials from a config file
    private $host = 'localhost';
    private $db_name = 'your_db_name';
    private $username = 'your_username';
    private $password = 'your_password';

    public function __construct() {
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->db_name . ';charset=utf8';
        $options = [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ, // Default to objects
        ];

        try {
            $this->db = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            // In a real application, you would log this error and show a user-friendly message
            die('Connection failed: ' . $e->getMessage());
        }
    }

    // Generic query execution
    protected function executeQuery($sql, $params = []) {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            // Log error
            error_log("Database Error: " . $e->getMessage() . " in query: " . $sql . " with params: " . implode(", ", $params));
            return false;
        }
    }

    // Generic insert
    protected function insertRecord($data) {
        if (empty($this->table) || empty($data)) {
            return false;
        }
        $fields = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO {$this->table} ({$fields}) VALUES ({$placeholders})";

        $stmt = $this->executeQuery($sql, $data);
        return $stmt ? $this->db->lastInsertId() : false;
    }

    // Generic select
    // if $id is provided, fetches one record by id, otherwise all records
    protected function selectRecords($id = null, $conditions = [], $orderBy = '', $limit = '') {
        if (empty($this->table)) {
            return false;
        }
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        if ($id !== null) {
            $sql .= " WHERE id = :id";
            $params['id'] = $id;
        } elseif (!empty($conditions)) {
            $sql .= " WHERE ";
            $condParts = [];
            foreach ($conditions as $key => $value) {
                $condParts[] = "{$key} = :{$key}";
                $params[$key] = $value;
            }
            $sql .= implode(' AND ', $condParts);
        }

        if (!empty($orderBy)) {
            $sql .= " ORDER BY {$orderBy}";
        }

        if (!empty($limit)) {
            $sql .= " LIMIT {$limit}";
        }

        $stmt = $this->executeQuery($sql, $params);
        if ($id !== null) {
            return $stmt ? $stmt->fetch() : false;
        }
        return $stmt ? $stmt->fetchAll() : false;
    }

    // Generic update
    protected function updateRecord($id, $data) {
        if (empty($this->table) || empty($data) || $id === null) {
            return false;
        }
        $fields = [];
        foreach (array_keys($data) as $key) {
            $fields[] = "{$key} = :{$key}";
        }
        $fieldStr = implode(', ', $fields);
        $sql = "UPDATE {$this->table} SET {$fieldStr} WHERE id = :id";

        $params = $data;
        $params['id'] = $id;

        $stmt = $this->executeQuery($sql, $params);
        return $stmt ? $stmt->rowCount() : false; // rowCount can be 0 if no rows were affected but query was successful
    }

    // Generic delete
    protected function deleteRecord($id) {
        if (empty($this->table) || $id === null) {
            return false;
        }
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->executeQuery($sql, ['id' => $id]);
        return $stmt ? $stmt->rowCount() : false;
    }
}
?>
