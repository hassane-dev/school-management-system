<?php
// app/Core/helpers.php
use App\Core\I18n;
use App\Core\AppSettings;
use App\Core\Auth; // For new Auth helpers

// --- I18n Helper Functions ---
if (!function_exists('__')) {
    function __($key, $replacements = []) {
        return I18n::translate($key, $replacements);
    }
}
if (!function_exists('getCurrentLanguage')) {
    function getCurrentLanguage() {
        return I18n::getCurrentLang();
    }
}
if (!function_exists('getAvailableLanguages')) {
    function getAvailableLanguages() {
        return I18n::getAvailableLanguages();
    }
}
if (!function_exists('get_app_direction')) {
    function get_app_direction() {
        return (I18n::getCurrentLang() === 'ar') ? 'rtl' : 'ltr';
    }
}

// --- AppSettings Helper Functions ---
if (!function_exists('get_school_type')) {
    function get_school_type() { return AppSettings::getSchoolType(); }
}
if (!function_exists('get_school_name')) {
    function get_school_name() { return AppSettings::getSchoolName(); }
}
if (!function_exists('get_school_logo_path')) {
    function get_school_logo_path() { return AppSettings::getSchoolLogoPath(); }
}
if (!function_exists('get_application_name')) {
    function get_application_name(){ return AppSettings::getApplicationName(); }
}

if (!function_exists('get_setting')) {
    /**
     * Gets a specific general setting value using AppSettings.
     * This is a generic getter; specific getters in AppSettings are preferred for known settings.
     * @param string $key The setting key (maps to property name in parametres_generaux).
     * @param mixed $default Default value if setting not found.
     * @return mixed The setting value or default.
     */
    function get_setting($key, $default = null) {
        // This requires AppSettings to have a generic getter or specific ones.
        // For now, let's assume AppSettings might have specific getters like getDateFormat, getTimeFormat
        // or a generic one like AppSettings::getGeneralSetting('format_date');
        // Adding specific known ones for now:
        if ($key === 'format_date') {
            // Need to ensure AppSettings can provide this, e.g. AppSettings::getDateFormat()
            // For now, as a placeholder until AppSettings is expanded or used directly:
            return AppSettings::getGeneralSetting('format_date') ?? (defined('DEFAULT_DATE_FORMAT') ? DEFAULT_DATE_FORMAT : 'Y-m-d');
        }
        if ($key === 'format_heure') {
            return AppSettings::getGeneralSetting('format_heure') ?? (defined('DEFAULT_TIME_FORMAT') ? DEFAULT_TIME_FORMAT : 'H:i:s');
        }
        // Fallback for other keys - this generic getter isn't fully implemented in AppSettings yet
        // return AppSettings::getGeneralSetting($key) ?? $default;
        return $default; // Placeholder
    }
}
// Adding a more direct way to get general settings for now in AppSettings
// AppSettings would need:
// public static function getGeneralSetting($key, $default = null) {
//     self::loadGeneralSettings(); // Ensure general settings are loaded
//     return self::$generalSettings->$key ?? $default;
// }
// And this needs to be added to AppSettings.php


// --- URL & Redirection Helper ---
if (!function_exists('redirectTo')) {
    /**
     * Redirects to a given path within the application.
     * Ensures URL_ROOT (defined in config.php) is used for base path.
     * @param string $path Path to redirect to (e.g., '/users/login').
     */
    function redirectTo($path) {
        $baseUrl = defined('URL_ROOT') ? URL_ROOT : '';
        // Ensure path starts with a slash if not already, and avoid double slashes
        if (strpos($path, '/') !== 0) {
            $path = '/' . $path;
        }
        header("Location: " . rtrim($baseUrl, '/') . $path);
        exit;
    }
}

// --- Auth Helper Functions ---
if (!function_exists('auth_is_logged_in')) {
    function auth_is_logged_in(): bool { return Auth::isLoggedIn(); }
}
if (!function_exists('auth_id')) {
    function auth_id(): ?int { return Auth::getCurrentUserId(); }
}
if (!function_exists('auth_user')) {
    function auth_user(): ?object { return Auth::getCurrentUser(); }
}
if (!function_exists('auth_roles')) { // Get current user's role objects for current context
    function auth_roles(): array { return Auth::getCurrentUserRoles(); }
}
if (!function_exists('auth_role_names')) { // Get current user's role names for current context
    function auth_role_names(): array { return Auth::getCurrentUserRoleNames(); }
}
if (!function_exists('auth_can')) { // Optimized permission check for current user, current context
    function auth_can($permissionName): bool { return Auth::can($permissionName); }
}
if (!function_exists('auth_check')) { // Flexible permission check for any user/context
    function auth_check($permissionName, $userId = null, $anneeId = null): bool {
        return Auth::check($permissionName, $userId, $anneeId);
    }
}
if (!function_exists('auth_require_permission')) {
    function auth_require_permission($permissionName) { Auth::requirePermission($permissionName); }
}

// You can add other global helper functions here as your application grows.
?>
