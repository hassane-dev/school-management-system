<?php
namespace App\Models;

use App\Core\Model; // Assuming Model.php is in App\Core
use PDO;

class ParametresEcoleModel extends Model {
    private $settingsId = 1; // This table will only have one row with id=1

    public function getSettings() {
        $this->db->query("SELECT * FROM parametres_ecole WHERE id = :id");
        $this->db->bind(':id', $this->settingsId, PDO::PARAM_INT);
        return $this->db->single();
    }

    public function updateSettings($data) {
        // Define allowed keys to prevent arbitrary column updates and ensure data integrity
        $allowedKeys = [
            'nom_ecole', 'logo_path', 'type_etablissement', 'adresse_physique',
            'boite_postale', 'telephone_contact', 'email_contact', 'site_web'
        ];

        $setClauses = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedKeys)) {
                $setClauses[] = "$key = :$key";
            }
        }

        if (empty($setClauses)) {
            return false; // No valid fields to update or $data was empty
        }
        $setString = implode(', ', $setClauses);

        $sql = "UPDATE parametres_ecole SET $setString WHERE id = :id";
        $this->db->query($sql);

        // Bind data values
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedKeys)) {
                // Let the Database::bind method auto-detect type, or be specific if needed
                $this->db->bind(":$key", $value);
            }
        }
        $this->db->bind(':id', $this->settingsId, PDO::PARAM_INT);

        try {
            return $this->db->execute();
        } catch (\PDOException $e) {
            // Log error for diagnostics
            error_log("Error updating school settings: " . $e->getMessage());
            return false;
        }
    }
}
?>
