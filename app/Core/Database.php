<?php
namespace App\Core;

use PDO;
use PDOException;

class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    private $charset = DB_CHARSET;

    private $pdo;
    private $stmt;
    private $error;

    public function __construct() {
        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
        $options = [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            // In a real app, log this error and show a user-friendly message
            error_log("Database Connection Error: " . $this->error); // Log to server error log
            die("Database Connection Error. Please check configuration or contact support.");
        }
    }

    public function query($sql) {
        $this->stmt = $this->pdo->prepare($sql);
    }

    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }

    public function execute() {
        try {
            return $this->stmt->execute();
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            error_log("Database Query Execution Error: " . $this->error . " | SQL: " . $this->stmt->queryString);
            // Optionally re-throw or handle more gracefully
            throw $e; // Re-throw for model layer to potentially catch
        }
    }

    public function resultSet() {
        $this->execute();
        return $this->stmt->fetchAll();
    }

    public function single() {
        $this->execute();
        return $this->stmt->fetch();
    }

    public function rowCount() {
        return $this->stmt->rowCount();
    }

    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }

    // Helper to get the PDO instance if needed elsewhere (e.g. for transactions)
    public function getPdo() {
        return $this->pdo;
    }

    /**
     * Returns the SQLSTATE error code associated with the last operation on the statement handle.
     * @return string|null SQLSTATE error code, or null if no error.
     */
    public function getErrorCode(){
        return $this->stmt ? $this->stmt->errorCode() : ($this->pdo ? $this->pdo->errorCode() : null);
    }

    /**
     * Returns extended error information associated with the last operation on the statement handle.
     * Returns an array: [SQLSTATE error code, Driver-specific error code, Driver-specific error message].
     * This method specifically returns the driver-specific error message.
     * @return string|null Driver-specific error message, or null if no error.
     */
    public function getErrorInfo(){
        $errorInfo = $this->stmt ? $this->stmt->errorInfo() : ($this->pdo ? $this->pdo->errorInfo() : null);
        // Return driver-specific error message (index 2)
        return $errorInfo[2] ?? null;
    }
}
?>
