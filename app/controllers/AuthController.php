<?php

class AuthController extends Controller {
    private $utilisateurModel;
    private $roleModel;

    public function __construct() {
        // Session is already started in public/index.php
        $this->utilisateurModel = $this->model('Utilisateur_model');
        $this->roleModel = $this->model('Role_model');
    }

    /**
     * Handles user login.
     * Displays login form (GET) or processes login attempt (POST).
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Process login
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                $_SESSION['flash_message'] = __('login_credentials_required', 'Email and password are required.');
                $_SESSION['flash_type'] = 'error';
                $this->view('auth/login', ['email' => $email]);
                return;
            }

            $user = $this->utilisateurModel->findByEmail($email);

            if ($user && password_verify($password, $user->mot_de_passe)) {
                // Credentials are valid
                session_regenerate_id(true); // Regenerate session ID for security

                $_SESSION['user_id'] = $user->id;
                $_SESSION['user_nom'] = $user->nom;
                $_SESSION['user_email'] = $user->email;
                $_SESSION['role_id'] = $user->role_id;
                $_SESSION['lang'] = $user->langue_preferee ?? CURRENT_LANG; // Use user's pref, fallback to current

                // Fetch role name
                if ($user->role_id) {
                    $role = $this->roleModel->read($user->role_id);
                    $_SESSION['role_nom'] = $role ? $role->nom : 'unknown_role';
                } else {
                    $_SESSION['role_nom'] = 'guest'; // Or some default if role_id can be null
                }

                // Update CURRENT_LANG if user preference is different and valid
                if (isset($_SESSION['lang']) && $_SESSION['lang'] !== CURRENT_LANG) {
                    // This would require re-loading translations or handling it at the very beginning of next request
                    // For simplicity now, session 'lang' will be picked up by index.php on next request.
                }


                $_SESSION['flash_message'] = __('login_success', 'Login successful. Welcome!');
                $_SESSION['flash_type'] = 'success';
                // Redirect to a dashboard or role-specific page
                // For now, redirecting to a generic dashboard path. This needs to be created.
                header('Location: ' . base_url('dashboard/index'));
                exit;

            } else {
                $_SESSION['flash_message'] = __('login_failed', 'Invalid email or password.');
                $_SESSION['flash_type'] = 'error';
                $this->view('auth/login', ['email' => $email]);
                return;
            }

        } else {
            // Display login form
            // If user is already logged in, maybe redirect to dashboard?
            if (isset($_SESSION['user_id'])) {
                header('Location: ' . base_url('dashboard/index'));
                exit;
            }
            $this->view('auth/login');
        }
    }

    /**
     * Handles user registration.
     * Displays registration form (GET) or processes registration attempt (POST).
     */
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = [
                'nom' => trim($_POST['nom'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'mot_de_passe' => $_POST['password'] ?? '', // 'password' for consistency with login
                'password_confirm' => $_POST['password_confirm'] ?? '',
                'langue_preferee' => $_POST['langue_preferee'] ?? 'fr', // Default 'fr'
                'errors' => []
            ];

            // Basic Validation
            if (empty($data['nom'])) {
                $data['errors']['nom'] = __('register_name_required', 'Name is required.');
            }
            if (empty($data['email'])) {
                $data['errors']['email'] = __('register_email_required', 'Email is required.');
            } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $data['errors']['email'] = __('register_email_invalid', 'Invalid email format.');
            } elseif ($this->utilisateurModel->findByEmail($data['email'])) {
                $data['errors']['email'] = __('register_email_exists', 'This email address is already registered.');
            }
            if (empty($data['mot_de_passe'])) {
                $data['errors']['password'] = __('register_password_required', 'Password is required.');
            } elseif (strlen($data['mot_de_passe']) < 6) { // Example: min 6 chars
                $data['errors']['password'] = __('register_password_short', 'Password must be at least 6 characters.');
            }
            if ($data['mot_de_passe'] !== $data['password_confirm']) {
                $data['errors']['password_confirm'] = __('register_password_mismatch', 'Passwords do not match.');
            }
            if (!in_array($data['langue_preferee'], ['en', 'fr'])) { // Example allowed languages
                $data['langue_preferee'] = 'fr'; // Default if invalid
            }

            if (empty($data['errors'])) {
                // Validation passed, create user
                $defaultRole = $this->roleModel->findByName('utilisateur'); // Assuming 'utilisateur' is a seeded role
                $role_id = $defaultRole ? $defaultRole->id : null;
                // What if 'utilisateur' role doesn't exist? Fallback or error.
                // For now, let's assume it exists or role_id can be NULL (if DB allows)
                // A better approach is to ensure 'utilisateur' role ID is configured or reliably fetched.
                // For this example, if 'utilisateur' is not found, role_id will be null.
                // The DB schema for utilisateurs.role_id allows NULL (ON DELETE SET NULL).

                $userData = [
                    'nom' => $data['nom'],
                    'email' => $data['email'],
                    'mot_de_passe' => $data['mot_de_passe'], // Hashing is done in model's create()
                    'langue_preferee' => $data['langue_preferee'],
                    'role_id' => $role_id
                ];

                $userId = $this->utilisateurModel->create($userData);

                if ($userId) {
                    $_SESSION['flash_message'] = __('register_success', 'Registration successful. You can now log in.');
                    $_SESSION['flash_type'] = 'success';
                    header('Location: ' . base_url('auth/login'));
                    exit;
                } else {
                    // Generic error if creation failed at model level
                    $_SESSION['flash_message'] = __('register_failed_system', 'Registration failed due to a system error. Please try again.');
                    $_SESSION['flash_type'] = 'error';
                    $this->view('auth/register', $data); // Pass back data to repopulate form
                }
            } else {
                // Validation failed, reload form with errors and original data
                $_SESSION['flash_message'] = __('register_validation_errors', 'Please correct the errors below.');
                $_SESSION['flash_type'] = 'error';
                $this->view('auth/register', $data);
            }

        } else {
            // Display registration form
             if (isset($_SESSION['user_id'])) { // If already logged in, redirect
                header('Location: ' . base_url('dashboard/index'));
                exit;
            }
            $this->view('auth/register');
        }
    }

    /**
     * Handles user logout.
     */
    public function logout() {
        // Make sure all session data is cleared
        $_SESSION = array(); // Clear the $_SESSION array

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();

        $_SESSION['flash_message'] = __('logout_success', 'You have been logged out successfully.');
        $_SESSION['flash_type'] = 'info'; // Or 'success'
        header('Location: ' . base_url('auth/login'));
        exit;
    }
}
?>
