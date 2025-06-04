<?php
// app/Core/helpers.php
use App\Core\I18n; // Ensures the I18n class is recognized in this namespace context.
use App\Core\AppSettings; // For new helper functions

if (!function_exists('__')) {
    /**
     * Translates a given key using the I18n system.
     *
     * @param string $key The translation key.
     * @param array $replacements An associative array of placeholder => value pairs for replacing in the string.
     * @return string The translated string, or the key itself if not found.
     */
    function __($key, $replacements = []) {
        return I18n::translate($key, $replacements);
    }
}

if (!function_exists('getCurrentLanguage')) {
    /**
     * Gets the currently active language code.
     *
     * @return string The current language code (e.g., 'en', 'fr').
     */
    function getCurrentLanguage() {
        return I18n::getCurrentLang();
    }
}

if (!function_exists('getAvailableLanguages')) {
    /**
     * Gets the list of available language codes.
     *
     * @return array List of available language codes.
     */
    function getAvailableLanguages() {
        return I18n::getAvailableLanguages();
    }
}

if (!function_exists('getLocaleDirection')) {
    /**
     * Gets the text direction ('ltr' or 'rtl') for the current language.
     *
     * @return string 'rtl' if current language is Arabic, otherwise 'ltr'.
     */
    function get_app_direction() { // Renamed from getLocaleDirection for clarity
        return (I18n::getCurrentLang() === 'ar') ? 'rtl' : 'ltr';
    }
}

// --- AppSettings Helper Functions ---

if (!function_exists('get_school_type')) {
    /**
     * Gets the type of the school (e.g., 'public', 'prive').
     * @return string|null School type or null if not set.
     */
    function get_school_type() {
        return AppSettings::getSchoolType();
    }
}

if (!function_exists('get_school_name')) {
    /**
     * Gets the name of the school. Falls back to SITE_NAME from config if not set in DB.
     * @return string School name.
     */
    function get_school_name() {
        return AppSettings::getSchoolName();
    }
}

if (!function_exists('get_school_logo_path')) {
    /**
     * Gets the path to the school's logo.
     * @return string|null Logo path or null if not set.
     */
    function get_school_logo_path() {
        return AppSettings::getSchoolLogoPath();
    }
}

if (!function_exists('get_application_name')) {
    /**
     * Gets the application name from general settings. Falls back to SITE_NAME.
     * @return string Application name.
     */
    function get_application_name(){
        return AppSettings::getApplicationName();
    }
}


// You can add other global helper functions here as your application grows.
?>
