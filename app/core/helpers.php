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

?>
