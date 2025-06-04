<?php
// File: app/Core/Auth.php
namespace App\Core;

// These models will be instantiated within methods.
// Autoloader should handle them.
use App\Models\UtilisateurModel;
use App\Models\UserRoleModel;
use App\Models\RolePermissionModel;
use App\Models\AnneeAcademiqueModel;
use App\Models\PermissionModel;
use App\Core\I18n; // For translating messages

class Auth {
    private static $utilisateurModel = null;
    private static $userRoleModel = null;
    private static $rolePermissionModel = null;
    private static $anneeAcademiqueModel = null;
    private static $permissionModel = null;

    // Private static getters for models to ensure they are singletons within Auth class context
    private static function getUtilisateurModel(): UtilisateurModel {
        if (self::$utilisateurModel === null) self::$utilisateurModel = new UtilisateurModel();
        return self::$utilisateurModel;
    }
    private static function getUserRoleModel(): UserRoleModel {
        if (self::$userRoleModel === null) self::$userRoleModel = new UserRoleModel();
        return self::$userRoleModel;
    }
    private static function getRolePermissionModel(): RolePermissionModel {
        if (self::$rolePermissionModel === null) self::$rolePermissionModel = new RolePermissionModel();
        return self::$rolePermissionModel;
    }
    private static function getAnneeAcademiqueModel(): AnneeAcademiqueModel {
        if (self::$anneeAcademiqueModel === null) self::$anneeAcademiqueModel = new AnneeAcademiqueModel();
        return self::$anneeAcademiqueModel;
    }
    private static function getPermissionModel(): PermissionModel {
        if (self::$permissionModel === null) self::$permissionModel = new PermissionModel();
        return self::$permissionModel;
    }

    private static function startSession() {
        if (session_status() == PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
    }

    public static function getActiveAcademicYearId() {
        self::startSession();
        if (isset($_SESSION['active_annee_id'])) {
            return $_SESSION['active_annee_id'];
        }
        try {
            $activeYear = self::getAnneeAcademiqueModel()->getActiveYear();
            $activeYearId = $activeYear ? $activeYear->id : null;
            // Cache it in session for the duration of the user's session,
            // or until it's explicitly refreshed.
            $_SESSION['active_annee_id'] = $activeYearId;
            return $activeYearId;
        } catch (\Exception $e) {
            error_log("Auth::getActiveAcademicYearId - Error fetching active academic year: " . $e->getMessage());
            return null; // Or a sensible default/fallback
        }
    }

    // Call this if the active year changes in admin panel to update session cache
    public static function refreshActiveAcademicYearId() {
        self::startSession();
        unset($_SESSION['active_annee_id']); // Remove to force reload on next getActiveAcademicYearId call
        return self::getActiveAcademicYearId();
    }


    public static function isLoggedIn(): bool {
        self::startSession();
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    public static function getCurrentUserId(): ?int {
        self::startSession();
        return $_SESSION['user_id'] ?? null;
    }

    public static function getCurrentUser(): ?object {
        self::startSession();
        return isset($_SESSION['user_data']) ? (object) $_SESSION['user_data'] : null;
    }

    public static function getCurrentUserRoles(): array {
        self::startSession();
        // This session variable is populated by loadUserAuthContext
        return $_SESSION['user_current_context_roles'] ?? [];
    }

    public static function getCurrentUserRoleNames(): array {
        $roles = self::getCurrentUserRoles(); // Array of role objects
        return array_map(function($role) { return $role->nom; }, $roles);
    }

    /**
     * Live check of permission against DB for a given user and context.
     * SuperAdmin role implicitly has all permissions.
     */
    public static function check($permissionName, $userId = null, $anneeAcademiqueId = null): bool {
        self::startSession(); // Ensure session is available if needed for user ID
        $userIdToCheck = $userId ?? self::getCurrentUserId();
        if (!$userIdToCheck) return false;

        // Use the provided anneeAcademiqueId, or fetch the active one if not provided.
        // If $anneeAcademiqueId is explicitly passed as NULL, it means check for global roles only.
        $contextAnneeId = ($anneeAcademiqueId === null && func_num_args() < 3) ? self::getActiveAcademicYearId() : $anneeAcademiqueId;

        try {
            $userRoles = self::getUserRoleModel()->getRolesForUser($userIdToCheck, $contextAnneeId);
            if (empty($userRoles)) return false;

            foreach ($userRoles as $role) {
                if ($role->nom === 'SuperAdmin') return true;
                if (self::getRolePermissionModel()->roleHasPermission($role->id, $permissionName)) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            error_log("Auth::check - Error during permission check: " . $e->getMessage());
            return false;
        }
        return false;
    }

    /**
     * Optimized permission check using cached list in session.
     */
    public static function can($permissionName): bool {
        self::startSession();
        if (!self::isLoggedIn()) return false;

        // The permission list in session should be comprehensive due to loadUserAuthContext
        return isset($_SESSION['user_permissions_list']) &&
               is_array($_SESSION['user_permissions_list']) &&
               in_array($permissionName, $_SESSION['user_permissions_list']);
    }

    public static function requirePermission($permissionName) {
        if (!self::can($permissionName)) {
            self::startSession();
            // Use I18n for messages. Ensure I18n class is loaded.
            $message = class_exists('App\Core\I18n') ? I18n::translate('global.access_denied_permission', "Access Denied: You don't have the required permission.") : "Access Denied.";

            if (class_exists('App\Core\AuthSession') && method_exists('App\Core\AuthSession', 'setFlash')) {
                 // If old AuthSession still exists and is used for flash messages
                \App\Core\AuthSession::setFlash($message, 'danger');
            } else {
                $_SESSION['flash_message'] = ['text' => $message, 'type' => 'danger'];
            }

            $redirectTo = self::isLoggedIn() ? '/admin/dashboard' : '/auth/login';

            if (function_exists('redirectTo')) { // redirectTo helper from app/Core/helpers.php
                redirectTo($redirectTo);
            } else {
                header("Location: " . (defined('URL_ROOT') ? URL_ROOT : '') . $redirectTo);
                exit;
            }
        }
    }

    public static function loadUserAuthContext($userId): bool {
        self::startSession();
        try {
            $user = self::getUtilisateurModel()->getById($userId);
            if (!$user) {
                self::logout();
                return false;
            }

            $_SESSION['user_id'] = $user->id;
            // Store only necessary, non-sensitive user data. Avoid storing password hash.
            $userDataToStore = ['id' => $user->id, 'nom' => $user->nom, 'email' => $user->email, 'langue_preferee' => $user->langue_preferee];
            $_SESSION['user_data'] = $userDataToStore;

            $contextAnneeId = self::getActiveAcademicYearId();
            $_SESSION['active_annee_id'] = $contextAnneeId;

            // Fetch roles for the current context (active year + global roles)
            $userRoles = self::getUserRoleModel()->getRolesForUser($userId, $contextAnneeId, true); // true to include global roles
            $_SESSION['user_current_context_roles'] = $userRoles;

            $allPermissionNames = [];
            $isSuperAdmin = false;
            if (!empty($userRoles)) {
                foreach ($userRoles as $role) {
                    if ($role->nom === 'SuperAdmin') {
                        $isSuperAdmin = true;
                        break;
                    }
                }
            }

            if ($isSuperAdmin) {
                $allSystemPermissions = self::getPermissionModel()->getAll();
                foreach ($allSystemPermissions as $p) {
                    $allPermissionNames[$p->nom] = true; // Using keys for uniqueness
                }
            } else if (!empty($userRoles)) {
                foreach ($userRoles as $role) {
                    $permissionsForRole = self::getRolePermissionModel()->getPermissionsForRole($role->id);
                    foreach ($permissionsForRole as $perm) {
                        $allPermissionNames[$perm->nom] = true; // Using keys for uniqueness
                    }
                }
            }
            $_SESSION['user_permissions_list'] = array_keys($allPermissionNames); // Store unique permission names

            // Set current language from user preference
            if (isset($user->langue_preferee) && class_exists('App\Core\I18n')) {
                I18n::setCurrentLang($user->langue_preferee);
            }

            return true;
        } catch (\Exception $e) {
            error_log("Auth::loadUserAuthContext - Error: " . $e->getMessage());
            self::logout(); // Log out user on error during context load
            return false;
        }
    }

    public static function logout() {
        self::startSession();
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        @session_destroy(); // Suppress errors if session already destroyed or invalid
    }
}
?>
