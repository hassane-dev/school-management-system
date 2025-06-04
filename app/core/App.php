<?php

// Autoload AuthSession if not already handled by a global autoloader
if (!class_exists('AuthSession') && file_exists(__DIR__ . '/AuthSession.php')) {
    require_once __DIR__ . '/AuthSession.php';
}
// Autoload helpers if redirectTo is needed and not globally available
if (!function_exists('redirectTo') && file_exists(__DIR__ . '/helpers.php')) {
    // Note: helpers.php is already included by autoload.php, but this is a safeguard
    // if App.php were ever used in a context where autoload.php hasn't run.
    // require_once __DIR__ . '/helpers.php';
}


class App {
    protected $controllerName = 'HomeController'; // Default controller class name (string)
    protected $methodName = 'index';          // Default method name (string)
    protected $params = [];
    protected $controllerInstance;

    public function __construct() {
        $url = $this->parseUrl();

        // Determine Controller
        // This logic needs to be robust enough for controllers in subdirectories e.g. admin/UsersController
        // For now, let's assume a simple structure or that admin controllers are uniquely named.
        // Example: URL 'admin/users/edit/1' -> $url[0] = 'admin', $url[1] = 'users'
        // A more advanced router would handle this better.
        // For this iteration, we'll assume controller names are unique or prefixed.
        // E.g. AdminUsersController for admin/users.

        $controllerCandidate = 'HomeController'; // Default

        if (isset($url[0])) {
            $potentialControllerFile = '../app/controllers/' . ucfirst($url[0]) . 'Controller.php';
            if (file_exists($potentialControllerFile)) {
                $this->controllerName = ucfirst($url[0]) . 'Controller';
                unset($url[0]);
            } else {
                // Basic check for admin controllers in an 'admin' subdirectory
                // e.g. /admin/dashboard -> AdminDashboardController.php
                // or /admin/users -> UsersController.php inside 'admin' folder (more complex)
                // The current ACL check relies on namespace 'App\Controllers\Admin\' or class name.
                // For simplicity, let's assume admin controllers might be prefixed if not using namespaces yet for routing.
                // e.g. AdminUsersController.php for admin/users.
                // If using namespaces for routing, the file path logic would need an update.
                // The prompt's ACL check uses `strpos(get_class($this->controller_obj), 'App\Controllers\Admin\')`
                // which means namespaces must be used for controllers if that check is to work as written.
                // However, the current file loading does not use namespaces.
                // I will adjust the ACL admin check to be based on controller name prefix "Admin" for now.
            }
        }

        // If a namespaced controller was intended, the file path needs to reflect that.
        // For now, stick to simple controller loading.
        $controllerFileToLoad = '../app/controllers/' . $this->controllerName . '.php';
        if (!file_exists($controllerFileToLoad)) {
            // Fallback or error for controller not found
            // For now, using a generic error, could be a dedicated 404 controller/method
            AuthSession::setFlash(__('global.access_denied', 'Controller not found.'), 'danger');
            if (function_exists('redirectTo')) redirectTo('/auth/login'); else die('Controller not found and redirectTo is unavailable.');
        }

        require_once $controllerFileToLoad;
        if (!class_exists($this->controllerName)) {
             AuthSession::setFlash(__('global.access_denied', 'Controller class not found.'), 'danger');
             if (function_exists('redirectTo')) redirectTo('/auth/login'); else die('Controller class not found and redirectTo is unavailable.');
        }
        $this->controllerInstance = new $this->controllerName();

        // Determine Method
        if (isset($url[1])) {
            if (method_exists($this->controllerInstance, $url[1])) {
                $this->methodName = $url[1];
                unset($url[1]);
            } else {
                // Method not found error
                AuthSession::setFlash(__('global.access_denied', 'Action not found.'), 'danger');
                if (function_exists('redirectTo')) redirectTo('/dashboard'); else die('Action not found and redirectTo is unavailable.');
            }
        }

        // --- ACL Check ---
        // Normalize controller name for route matching (e.g., "HomeController" -> "home")
        $controllerShortName = strtolower(str_replace('Controller', '', $this->controllerName));
        $currentRoute = $controllerShortName . '/' . $this->methodName;

        // Define public routes (controller/method in lowercase)
        $publicRoutes = [
            'auth/login',
            'auth/register',
            // Add 'home/index' or your default public landing page if you have one.
            // If your default controller is 'HomeController' and method 'index':
            'home/index',
            'parametres/index' // Assuming settings page is public for now for testing, adjust as needed
        ];

        // Handle default route (e.g., if URL is empty, it maps to 'home/index')
        if (empty($url) && $controllerShortName === 'home' && $this->methodName === 'index') {
             // This is already covered if 'home/index' is in publicRoutes
        }


        $isPublic = false;
        foreach ($publicRoutes as $publicRoute) {
            if ($currentRoute === strtolower($publicRoute)) {
                $isPublic = true;
                break;
            }
        }

        if (!$isPublic) {
            if (!AuthSession::isLoggedIn()) {
                AuthSession::setFlash(__('global.login_required', 'Please log in to access this page.'), 'warning');
                redirectTo('/auth/login'); // Path relative to BASE_URL
            }

            // Example: Check for "admin" area access based on controller name prefix
            // This is a simplified check. A namespaced approach `App\Controllers\Admin\` is more robust.
            if (strpos($this->controllerName, 'Admin') === 0 || $controllerShortName === 'admin') {
                 // (e.g. AdminUsersController, or if route was admin/users -> controller 'admin', method 'users')
                if (!AuthSession::hasRole(['admin', 'super_admin'])) {
                    AuthSession::setFlash(__('global.access_denied_admin_area', 'Access Denied. You must be an administrator to access this area.'), 'danger');
                    redirectTo('/dashboard'); // Or a generic access denied page
                }
                // Specific permission for accessing any admin functionality
                // if (!AuthSession::hasPermission('access_admin_area')) {
                //    AuthSession::setFlash(__('global.access_denied'), 'danger');
                //    redirectTo('/dashboard');
                // }
            }

            // Example: More granular permission check for a specific controller/method
            // This should ideally be handled within the controller method itself for clarity,
            // or via a more sophisticated routing/middleware system.
            if ($controllerShortName === 'parametres' && $this->methodName !== 'index') { // e.g. update methods
               if (!AuthSession::hasPermission('manage_settings')) {
                   AuthSession::setFlash(__('global.access_denied', "You don't have permission to manage settings."), 'danger');
                   redirectTo('/parametres/index'); // or /dashboard
               }
            }
             if ($controllerShortName === 'adminusers') { // Assuming AdminUsersController maps to 'adminusers'
                if ($this->methodName === 'create' || $this->methodName === 'store' || $this->methodName === 'edit' || $this->methodName === 'update' || $this->methodName === 'delete') {
                    if (!AuthSession::hasPermission('manage_users')) {
                        AuthSession::setFlash(__('global.access_denied', "You don't have permission to manage users."), 'danger');
                        redirectTo('/admin/users/index'); // or /dashboard
                    }
                }
            }


        }
        // --- End ACL Check ---

        // Get remaining params
        $this->params = $url ? array_values($url) : [];

        // Call the method on the controller instance
        call_user_func_array([$this->controllerInstance, $this->methodName], $this->params);
    }

    public function parseUrl() {
        if (isset($_GET['url'])) {
            return explode('/', filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL));
        }
        return [];
    }
}
?>
