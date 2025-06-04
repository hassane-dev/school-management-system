<?php
namespace App\Core;

class I18n {
    private static $translations = [];
    private static $currentLang = DEFAULT_LANG; // From config.php
    // Define available languages. Could also be dynamically discovered from locales directory.
    private static $availableLanguages = ['en', 'fr', 'ar'];

    public static function setCurrentLang($lang) {
        if (in_array($lang, self::$availableLanguages)) {
            self::$currentLang = $lang;
            if (session_status() == PHP_SESSION_ACTIVE) { // Check if session is active
                $_SESSION['lang'] = $lang;
            }
            self::loadTranslations(); // Load translations when language is set
        }
    }

    public static function getCurrentLang() {
        // Ensure language is initialized if not already
        if (empty(self::$translations) && empty(self::$currentLang) && defined('DEFAULT_LANG')) {
             self::determineInitialLanguage();
        } elseif (empty(self::$translations) && self::$currentLang !== DEFAULT_LANG && defined('DEFAULT_LANG')) {
            // If current lang is set but translations not loaded, load them.
            // This can happen if setCurrentLang was called before session was available, then session restored.
            self::loadTranslations();
        } else if (empty(self::$translations) && self::$currentLang === DEFAULT_LANG && defined('DEFAULT_LANG')) {
             self::loadTranslations(); // Ensure default is loaded if somehow missed
        }
        return self::$currentLang;
    }

    public static function getAvailableLanguages() {
        return self::$availableLanguages;
    }

    public static function loadTranslations() {
        // APP_ROOT points to 'app' directory. locales is sibling to 'app'.
        $langFile = dirname(APP_ROOT) . '/locales/' . self::$currentLang . '.json';

        if (file_exists($langFile)) {
            $jsonContent = file_get_contents($langFile);
            self::$translations = json_decode($jsonContent, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                self::$translations = []; // Reset on error
                error_log("I18n: Failed to decode JSON from " . $langFile . ". Error: " . json_last_error_msg());
            }
        } else {
            error_log("I18n: Language file not found: " . $langFile);
            // Fallback to default language if current lang file not found and it's not already default
            if (self::$currentLang !== DEFAULT_LANG) {
                error_log("I18n: Attempting fallback to default language " . DEFAULT_LANG);
                self::$currentLang = DEFAULT_LANG; // Fallback to default
                if (session_status() == PHP_SESSION_ACTIVE) {
                    $_SESSION['lang'] = self::$currentLang;
                }
                self::loadTranslations(); // Recursively call to load default translations
            } else {
                self::$translations = []; // No translations if default also fails
            }
        }
    }

    public static function translate($key, $replacements = []) {
        // Ensure translations are loaded if they haven't been yet.
        // This might happen if translate() is called before determineInitialLanguage() in index.php,
        // or if the session wasn't started when determineInitialLanguage was first called.
        if (empty(self::$translations)) {
            self::determineInitialLanguage(); // This will also call loadTranslations via setCurrentLang
        }

        $translation = self::$translations[$key] ?? $key; // Return key if not found

        if (!empty($replacements) && is_array($replacements)) {
            foreach ($replacements as $placeholder => $value) {
                $translation = str_replace('{' . $placeholder . '}', $value, $translation);
            }
        }
        return $translation;
    }

    public static function determineInitialLanguage() {
        // Ensure session is available for use.
        if (session_status() == PHP_SESSION_NONE && !headers_sent()) {
            // This is a fallback, session should be started in public/index.php ideally.
            session_start();
        }

        $langToSet = defined('DEFAULT_LANG') ? DEFAULT_LANG : 'en'; // Default to 'en' if DEFAULT_LANG not set

        // 1. Check URL parameter
        if (isset($_GET['lang']) && in_array($_GET['lang'], self::$availableLanguages)) {
            $langToSet = $_GET['lang'];
        }
        // 2. Else, check Session (if active)
        elseif (session_status() == PHP_SESSION_ACTIVE && isset($_SESSION['lang']) && in_array($_SESSION['lang'], self::$availableLanguages)) {
            $langToSet = $_SESSION['lang'];
        }
        // (Future: 3. Else, check User DB preference if user is logged in)
        // (Future: 4. Else, check browser Accept-Language header)

        self::setCurrentLang($langToSet);
        // setCurrentLang calls loadTranslations
    }
}
?>
