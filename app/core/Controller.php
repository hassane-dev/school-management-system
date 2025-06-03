<?php

class Controller {
    public function model($model) {
        require_once '../app/models/' . $model . '.php';
        return new $model();
    }

    public function view($viewName, $data = []) {
        // Extract data for view availability
        extract($data);

        // Path to the specific view file
        $viewFile = '../app/views/' . $viewName . '.php';

        if (file_exists($viewFile)) {
            // Capture the view's content
            ob_start();
            require $viewFile;
            $content_for_layout = ob_get_clean();

            // Path to the main layout file
            $layoutFile = '../app/views/layouts/main.php';
            if (file_exists($layoutFile)) {
                require $layoutFile;
            } else {
                // Fallback if layout is missing (though it shouldn't be)
                echo $content_for_layout;
                // Or die('Layout file not found: ' . $layoutFile);
            }
        } else {
            die('View file does not exist: ' . $viewFile);
        }
    }
}
?>
