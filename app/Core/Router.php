<?php
namespace App\Core;

// Ensure APP_ROOT is available (defined in config.php)

class Router {
    protected $currentControllerName = 'HomeController'; // Default controller class name (short name, e.g. 'Home')
    protected $currentControllerNamespace = 'App\\Controllers\\'; // Default namespace
    protected $currentMethodName = 'index';
    protected $params = [];
    protected $currentControllerInstance;

    public function __construct() {
        $url = $this->getUrl();

        // Default controller path and namespace
        $controllerNameCandidate = $this->currentControllerName;
        $namespaceCandidate = $this->currentControllerNamespace;
        $controllerFileCandidate = APP_ROOT . '/Controllers/' . $controllerNameCandidate . '.php';

        // Check for admin routes first: e.g., /admin/controller/method/params
        if (isset($url[0]) && strtolower($url[0]) === 'admin') {
            if (isset($url[1]) && !empty($url[1])) {
                // This is an admin controller
                $controllerNameCandidate = ucwords(strtolower($url[1])) . 'Controller';
                $namespaceCandidate = 'App\\Controllers\\Admin\\';
                $controllerFileCandidate = APP_ROOT . '/Controllers/Admin/' . $controllerNameCandidate . '.php';
                unset($url[0]); // Consumed 'admin'
                unset($url[1]); // Consumed controller name part
            } else {
                // URL is just '/admin' or '/admin/' - map to a default admin controller/method if desired
                // For example, an AdminDashboardController
                $controllerNameCandidate = 'AdminDashboardController'; // Example default admin controller
                $namespaceCandidate = 'App\\Controllers\\Admin\\';
                $controllerFileCandidate = APP_ROOT . '/Controllers/Admin/' . $controllerNameCandidate . '.php';
                unset($url[0]); // Consumed 'admin'
                // Method will default to 'index'
            }
        } elseif (isset($url[0]) && !empty($url[0])) {
            // Non-admin route
            $controllerNameCandidate = ucwords(strtolower($url[0])) . 'Controller';
            $namespaceCandidate = 'App\\Controllers\\';
            $controllerFileCandidate = APP_ROOT . '/Controllers/' . $controllerNameCandidate . '.php';
            unset($url[0]); // Consumed controller name part
        }
        // If $url[0] was not set or empty, it defaults to HomeController

        // Check if the determined controller file exists
        if (file_exists($controllerFileCandidate)) {
            $this->currentControllerName = $namespaceCandidate . $controllerNameCandidate;
        } else {
            // Fallback to a generic 404 or default page if controller file not found
            // For now, if a specific controller was requested but not found, show error.
            // If no controller was in URL, it defaults to HomeController which should exist.
            if ((isset($_GET['url']) && !empty($_GET['url'])) && $controllerFileCandidate !== APP_ROOT . '/Controllers/HomeController.php') {
                 error_log("Router Error: Controller file " . $controllerFileCandidate . " not found.");
                 // TODO: Implement a proper 404 handler (e.g., load a NotFoundController)
                 die("Error 404: Page not found (controller file missing). Requested: " . htmlspecialchars($_GET['url']));
            }
            // Otherwise, it's already set to default HomeController, let it proceed.
            $this->currentControllerName = 'App\\Controllers\\HomeController'; // Ensure default
        }

        // Instantiate controller
        if (class_exists($this->currentControllerName)) {
            $this->currentControllerInstance = new $this->currentControllerName();
        } else {
            error_log("Router Error: Controller class " . $this->currentControllerName . " not found, though file might exist or defaulted.");
            die("Error 404: Page not found (controller class invalid). Class: " . $this->currentControllerName);
        }

        // Look for method in the next part of URL
        if (isset($url[0]) && !empty($url[0])) { // After controller parts are unset, $url[0] is method
            $methodCandidate = $url[0];
            if (method_exists($this->currentControllerInstance, $methodCandidate)) {
                $this->currentMethodName = $methodCandidate;
                unset($url[0]);
            } else {
                // Method not found in specified controller.
                error_log("Router Error: Method " . $methodCandidate . " not found in controller " . $this->currentControllerName);
                // TODO: Implement a proper 404 handler for method not found
                die("Error 404: Action not found in controller. Controller: " . $this->currentControllerName . ", Method: " . $methodCandidate);
            }
        }
        // If $url[0] was not set, method defaults to 'index'

        // Get params - remaining parts of URL
        $this->params = $url ? array_values($url) : [];

        // Call the controller method with params
        // Ensure method still exists (could be private, etc., though method_exists checks public)
        if (is_callable([$this->currentControllerInstance, $this->currentMethodName])) {
            call_user_func_array([$this->currentControllerInstance, $this->currentMethodName], $this->params);
        } else {
             error_log("Router Error: Method " . $this->currentMethodName . " is not callable in controller " . get_class($this->currentControllerInstance));
             die("Error 404: Action not callable. Controller: " . get_class($this->currentControllerInstance) . ", Method: " . $this->currentMethodName);
        }
    }

    protected function getUrl() {
        if (isset($_GET['url'])) {
            $url = rtrim($_GET['url'], '/');
            $url = filter_var($url, FILTER_SANITIZE_URL);
            // Allow explicitly 'admin' as a segment even if other segments might be filtered for typical controller/method names
            // This basic filter is okay for now. More complex routing might need more specific validation.
            // $url = preg_replace('/[^a-zA-Z0-9_=\/\-]/', '', $url); // Example of stricter filtering
            $url = explode('/', $url);
            return $url;
        }
        return [];
    }
}
?>
