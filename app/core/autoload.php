<?php

spl_autoload_register(function ($className) {
    $paths = [
        '../app/core/',
        '../app/controllers/',
        '../app/models/'
    ];

    foreach ($paths as $path) {
        $file = $path . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Additionally, load core classes that might not follow the naming convention directly
// or are essential and should be loaded upfront.
// For this basic setup, App.php is critical for routing.
if (file_exists('../app/core/App.php')) {
    require_once '../app/core/App.php';
}

if (file_exists('../app/core/Controller.php')) {
    require_once '../app/core/Controller.php';
}

if (file_exists('../app/core/Model.php')) {
    require_once '../app/core/Model.php';
}

// Load helper functions
if (file_exists('../app/core/helpers.php')) {
    require_once '../app/core/helpers.php';
}

?>
