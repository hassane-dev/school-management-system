<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;     // For ACL checks
use App\Core\I18n;    // For translations in flash messages
use App\Models\RoleModel;
use App\Models\PermissionModel;
use App\Models\RolePermissionModel;

class RolesController extends Controller {
    private $roleModel;
    private $permissionModel;
    private $rolePermissionModel;

    public function __construct() {
        // Protect entire controller. User must be able to 'view_roles' to access any method.
        // Individual methods can have more granular permission checks if needed.
        Auth::requirePermission('view_roles');

        $this->roleModel = $this->model('RoleModel');
        $this->permissionModel = $this->model('PermissionModel');
        $this->rolePermissionModel = $this->model('RolePermissionModel');
    }

    public function index() {
        $roles = $this->roleModel->getAll();
        $this->view('admin/roles/index', ['roles' => $roles, 'title' => __('roles_title_list', 'Roles List')], 'admin_default');
    }

    public function add() {
        Auth::requirePermission('create_role'); // Specific permission for adding

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            $data_form = [ // Renamed to avoid conflict with $data array for view
                'nom' => trim($_POST['nom'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                // 'est_systeme' is typically not set via UI for new roles, defaults to 0 in DB/Model
                'est_systeme' => isset($_POST['est_systeme']) ? 1 : 0, // If form allows it
                'errors' => []
            ];

            if (empty($data_form['nom'])) {
                $data_form['errors']['nom'] = I18n::translate('validation.required', ['field' => __('roles_form_label_name', 'Role Name')]);
            }
            if (!empty($data_form['nom']) && $this->roleModel->getByName($data_form['nom'])) {
                $data_form['errors']['nom'] = I18n::translate('roles_error_name_exists', 'This role name already exists.');
            }

            if (empty($data_form['errors'])) {
                $createData = ['nom' => $data_form['nom'], 'description' => $data_form['description'], 'est_systeme' => $data_form['est_systeme']];
                $newRoleIdOrError = $this->roleModel->create($createData);

                if ($newRoleIdOrError && !is_string($newRoleIdOrError)) { // Check if it's an ID and not an error string
                    $newRoleId = $newRoleIdOrError;
                     // Assign permissions if 'assign_permissions_to_role' is granted
                    if (Auth::can('assign_permissions_to_role') && isset($_POST['permissions']) && is_array($_POST['permissions'])) {
                        $this->rolePermissionModel->syncRolePermissions($newRoleId, $_POST['permissions']);
                    }
                    $_SESSION['flash_message'] = ['text' => I18n::translate('roles_add_success_msg', 'Role added successfully.'), 'type' => 'success'];
                    redirectTo('/admin/roles');
                } else {
                    $errorMessage = ($newRoleIdOrError === 'duplicate_nom') ?
                                    I18n::translate('roles_error_name_exists', 'This role name already exists.') :
                                    I18n::translate('roles_add_error_msg', 'Error adding role.');
                    $_SESSION['flash_message'] = ['text' => $errorMessage, 'type' => 'danger'];
                    // Add to errors to display inline if desired
                    if ($newRoleIdOrError === 'duplicate_nom') $data_form['errors']['nom'] = $errorMessage;
                }
            }
            // Fallthrough to show form with errors/data if any error occurred
            $viewData = [
                'data' => $data_form, // Contains submitted values and errors
                'all_permissions' => $this->permissionModel->getAll(), // For the form
                'role_permissions' => $_POST['permissions'] ?? [], // Repopulate selected permissions on error
                'title' => __('roles_title_add', 'Add New Role'),
                'mode' => 'add'
            ];
            $this->view('admin/roles/form', $viewData, 'admin_default');

        } else { // GET request
            $viewData = [
                'data' => ['nom' => '', 'description' => '', 'est_systeme' => 0, 'errors' => []],
                'all_permissions' => $this->permissionModel->getAll(),
                'role_permissions' => [], // No permissions for a new role yet
                'title' => __('roles_title_add', 'Add New Role'),
                'mode' => 'add'
            ];
            $this->view('admin/roles/form', $viewData, 'admin_default');
        }
    }

    public function edit($id) {
        Auth::requirePermission('edit_role');
        $id = (int)$id;
        $role = $this->roleModel->getById($id);

        if (!$role) {
            $_SESSION['flash_message'] = ['text' => I18n::translate('roles_not_found_msg', 'Role not found.'), 'type' => 'warning'];
            redirectTo('/admin/roles');
        }

        // Extra check: Only SuperAdmins (or those with special perm) can edit the 'SuperAdmin' role itself.
        if ($role->nom === 'SuperAdmin' && !Auth::can('access_superadmin_interface')) {
             Auth::requirePermission('access_superadmin_interface');
        }


        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            $data_form = [ // Renamed to avoid conflict
                'id' => $id,
                'nom' => trim($_POST['nom'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                // 'est_systeme' is not changed via this form. Fetch from existing role.
                'est_systeme' => $role->est_systeme,
                'errors' => []
            ];

            if (empty($data_form['nom'])) {
                $data_form['errors']['nom'] = I18n::translate('validation.required', ['field' => __('roles_form_label_name', 'Role Name')]);
            }
            $existingRoleByName = $this->roleModel->getByName($data_form['nom']);
            if ($existingRoleByName && $existingRoleByName->id != $id) {
                $data_form['errors']['nom'] = I18n::translate('roles_error_name_exists', 'This role name already exists.');
            }

            if (empty($data_form['errors'])) {
                // Data for model update (only fields that can be changed)
                $updateData = ['nom' => $data_form['nom'], 'description' => $data_form['description']];

                $updateResult = $this->roleModel->update($id, $updateData);

                if ($updateResult && !is_string($updateResult)) { // Not an error string
                    // Update permissions if user has permission to do so
                    if (Auth::can('assign_permissions_to_role')) {
                        $submittedPermissions = $_POST['permissions'] ?? [];
                        $this->rolePermissionModel->syncRolePermissions($id, $submittedPermissions);
                    }
                    $_SESSION['flash_message'] = ['text' => I18n::translate('roles_edit_success_msg', 'Role updated successfully.'), 'type' => 'success'];
                    redirectTo('/admin/roles');
                } else {
                    $errorMessage = $updateResult; // Could be 'duplicate_nom' or other error string from model
                    if ($updateResult === 'error_system_role_name_change') {
                         $errorMessage = I18n::translate('roles_error_system_name_change', 'Name of system roles cannot be changed.');
                    } elseif (is_string($updateResult) && strpos($updateResult, 'duplicate') !== false) {
                        $errorMessage = I18n::translate('roles_error_name_exists');
                    } elseif (!$updateResult) { // Generic false
                        $errorMessage = I18n::translate('roles_edit_error_msg', 'Error updating role or no changes made.');
                    }
                    $_SESSION['flash_message'] = ['text' => $errorMessage, 'type' => 'danger'];
                     if (is_string($updateResult) && strpos($updateResult, 'duplicate') !== false) $data_form['errors']['nom'] = $errorMessage;
                }
            }
            // Fallthrough to show form with errors/data
            $viewData = [
                'data' => $data_form, // Includes submitted values and errors
                'all_permissions' => $this->permissionModel->getAll(),
                'role_permissions' => $this->rolePermissionModel->getPermissionIdsForRole($id), // Get fresh permissions
                'title' => __('roles_title_edit', 'Edit Role') . ': ' . htmlspecialchars($role->nom),
                'mode' => 'edit',
                'role_id' => $id
            ];
             $this->view('admin/roles/form', $viewData, 'admin_default');

        } else { // GET request
            $viewData = [
                'data' => (array)$role + ['errors' => []], // Initial form data from DB
                'all_permissions' => $this->permissionModel->getAll(),
                'role_permissions' => $this->rolePermissionModel->getPermissionIdsForRole($id),
                'title' => __('roles_title_edit', 'Edit Role') . ': ' . htmlspecialchars($role->nom),
                'mode' => 'edit',
                'role_id' => $id
            ];
            $this->view('admin/roles/form', $viewData, 'admin_default');
        }
    }

    public function delete($id) {
        Auth::requirePermission('delete_role');
        $id = (int)$id;

        if ($_SERVER['REQUEST_METHOD'] == 'POST') { // Ensure deletion is via POST
            $role = $this->roleModel->getById($id);
            if (!$role) {
                $_SESSION['flash_message'] = ['text' => I18n::translate('roles_not_found_msg', 'Role not found.'), 'type' => 'warning'];
            } elseif ($role->est_systeme == 1) {
                $_SESSION['flash_message'] = ['text' => I18n::translate('roles_delete_error_system_role_msg', 'System roles cannot be deleted.'), 'type' => 'danger'];
            } else {
                // Optional: Check if role is assigned to any users before deleting
                // $userRoleModel = $this->model('UserRoleModel');
                // if ($userRoleModel->getUsersForRole($id)) { // Assuming getUsersForRole exists
                //    $_SESSION['flash_message'] = ['text' => I18n::translate('roles_delete_error_in_use_msg', 'Role cannot be deleted as it is assigned to users.'), 'type' => 'danger'];
                // } else {
                    $deleteResult = $this->roleModel->delete($id); // Model already prevents deleting system roles
                    if ($deleteResult && !is_string($deleteResult)) {
                        $_SESSION['flash_message'] = ['text' => I18n::translate('roles_delete_success_msg', 'Role deleted successfully.'), 'type' => 'success'];
                    } else {
                         $errorMessage = ($deleteResult === 'error_system_role') ? I18n::translate('roles_delete_error_system_role_msg') : I18n::translate('roles_delete_error_msg', 'Error deleting role.');
                        $_SESSION['flash_message'] = ['text' => $errorMessage, 'type' => 'danger'];
                    }
                // }
            }
        } else {
            $_SESSION['flash_message'] = ['text' => I18n::translate('global.invalid_request_method'), 'type' => 'danger'];
        }
        redirectTo('/admin/roles');
    }
}
?>
