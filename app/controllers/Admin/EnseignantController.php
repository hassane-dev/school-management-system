<?php

namespace App\Controllers\Admin;

// If your base Controller is not namespaced or in App\Core, adjust this use statement.
// Assuming base Controller is in the global scope or App\Core.
// If Controller.php is `class Controller { ... }` in app/core/Controller.php, then:
// use Controller; // or use App\Core\Controller; if it's namespaced

// For this example, let's assume the base Controller class is accessible without a specific use statement
// if it's in the global namespace and autoloaded, or adjust as per your actual base controller.
// If Controller.php is "class Controller" and App.php is in global, this should work.
// If App.php defines "class App extends Controller" then Controller is global.
// For now, assuming base `Controller` is properly autoloaded and accessible.
// If your base Controller is namespaced e.g. `App\Core\Controller` then use `use App\Core\Controller;`
// and `class EnseignantController extends \App\Core\Controller`
// For now, let's assume the base `Controller` class is in the global namespace for simplicity
// or that an autoloader handles App\Core\Controller.

// If Controller.php is in app/core/ and not namespaced, this might be needed:
require_once __DIR__ . '/../../core/Controller.php'; // Adjust path if needed


class EnseignantController extends \Controller { // Or \App\Core\Controller if namespaced
    private $utilisateurModel;
    private $roleModel;
    private $accreditationModel;

    public function __construct() {
        // Ensure AuthSession is loaded for ACL checks
        if (!class_exists('AuthSession') && file_exists(__DIR__ . '/../../core/AuthSession.php')) {
            require_once __DIR__ . '/../../core/AuthSession.php';
        }

        // ACL: Ensure user is admin
        if (!\AuthSession::isLoggedIn()) {
            \AuthSession::setFlash(__('global.login_required'), 'warning');
            redirectTo('/auth/login');
        }
        if (!\AuthSession::hasRole(['admin', 'super_admin'])) {
            \AuthSession::setFlash(__('global.access_denied_admin_area'), 'danger');
            redirectTo('/dashboard');
        }
        // More specific permission for managing teachers/accreditations
        if (!\AuthSession::hasPermission('manage_teachers_accreditations')) { // Example permission
             \AuthSession::setFlash(__('global.access_denied', 'You do not have permission to manage teacher accreditations.'), 'danger');
             redirectTo('/dashboard'); // Or admin dashboard
        }


        $this->utilisateurModel = $this->model('Utilisateur_model');
        $this->roleModel = $this->model('Role_model');
        $this->accreditationModel = $this->model('Accreditation_model');
    }

    /**
     * Lists all users identified as teachers.
     */
    public function index() {
        // Define which roles are considered "teacher" roles
        $teacherRoleNames = ['enseignant', 'professeur principal', 'teacher']; // Adjust as per your roles table
        $teachers = $this->utilisateurModel->getAccreditedTeachers($teacherRoleNames);

        // Enhance teacher objects with their full list of accreditations (role names) for the view
        if ($teachers) {
            foreach ($teachers as $teacher) {
                $teacher->accreditations = $this->accreditationModel->getRoleNamesForUser($teacher->id);
            }
        }

        $data = [
            'teachers' => $teachers,
            'page_title' => __('enseignants.list_title', 'Teacher List')
        ];
        $this->view('admin/enseignants/index', $data);
    }

    /**
     * Manages accreditations for a specific user.
     * @param int $userId
     */
    public function manageAccreditations($userId) {
        $user = $this->utilisateurModel->read($userId);
        if (!$user) {
            \AuthSession::setFlash(__('users.user_not_found', 'User not found.'), 'danger');
            redirectTo('/admin/enseignant'); // Or wherever appropriate
        }

        $userCurrentRoles = $this->accreditationModel->getRolesForUser($userId); // Gets array of role objects
        $allAvailableRoles = $this->roleModel->read(); // Gets all role objects

        // Filter out roles the user already has from the list of roles they can be assigned
        $userCurrentRoleIds = array_map(function($role) { return $role->id; }, $userCurrentRoles);
        $assignableRoles = array_filter($allAvailableRoles, function($role) use ($userCurrentRoleIds) {
            return !in_array($role->id, $userCurrentRoleIds);
        });

        $data = [
            'user' => $user,
            'userCurrentRoles' => $userCurrentRoles, // For display and removal
            'assignableRoles' => $assignableRoles,  // For the "add accreditation" dropdown
            'page_title' => __('enseignants.manage_accreditations_title', 'Manage Accreditations for ') . $user->nom
        ];
        $this->view('admin/enseignants/accreditations', $data);
    }

    /**
     * Adds an accreditation to a user.
     * @param int $userId
     */
    public function addAccreditation($userId) {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $roleId = $_POST['role_id'] ?? null;

            if (empty($userId) || empty($roleId)) {
                \AuthSession::setFlash(__('enseignants.add_accreditation_error_input', 'User ID and Role ID are required.'), 'danger');
            } else {
                if ($this->accreditationModel->create(['utilisateur_id' => $userId, 'role_id' => $roleId])) {
                    \AuthSession::setFlash(__('enseignants.add_accreditation_success', 'Accreditation added successfully.'), 'success');
                } else {
                    // Check if it was a duplicate error (model returns true for existing)
                    // The model's create method returns true if already exists, so this path might mean a DB error
                     \AuthSession::setFlash(__('enseignants.add_accreditation_error_db', 'Could not add accreditation. It might already exist or a database error occurred.'), 'danger');
                }
            }
            redirectTo('/admin/enseignant/manageAccreditations/' . $userId);
        } else {
            // Redirect if not POST
            redirectTo('/admin/enseignant/manageAccreditations/' . $userId);
        }
    }

    /**
     * Removes an accreditation from a user.
     * @param int $userId
     * @param int $roleId
     */
    public function removeAccreditation($userId, $roleId) {
        // CSRF protection would be good here if it's a GET request action
        if (empty($userId) || empty($roleId)) {
            \AuthSession::setFlash(__('enseignants.remove_accreditation_error_input', 'User ID and Role ID are required for removal.'), 'danger');
        } else {
            if ($this->accreditationModel->delete($userId, $roleId)) {
                \AuthSession::setFlash(__('enseignants.remove_accreditation_success', 'Accreditation removed successfully.'), 'success');
            } else {
                \AuthSession::setFlash(__('enseignants.remove_accreditation_error_db', 'Could not remove accreditation or it was not found.'), 'danger');
            }
        }
        redirectTo('/admin/enseignant/manageAccreditations/' . $userId);
    }
}
?>
