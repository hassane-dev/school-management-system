<?php

class ParametresController extends Controller {
    private $parametresGenerauxModel;
    private $parametresEcoleModel;
    private $anneesAcademiquesModel;

    public function __construct() {
        // Autoloading should handle model loading, but explicit instantiation is needed.
        // The base Controller's model() method can be used here.
        $this->parametresGenerauxModel = $this->model('ParametresGeneraux_model');
        $this->parametresEcoleModel = $this->model('ParametresEcole_model');
        $this->anneesAcademiquesModel = $this->model('AnneesAcademiques_model');

        // Initialize session for flash messages if not already started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function index() {
        // Fetch data
        // Assuming ID 1 for single-row tables or first record.
        // The model's read() method for these single-row tables was designed to fetch the first row if no ID is given.
        $data['parametres_generaux'] = $this->parametresGenerauxModel->read();
        $data['parametres_ecole'] = $this->parametresEcoleModel->read();
        $data['annees_academiques'] = $this->anneesAcademiquesModel->read();
        $data['active_annee'] = $this->anneesAcademiquesModel->getActiveYear();

        // Load view
        // The view 'parametres/index.php' will need to be created in a subsequent step.
        $this->view('parametres/index', $data);
    }

    public function update_generaux() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Sanitize POST data (basic example)
            $post_data = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            // Data for parametres_generaux
            $data_generaux = [
                'langue_defaut' => $post_data['langue_defaut'] ?? 'fr',
                'theme' => $post_data['theme'] ?? 'default',
                'devise_monnaie' => $post_data['devise_monnaie'] ?? 'USD',
                'pays' => $post_data['pays'] ?? '',
                'region' => $post_data['region'] ?? '',
                'ville' => $post_data['ville'] ?? '',
                'quartier' => $post_data['quartier'] ?? '',
                'nombre_langues' => (int)($post_data['nombre_langues'] ?? 1),
                'langue_1' => $post_data['langue_1'] ?? 'fr',
                'langue_2' => $post_data['langue_2'] ?? null,
                'langue_3' => $post_data['langue_3'] ?? null,
            ];

            // ParametresGeneraux_model expects an ID for update.
            // Assuming we are updating the first record (or ID 1 if it exists)
            // A more robust way is to fetch the record, get its ID, then update.
            // Or, if the table is guaranteed to have only one row, an ID might be static (e.g. 1)
            // For now, let's assume an "upsert" or that an ID is known.
            // The model's read() fetches the first row. If it exists, use its ID.
            $current_settings = $this->parametresGenerauxModel->read();
            $settings_id = $current_settings ? $current_settings->id : null;

            if ($settings_id) {
                if ($this->parametresGenerauxModel->update($settings_id, $data_generaux)) {
                    $_SESSION['flash_message'] = 'General settings updated successfully!';
                    $_SESSION['flash_type'] = 'success';
                } else {
                    $_SESSION['flash_message'] = 'Failed to update general settings.';
                    $_SESSION['flash_type'] = 'error';
                }
            } else {
                 // If no settings exist, create them
                if ($this->parametresGenerauxModel->create($data_generaux)) {
                    $_SESSION['flash_message'] = 'General settings created successfully!';
                    $_SESSION['flash_type'] = 'success';
                } else {
                    $_SESSION['flash_message'] = 'Failed to create general settings.';
                    $_SESSION['flash_type'] = 'error';
                }
            }
            header('Location: /index.php?url=parametres/index'); // Adjust URL as per routing
            exit;
        }
    }

    public function update_ecole() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $post_data = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $data_ecole = [
                'nom_ecole' => $post_data['nom_ecole'] ?? '',
                'sigle' => $post_data['sigle'] ?? '',
                'email' => $post_data['email'] ?? '',
                'telephone' => $post_data['telephone'] ?? '',
                'site_web' => $post_data['site_web'] ?? null,
                'cycle_etude' => $post_data['cycle_etude'] ?? '',
            ];

            $current_ecole_settings = $this->parametresEcoleModel->read();
            $ecole_settings_id = $current_ecole_settings ? $current_ecole_settings->id : null;

            if ($ecole_settings_id) {
                if ($this->parametresEcoleModel->update($ecole_settings_id, $data_ecole)) {
                    $_SESSION['flash_message'] = 'School settings updated successfully!';
                    $_SESSION['flash_type'] = 'success';
                } else {
                    $_SESSION['flash_message'] = 'Failed to update school settings.';
                    $_SESSION['flash_type'] = 'error';
                }
            } else {
                if ($this->parametresEcoleModel->create($data_ecole)) {
                    $_SESSION['flash_message'] = 'School settings created successfully!';
                    $_SESSION['flash_type'] = 'success';
                } else {
                    $_SESSION['flash_message'] = 'Failed to create school settings.';
                    $_SESSION['flash_type'] = 'error';
                }
            }
            header('Location: /index.php?url=parametres/index');
            exit;
        }
    }

    public function add_annee_academique() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $post_data = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            $data = [
                'libelle' => $post_data['libelle'] ?? '',
                'date_debut' => $post_data['date_debut'] ?? '',
                'date_fin' => $post_data['date_fin'] ?? '',
                'active' => isset($post_data['active']) ? 1 : 0,
            ];

            if ($this->anneesAcademiquesModel->create($data)) {
                // If this new year is set to active, deactivate others
                if ($data['active']) {
                    $newId = $this->anneesAcademiquesModel->db->lastInsertId(); // Requires model to expose DB or lastInsertId
                    $this->anneesAcademiquesModel->executeQuery("UPDATE annees_academiques SET active = 0 WHERE id != :id", ['id' => $newId]);
                }
                $_SESSION['flash_message'] = 'Academic year added successfully!';
                $_SESSION['flash_type'] = 'success';
            } else {
                $_SESSION['flash_message'] = 'Failed to add academic year.';
                $_SESSION['flash_type'] = 'error';
            }
            header('Location: /index.php?url=parametres/index');
            exit;
        }
    }

    public function update_annee_academique($id) {
        if (empty($id)) {
             $_SESSION['flash_message'] = 'Invalid ID for academic year update.';
             $_SESSION['flash_type'] = 'error';
             header('Location: /index.php?url=parametres/index');
             exit;
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $post_data = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            $data = [
                'libelle' => $post_data['libelle'] ?? '',
                'date_debut' => $post_data['date_debut'] ?? '',
                'date_fin' => $post_data['date_fin'] ?? '',
                'active' => isset($post_data['active']) ? 1 : 0,
            ];

            if ($this->anneesAcademiquesModel->update($id, $data)) {
                 if ($data['active']) {
                    // Deactivate other active years if this one is now active
                    $this->anneesAcademiquesModel->executeQuery("UPDATE annees_academiques SET active = 0 WHERE id != :id AND active = 1", ['id' => $id]);
                }
                $_SESSION['flash_message'] = 'Academic year updated successfully!';
                $_SESSION['flash_type'] = 'success';
            } else {
                $_SESSION['flash_message'] = 'Failed to update academic year.';
                $_SESSION['flash_type'] = 'error';
            }
            header('Location: /index.php?url=parametres/index');
            exit;
        }
    }

    public function delete_annee_academique($id) {
        if (empty($id)) {
             $_SESSION['flash_message'] = 'Invalid ID for academic year deletion.';
             $_SESSION['flash_type'] = 'error';
             header('Location: /index.php?url=parametres/index');
             exit;
        }
        // Could add a check here to prevent deletion of active year, or handle re-assigning active status.
        if ($this->anneesAcademiquesModel->delete($id)) {
            $_SESSION['flash_message'] = 'Academic year deleted successfully!';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Failed to delete academic year.';
            $_SESSION['flash_type'] = 'error';
        }
        header('Location: /index.php?url=parametres/index');
        exit;
    }

    public function activer_annee_academique($id) {
        if (empty($id)) {
             $_SESSION['flash_message'] = 'Invalid ID for activating academic year.';
             $_SESSION['flash_type'] = 'error';
             header('Location: /index.php?url=parametres/index');
             exit;
        }
        // Deactivate all other academic years first
        $this->anneesAcademiquesModel->executeQuery("UPDATE annees_academiques SET active = 0 WHERE active = 1");

        if ($this->anneesAcademiquesModel->activer($id)) {
            $_SESSION['flash_message'] = 'Academic year activated successfully!';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Failed to activate academic year.';
            $_SESSION['flash_type'] = 'error';
        }
        header('Location: /index.php?url=parametres/index');
        exit;
    }

    public function desactiver_annee_academique($id) {
         if (empty($id)) {
             $_SESSION['flash_message'] = 'Invalid ID for deactivating academic year.';
             $_SESSION['flash_type'] = 'error';
             header('Location: /index.php?url=parametres/index');
             exit;
        }
        if ($this->anneesAcademiquesModel->desactiver($id)) {
            $_SESSION['flash_message'] = 'Academic year deactivated successfully!';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Failed to deactivate academic year.';
            $_SESSION['flash_type'] = 'error';
        }
        header('Location: /index.php?url=parametres/index');
        exit;
    }
}
?>
