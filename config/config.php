<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'db_user_placeholder'); // Replace
define('DB_PASS', 'db_password_placeholder'); // Replace
define('DB_NAME', 'db_name_placeholder'); // Replace
define('DB_CHARSET', 'utf8mb4');

// Application Configuration
// Assuming this file is in /config/, then dirname(dirname(__FILE__)) is the project root.
// APP_ROOT should point to the 'app' directory.
define('APP_ROOT', dirname(__DIR__) . '/app');
define('URL_ROOT', 'http://localhost/msm_public'); // Replace - Base URL of the app's public folder
define('SITE_NAME', 'Modular School Management');

// Default language
define('DEFAULT_LANG', 'fr');
?>
