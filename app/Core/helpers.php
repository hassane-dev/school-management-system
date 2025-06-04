<?php
// app/Core/helpers.php
use App\Core\I18n; // Ensures the I18n class is recognized in this namespace context.

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
    function getLocaleDirection() {
        return (I18n::getCurrentLang() === 'ar') ? 'rtl' : 'ltr';
    }
}

// You can add other global helper functions here as your application grows.
?>
