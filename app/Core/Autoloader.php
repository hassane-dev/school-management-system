<?php
namespace App\Core;

class Autoloader {
    public static function register() {
        spl_autoload_register(function ($className) {
            // Adjust namespace prefix and base directory if your structure differs
            $namespacePrefix = 'App\\'; // Corrected: Escaped backslash
            // APP_ROOT should be defined in config.php and point to the 'app' directory
            // For the autoloader, the base directory for 'App\' namespace is the 'app' folder itself.
            $baseDirForAppNamespace = APP_ROOT . '/';

            if (strpos($className, $namespacePrefix) === 0) {
                // Remove the namespace prefix 'App\' from the class name
                $relativeClassName = substr($className, strlen($namespacePrefix));

                // Replace namespace separators with directory separators
                $filePath = $baseDirForAppNamespace . str_replace('\\', '/', $relativeClassName) . '.php';

                if (file_exists($filePath)) {
                    require_once $filePath;
                }
            }
        });
    }
}
?>
