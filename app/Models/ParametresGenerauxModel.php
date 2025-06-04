<?php
namespace App\Models;

use App\Core\Model; // Assuming Model.php is in App\Core
use PDO; // For using PDO type constants if needed, though Database class handles types

class ParametresGenerauxModel extends Model {
    private $settingsId = 1; // This table will only have one row with id=1

    public function getSettings() {
        $this->db->query("SELECT * FROM parametres_generaux WHERE id = :id");
        $this->db->bind(':id', $this->settingsId, PDO::PARAM_INT);
        return $this->db->single(); // single() fetches as object by default (from Database class)
    }

    public function updateSettings($data) {
        // Construct SET part of the query dynamically
        $setClauses = [];
        $allowedKeys = [ // Define allowed keys to prevent arbitrary column updates
            'nom_application', 'langue_site_par_defaut', 'theme_defaut',
            'devise_monnaie', 'pays_par_defaut', 'ville_par_defaut',
            'fuseau_horaire', 'format_date', 'format_heure', 'email_contact_general'
        ];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedKeys)) {
                $setClauses[] = "$key = :$key";
            }
        }

        if (empty($setClauses)) {
            return false; // No valid fields to update
        }
        $setString = implode(', ', $setClauses);

        $sql = "UPDATE parametres_generaux SET $setString WHERE id = :id";
        $this->db->query($sql);

        // Bind data values
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedKeys)) {
                 $this->db->bind(":$key", $value); // Type is auto-detected by Database::bind
            }
        }
        $this->db->bind(':id', $this->settingsId, PDO::PARAM_INT);

        try {
            return $this->db->execute();
        } catch (\PDOException $e) {
            // Log error, e.g., $e->getMessage()
            error_log("Error updating general settings: " . $e->getMessage());
            return false;
        }
    }
}
?>
