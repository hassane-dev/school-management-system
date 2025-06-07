<?php
// File: public/index.php

// Define FCPATH (File system path to public directory) if not already defined
if (!defined('FCPATH')) {
    define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
}

// Configure secure session cookie parameters BEFORE session_start()
$cookieParams = [
    'lifetime' => 0, // 0 = until browser closes. Or set a specific lifetime (e.g., 3600 * 24 for 1 day)
    'path' => '/',   // Available for the entire domain. Adjust if app is in a subdirectory.
    'domain' => '',  // Current domain. Set explicitly if needed for subdomains (e.g., '.yourdomain.com').
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', // Only send cookie over HTTPS
    'httponly' => true, // Prevent JavaScript access to session cookie
    'samesite' => 'Lax' // Or 'Strict'. Mitigates CSRF. 'Lax' is a good default.
];
session_set_cookie_params($cookieParams);

// Start session
// (Auth.php and I18n.php also have fallbacks, but starting it here once is best)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Load configuration (defines APP_ROOT, URL_ROOT, SITE_NAME, DB_Creds, DEFAULT_LANG etc.)
require_once '../config/config.php';

// Basic Error Reporting (for development) - Set to 0 in production
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load Autoloader
require_once APP_ROOT . '/Core/Autoloader.php';
App\Core\Autoloader::register();

// Load core helpers (including i18n __() function and Auth helpers)
require_once APP_ROOT . '/Core/helpers.php';

// Initialize I18n and determine language (before any output or Auth context loading)
App\Core\I18n::determineInitialLanguage();

// Init Router (Auth checks will happen inside controllers called by router, or by router itself)
try {
    $router = new App\Core\Router();
} catch (\Throwable $e) { // Catch Throwable for PHP 7+ to include Errors
    error_log("Critical Bootstrap Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    // Display a generic, user-friendly error page in production
    // For development, die() can show more details if display_errors is on.
    if (ini_get('display_errors')) {
         die("An unexpected application error occurred. Please check server logs. Error: " . htmlspecialchars($e->getMessage()));
    } else {
         die("An unexpected application error occurred. Please try again later or contact support.");
    }
}
?>
