<?php
// File: app/Models/UtilisateurModel.php
namespace App\Models;
use App\Core\Model;
use PDO;

class UtilisateurModel extends Model {

    public function __construct() {
        parent::__construct(); // Ensures $this->db is initialized
        // Note: The table name is not explicitly set here.
        // The base Model class or individual query methods must handle table names.
        // For consistency, it's good practice to define $this->table = 'utilisateurs';
        // if the base Model class uses $this->table.
        // The provided base Model.php doesn't automatically use a $table property in findById.
    }

    public function getById($id) {
        $this->db->query("SELECT * FROM utilisateurs WHERE id = :id");
        $this->db->bind(':id', $id, PDO::PARAM_INT);
        return $this->db->single();
    }

    public function findByEmail($email) { // Needed by AuthController
        $this->db->query("SELECT * FROM utilisateurs WHERE email = :email");
        $this->db->bind(':email', $email);
        return $this->db->single();
    }

    // Basic create for user seeding or future user management (Sprint 4)
    public function create($data) {
        // Assumes 'mot_de_passe' is already hashed
        // Assumes 'nom', 'email', 'mot_de_passe' are provided in $data
        if (empty($data['nom']) || empty($data['email']) || empty($data['mot_de_passe'])) {
            error_log("UtilisateurModel::create - Missing required data.");
            return false;
        }

        $sql = "INSERT INTO utilisateurs (nom, email, mot_de_passe";
        $placeholders = ":nom, :email, :mot_de_passe";

        if (isset($data['langue_preferee'])) {
            $sql .= ", langue_preferee";
            $placeholders .= ", :langue_preferee";
        }
        // Note: 'role_id' was removed from 'utilisateurs' table in sprint3_database.sql
        // Roles are now handled by 'user_roles' table.

        $sql .= ") VALUES (" . $placeholders . ")";

        $this->db->query($sql);
        $this->db->bind(':nom', $data['nom']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':mot_de_passe', $data['mot_de_passe']);

        if (isset($data['langue_preferee'])) {
            $this->db->bind(':langue_preferee', $data['langue_preferee']);
        }

        try {
            if ($this->db->execute()) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch (\PDOException $e) {
            error_log("UtilisateurModel::create Error: " . $e->getMessage());
             if ($e->getCode() == '23000') { // Integrity constraint violation (e.g. unique email)
                return 'duplicate_email';
            }
            return false;
        }
    }

    // Add other methods from the more complete UtilisateurModel from "Develop/Update Models for Sprint 3"
    // if they are intended to be part of this consolidated model.
    // For now, this fulfills the minimal requirement for Auth context.
}
?>
