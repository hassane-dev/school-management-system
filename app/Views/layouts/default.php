<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>" dir="<?php echo getLocaleDirection(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('site_title', SITE_NAME); // Fallback to SITE_NAME if key not found ?></title>

    <link rel="stylesheet" href="<?php echo URL_ROOT; ?>/css/style.css">
    <?php if (getCurrentLanguage() === 'ar'): ?>
        <link rel="stylesheet" href="<?php echo URL_ROOT; ?>/css/rtl.css">
    <?php endif; ?>
</head>
<body>
    <header class="container">
        <nav class="navbar">
            <a class="navbar-brand" href="<?php echo URL_ROOT; ?>"><?php echo __('site_title', SITE_NAME); ?></a>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <!-- Example: Using a key from HomeController for the link text -->
                    <a class="nav-link" href="<?php echo URL_ROOT; ?>/home/index"><?php echo __('welcome_message'); ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo URL_ROOT; ?>/home/about"><?php echo __('go_to_about'); ?></a>
                </li>
            </ul>
            <div class="ml-auto"> <!-- Aligns form to the right -->
                <form method="GET" action="<?php echo URL_ROOT . '/' . htmlspecialchars($_GET['url'] ?? ''); ?>" style="display: inline-block; margin:0;">
                    <label for="lang_select" style="margin-right: 5px;"><?php echo __('language_selector_label'); ?></label>
                    <select name="lang" id="lang_select" onchange="this.form.submit()" style="padding: 5px;">
                        <?php foreach (getAvailableLanguages() as $langCode): ?>
                            <option value="<?php echo $langCode; ?>" <?php echo (getCurrentLanguage() === $langCode ? 'selected' : ''); ?>>
                                <?php echo strtoupper($langCode); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php
                    // Preserve other GET parameters by reading them from the current URL's query string
                    $queryParams = [];
                    parse_str($_SERVER['QUERY_STRING'] ?? '', $queryParams);
                    foreach ($queryParams as $key => $value) {
                        if ($key !== 'lang') { // 'url' is already part of the action path
                            echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
                        }
                    }
                    // If 'url' is not part of query string but in path, it's handled by form action.
                    // If it IS part of query string (e.g. from non-htaccess setup), it would be included above.
                    // The form action itself should correctly point to the current page.
                    // A simpler way for action if .htaccess is working: action=""
                    ?>
                </form>
            </div>
        </nav>
    </header>

    <main class="container">
        <?php
        // Display flash messages
        if (class_exists('App\Core\AuthSession') && method_exists('App\Core\AuthSession', 'displayFlash')) {
            echo \App\Core\AuthSession::displayFlash();
        } elseif (isset($_SESSION['flash_message'])) { // Basic fallback
            $alertType = $_SESSION['flash_type'] ?? 'info';
            $alertMessage = $_SESSION['flash_message'];
            unset($_SESSION['flash_message']);
            unset($_SESSION['flash_type']);
            // Basic alert styling, can be improved
            echo '<div style="padding: 10px; margin-bottom: 15px; border: 1px solid transparent; border-radius: 4px; color: #31708f; background-color: #d9edf7; border-color: #bce8f1;">'
                 . htmlspecialchars($alertMessage) . '</div>';
        }
        ?>

        <?php echo $content_for_layout ?? '<!-- Page Content Goes Here -->'; // Content from specific views ?>

        <p style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px;">
            <em><?php echo __('current_language_is'); ?></em>
        </p>
    </main>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> <?php echo __('site_title', SITE_NAME); ?>. All rights reserved.</p>
    </footer>

</body>
</html>
