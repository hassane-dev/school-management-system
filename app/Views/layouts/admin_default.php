<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>" dir="<?php echo get_app_direction(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(get_application_name()); ?> - <?php echo __('admin_area_title', 'Admin Area'); ?> <?php echo isset($title) ? ' | ' . htmlspecialchars($title) : ''; ?></title>
    <link rel="stylesheet" href="<?php echo URL_ROOT; ?>/css/style.css"> <!-- General styles -->
    <link rel="stylesheet" href="<?php echo URL_ROOT; ?>/css/admin_style.css"> <!-- Admin specific styles -->
    <?php if (get_app_direction() === 'rtl'): ?>
        <link rel="stylesheet" href="<?php echo URL_ROOT; ?>/css/rtl.css"> <!-- General RTL -->
        <link rel="stylesheet" href="<?php echo URL_ROOT; ?>/css/admin_rtl.css"> <!-- Admin specific RTL -->
    <?php endif; ?>
</head>
<body>
    <div class="admin-wrapper">
        <aside class="admin-sidebar">
            <h3><?php echo __('admin_menu_title', 'Admin Menu'); ?></h3>
            <nav>
                <ul>
                    <li><a href="<?php echo URL_ROOT; ?>/admin/dashboard"><?php echo __('admin_dashboard_link', 'Dashboard'); ?></a></li>
                    <li><hr></li>
                    <li><strong><?php echo __('admin_settings_group_title', 'Settings'); ?></strong></li>
                    <li><a href="<?php echo URL_ROOT; ?>/admin/parametresgeneraux"><?php echo __('admin_nav_general_settings', 'General Settings'); ?></a></li>
                    <li><a href="<?php echo URL_ROOT; ?>/admin/anneesacademiques"><?php echo __('admin_nav_academic_years', 'Academic Years'); ?></a></li>
                    <li><a href="<?php echo URL_ROOT; ?>/admin/parametresecole"><?php echo __('admin_nav_school_settings', 'School Settings'); ?></a></li>
                    <li><hr></li>
                    <li><strong><?php echo __('admin_management_group_title', 'Management'); ?></strong></li>
                    <li><a href="<?php echo URL_ROOT; ?>/admin/enseignant"><?php echo __('admin_nav_teachers', 'Teachers'); ?></a></li>
                    <li><a href="<?php echo URL_ROOT; ?>/admin/enseignement"><?php echo __('admin_nav_assignments', 'Assignments'); ?></a></li>
                    <?php // Future links: Users, Roles, Students, etc. ?>
                    <li><hr></li>
                    <li><a href="<?php echo URL_ROOT; ?>/auth/logout" style="color: #ffc107;"><?php echo __('admin_logout_link', 'Logout'); ?></a></li>
                    <li><hr></li>
                    <li><a href="<?php echo URL_ROOT; ?>/"><?php echo __('admin_back_to_site_link', 'Back to Main Site'); ?></a></li>
                </ul>
            </nav>
            <div style="padding: 10px; margin-top: 20px; border-top: 1px solid #34495e;">
                <form method="GET" action="<?php echo URL_ROOT . '/' . htmlspecialchars($_GET['url'] ?? ''); ?>">
                    <label for="lang_select_admin" style="color: #ecf0f1; font-size: 0.9em;"><?php echo __('language_selector_label', 'Language:'); ?></label>
                    <select name="lang" id="lang_select_admin" onchange="this.form.submit()" style="width: 100%; padding: 5px; background-color: #34495e; color: #ecf0f1; border: 1px solid #2c3e50;">
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
            </div>
        </aside>
        <main class="admin-main-content">
            <header class="admin-header">
                <h2><?php echo htmlspecialchars(get_school_name()); ?> - <?php echo __('admin_panel_title', 'Administration Panel'); ?></h2>
                <?php if (isset($title)): ?>
                    <h3><?php echo htmlspecialchars($title); ?></h3>
                <?php endif; ?>
            </header>
            <div class="admin-page-content">
              <?php
                // Flash message display using AuthSession if available
                if (class_exists('App\Core\AuthSession') && method_exists('App\Core\AuthSession', 'displayFlash')) {
                    echo \App\Core\AuthSession::displayFlash();
                } elseif (isset($_SESSION['flash_message'])) { // Basic fallback
                    $flash = $_SESSION['flash_message'];
                    $alertType = is_array($flash) ? ($flash['type'] ?? 'info') : 'info';
                    $alertMessage = is_array($flash) ? ($flash['text'] ?? '') : $flash;
                    unset($_SESSION['flash_message']);
                    // Using specific admin alert styles if available, otherwise generic
                    echo '<div class="alert alert-' . htmlspecialchars($alertType) . '" role="alert">' . htmlspecialchars($alertMessage) . '</div>';
                }
              ?>
              <?php echo $content_for_layout ?? '<!-- Page Content Here -->'; ?>
            </div>
        </main>
    </div>
    <?php // Global JS scripts can be loaded here if needed for admin panel ?>
</body>
</html>
