<?php
namespace App\Core;

class Router {
    protected $currentControllerNamespace = 'App\\Controllers\\';
    protected $currentControllerName = 'HomeController'; // Default controller (short name)
    protected $currentMethodName = 'index';
    protected $params = [];
    protected $currentControllerInstance;

    public function __construct() {
        $url = $this->getUrl();

        $controllerNameCandidate = $this->currentControllerName; // e.g. HomeController
        $namespaceCandidate = $this->currentControllerNamespace; // e.g. App\Controllers\
        $controllerFileBaseDir = APP_ROOT . '/Controllers/';

        // Check if first URL segment indicates a subdirectory (namespace)
        if (isset($url[0]) && !empty($url[0])) {
            $potentialSubDir = ucwords(strtolower($url[0]));
            $subDirPath = $controllerFileBaseDir . $potentialSubDir;

            if (is_dir($subDirPath) && isset($url[1]) && !empty($url[1])) {
                // First segment is a directory, second is the controller
                $controllerNameCandidate = ucwords(strtolower($url[1])) . 'Controller';
                $namespaceCandidate = "App\\Controllers\\" . $potentialSubDir . "\\";
                $controllerFileCandidate = $subDirPath . '/' . $controllerNameCandidate . '.php';

                if (file_exists($controllerFileCandidate)) {
                    unset($url[0]); // Consumed subdir
                    unset($url[1]); // Consumed controller
                } else {
                    // Subdirectory controller not found, fallback to root controller with $url[0] as name
                    $controllerNameCandidate = ucwords(strtolower($url[0])) . 'Controller';
                    $namespaceCandidate = "App\\Controllers\\";
                    $controllerFileCandidate = $controllerFileBaseDir . $controllerNameCandidate . '.php';
                    if (file_exists($controllerFileCandidate)) {
                        unset($url[0]); // Consumed controller
                    } else {
                        // Controller not found even in root, use default HomeController
                        $controllerNameCandidate = 'HomeController';
                        $namespaceCandidate = "App\\Controllers\\";
                        $controllerFileCandidate = $controllerFileBaseDir . $controllerNameCandidate . '.php';
                    }
                }
            } else {
                // First segment is treated as a controller in the root Controllers directory
                $controllerNameCandidate = ucwords(strtolower($url[0])) . 'Controller';
                $namespaceCandidate = "App\\Controllers\\";
                $controllerFileCandidate = $controllerFileBaseDir . $controllerNameCandidate . '.php';
                if (file_exists($controllerFileCandidate)) {
                    unset($url[0]); // Consumed controller
                } else {
                    // Controller not found, use default HomeController
                    // No need to change candidates, already set to default.
                    // This also handles the case where $url[0] exists but is not a valid controller.
                }
            }
        }
        // If $url was empty, defaults (HomeController, App\Controllers\) are used.

        $this->currentControllerName = $namespaceCandidate . $controllerNameCandidate;

        if (!class_exists($this->currentControllerName)) {
             // If after all checks, the class doesn't exist (e.g. default HomeController.php is missing)
            error_log("Router Error: Controller class '{$this->currentControllerName}' not found. File: '{$controllerFileCandidate}' attempt.");
            // Fallback to a very basic error or a dedicated error controller if implemented
            if ($this->currentControllerName !== "App\\Controllers\\HomeController") { // If it wasn't already the default
                // Try to load HomeController as a last resort if a specific one failed
                $this->currentControllerName = "App\\Controllers\\HomeController";
                if (!class_exists($this->currentControllerName)) {
                     die("Error 404: Critical - Default controller '{$this->currentControllerName}' not found.");
                }
            } else { // Default HomeController itself not found
                 die("Error 404: Critical - Default controller '{$this->currentControllerName}' not found.");
            }
        }

        $this->currentControllerInstance = new $this->currentControllerName();

        // Method determination (from remaining $url segments)
        if (isset($url[0]) && !empty($url[0])) {
            $methodCandidate = $url[0];
            if (method_exists($this->currentControllerInstance, $methodCandidate)) {
                $this->currentMethodName = $methodCandidate;
                unset($url[0]);
            } else {
                error_log("Router Error: Method '{$methodCandidate}' not found in controller '" . get_class($this->currentControllerInstance) . "'. Defaulting to 'index'.");
                // Optionally, redirect to a 404 page for method not found
                // For now, it will try to call 'index' if currentMethodName remains 'index' and method is not found.
                // If index also doesn't exist, the final call_user_func_array will fail.
                // A better 404 for method:
                // die("Error 404: Action '{$methodCandidate}' not found in controller '" . get_class($this->currentControllerInstance) . "'.");
                $this->currentMethodName = 'index'; // Default to index, check access below
                 if (!method_exists($this->currentControllerInstance, $this->currentMethodName)) {
                    die("Error 404: Default action 'index' not found in controller '" . get_class($this->currentControllerInstance) . "'.");
                }
            }
        }

        $this->params = $url ? array_values($url) : [];

        if (is_callable([$this->currentControllerInstance, $this->currentMethodName])) {
            call_user_func_array([$this->currentControllerInstance, $this->currentMethodName], $this->params);
        } else {
             error_log("Router Error: Method '{$this->currentMethodName}' is not callable in controller '" . get_class($this->currentControllerInstance) . "'.");
             die("Error 404: Action not callable. Controller: '" . get_class($this->currentControllerInstance) . "', Method: '{$this->currentMethodName}'.");
        }
    }

    protected function getUrl() {
        if (isset($_GET['url'])) {
            $url = rtrim($_GET['url'], '/');
            $url = filter_var($url, FILTER_SANITIZE_URL);
            return explode('/', $url);
        }
        return [];
    }
}
?>
