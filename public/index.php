<?php
// Start session if needed later (good to have early)
// Ensures session is available for flash messages, login status, etc.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Load configuration - This is crucial as it defines APP_ROOT, URL_ROOT etc.
// It's important that APP_ROOT is correctly pointing to the 'app' directory.
// config.php defines: define('APP_ROOT', dirname(__DIR__) . '/app');
// If public/index.php is in /public/, then dirname(__DIR__) is the project root.
// So APP_ROOT will be project_root/app. This seems correct.
require_once '../config/config.php';

// Load Autoloader
// APP_ROOT should be correctly defined by now.
require_once APP_ROOT . '/Core/Autoloader.php';
App\Core\Autoloader::register(); // Register the autoloader

// Load core helpers (including i18n __() function)
// This needs to be loaded after Autoloader if helpers depend on Core classes like I18n.
if (file_exists(APP_ROOT . '/Core/helpers.php')) {
    require_once APP_ROOT . '/Core/helpers.php';
}

// Initialize I18n and determine language
// This will set the language based on URL param, session, or default
// and load the corresponding translation file.
App\Core\I18n::determineInitialLanguage();


// Basic Error Reporting (for development) - Consider more robust logging for production
error_reporting(E_ALL);
ini_set('display_errors', 1); // Set to 0 in production

// Define BASE_URL if not already defined in config.php (it's better in config or here consistently)
// The Router might use it, or redirect helpers.
// The config.php provided in the prompt defined URL_ROOT, not BASE_URL. Let's ensure consistent usage.
// For now, URL_ROOT is used for links. If BASE_URL is needed for server-side paths from public, define it.
// The previous `redirectTo` helper used BASE_URL. Let's ensure it's available.
if (!defined('BASE_URL')) {
    // This definition assumes URL_ROOT is the base for the public accessible part of the app.
    // And that .htaccess makes URLs relative to index.php's location.
    // Example: if URL_ROOT is 'http://localhost/myproject/public', BASE_URL might be '/myproject/public' or just '/'
    // depending on server setup and how htaccess works.
    // For simplicity, let's assume URL_ROOT is sufficient for now for constructing URLs.
    // The redirectTo helper will use URL_ROOT.
}


// Init Router - The router will handle controller and method dispatching
// Router expects APP_ROOT to be defined for resolving controller paths.
try {
    $router = new App\Core\Router();
} catch (Exception $e) {
    // Catch any critical errors during routing or controller instantiation
    error_log("Critical Error: " . $e->getMessage());
    // Display a generic error message to the user
    // In a real app, you might have a dedicated error view or handler.
    die("An unexpected error occurred. Please try again later or contact support. Error details: " . $e->getMessage());
}
?>
