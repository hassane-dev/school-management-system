<?php
namespace App\Core;

class Controller {
    /**
     * Loads a model class.
     * @param string $modelName The name of the model (e.g., 'User' for UserModel.php).
     * @return object|null An instance of the model, or null if not found.
     */
    protected function model($modelName) {
        $modelClass = "App\\Models\\" . $modelName;
        if (class_exists($modelClass)) {
            return new $modelClass();
        } else {
            error_log("Controller Error: Model class " . $modelClass . " not found for model name " . $modelName);
            die("Model " . $modelName . " does not exist.");
        }
    }

    /**
     * Loads a view file, optionally wrapped by a layout.
     * @param string $viewPath The path to the view file relative to APP_ROOT/Views/ (e.g., 'home/index').
     * @param array $data Data to be extracted and made available to the view and layout.
     * @param string|null $layout The name of the layout file in APP_ROOT/Views/layouts/ (e.g., 'default', 'admin_default').
     *                             If null, no layout is used. If a layout is specified but not found, it falls back to no layout.
     */
    protected function view($viewPath, $data = [], $layout = 'default') {
        $fullViewPath = APP_ROOT . '/Views/' . $viewPath . '.php';

        if (file_exists($fullViewPath)) {
            extract($data); // Make $data available to both view and layout

            ob_start();
            require $fullViewPath;
            $content_for_layout = ob_get_clean();

            if ($layout !== null) {
                $layoutPath = APP_ROOT . '/Views/layouts/' . $layout . '.php';
                if (file_exists($layoutPath)) {
                    require $layoutPath; // Layout file should echo $content_for_layout
                } else {
                    error_log("Controller Error: Layout file not found at: " . $layoutPath . ". Rendering view without layout.");
                    echo $content_for_layout; // Fallback if specified layout is missing
                }
            } else {
                echo $content_for_layout; // No layout specified, render view directly
            }
        } else {
            error_log("Controller Error: View file not found at: " . $fullViewPath);
            die("View " . $viewPath . " does not exist.");
        }
    }
}
?>
