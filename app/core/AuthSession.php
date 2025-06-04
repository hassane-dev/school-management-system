<?php

class AuthSession {

    // Ensure session is started (idempotent)
    private static function ensureSessionStarted() {
        if (session_status() == PHP_SESSION_NONE) {
            // This should ideally be called only once at the very beginning,
            // like in public/index.php. Calling it here is a fallback.
            session_start();
        }
    }

    /**
     * Checks if a user is currently logged in.
     * @return bool True if logged in, false otherwise.
     */
    public static function isLoggedIn() {
        self::ensureSessionStarted();
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Gets the current logged-in user's ID.
     * @return int|null User ID if logged in, null otherwise.
     */
    public static function userId() {
        self::ensureSessionStarted();
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Gets all session data for the current logged-in user.
     * @return array|null User session data if logged in, null otherwise.
     *                     This might include user_id, user_nom, user_email, role_id, role_nom, lang.
     */
    public static function user() {
        self::ensureSessionStarted();
        if (!self::isLoggedIn()) {
            return null;
        }
        // Return relevant user data stored in session
        // 'role_id' and 'role_nom' are deprecated as single values.
        // Roles are now in 'user_roles' (array of objects) and 'user_role_names' (array of strings).
        return [
            'id' => $_SESSION['user_id'],
            'nom' => $_SESSION['user_nom'] ?? null,
            'email' => $_SESSION['user_email'] ?? null,
            'roles' => $_SESSION['user_roles'] ?? [], // Array of role objects {id, nom}
            'role_names' => $_SESSION['user_role_names'] ?? [], // Array of role names
            'lang' => $_SESSION['lang'] ?? null,
        ];
    }

    /**
     * Gets a specific piece of user data from the session.
     * @param string $key The key of the data to retrieve (e.g., 'user_nom', 'user_roles').
     * @return mixed|null The value if set, null otherwise.
     */
    public static function get($key) {
        self::ensureSessionStarted();
        // Special handling for deprecated keys if needed for backward compatibility during transition
        if ($key === 'role_id' || $key === 'role_nom') {
            // error_log("AuthSession::get - Accessing deprecated session key: $key. Use user()['roles'] or user()['role_names'].");
            // Optionally return primary role's id/name if such concept is maintained
            // For now, return null to enforce new structure.
            return null;
        }
        return $_SESSION[$key] ?? null;
    }


    /**
     * Checks if the current logged-in user has a specific role or any of an array of roles.
     * @param string|array $roleName Single role name (string) or an array of role names.
     * @return bool True if the user has at least one of the specified roles, false otherwise.
     */
    public static function hasRole($roleNameOrNames) {
        self::ensureSessionStarted();
        if (!self::isLoggedIn() || !isset($_SESSION['user_role_names']) || empty($_SESSION['user_role_names'])) {
            return false;
        }

        $userRoleNames = $_SESSION['user_role_names']; // This is an array of role names

        if (is_array($roleNameOrNames)) {
            // Check if any of user's roles intersect with the required roles
            return !empty(array_intersect($roleNameOrNames, $userRoleNames));
        }
        // Check if the single required role name is in the user's list of roles
        return in_array($roleNameOrNames, $userRoleNames);
    }

    /**
     * Checks if the current logged-in user has a specific permission through any of their roles.
     * Requires RolePermission_model to be available and DB connection active.
     * @param string $permissionName The name of the permission (e.g., 'manage_users').
     * @return bool True if the user has the permission through any of their roles, false otherwise.
     */
    public static function hasPermission($permissionName) {
        self::ensureSessionStarted();
        if (!self::isLoggedIn() || !isset($_SESSION['user_roles']) || empty($_SESSION['user_roles'])) {
            // error_log("AuthSession::hasPermission - User not logged in or user_roles not set/empty in session.");
            return false;
        }

        $userRoles = $_SESSION['user_roles']; // Array of role objects {id, nom}

        // Static variable to cache the model instance
        static $rolePermissionModel = null;

        if ($rolePermissionModel === null) {
            // Attempt to load the model.
            // This assumes 'RolePermission_model.php' is in the models directory and
            // 'Model.php' (base) is in the core directory, and they can be loaded.
            // Direct require might be needed if autoloader isn't robust enough here or not yet run.
            if (!class_exists('RolePermission_model')) {
                 // Try to load Model base class first if not already loaded by autoloader
                if (!class_exists('Model') && file_exists(__DIR__ . '/Model.php')) {
                    require_once __DIR__ . '/Model.php'; // Base Model for PDO
                }
                $modelPath = __DIR__ . '/../models/RolePermission_model.php';
                if (file_exists($modelPath)) {
                    require_once $modelPath;
                } else {
                    // error_log("AuthSession::hasPermission - RolePermission_model file not found at {$modelPath}.");
                    return false;
                }
            }

            if (!class_exists('RolePermission_model')) {
                // error_log("AuthSession::hasPermission - RolePermission_model class does not exist even after attempting load.");
                return false;
            }

            try {
                // Check if $rolePermissionModel is already an instance of RolePermission_model
                // This check is actually redundant due to the outer `if ($rolePermissionModel === null)`
                // but kept for clarity during potential refactoring.
                if (!($rolePermissionModel instanceof RolePermission_model)) {
                     $rolePermissionModel = new RolePermission_model();
                }
            } catch (PDOException $e) {
                // error_log("AuthSession::hasPermission - DB Connection failed for RolePermission_model: " . $e->getMessage());
                return false;
            } catch (Exception $e) {
                // error_log("AuthSession::hasPermission - Error instantiating RolePermission_model: " . $e->getMessage());
                return false;
            }
        }

        // Iterate through each of the user's roles and check for the permission
        foreach ($userRoles as $role) {
            if (isset($role->id)) { // Ensure role object has an id
                try {
                    if ($rolePermissionModel->hasPermission($role->id, $permissionName)) {
                        return true; // Permission found in one of the roles
                    }
                } catch (Exception $e) {
                    // error_log("AuthSession::hasPermission - Error during hasPermission check for role ID {$role->id}: " . $e->getMessage());
                    // Continue checking other roles
                }
            }
        }

        return false; // Permission not found in any of the user's roles
    }

    // Flash message methods (can also be part of this class for session-related utilities)
            return false;
        }
    }

    // Flash message methods (can also be part of this class for session-related utilities)
    public static function setFlash($message, $type = 'info') {
        self::ensureSessionStarted();
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }

    public static function displayFlash() {
        self::ensureSessionStarted();
        if (isset($_SESSION['flash_message'])) {
            $message = $_SESSION['flash_message'];
            $type = $_SESSION['flash_type'] ?? 'info';
            unset($_SESSION['flash_message']);
            unset($_SESSION['flash_type']);
            return '<div class="alert alert-' . htmlspecialchars($type) . ' alert-dismissible fade show" role="alert">' .
                   htmlspecialchars($message) .
                   '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' .
                   '</div>';
        }
        return '';
    }
}
?>
