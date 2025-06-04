<?php
namespace App\Core;

class Controller {
    /**
     * Loads a model class.
     * @param string $model The name of the model (e.g., 'User' for UserModel.php).
     * @return object|null An instance of the model, or null if not found.
     */
    protected function model($modelName) {
        // Construct model class name with namespace
        $modelClass = "App\\Models\\" . $modelName; // Assumes models are in App\Models namespace

        // Autoloader should handle requiring the file if class_exists is called.
        if (class_exists($modelClass)) {
            return new $modelClass();
        } else {
            // In a real app, throw an exception or log error
            die("Model " . $modelName . " (class " . $modelClass . ") does not exist.");
        }
    }

    /**
     * Loads a view file.
     * @param string $view The path to the view file (e.g., 'home/index' for Views/home/index.php).
     * @param array $data Data to be extracted and made available to the view.
     */
    protected function view($view, $data = []) {
        // APP_ROOT should be defined in config.php and point to the 'app' directory.
        $viewPath = APP_ROOT . '/Views/' . $view . '.php';

        if (file_exists($viewPath)) {
            // Extract data to variables for the view
            extract($data);

            // Start output buffering
            ob_start();

            // Include the view file
            require_once $viewPath;

            // Get the content of the buffer and clean it
            $content_for_layout = ob_get_clean();

            // By default, just echo content. Layouts can be handled here or in a dedicated layout system.
            // For now, if a layout named 'default.php' exists in Views/layouts, use it.
            $layoutPath = APP_ROOT . '/Views/layouts/default.php';
            if (file_exists($layoutPath)) {
                require_once $layoutPath; // The layout should echo $content_for_layout
            } else {
                echo $content_for_layout; // No layout found, just echo the view's content
            }

        } else {
            // In a real app, throw an exception or log error, then show a user-friendly error page
            die("View file " . $viewPath . " does not exist.");
        }
    }
}
?>
