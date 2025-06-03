<?php

// 1. Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Define a hardcoded fallback language
define('FALLBACK_LANG', 'fr');

// 3. Language selection logic
$chosen_lang = null;

// Priority 1: URL parameter
if (isset($_GET['lang']) && preg_match('/^[a-z]{2,3}(?:-[A-Z]{2,3})?$/', $_GET['lang'])) { // Basic lang code validation
    $chosen_lang = $_GET['lang'];
    $_SESSION['lang'] = $chosen_lang;
}
// Priority 2: Session value
elseif (isset($_SESSION['lang']) && preg_match('/^[a-z]{2,3}(?:-[A-Z]{2,3})?$/', $_SESSION['lang'])) {
    $chosen_lang = $_SESSION['lang'];
}
// Priority 3: Database default (will require DB connection and model)
else {
    // Temporary placeholder for DB connection and model loading
    // This part will be tricky as the full app isn't bootstrapped yet.
    // We need to load the Model class and ParametresGeneraux_model specifically.
    // This assumes 'app/core/Model.php' and 'app/models/ParametresGeneraux_model.php' can be loaded here.

    // Manually require core Model and specific model if autoloader isn't ready
    if (file_exists('../app/core/Model.php')) {
        require_once '../app/core/Model.php';
    }
    if (file_exists('../app/models/ParametresGeneraux_model.php')) {
        require_once '../app/models/ParametresGeneraux_model.php';
    }

    if (class_exists('ParametresGeneraux_model')) {
        try {
            $paramsModel = new ParametresGeneraux_model(); // Model constructor handles DB connection
            $generalSettings = $paramsModel->read(); // read() fetches the first/only row
            if ($generalSettings && isset($generalSettings->langue_defaut) && preg_match('/^[a-z]{2,3}(?:-[A-Z]{2,3})?$/', $generalSettings->langue_defaut)) {
                $chosen_lang = $generalSettings->langue_defaut;
            }
        } catch (PDOException $e) {
            // DB connection might fail if credentials are not set, or DB not set up
            // Log this error in a real app: error_log("DB connection error for language fetch: " . $e->getMessage());
            // Fallback will be used.
        } catch (Error $e) {
            // Class not found or other critical error
            // error_log("Error fetching language from DB: " . $e->getMessage());
        }
    }
}

// Priority 4: Fallback to hardcoded default
if ($chosen_lang === null) {
    $chosen_lang = FALLBACK_LANG;
}

// 4. Define global constant for current language
define('CURRENT_LANG', $chosen_lang);

// 5. Autoload core classes (including helpers.php which will use CURRENT_LANG)
require_once '../app/core/autoload.php';

// 6. Initialize the App class to handle routing
$app = new App();

?>
