<?php

// This variable will hold the loaded translations for the CURRENT_LANG
$GLOBALS['translations'] = [];

function load_translations() {
    // CURRENT_LANG is defined in public/index.php
    $lang_code = CURRENT_LANG;
    $file_path = '../locales/' . $lang_code . '.json';

    if (file_exists($file_path)) {
        $GLOBALS['translations'] = json_decode(file_get_contents($file_path), true);
        if ($GLOBALS['translations'] === null) { // Handle JSON decode error
            $GLOBALS['translations'] = [];
            // error_log("Failed to decode JSON for language: " . $lang_code);
        }
    } else {
        // error_log("Language file not found: " . $file_path);
        // Optionally, try to load fallback language if CURRENT_LANG's file is missing
        // For now, it will just result in keys being returned by __()
        $GLOBALS['translations'] = [];
    }
}

// Load translations as soon as this helper is loaded
load_translations();

/**
 * Translates a given key using the loaded language file.
 *
 * @param string $key The translation key.
 * @param string $default The default value to return if the key is not found.
 * @return string The translated string or the default/key itself.
 */
function __($key, $default = null) {
    if (isset($GLOBALS['translations'][$key])) {
        return $GLOBALS['translations'][$key];
    }
    return $default !== null ? $default : $key; // Return default value if provided, else the key
}


if (!function_exists('redirectTo')) {
    function redirectTo($path) {
        // Ensure path starts with a slash if it's meant to be from BASE_URL root,
        // or handle it as relative if not. For consistency, paths from root are better.
        if (strpos($path, '/') !== 0) {
            $path = '/' . $path;
        }

        // BASE_URL should be defined in public/index.php
        if (!defined('BASE_URL')) {
            // Fallback BASE_URL definition (less ideal, should be set in index.php)
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $host = $_SERVER['HTTP_HOST'];
            $scriptName = str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']);
            $scriptName = $scriptName === '' ? '/' : $scriptName; // Handle root access
            define('BASE_URL', rtrim($protocol . $host . $scriptName, '/'));
        }
        header("Location: " . BASE_URL . $path);
        exit;
    }
}

?>
