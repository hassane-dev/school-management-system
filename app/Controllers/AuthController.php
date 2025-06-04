<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth; // Use the new Auth class
use App\Core\I18n; // For translations
use App\Models\UtilisateurModel; // Still needed for findByEmail
use App\Models\RoleModel; // For assigning default role on register

class AuthController extends Controller {
    private $utilisateurModel;
    private $roleModel; // For registration default role

    public function __construct() {
        // parent::__construct(); // If base Controller has a constructor
        $this->utilisateurModel = $this->model('UtilisateurModel');
        $this->roleModel = $this->model('RoleModel'); // For registration
        // No need to load UserRoleModel or RolePermissionModel here, Auth class handles them
    }

    public function login() {
        if (Auth::isLoggedIn()) { // If already logged in, redirect from login page
            redirectTo('/admin/dashboard'); // Or user-specific dashboard
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                $_SESSION['flash_message'] = ['text' => I18n::translate('login_credentials_required', 'Email and password are required.'), 'type' => 'danger'];
                redirectTo('/auth/login');
            }

            $user = $this->utilisateurModel->findByEmail($email);

            if ($user && password_verify($password, $user->mot_de_passe)) {
                if (Auth::loadUserAuthContext($user->id)) {
                    session_regenerate_id(true); // Security measure
                    $_SESSION['flash_message'] = ['text' => I18n::translate('login_success', 'Login successful!'), 'type' => 'success'];
                    // Redirect to a role-based dashboard or a default one
                    // Example: $userRoles = Auth::getCurrentUserRoleNames(); if (in_array('Admin', $userRoles)) ...
                    redirectTo('/admin/dashboard');
                } else {
                    $_SESSION['flash_message'] = ['text' => I18n::translate('login_error_context', 'Could not set up user session. Please try again.'), 'type' => 'danger'];
                    redirectTo('/auth/login');
                }
            } else {
                $_SESSION['flash_message'] = ['text' => I18n::translate('login_invalid_credentials', 'Invalid email or password.'), 'type' => 'danger'];
                // Pass back email for form repopulation if desired, though redirect clears POST
                // Consider rendering view directly with error: $this->view('auth/login', ['email' => $email, 'error' => ...]);
                redirectTo('/auth/login');
            }
        } else { // GET request
            $this->view('auth/login', ['title' => I18n::translate('login_page_title', 'Login')]);
        }
    }

    public function register() {
        if (Auth::isLoggedIn()) {
            redirectTo('/admin/dashboard');
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            $data = [
                'nom' => trim($_POST['nom'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'mot_de_passe' => $_POST['mot_de_passe'] ?? '',
                'password_confirm' => $_POST['password_confirm'] ?? '',
                'langue_preferee' => $_POST['langue_preferee'] ?? DEFAULT_LANG,
                'errors' => []
            ];

            // --- Validation ---
            if (empty($data['nom'])) $data['errors']['nom'] = I18n::translate('validation.required', ['field' => 'Name']);
            if (empty($data['email'])) {
                $data['errors']['email'] = I18n::translate('validation.required', ['field' => 'Email']);
            } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $data['errors']['email'] = I18n::translate('validation.email_invalid', ['field' => 'Email']);
            } elseif ($this->utilisateurModel->findByEmail($data['email'])) {
                $data['errors']['email'] = I18n::translate('validation.email_exists', ['field' => 'Email']);
            }
            if (empty($data['mot_de_passe'])) {
                $data['errors']['password'] = I18n::translate('validation.required', ['field' => 'Password']);
            } elseif (strlen($data['mot_de_passe']) < 6) { // Example minimum length
                $data['errors']['password'] = I18n::translate('validation.min_length', ['field' => 'Password', 'min' => 6]);
            }
            if ($data['mot_de_passe'] !== $data['password_confirm']) {
                $data['errors']['password_confirm'] = I18n::translate('validation.password_mismatch');
            }
            // Validate language preference if necessary
            $availableLangs = class_exists('App\Core\I18n') ? I18n::getAvailableLanguages() : [DEFAULT_LANG];
            if (!in_array($data['langue_preferee'], $availableLangs)) {
                $data['langue_preferee'] = DEFAULT_LANG; // Fallback
            }
            // --- End Validation ---

            if (empty($data['errors'])) {
                $userData = [
                    'nom' => $data['nom'],
                    'email' => $data['email'],
                    'mot_de_passe' => password_hash($data['mot_de_passe'], PASSWORD_DEFAULT),
                    'langue_preferee' => $data['langue_preferee']
                ];

                $userId = $this->utilisateurModel->create($userData);

                if ($userId && $userId !== 'duplicate_email') {
                    // Assign a default role, e.g., 'Etudiant' or 'User'
                    $defaultRole = $this->roleModel->getByName('Etudiant'); // Make sure 'Etudiant' role is seeded
                    if ($defaultRole && class_exists('App\Models\UserRoleModel')) {
                        $userRoleModel = $this->model('UserRoleModel');
                        $userRoleModel->assignRoleToUser($userId, $defaultRole->id); // Assign globally for now
                    }
                    $_SESSION['flash_message'] = ['text' => I18n::translate('register_success', 'Registration successful. You can now log in.'), 'type' => 'success'];
                    redirectTo('/auth/login');
                } elseif($userId === 'duplicate_email') {
                    $data['errors']['email'] = I18n::translate('validation.email_exists', ['field' => 'Email']);
                     $_SESSION['flash_message'] = ['text' => I18n::translate('validation.form_errors_detail'), 'type' => 'danger'];
                    $this->view('auth/register', ['title' => I18n::translate('register_page_title', 'Register'), 'data' => $data]);
                }else {
                    $_SESSION['flash_message'] = ['text' => I18n::translate('register_error_generic', 'Registration failed. Please try again.'), 'type' => 'danger'];
                    $this->view('auth/register', ['title' => I18n::translate('register_page_title', 'Register'), 'data' => $data]);
                }
            } else {
                $_SESSION['flash_message'] = ['text' => I18n::translate('validation.form_errors_detail', 'Please correct the errors below and try again.'), 'type' => 'danger'];
                $this->view('auth/register', ['title' => I18n::translate('register_page_title', 'Register'), 'data' => $data]);
            }
        } else { // GET request
            $this->view('auth/register', ['title' => I18n::translate('register_page_title', 'Register'), 'data' => ['errors' => []]]);
        }
    }

    public function logout() {
        Auth::logout();
        // Set flash message after logout, as logout clears session.
        // For this to work, session must be started again immediately after destroy, or handle flash differently.
        // A common way is to pass it as GET param for one-time display or use a more persistent flash mechanism.
        // For now, let's assume session can be restarted by redirectTo or next page load.
        session_start(); // Restart session just for the flash message
        $_SESSION['flash_message'] = ['text' => I18n::translate('logout_success', 'You have been logged out.'), 'type' => 'success'];
        redirectTo('/auth/login');
    }
}
?>
