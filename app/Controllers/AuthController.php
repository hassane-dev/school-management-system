<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth; // Use the new Auth class
use App\Core\I18n; // For translations
use App\Models\UtilisateurModel;
use App\Models\RoleModel;

class AuthController extends Controller {
    private $utilisateurModel;
    private $roleModel;

    public function __construct() {
        // parent::__construct(); // If base Controller has a constructor
        // Ensure UtilisateurModel is loaded for status checks before full Auth context load
        $this->utilisateurModel = $this->model('UtilisateurModel');
        $this->roleModel = $this->model('RoleModel');
    }

    public function login() {
        if (Auth::isLoggedIn()) {
            // Basic role-based redirect for already logged-in users
            $userRoles = Auth::getCurrentUserRoleNames();
            if (in_array('SuperAdmin', $userRoles) || in_array('Admin', $userRoles)) {
                 redirectTo('/admin/dashboard');
            } else {
                 redirectTo('/dashboard'); // General user dashboard
            }
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Sanitize inputs (basic)
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            if ($email) $email = trim($email);
            $password = $_POST['password'] ?? ''; // Password should not be trimmed or overly sanitized before verification

            if (empty($email) || empty($password)) {
                $_SESSION['flash_message'] = ['text' => I18n::translate('login_fill_all_fields', 'Please fill in all fields.'), 'type' => 'danger'];
                redirectTo('/auth/login');
            }

            $user = $this->utilisateurModel->findByEmail($email);

            if ($user && password_verify($password, $user->mot_de_passe)) {
                // User authenticated, now check account status
                if ($user->statut_compte !== 'actif') {
                    $statusMessageKey = 'login_error_status_generic_issue'; // Default message
                    switch ($user->statut_compte) {
                        case 'inactif':
                            $statusMessageKey = 'login_error_status_inactive';
                            break;
                        case 'suspendu':
                            $statusMessageKey = 'login_error_status_suspended';
                            break;
                        case 'en_attente_validation':
                            $statusMessageKey = 'login_error_status_pending_validation';
                            break;
                    }
                    $_SESSION['flash_message'] = ['text' => I18n::translate($statusMessageKey), 'type' => 'danger'];
                    redirectTo('/auth/login');
                }

                // User is active, proceed to load auth context and log in
                if (Auth::loadUserAuthContext($user->id)) {
                    session_regenerate_id(true); // Security measure

                    // Update last login time
                    $this->utilisateurModel->updateLastLogin($user->id);

                    $_SESSION['flash_message'] = ['text' => I18n::translate('login_success', 'Login successful!'), 'type' => 'success'];

                    // Refined Role-based redirection
                    $userRoles = Auth::getCurrentUserRoleNames();
                    $userEmail = $user->email; // Assuming $user object is available here and has email

                    // Priority for Super User by specific email
                    if ($userEmail === 'hasmixione@gmail.com') {
                        // Optionally ensure 'SuperAdmin' role is also present or treat email as override
                        // For now, email is king for this specific Super User.
                        // This user should also be assigned 'SuperAdmin' role with 'access_superadmin_interface' perm.
                        $_SESSION['is_ultimate_superadmin'] = true; // Special flag if needed
                        redirectTo('/superadmin/dashboard');
                    }
                    // Then check by roles
                    elseif (in_array('SuperAdmin', $userRoles)) {
                        redirectTo('/superadmin/dashboard');
                    } elseif (in_array('Admin', $userRoles)) {
                        redirectTo('/admin/dashboard');
                    } elseif (in_array('Enseignant', $userRoles)) {
                        redirectTo('/enseignant/dashboard');
                    } elseif (in_array('Etudiant', $userRoles)) {
                        redirectTo('/etudiant/dashboard');
                    } elseif (in_array('Parent', $userRoles)) {
                        redirectTo('/parent/dashboard');
                    } else {
                        // Default redirect if no specific role match or for users with basic/no roles
                        redirectTo('/dashboard'); // Generic user dashboard or site homepage
                    }

                } else {
                    // This case implies user was found, password verified, status active, but loadUserAuthContext failed
                    $_SESSION['flash_message'] = ['text' => I18n::translate('login_error_context', 'Could not set up user session. Please try again.'), 'type' => 'danger'];
                    redirectTo('/auth/login');
                }
            } else {
                // Invalid email or password
                $_SESSION['flash_message'] = ['text' => I18n::translate('login_invalid_credentials', 'Invalid email or password.'), 'type' => 'danger'];
                redirectTo('/auth/login');
            }
        } else { // GET request
            $this->view('auth/login', ['title' => I18n::translate('login_page_title', 'Login')]);
        }
    }

    public function register() {
        if (Auth::isLoggedIn()) {
            redirectTo('/admin/dashboard'); // Or user dashboard
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Sanitize inputs
            $nom = trim(filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_STRING));
            $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
            $mot_de_passe = $_POST['mot_de_passe'] ?? ''; // No trim/sanitize before hashing logic
            $password_confirm = $_POST['password_confirm'] ?? '';
            $langue_preferee = trim(filter_input(INPUT_POST, 'langue_preferee', FILTER_SANITIZE_STRING) ?? DEFAULT_LANG);

            $data_form = [ // For repopulating form and passing to view
                'nom' => $nom,
                'email' => $email,
                // Do not pass passwords back to view for repopulation
                'langue_preferee' => $langue_preferee,
                'errors' => []
            ];

            // --- Validation (using UsersController's validation as a template, simplified here) ---
            if (empty($nom)) $data_form['errors']['nom'] = I18n::translate('validation.required', ['field' => 'Name']);
            if (empty($email)) {
                $data_form['errors']['email'] = I18n::translate('validation.required', ['field' => 'Email']);
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $data_form['errors']['email'] = I18n::translate('validation.email_invalid', ['field' => 'Email']);
            } elseif ($this->utilisateurModel->findByEmail($email)) { // Check if email exists
                $data_form['errors']['email'] = I18n::translate('validation.email_exists', ['field' => 'Email', 'value' => htmlspecialchars($email)]);
            }
            if (empty($mot_de_passe)) {
                $data_form['errors']['mot_de_passe'] = I18n::translate('validation.required', ['field' => 'Password']);
            } elseif (strlen($mot_de_passe) < 6) {
                $data_form['errors']['mot_de_passe'] = I18n::translate('validation.min_length', ['field' => 'Password', 'min' => 6]);
            }
            if ($mot_de_passe !== $password_confirm) {
                $data_form['errors']['password_confirm'] = I18n::translate('validation.password_mismatch');
            }
            $availableLangs = class_exists('App\Core\I18n') ? I18n::getAvailableLanguages() : [DEFAULT_LANG];
            if (!in_array($langue_preferee, $availableLangs)) {
                $langue_preferee = DEFAULT_LANG;
            }
            // --- End Validation ---

            if (empty($data_form['errors'])) {
                $userData = [
                    'nom' => $nom,
                    'email' => $email,
                    'mot_de_passe' => password_hash($mot_de_passe, PASSWORD_DEFAULT),
                    'langue_preferee' => $langue_preferee,
                    'statut_compte' => 'actif' // Or 'en_attente_validation' if email verification is implemented
                ];

                $userIdOrError = $this->utilisateurModel->create($userData);

                if (is_numeric($userIdOrError)) {
                    $userId = $userIdOrError;
                    $defaultRoleName = 'Etudiant'; // Default role for new registrations
                    $defaultRole = $this->roleModel->getByName($defaultRoleName);
                    if ($defaultRole && class_exists('App\Models\UserRoleModel')) {
                        $userRoleModel = $this->model('UserRoleModel');
                        // Assign globally or to active academic year? For registration, global is simpler.
                        $userRoleModel->assignRoleToUser($userId, $defaultRole->id, null);
                    } else {
                        error_log("Default role '$defaultRoleName' not found for new user registration (ID: $userId).");
                    }
                    $_SESSION['flash_message'] = ['text' => I18n::translate('register_success', 'Registration successful. You can now log in.'), 'type' => 'success'];
                    redirectTo('/auth/login');
                } elseif ($userIdOrError === 'duplicate_email') {
                    $data_form['errors']['email'] = I18n::translate('validation.email_exists', ['field' => 'Email', 'value' => htmlspecialchars($email)]);
                    $_SESSION['flash_message'] = ['text' => I18n::translate('validation.form_errors_detail', 'Please correct the errors and try again.'), 'type' => 'danger'];
                    $this->view('auth/register', ['title' => I18n::translate('register_page_title', 'Register'), 'data' => $data_form]);
                } else {
                    $_SESSION['flash_message'] = ['text' => I18n::translate('register_error_generic', 'Registration failed. Please try again.'), 'type' => 'danger'];
                    $this->view('auth/register', ['title' => I18n::translate('register_page_title', 'Register'), 'data' => $data_form]);
                }
            } else {
                $_SESSION['flash_message'] = ['text' => I18n::translate('validation.form_errors_detail', 'Please correct the errors and try again.'), 'type' => 'danger'];
                $this->view('auth/register', ['title' => I18n::translate('register_page_title', 'Register'), 'data' => $data_form]);
            }
        } else { // GET request
            $this->view('auth/register', ['title' => I18n::translate('register_page_title', 'Register'), 'data' => ['errors' => [], 'langue_preferee' => DEFAULT_LANG]]);
        }
    }

    public function logout() {
        Auth::logout();
        Auth::startSession(); // Restart session for flash message
        $_SESSION['flash_message'] = ['text' => I18n::translate('logout_success_msg', 'You have been logged out successfully.'), 'type' => 'success'];
        redirectTo('/auth/login');
    }
}
?>
