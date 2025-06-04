<?php
namespace App\Core;

// Ensure ParametresEcoleModel can be loaded.
// If Autoloader is working, this direct require might not be needed,
// but it's safer for a core class like this if it's used very early.
// However, direct requires in classes are not typical PSR-4.
// Relying on autoloader registered in public/index.php is better.
// use App\Models\ParametresEcoleModel; // This assumes autoloader handles it.

class AppSettings {
    private static $schoolSettings = null;
    private static $generalSettings = null; // For general app settings if needed

    private static function loadSchoolSettings() {
        if (self::$schoolSettings === null) {
            // Ensure model is loaded. Autoloader should handle this.
            // If not, a direct require_once APP_ROOT . '/Models/ParametresEcoleModel.php'; might be needed here,
            // but that's not ideal.
            if (!class_exists('App\Models\ParametresEcoleModel')) {
                // This is a fallback if autoloader hasn't run or can't find it.
                // Should not be necessary if autoloader is robust.
                $modelPath = APP_ROOT . '/Models/ParametresEcoleModel.php';
                if (file_exists($modelPath)) {
                    require_once $modelPath;
                } else {
                    error_log("AppSettings Error: ParametresEcoleModel file not found at {$modelPath}");
                    // Return or throw, preventing further errors if model is critical
                    // For now, allow it to fail and return nulls for settings.
                    self::$schoolSettings = (object)[]; // Set to empty object to prevent repeated load attempts
                    return;
                }
            }

            try {
                $paramsEcoleModel = new \App\Models\ParametresEcoleModel(); // Use fully qualified name
                self::$schoolSettings = $paramsEcoleModel->getSettings();
                if (!self::$schoolSettings) { // If getSettings returns false (no record)
                    self::$schoolSettings = (object)[]; // Ensure it's an object to prevent errors on property access
                }
            } catch (\Exception $e) {
                error_log("AppSettings Error loading school settings: " . $e->getMessage());
                self::$schoolSettings = (object)[]; // Prevent further load attempts on error
            }
        }
    }

    // Example for general settings (parametres_generaux)
    private static function loadGeneralSettings() {
        if (self::$generalSettings === null) {
            if (!class_exists('App\Models\ParametresGenerauxModel')) {
                $modelPath = APP_ROOT . '/Models/ParametresGenerauxModel.php';
                if (file_exists($modelPath)) {
                    require_once $modelPath;
                } else {
                     error_log("AppSettings Error: ParametresGenerauxModel file not found at {$modelPath}");
                    self::$generalSettings = (object)[];
                    return;
                }
            }
            try {
                $paramsGenerauxModel = new \App\Models\ParametresGenerauxModel();
                self::$generalSettings = $paramsGenerauxModel->getSettings();
                 if (!self::$generalSettings) {
                    self::$generalSettings = (object)[];
                }
            } catch (\Exception $e) {
                error_log("AppSettings Error loading general settings: " . $e->getMessage());
                self::$generalSettings = (object)[];
            }
        }
    }

    public static function getSchoolType() {
        self::loadSchoolSettings();
        return self::$schoolSettings->type_etablissement ?? null;
    }

    public static function getSchoolName() {
        self::loadSchoolSettings();
        // Fallback to SITE_NAME constant if nom_ecole is not set or empty
        return self::$schoolSettings->nom_ecole ?? (defined('SITE_NAME') ? SITE_NAME : 'My School');
    }

    public static function getSchoolLogoPath() {
        self::loadSchoolSettings();
        return self::$schoolSettings->logo_path ?? null;
    }

    // Example getter for a general setting
    public static function getDefaultSiteLanguage() {
        self::loadGeneralSettings();
        return self::$generalSettings->langue_site_par_defaut ?? (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'en');
    }

    public static function getApplicationName() {
        self::loadGeneralSettings();
        return self::$generalSettings->nom_application ?? (defined('SITE_NAME') ? SITE_NAME : 'School Application');
    }

    // Method to clear cached settings, e.g., after an update in admin panel
    public static function clearCachedSettings() {
        self::$schoolSettings = null;
        self::$generalSettings = null;
    }
}
?>
