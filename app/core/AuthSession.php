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
        return [
            'id' => $_SESSION['user_id'],
            'nom' => $_SESSION['user_nom'] ?? null,
            'email' => $_SESSION['user_email'] ?? null,
            'role_id' => $_SESSION['role_id'] ?? null,
            'role_nom' => $_SESSION['role_nom'] ?? null,
            'lang' => $_SESSION['lang'] ?? null,
        ];
    }

    /**
     * Gets a specific piece of user data from the session.
     * @param string $key The key of the data to retrieve (e.g., 'user_nom', 'role_id').
     * @return mixed|null The value if set, null otherwise.
     */
    public static function get($key) {
        self::ensureSessionStarted();
        return $_SESSION[$key] ?? null;
    }


    /**
     * Checks if the current logged-in user has a specific role.
     * @param string|array $roleName Role name or an array of role names to check against.
     * @return bool True if the user has the role, false otherwise.
     */
    public static function hasRole($roleName) {
        self::ensureSessionStarted();
        if (!self::isLoggedIn() || !isset($_SESSION['role_nom'])) {
            return false;
        }

        $userRoleName = $_SESSION['role_nom'];

        if (is_array($roleName)) {
            return in_array($userRoleName, $roleName);
        }
        return $userRoleName === $roleName;
    }

    /**
     * Checks if the current logged-in user's role has a specific permission.
     * Requires RolePermission_model to be available and DB connection active.
     * @param string $permissionName The name of the permission (e.g., 'manage_users').
     * @return bool True if the user has the permission, false otherwise.
     */
    public static function hasPermission($permissionName) {
        self::ensureSessionStarted();
        if (!self::isLoggedIn() || !isset($_SESSION['role_id'])) {
            // error_log("AuthSession::hasPermission - User not logged in or role_id not set in session.");
            return false;
        }

        $roleId = $_SESSION['role_id'];

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
                    require_once __DIR__ . '/Model.php';
                }
                if (file_exists(__DIR__ . '/../models/RolePermission_model.php')) {
                    require_once __DIR__ . '/../models/RolePermission_model.php';
                } else {
                    // error_log("AuthSession::hasPermission - RolePermission_model file not found.");
                    return false; // Model file not found
                }
            }

            if (!class_exists('RolePermission_model')) {
                // error_log("AuthSession::hasPermission - RolePermission_model class does not exist even after attempting load.");
                return false; // Class still doesn't exist
            }

            try {
                $rolePermissionModel = new RolePermission_model();
            } catch (PDOException $e) {
                // error_log("AuthSession::hasPermission - DB Connection failed for RolePermission_model: " . $e->getMessage());
                return false; // DB connection failed
            } catch (Exception $e) {
                // error_log("AuthSession::hasPermission - Error instantiating RolePermission_model: " . $e->getMessage());
                return false;
            }
        }

        try {
            return $rolePermissionModel->hasPermission($roleId, $permissionName);
        } catch (Exception $e) {
            // error_log("AuthSession::hasPermission - Error during hasPermission check: " . $e->getMessage());
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
