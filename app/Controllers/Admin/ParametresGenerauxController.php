<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\I18n;     // For translating flash messages
use App\Core\Auth;      // For ACL

class ParametresGenerauxController extends Controller {
    private $parametresModel;

    public function __construct() {
        // Router already protects /admin/* path.
        // Specific permissions will be checked in methods like update().
        // Viewing settings (index) might be allowed for more roles if granular view permissions are added later.
        // For now, if a user can access admin, they can view this page.
        // If 'view_general_settings' permission was defined and desired:
        // Auth::requirePermission('view_general_settings'); // Would apply to all methods if in constructor

        $this->parametresModel = $this->model('ParametresGenerauxModel');
        if (!$this->parametresModel) {
            // Handle error: model not loaded
            die("Error loading ParametresGenerauxModel.");
        }
    }

    public function index() {
        $settings = $this->parametresModel->getSettings();
        if (!$settings) {
            // This might happen if the default row (id=1) isn't in the DB.
            // Provide default empty object or handle error.
            $settings = (object)[]; // Empty object to prevent errors in view if settings are missing
            // Optionally set a warning flash message if settings are missing.
            $_SESSION['flash_message'] = ['text' => I18n::translate('pg_error_settings_missing', 'General settings not found in database. Using defaults.'), 'type' => 'warning'];
        }

        $data = [
            'settings' => $settings,
            'title' => I18n::translate('pg_title', 'General Settings Management') // Example key
        ];
        $this->view('admin/parametres_generaux/index', $data, 'admin_default');
    }

    public function update() {
        Auth::requirePermission('manage_general_settings'); // Protect the update action

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Sanitize POST data (basic example)
            // A more robust sanitization/validation library would be better.
            $sanitizedPost = [];
            foreach($_POST as $key => $value){
                $sanitizedPost[$key] = htmlspecialchars(strip_tags(trim($value)));
            }

            $data = [
                'nom_application' => $sanitizedPost['nom_application'] ?? '',
                'langue_site_par_defaut' => $sanitizedPost['langue_site_par_defaut'] ?? DEFAULT_LANG,
                'theme_defaut' => $sanitizedPost['theme_defaut'] ?? 'default',
                'devise_monnaie' => $sanitizedPost['devise_monnaie'] ?? 'XOF',
                'pays_par_defaut' => $sanitizedPost['pays_par_defaut'] ?? '',
                'ville_par_defaut' => $sanitizedPost['ville_par_defaut'] ?? '',
                'fuseau_horaire' => $sanitizedPost['fuseau_horaire'] ?? 'UTC',
                'format_date' => $sanitizedPost['format_date'] ?? 'Y-m-d',
                'format_heure' => $sanitizedPost['format_heure'] ?? 'H:i:s',
                'email_contact_general' => filter_var($sanitizedPost['email_contact_general'] ?? '', FILTER_SANITIZE_EMAIL),
            ];

            // Validate email format (simple example)
            if (!empty($data['email_contact_general']) && !filter_var($data['email_contact_general'], FILTER_VALIDATE_EMAIL)) {
                 $_SESSION['flash_message'] = ['text' => I18n::translate('pg_update_error_invalid_email', 'Invalid contact email format.'), 'type' => 'danger'];
                 header("Location: " . URL_ROOT . "/admin/parametresgeneraux");
                 exit;
            }


            if ($this->parametresModel->updateSettings($data)) {
                $_SESSION['flash_message'] = ['text' => I18n::translate('pg_update_success_msg', 'General settings updated successfully!'), 'type' => 'success'];

                // If default language changed, update session for current user if they are using default
                // And potentially update DEFAULT_LANG if I18n relies on it dynamically (complex)
                if (isset($_SESSION['lang']) && $_SESSION['lang'] !== $data['langue_site_par_defaut'] &&
                    (!isset($_SESSION['user_lang_override']) || !$_SESSION['user_lang_override'])) { // Example check
                    // $_SESSION['lang'] = $data['langue_site_par_defaut'];
                    // App\Core\I18n::setCurrentLang($data['langue_site_par_defaut']); // To reload translations
                }

            } else {
                $_SESSION['flash_message'] = ['text' => I18n::translate('pg_update_error_msg', 'Failed to update general settings.'), 'type' => 'danger'];
            }
            header("Location: " . URL_ROOT . "/admin/parametresgeneraux");
            exit;

        } else {
            header("Location: " . URL_ROOT . "/admin/parametresgeneraux");
            exit;
        }
    }
}
?>
