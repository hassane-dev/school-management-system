<?php
// File: app/Controllers/Admin/UsersController.php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\I18n;
use App\Models\UtilisateurModel;
use App\Models\RoleModel;
use App\Models\UserRoleModel;

class UsersController extends Controller {
    private $userModel;
    private $roleModel;
    private $userRoleModel;

    // Define statuses for validation/dropdowns
    private $accountStatuses = ['actif', 'inactif', 'suspendu', 'en_attente_validation']; // Add 'supprime' if using soft delete

    public function __construct() {
        Auth::requirePermission('view_users'); // Base permission for accessing user management
        $this->userModel = $this->model('UtilisateurModel');
        $this->roleModel = $this->model('RoleModel');
        $this->userRoleModel = $this->model('UserRoleModel');
        // Add AnneeAcademiqueModel for the reactivate method
        $this->anneeAcademiqueModel = $this->model('AnneeAcademiqueModel');
    }

    public function index() {
        $filters = [
            'search' => trim($_GET['search'] ?? ''),
            'statut_compte' => trim($_GET['statut_compte'] ?? ''),
            // 'role_id' => trim($_GET['role_id'] ?? '') // Requires JOIN in model or post-processing
        ];

        $page = (int)($_GET['page'] ?? 1);
        $perPage = 15; // Make this configurable later if needed
        $totalUsers = $this->userModel->getTotalCount($filters);
        $totalPages = ceil($totalUsers / $perPage);
        $offset = ($page - 1) * $perPage;

        $options = [
            'limit' => $perPage,
            'offset' => $offset,
            'orderBy' => trim($_GET['orderBy'] ?? 'u.nom'), // Default order
            'orderDir' => trim($_GET['orderDir'] ?? 'ASC'),
        ];

        $users = $this->userModel->getAll($filters, $options);

        // Attach roles to each user for display
        // Consider performance implications for very large lists.
        if ($users) {
            foreach ($users as $user) {
                // Fetch roles in the currently active academic year context (or global)
                $userRolesObjects = $this->userRoleModel->getRolesForUser($user->id, Auth::getActiveAcademicYearId(), true);
                $user->roles = array_map(function($r){ return $r->nom; }, $userRolesObjects);
            }
        }

        $this->view('admin/users/index', [
            'users' => $users,
            'title' => __('users_title_list', 'User Management'),
            'filters' => $filters,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'perPage' => $perPage,
            'totalUsers' => $totalUsers,
            'orderBy' => $options['orderBy'],
            'orderDir' => $options['orderDir'],
            'accountStatuses' => $this->accountStatuses,
            'allRoles' => $this->roleModel->getAll() // For role filter dropdown
        ], 'admin_default');
    }

    private function validateUserData($data, $isEditMode = false, $userId = null) {
        $errors = [];
        $defaultLang = defined('DEFAULT_LANG') ? DEFAULT_LANG : 'fr';

        if (empty($data['nom'])) $errors['nom'] = I18n::translate('validation.required', ['field' => __('users_form_label_name', 'Name')]);

        if (empty($data['email'])) {
            $errors['email'] = I18n::translate('validation.required', ['field' => __('users_form_label_email', 'Email')]);
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = I18n::translate('validation.email_invalid', ['field' => __('users_form_label_email', 'Email')]);
        } elseif (!$this->userModel->isEmailUnique($data['email'], $userId)) {
            // Pass the value to the translation
            $errors['email'] = I18n::translate('validation.unique', ['field' => __('users_form_label_email', 'Email'), 'value' => htmlspecialchars($data['email'])]);
        }

        if (!$isEditMode || !empty($data['mot_de_passe'])) {
            if (empty($data['mot_de_passe']) && !$isEditMode) { // Required only on add
                $errors['mot_de_passe'] = I18n::translate('validation.required', ['field' => __('users_form_label_password', 'Password')]);
            } elseif (!empty($data['mot_de_passe']) && strlen($data['mot_de_passe']) < 6) {
                $errors['mot_de_passe'] = I18n::translate('validation.min_length', ['field' => __('users_form_label_password', 'Password'), 'min' => 6]);
            } elseif (!empty($data['mot_de_passe']) && $data['mot_de_passe'] !== $data['mot_de_passe_confirm']) {
                $errors['mot_de_passe_confirm'] = I18n::translate('validation.password_mismatch');
            }
        }
        if (empty($data['langue_preferee'])) $data['langue_preferee'] = $defaultLang; // Default if empty rather than error

        $availableLangs = class_exists('App\Core\I18n') ? I18n::getAvailableLanguages() : [$defaultLang];
        if (!in_array($data['langue_preferee'], $availableLangs)) {
             $errors['langue_preferee'] = I18n::translate('validation.invalid_selection', ['field' => __('users_form_label_langue_preferee', 'Preferred Language')]);
        }

        if (empty($data['statut_compte']) || !in_array($data['statut_compte'], $this->accountStatuses)) {
             $errors['statut_compte'] = I18n::translate('validation.invalid_selection', ['field' => __('users_form_label_statut_compte', 'Account Status')]);
        }
        // Role validation depends on if it's mandatory and how it's submitted (e.g. array of IDs)
        if (empty($data['roles']) || !is_array($data['roles']) || count($data['roles']) === 0) {
            $errors['roles'] = I18n::translate('validation.min_selected', ['field' => __('users_form_label_roles', 'Roles'), 'count' => 1]);
        } else {
            // Optional: Check if all submitted role IDs are valid
            $allValidRoles = array_map(function($r){ return $r->id; }, $this->roleModel->getAll());
            foreach($data['roles'] as $roleId) {
                if(!in_array($roleId, $allValidRoles)) {
                    $errors['roles'] = I18n::translate('validation.invalid_role_id', ['role_id' => htmlspecialchars($roleId)]);
                    break;
                }
            }
        }
        return $errors;
    }

    public function add() {
        Auth::requirePermission('create_user');
        $defaultLang = defined('DEFAULT_LANG') ? DEFAULT_LANG : 'fr';
        $formData = ['nom' => '', 'email' => '', 'langue_preferee' => $defaultLang, 'statut_compte' => 'actif', 'roles' => [], 'errors' => []];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING); // Basic sanitize, be careful with complex inputs
            $formData = [
                'nom' => trim($_POST['nom'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'mot_de_passe' => $_POST['mot_de_passe'] ?? '',
                'mot_de_passe_confirm' => $_POST['mot_de_passe_confirm'] ?? '',
                'langue_preferee' => trim($_POST['langue_preferee'] ?? $defaultLang),
                'statut_compte' => trim($_POST['statut_compte'] ?? 'actif'),
                'roles' => $_POST['roles'] ?? [],
                'errors' => []
            ];

            $formData['errors'] = $this->validateUserData($formData);

            if (empty($formData['errors'])) {
                $userDataForModel = [
                    'nom' => $formData['nom'],
                    'email' => $formData['email'],
                    'mot_de_passe' => password_hash($formData['mot_de_passe'], PASSWORD_DEFAULT),
                    'langue_preferee' => $formData['langue_preferee'],
                    'statut_compte' => $formData['statut_compte']
                ];

                $userIdOrError = $this->userModel->create($userDataForModel);

                if (is_numeric($userIdOrError)) {
                    $userId = $userIdOrError;
                    // Assign roles (global context for now, or use active academic year from Auth)
                    $activeYearId = null; // Auth::getActiveAcademicYearId(); // Or null for global roles by default for new users
                    if (Auth::can('assign_roles_to_user')) { // Check permission before assigning roles
                         $this->userRoleModel->syncUserRoles($userId, $formData['roles'], $activeYearId);
                    }
                    $_SESSION['flash_message'] = ['text' => I18n::translate('users_add_success_msg', 'User added successfully.'), 'type' => 'success'];
                    redirectTo('/admin/users');
                } else {
                    $errorMessage = ($userIdOrError === 'duplicate_email') ?
                                    I18n::translate('validation.unique', ['field' => 'Email', 'value' => $formData['email']]) :
                                    I18n::translate('users_add_error_msg', 'Error adding user.');
                    $_SESSION['flash_message'] = ['text' => $errorMessage, 'type' => 'danger'];
                    if ($userIdOrError === 'duplicate_email') $formData['errors']['email'] = $errorMessage;
                }
            } else {
                 $_SESSION['flash_message'] = ['text' => I18n::translate('validation.form_errors_detail'), 'type' => 'danger'];
            }
        }

        $viewData = [
            'data' => $formData,
            'title' => __('users_title_add', 'Add New User'),
            'mode' => 'add',
            'allRoles' => $this->roleModel->getAll(),
            'availableLanguages' => class_exists('App\Core\I18n') ? I18n::getAvailableLanguages() : [$defaultLang],
            'accountStatuses' => $this->accountStatuses
        ];
        $this->view('admin/users/form', $viewData, 'admin_default');
    }

    public function edit($id) {
        Auth::requirePermission('edit_user');
        $id = (int)$id;
        $user = $this->userModel->getById($id);
        if (!$user) {
            $_SESSION['flash_message'] = ['text' => __('users_not_found_msg', 'User not found.'), 'type' => 'warning'];
            redirectTo('/admin/users');
        }

        // Fetch current roles for the user in the relevant context (e.g., global or current year)
        // For user editing, typically global roles or roles in the currently active academic year might be relevant.
        // Let's assume we manage global roles primarily here, or roles in the current active year.
        $activeYearId = null; // Auth::getActiveAcademicYearId(); // Or null for global context
        $currentRolesIds = array_map(function($r){ return $r->id; }, $this->userRoleModel->getRolesForUser($id, $activeYearId, true));

        $formData = (array)$user;
        $formData['roles'] = $currentRolesIds;
        $formData['errors'] = [];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            $formDataUpdate = [ // Use a different name to avoid confusion with initial $formData
                'id' => $id,
                'nom' => trim($_POST['nom'] ?? $user->nom),
                'email' => trim($_POST['email'] ?? $user->email),
                'mot_de_passe' => $_POST['mot_de_passe'] ?? '',
                'mot_de_passe_confirm' => $_POST['mot_de_passe_confirm'] ?? '',
                'langue_preferee' => trim($_POST['langue_preferee'] ?? $user->langue_preferee),
                'statut_compte' => trim($_POST['statut_compte'] ?? $user->statut_compte),
                'roles' => $_POST['roles'] ?? [],
                'errors' => []
            ];

            $formDataUpdate['errors'] = $this->validateUserData($formDataUpdate, true, $id);

            if (empty($formDataUpdate['errors'])) {
                $userDataForModel = [
                    'nom' => $formDataUpdate['nom'],
                    'email' => $formDataUpdate['email'],
                    'langue_preferee' => $formDataUpdate['langue_preferee'],
                    'statut_compte' => $formDataUpdate['statut_compte']
                ];

                $passwordChanged = false;
                if (!empty($formDataUpdate['mot_de_passe'])) {
                    if ($this->userModel->updatePassword($id, password_hash($formDataUpdate['mot_de_passe'], PASSWORD_DEFAULT))) {
                        $passwordChanged = true;
                    } else {
                        // Password update failed, but other fields might still be updated. Add specific error?
                        $_SESSION['flash_message'] = ['text' => __('users_password_update_error_msg', 'Error updating password, but other details might be saved.'), 'type' => 'warning'];
                    }
                }

                $updateResult = $this->userModel->update($id, $userDataForModel);

                if ($updateResult || $passwordChanged) {
                    if (Auth::can('assign_roles_to_user')) {
                        $this->userRoleModel->syncUserRoles($id, $formDataUpdate['roles'], $activeYearId);
                    }
                     // Set success message only if no prior warning about password
                    if (!isset($_SESSION['flash_message']) || $_SESSION['flash_message']['type'] !== 'warning') {
                        $_SESSION['flash_message'] = ['text' => __('users_edit_success_msg', 'User updated successfully.'), 'type' => 'success'];
                    }
                    redirectTo('/admin/users');
                } elseif (is_string($updateResult)) {
                     $_SESSION['flash_message'] = ['text' => $updateResult, 'type' => 'danger']; // e.g. 'duplicate_email'
                     if ($updateResult === 'duplicate_email') $formDataUpdate['errors']['email'] = $updateResult;

                } else { // Generic update error if not string and not true
                    $_SESSION['flash_message'] = ['text' => __('users_edit_error_msg', 'Error updating user or no changes made.'), 'type' => 'danger'];
                }
            }
            // If errors, merge back into $formData to repopulate view
            $formData = $formDataUpdate; // Use the data that failed validation to repopulate
        }

        $viewData = [
            'data' => $formData,
            'title' => __('users_title_edit', 'Edit User') . ': ' . htmlspecialchars($user->nom),
            'mode' => 'edit',
            'allRoles' => $this->roleModel->getAll(),
            'availableLanguages' => class_exists('App\Core\I18n') ? I18n::getAvailableLanguages() : [DEFAULT_LANG],
            'accountStatuses' => $this->accountStatuses,
            'userId' => $id
        ];
        $this->view('admin/users/form', $viewData, 'admin_default');
    }

    public function update_status($id) {
        Auth::requirePermission('edit_user');
        $id = (int)$id;
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $newStatus = trim($_POST['statut_compte'] ?? '');
            // Prevent self-status update for safety or specific logic
            if ($id === Auth::getCurrentUserId()) {
                 $_SESSION['flash_message'] = ['text' => __('users_status_error_self_msg', 'You cannot change your own account status directly.'), 'type' => 'danger'];
                 redirectTo('/admin/users/edit/' . $id); // Redirect to edit page of self
            }

            if (in_array($newStatus, $this->accountStatuses)) {
                if ($this->userModel->updateStatus($id, $newStatus)) {
                    $_SESSION['flash_message'] = ['text' => __('users_status_update_success_msg', 'User status updated successfully.'), 'type' => 'success'];
                } else {
                    $_SESSION['flash_message'] = ['text' => __('users_status_update_error_msg', 'Error updating user status.'), 'type' => 'danger'];
                }
            } else {
                $_SESSION['flash_message'] = ['text' => I18n::translate('validation.invalid_selection', ['field' => __('users_form_label_statut_compte', 'Account Status')]), 'type' => 'danger'];
            }
        }
        redirectTo('/admin/users');
    }

    public function delete($id) {
        Auth::requirePermission('delete_user');
        $id = (int)$id;
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $userToDelete = $this->userModel->getById($id);
            if (!$userToDelete) {
                 $_SESSION['flash_message'] = ['text' => __('users_not_found_msg'), 'type' => 'danger'];
            } elseif ($userToDelete->id === Auth::getCurrentUserId()) {
                 $_SESSION['flash_message'] = ['text' => __('users_delete_error_self_msg', 'You cannot delete your own account.'), 'type' => 'danger'];
            } else {
                // Check if user is SuperAdmin before deleting (by role name)
                $userRoles = $this->userRoleModel->getRoleNamesForUser($id, null, true); // Check global and current year roles
                if (in_array('SuperAdmin', $userRoles)) {
                     $_SESSION['flash_message'] = ['text' => __('users_delete_error_superadmin_msg', 'The SuperAdmin user account cannot be deleted.'), 'type' => 'danger'];
                } else {
                    $deleteResult = $this->userModel->delete($id);
                    if ($deleteResult && !is_string($deleteResult)) {
                        $_SESSION['flash_message'] = ['text' => __('users_delete_success_msg', 'User deleted successfully.'), 'type' => 'success'];
                    } else {
                        $errorMessage = ($deleteResult === 'error_linked') ?
                                        I18n::translate('users_delete_error_linked_msg', 'User cannot be deleted as they are linked to other critical records.') :
                                        I18n::translate('users_delete_error_msg', 'Error deleting user.');
                        $_SESSION['flash_message'] = ['text' => $errorMessage, 'type' => 'danger'];
                    }
                }
            }
        } else {
            $_SESSION['flash_message'] = ['text' => I18n::translate('global.invalid_request_method'), 'type' => 'warning'];
        }
        redirectTo('/admin/users');
    }

    public function reactivate($userId) {
        Auth::requirePermission('reactivate_user_account');
        $userId = (int)$userId;

        $user = $this->userModel->getById($userId);
        if (!$user) {
            $_SESSION['flash_message'] = ['text' => __('users_not_found_msg', 'User not found.'), 'type' => 'warning'];
            redirectTo('/admin/users');
        }

        $activeAcademicYear = $this->anneeAcademiqueModel->getActiveYear();
        if (!$activeAcademicYear) {
            $_SESSION['flash_message'] = ['text' => __('users_reactivate_error_no_active_year', 'Cannot reactivate user: No academic year is currently active.'), 'type' => 'danger'];
            redirectTo('/admin/users');
        }
        $activeAcademicYearId = $activeAcademicYear->id;

        $reactivationSuccess = true;
        $actionsTaken = []; // To track what was done for the success message

        // 1. Set user's account status to 'actif' if it's not already
        if ($user->statut_compte !== 'actif') {
            if ($this->userModel->updateStatus($userId, 'actif')) {
                $actionsTaken[] = __('users_reactivate_status_set_active');
            } else {
                $reactivationSuccess = false;
                $_SESSION['flash_message'] = ['text' => __('users_status_update_error_msg', 'Error updating user status for reactivation.'), 'type' => 'danger'];
                // Decide if to bail out or try assigning role anyway
            }
        }

        // 2. Ensure user has an appropriate role for the CURRENT ACTIVE academic year.
        $userRolesInCurrentYearContext = $this->userRoleModel->getRolesForUser($userId, $activeAcademicYearId, true); // true to include global roles

        $hasSpecificRoleForCurrentYear = false;
        if (!empty($userRolesInCurrentYearContext)) {
            foreach ($userRolesInCurrentYearContext as $r) {
                if ($r->annee_academique_id == $activeAcademicYearId) {
                    $hasSpecificRoleForCurrentYear = true;
                    break;
                }
            }
        }

        $needsRoleForCurrentYear = !$hasSpecificRoleForCurrentYear;
        $assignedNewRoleName = null;

        if ($needsRoleForCurrentYear && $reactivationSuccess) { // Only try to assign if status update was okay (or not needed)
            // Strategy: Try to assign one of their existing global roles to the current year.
            // If no global roles, or if that fails, assign a system default 'Etudiant' role.
            $globalRoles = $this->userRoleModel->getRolesForUser($userId, null, false); // false: get ONLY global roles

            if (!empty($globalRoles)) {
                $primaryGlobalRole = $this->roleModel->getById($globalRoles[0]->id); // Fetch full role object
                if ($primaryGlobalRole) {
                    if ($this->userRoleModel->assignRoleToUser($userId, $primaryGlobalRole->id, $activeAcademicYearId)) {
                        $actionsTaken[] = __('users_reactivate_role_assigned', ['role' => $primaryGlobalRole->nom, 'year' => $activeAcademicYear->libelle]);
                        $assignedNewRoleName = $primaryGlobalRole->nom;
                        $needsRoleForCurrentYear = false; // Role successfully assigned
                    } else {
                        // Failed to assign their primary global role for current year
                        // Log this, but might fallback to default role assignment
                        error_log("Failed to assign existing global role {$primaryGlobalRole->nom} to user {$userId} for year {$activeAcademicYearId}");
                    }
                }
            }

            // If still needs a role (no global roles, or failed to assign one from global)
            if ($needsRoleForCurrentYear) {
                $defaultReactivationRoleName = 'Etudiant'; // Fallback default role
                $defaultRole = $this->roleModel->getByName($defaultReactivationRoleName);
                if ($defaultRole) {
                    if ($this->userRoleModel->assignRoleToUser($userId, $defaultRole->id, $activeAcademicYearId)) {
                        $actionsTaken[] = __('users_reactivate_role_assigned', ['role' => $defaultRole->nom, 'year' => $activeAcademicYear->libelle]);
                        $assignedNewRoleName = $defaultRole->nom;
                        $needsRoleForCurrentYear = false; // Role successfully assigned
                    } else {
                        $reactivationSuccess = false;
                        if (!isset($_SESSION['flash_message'])) { // Avoid overwriting previous error
                            $_SESSION['flash_message'] = ['text' => __('users_reactivate_error_role_assign', 'Failed to assign a role for the current academic year.'), 'type' => 'danger'];
                        }
                    }
                } else {
                    // Default reactivation role 'Etudiant' not found - system config issue
                    $reactivationSuccess = false;
                    if (!isset($_SESSION['flash_message'])) {
                         $_SESSION['flash_message'] = ['text' => __('users_reactivate_error_default_role_missing', ['role' => $defaultReactivationRoleName]), 'type' => 'warning'];
                    }
                }
            }
        }

        // Final success/error messaging
        if ($reactivationSuccess && !empty($actionsTaken)) {
            $successDetails = implode(". ", $actionsTaken);
            $_SESSION['flash_message'] = ['text' => __('users_reactivate_success_msg_details', ['name' => htmlspecialchars($user->nom), 'year' => htmlspecialchars($activeAcademicYear->libelle), 'details' => $successDetails ]), 'type' => 'success'];
        } elseif ($reactivationSuccess && empty($actionsTaken) && $user->statut_compte === 'actif' && !$needsRoleForCurrentYear) {
            // User was already active and had a role for the year, no action taken
             $_SESSION['flash_message'] = ['text' => __('users_reactivate_no_action_needed', ['name' => htmlspecialchars($user->nom), 'year' => htmlspecialchars($activeAcademicYear->libelle)]), 'type' => 'info'];
        } elseif (!isset($_SESSION['flash_message'])) {
            // Generic error if no specific one was set but reactivationSuccess is false or no actions taken
            $_SESSION['flash_message'] = ['text' => __('users_reactivate_error_generic', 'An error occurred during user reactivation or no action was performed.'), 'type' => 'danger'];
        }

        redirectTo('/admin/users');
    }
}
?>
