<?php
namespace App\Core;

// Ensure APP_ROOT is available (defined in config.php)
// No direct dependencies for Router itself, but controllers it loads will need it.

class Router {
    protected $currentControllerName = 'HomeController'; // Default controller class name (short name)
    protected $currentMethodName = 'index';
    protected $params = [];

    protected $currentControllerInstance;

    public function __construct() {
        $url = $this->getUrl();

        // Look for controller in first part of URL
        if (isset($url[0]) && !empty($url[0])) {
            $controllerCandidate = ucwords(strtolower($url[0])) . 'Controller';
            $controllerFile = APP_ROOT . '/Controllers/' . $controllerCandidate . '.php';

            // Check for controllers in Admin subdirectory
            // e.g., if url is admin/users, $url[0] = 'admin', $url[1] = 'users'
            if (strtolower($url[0]) === 'admin' && isset($url[1]) && !empty($url[1])) {
                $adminControllerCandidate = ucwords(strtolower($url[1])) . 'Controller';
                $adminControllerFile = APP_ROOT . '/Controllers/Admin/' . $adminControllerCandidate . '.php';
                if (file_exists($adminControllerFile)) {
                    $this->currentControllerName = "App\\Controllers\\Admin\\" . $adminControllerCandidate;
                    unset($url[0]); // Consumed 'admin'
                    unset($url[1]); // Consumed controller name
                } elseif (file_exists($controllerFile)) {
                    // Fallback: maybe there's an AdminController at top level? Less likely with this structure.
                    // Or if the admin part is not a directory but part of controller name e.g. AdminUsersController
                    // This part needs a clear convention. For now, admin/ControllerName is the primary check.
                     $this->currentControllerName = "App\\Controllers\\" . $controllerCandidate;
                     unset($url[0]);
                }
            } elseif (file_exists($controllerFile)) {
                 $this->currentControllerName = "App\\Controllers\\" . $controllerCandidate;
                 unset($url[0]);
            } else {
                 // Default to HomeController if specified controller not found, or handle 404
                 $this->currentControllerName = "App\\Controllers\\HomeController";
                 // Optionally, log that the requested controller $url[0] was not found
                 // and we are defaulting or preparing for a 404.
                 // For now, if $url[0] is set but controller not found, it will try to load HomeController
                 // and if the method from $url[1] is not in HomeController, it will be a method not found.
                 // A dedicated 404 controller would be better.
            }
        } else {
            // No controller specified in URL, use default HomeController
            $this->currentControllerName = "App\\Controllers\\HomeController";
        }


        // Instantiate controller
        if (class_exists($this->currentControllerName)) {
            $this->currentControllerInstance = new $this->currentControllerName();
        } else {
            // Handle controller not found error
            error_log("Router Error: Controller class " . $this->currentControllerName . " not found.");
            // TODO: Implement a proper 404 handler
            die("Error: Controller class " . $this->currentControllerName . " not found. Check class name and namespace.");
        }

        // Look for method in the next part of URL (if controller was resolved from $url[0] or $url[1] for admin)
        $methodUrlIndex = isset($url[0]) && strtolower($url[0]) === 'admin' ? 1 : 0;
        // This logic is a bit complex due to admin path. Simpler if getUrl shifts array.
        // Let's re-evaluate using the current state of $url after controller processing.

        $methodCandidate = $this->currentMethodName; // Default to 'index'
        if (!empty($url) && isset(current($url))) { // Check if there's a next segment for method
            $potentialMethod = current($url); // Use current() as $url might have been modified
             if (method_exists($this->currentControllerInstance, $potentialMethod)) {
                $this->currentMethodName = $potentialMethod;
                unset($url[key($url)]); // Remove the method part from $url
            } else {
                // Method not found in specified controller.
                // Log this, and potentially fall back to index or show 404.
                // For now, if method specified but not found, it will error later in call_user_func_array
                // unless we explicitly handle it.
                // It's often better to let it proceed to call_user_func_array and have it fail there if method truly doesn't exist.
                // Or, explicitly check and redirect to a 404 page or method.
                error_log("Router Error: Method " . $potentialMethod . " not found in controller " . $this->currentControllerName);
                // For now, let it try to call. A robust app would have a 404 here.
            }
        }


        // Get params - remaining parts of URL
        $this->params = $url ? array_values($url) : [];

        // Call the controller method with params
        if (method_exists($this->currentControllerInstance, $this->currentMethodName)) {
            call_user_func_array([$this->currentControllerInstance, $this->currentMethodName], $this->params);
        } else {
            error_log("Router Error: Method " . $this->currentMethodName . " does not exist in controller " . get_class($this->currentControllerInstance));
            // TODO: Implement a proper 404 handler for method not found
            die("Error: Method " . $this->currentMethodName . " not found in controller " . get_class($this->currentControllerInstance) . ".");
        }
    }

    protected function getUrl() {
        if (isset($_GET['url'])) {
            $url = rtrim($_GET['url'], '/');
            $url = filter_var($url, FILTER_SANITIZE_URL);
            $url = explode('/', $url);
            return $url;
        }
        return [];
    }
}
?>
