<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>" dir="<?php echo getLocaleDirection(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
        // Use application name from DB settings if available, otherwise site_title key, then SITE_NAME constant
        $application_name = function_exists('get_application_name') ? get_application_name() : (defined('SITE_NAME') ? SITE_NAME : 'School App');
        $site_title_key = __('site_title', $application_name); // Pass $application_name as default for site_title key
    ?>
    <title><?php echo htmlspecialchars($data['title'] ?? $site_title_key); ?></title>

    <link rel="stylesheet" href="<?php echo URL_ROOT; ?>/css/style.css">
    <?php if (getCurrentLanguage() === 'ar'): ?>
        <link rel="stylesheet" href="<?php echo URL_ROOT; ?>/css/rtl.css">
    <?php endif; ?>
</head>
<body>
    <header class="container">
        <nav class="navbar">
            <a class="navbar-brand" href="<?php echo URL_ROOT; ?>">
                <?php
                $logo_path = function_exists('get_school_logo_path') ? get_school_logo_path() : null;
                if ($logo_path && file_exists(FCPATH . $logo_path)): // FCPATH defined in public/index.php
                ?>
                    <img src="<?php echo URL_ROOT . '/' . htmlspecialchars($logo_path); ?>" alt="<?php echo htmlspecialchars(get_school_name()); ?> Logo" style="max-height: 40px; margin-right: 10px;">
                <?php else: ?>
                    <?php echo htmlspecialchars(get_school_name()); // Display school name if no logo ?>
                <?php endif; ?>
            </a>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo URL_ROOT; ?>/home/index"><?php echo __('welcome_message'); ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo URL_ROOT; ?>/home/about"><?php echo __('go_to_about'); ?></a>
                </li>
                <?php if (class_exists('App\Core\AuthSession') && \App\Core\AuthSession::isLoggedIn()): ?>
                    <?php if (\App\Core\AuthSession::hasRole(['admin', 'super_admin'])): ?>
                        <li class="nav-item dropdown"> <!-- Basic dropdown example -->
                            <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Admin
                            </a>
                            <div class="dropdown-menu" aria-labelledby="adminDropdown">
                                <a class="dropdown-item" href="<?= URL_ROOT ?>/admin/parametresgeneraux">Paramètres Généraux</a>
                                <a class="dropdown-item" href="<?= URL_ROOT ?>/admin/parametresecole">Paramètres École</a>
                                <a class="dropdown-item" href="<?= URL_ROOT ?>/admin/anneesacademiques">Années Académiques</a>
                                <!-- Add more admin links here -->
                            </div>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>


            </ul>
            <div class="ml-auto">
                <form method="GET" action="<?php echo URL_ROOT . '/' . htmlspecialchars($_GET['url'] ?? ''); ?>" style="display: inline-block; margin:0;">
                    <label for="lang_select" style="margin-right: 5px; color: <?php echo (getCurrentLanguage() === 'ar' ? '#fff':'#333'); ?>;"><?php echo __('language_selector_label'); ?></label>
                    <select name="lang" id="lang_select" onchange="this.form.submit()" style="padding: 5px;">
                        <?php foreach (getAvailableLanguages() as $langCode): ?>
                            <option value="<?php echo $langCode; ?>" <?php echo (getCurrentLanguage() === $langCode ? 'selected' : ''); ?>>
                                <?php echo strtoupper($langCode); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php
                    $queryParams = [];
                    parse_str($_SERVER['QUERY_STRING'] ?? '', $queryParams);
                    foreach ($queryParams as $key => $value) {
                        if ($key !== 'lang') {
                            echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
                        }
                    }
                    ?>
                </form>
                 <?php if (class_exists('App\Core\AuthSession') && \App\Core\AuthSession::isLoggedIn()): ?>
                    <a href="<?= URL_ROOT ?>/auth/logout" class="nav-link" style="display: inline-block; margin-left: 10px;"><?= __('logout_button', 'Logout')?></a>
                <?php else: ?>
                     <a href="<?= URL_ROOT ?>/auth/login" class="nav-link" style="display: inline-block; margin-left: 10px;"><?= __('login_button', 'Login')?></a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <main class="container">
        <?php
        if (class_exists('App\Core\AuthSession') && method_exists('App\Core\AuthSession', 'displayFlash')) {
            echo \App\Core\AuthSession::displayFlash();
        } elseif (isset($_SESSION['flash_message'])) {
            $flash = $_SESSION['flash_message'];
            $alertType = is_array($flash) ? ($flash['type'] ?? 'info') : 'info';
            $alertMessage = is_array($flash) ? ($flash['text'] ?? '') : $flash;
            unset($_SESSION['flash_message']);
            echo '<div class="alert alert-' . htmlspecialchars($alertType) . '" role="alert">' . htmlspecialchars($alertMessage) . '</div>';
        }
        ?>
        <?php echo $content_for_layout ?? '<!-- Page Content Goes Here -->'; ?>

        <p style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px;">
            <em><?php echo __('current_language_is'); ?></em>
        </p>
    </main>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(get_school_name()); ?> - <?php echo __('site_title', $application_name); ?></p>
       <?php $schoolType = get_school_type(); ?>
       <?php if ($schoolType === 'public'): ?>
           <p><small><?= __('footer_info_public_school', 'This is a public educational institution.') ?></small></p>
       <?php elseif ($schoolType === 'prive'): ?>
           <p><small><?= __('footer_info_private_school', 'This is a private educational institution.') ?></small></p>
       <?php elseif ($schoolType === 'parapublic'): ?>
           <p><small><?= __('footer_info_parapublic_school', 'This is a semi-public educational institution.') ?></small></p>
       <?php endif; ?>
    </footer>

    <!-- Basic Bootstrap JS for dropdowns, etc. (Optional if not using Bootstrap JS components) -->
    <script src="https_//code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https_//cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https_//stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
