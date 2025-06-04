<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\I18n;
use App\Core\Auth; // For ACL

class ParametresEcoleController extends Controller {
    private $paramsEcoleModel;

    public function __construct() {
        // Router already protects /admin/* path.
        // Specific permissions will be checked in methods like update().
        // If 'view_school_settings' permission was defined:
        // Auth::requirePermission('view_school_settings');

        $this->paramsEcoleModel = $this->model('ParametresEcoleModel');
        if (!$this->paramsEcoleModel) {
            die("Error loading ParametresEcoleModel.");
        }
         // Define FCPATH if not already defined (path to public folder)
        if (!defined('FCPATH')) {
            // Assuming this controller is in app/Controllers/Admin/
            // Adjust path to public folder accordingly.
            // This is a fallback; FCPATH should ideally be set in public/index.php
            define('FCPATH', dirname(APP_ROOT) . '/public/');
        }
    }

    public function index() {
        $settings = $this->paramsEcoleModel->getSettings();
        if (!$settings) {
            $settings = (object)[]; // Default empty object
            $_SESSION['flash_message'] = ['text' => I18n::translate('pe_error_settings_missing', 'School settings not found. Using defaults.'), 'type' => 'warning'];
        }
        $data = [
            'settings' => $settings,
            'title' => I18n::translate('pe_title', 'School Settings Management')
        ];
        $this->view('admin/parametres_ecole/index', $data, 'admin_default');
    }

    public function update() {
        Auth::requirePermission('manage_school_settings'); // Protect the update action

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Sanitize most text inputs
            $sanitizedPost = [];
            foreach($_POST as $key => $value){
                if ($key === 'adresse_physique') { // Allow more chars for address
                    $sanitizedPost[$key] = filter_var($value, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
                } else {
                    $sanitizedPost[$key] = filter_var(trim($value), FILTER_SANITIZE_STRING);
                }
            }

            $data = [
                'nom_ecole' => $sanitizedPost['nom_ecole'] ?? 'Mon École',
                'type_etablissement' => in_array($sanitizedPost['type_etablissement'], ['public', 'prive', 'parapublic']) ? $sanitizedPost['type_etablissement'] : 'prive',
                'adresse_physique' => $sanitizedPost['adresse_physique'] ?? '',
                'boite_postale' => $sanitizedPost['boite_postale'] ?? '',
                'telephone_contact' => $sanitizedPost['telephone_contact'] ?? '',
                'email_contact' => filter_var($sanitizedPost['email_contact'] ?? '', FILTER_SANITIZE_EMAIL),
                'site_web' => filter_var($sanitizedPost['site_web'] ?? '', FILTER_SANITIZE_URL),
            ];

            if (!empty($data['email_contact']) && !filter_var($data['email_contact'], FILTER_VALIDATE_EMAIL)) {
                 $_SESSION['flash_message'] = ['text' => I18n::translate('pe_update_error_invalid_email', 'Invalid contact email format.'), 'type' => 'danger'];
                 header("Location: " . URL_ROOT . "/admin/parametresecole");
                 exit;
            }
            if (!empty($data['site_web']) && !filter_var($data['site_web'], FILTER_VALIDATE_URL)) {
                // Allow empty or valid URL. If not empty and not valid, then error.
                // Some systems store URL without scheme, so simple validation might fail.
                // For this basic case, we'll accept it or clear if patently invalid, or you can add more robust URL validation.
                // $data['site_web'] = ''; // Or set error
            }


            $currentSettings = $this->paramsEcoleModel->getSettings();
            $data['logo_path'] = $currentSettings->logo_path ?? null;

            if (isset($_FILES['logo_upload']) && $_FILES['logo_upload']['error'] == UPLOAD_ERR_OK) {
                $uploadDirRelative = 'uploads/logos/'; // Relative to public directory (URL_ROOT)
                $publicUploadDirAbsolute = FCPATH . $uploadDirRelative;

                if (!is_dir($publicUploadDirAbsolute)) {
                    if (!mkdir($publicUploadDirAbsolute, 0775, true)) {
                        $_SESSION['flash_message'] = ['text' => I18n::translate('pe_error_mkdir_failed', 'Failed to create logo directory.'), 'type' => 'danger'];
                        // Proceed without logo update
                        goto save_settings; // Jump to saving other settings
                    }
                }

                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                $fileType = mime_content_type($_FILES['logo_upload']['tmp_name']);

                if (in_array($fileType, $allowedTypes)) {
                    $extension = pathinfo($_FILES['logo_upload']['name'], PATHINFO_EXTENSION);
                    $fileName = uniqid('logo_') . '.' . $extension;
                    $targetPath = $publicUploadDirAbsolute . $fileName;

                    if (move_uploaded_file($_FILES['logo_upload']['tmp_name'], $targetPath)) {
                        // Delete old logo if it exists and is different
                        if (!empty($currentSettings->logo_path) && file_exists(FCPATH . $currentSettings->logo_path) && $currentSettings->logo_path !== $uploadDirRelative . $fileName) {
                            @unlink(FCPATH . $currentSettings->logo_path);
                        }
                        $data['logo_path'] = $uploadDirRelative . $fileName;
                    } else {
                        $_SESSION['flash_message'] = ['text' => I18n::translate('pe_error_logo_upload_failed', 'Logo upload failed.'), 'type' => 'danger'];
                    }
                } else {
                    $_SESSION['flash_message'] = ['text' => I18n::translate('pe_error_logo_invalid_type', 'Invalid logo file type. Allowed: JPG, PNG, GIF.'), 'type' => 'danger'];
                }
            }

            save_settings:
            if ($this->paramsEcoleModel->updateSettings($data)) {
                 // Set success message only if no prior error message from logo handling
                if (!isset($_SESSION['flash_message'])) {
                     $_SESSION['flash_message'] = ['text' => I18n::translate('pe_update_success_msg', 'School settings updated successfully!'), 'type' => 'success'];
                } elseif (isset($_SESSION['flash_message']) && $_SESSION['flash_message']['type'] !== 'danger') {
                    // If there was a warning about logo, but settings saved, still show success for settings
                     $_SESSION['flash_message'] = ['text' => I18n::translate('pe_update_success_with_logo_issue_msg', 'School settings updated. Note: there was an issue with the logo: ') . $_SESSION['flash_message']['text'], 'type' => 'warning'];
                }
            } else {
                if (!isset($_SESSION['flash_message'])) { // Avoid overwriting specific logo error
                    $_SESSION['flash_message'] = ['text' => I18n::translate('pe_update_error_msg', 'Failed to update school settings or no changes made.'), 'type' => 'danger'];
                }
            }
            header("Location: " . URL_ROOT . "/admin/parametresecole");
            exit;

        } else {
            header("Location: " . URL_ROOT . "/admin/parametresecole");
            exit;
        }
    }
}
?>
