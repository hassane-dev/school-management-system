<!DOCTYPE html>
<html lang="<?= getCurrentLanguage() ?>" dir="<?= get_app_direction() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(get_application_name()) ?> - <?= isset($title) ? htmlspecialchars($title) : __('site_tagline_default', 'Your reliable educational management platform.') ?></title>

    <!-- Local Bootstrap CSS -->
    <link rel="stylesheet" href="<?= URL_ROOT ?>/vendors/bootstrap/css/bootstrap.min.css">
    <!-- Local Font Awesome CSS -->
    <link rel="stylesheet" href="<?= URL_ROOT ?>/vendors/fontawesome/css/all.min.css">

    <!-- Local Custom CSS -->
    <link rel="stylesheet" href="<?= URL_ROOT ?>/css/style.css">
    <?php if (get_app_direction() === 'rtl'): ?>
        <link rel="stylesheet" href="<?= URL_ROOT ?>/css/rtl.css">
    <?php endif; ?>

    <script>
        const URL_ROOT = "<?= URL_ROOT ?>"; // Global JS var for URL_ROOT
    </script>
</head>
<body>
    <header class="bg-dark text-white p-3 mb-4">
        <div class="container">
            <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-lg-start">
                <a href="<?= URL_ROOT ?>/" class="d-flex align-items-center mb-2 mb-lg-0 text-white text-decoration-none">
                    <i class="fas fa-school fa-2x me-2"></i> <!-- Example icon -->
                    <span class="fs-4"><?= htmlspecialchars(get_school_name()) ?></span>
                </a>

                <ul class="nav col-12 col-lg-auto me-lg-auto mb-2 justify-content-center mb-md-0 ms-lg-4">
                    <li><a href="<?= URL_ROOT ?>/" class="nav-link px-2 text-white"><?= __('home_link_text', 'Home') ?></a></li>
                    <li><a href="<?= URL_ROOT ?>/home/about" class="nav-link px-2 text-white"><?= __('about_us_link_text', 'About') ?></a></li>
                    <?php if(auth_is_logged_in()): ?>
                        <?php
                        // Determine appropriate dashboard link
                        $dashboard_link = URL_ROOT . '/user/dashboard'; // Generic dashboard
                        if (Auth::can('view_admin_dashboard')) $dashboard_link = URL_ROOT . '/admin/dashboard';
                        // Add more specific role-based dashboards if needed e.g. /student/dashboard, /teacher/dashboard
                        ?>
                        <li><a href="<?= $dashboard_link ?>" class="nav-link px-2 text-white"><?= __('user_dashboard_link_text', 'My Dashboard') ?></a></li>
                    <?php endif; ?>
                </ul>

                <div class="ms-lg-auto d-flex align-items-center">
                    <form method="GET" action="<?= URL_ROOT . '/' . ($_GET['url'] ?? '') ?>" class="me-2">
                        <label for="lang_select_main" class="visually-hidden"><?= __('language_selector_label') ?></label>
                        <select name="lang" id="lang_select_main" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 70px;">
                            <?php foreach (getAvailableLanguages() as $langCode): ?>
                                <option value="<?= $langCode ?>" <?= (getCurrentLanguage() === $langCode ? 'selected' : '') ?>><?= strtoupper($langCode) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php
                            if (isset($_GET['url'])) { echo '<input type="hidden" name="url" value="' . htmlspecialchars($_GET['url']) . '">'; }
                            $preservedParams = $_GET; unset($preservedParams['lang']); unset($preservedParams['url']);
                            foreach ($preservedParams as $key => $value) { echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">'; }
                        ?>
                    </form>
                    <div class="text-end">
                        <?php if(auth_is_logged_in()): ?>
                            <a href="<?= URL_ROOT ?>/auth/logout" class="btn btn-outline-light btn-sm"><?= __('logout_link_text', 'Logout') ?></a>
                        <?php else: ?>
                            <a href="<?= URL_ROOT ?>/auth/login" class="btn btn-outline-light btn-sm me-2"><?= __('login_link_text', 'Login') ?></a>
                            <?php /* Assuming registration is open, add a register button if desired */ ?>
                            <!-- <a href="<?= URL_ROOT ?>/auth/register" class="btn btn-warning btn-sm"><?= __('register_link_text', 'Register') ?></a> -->
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="container py-4">
        <?php
        if (isset($_SESSION['flash_message']) && is_array($_SESSION['flash_message'])) {
            echo '<div class="alert alert-' . htmlspecialchars($_SESSION['flash_message']['type']) . ' alert-dismissible fade show" role="alert">'
                 . htmlspecialchars($_SESSION['flash_message']['text'])
                 . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            unset($_SESSION['flash_message']);
        }
        ?>
        <?= $content_for_layout ?? '<!-- Page content will be_inserted here by the controller -->'; ?>
    </main>

    <footer class="container mt-5 py-3 border-top text-center">
        <p>&copy; <?= date('Y') ?> <?= htmlspecialchars(get_school_name() ?? get_application_name()) ?>. <?= __('site_title') ?>.</p>
        <?php
            $schoolType = get_setting('school_type', 'public'); // Assuming get_setting helper
            $footerMessageKey = 'footer_info_' . $schoolType;
            echo '<p><small>' . __($footerMessageKey, '') . '</small></p>';
        ?>
    </footer>

    <!-- Local Bootstrap JS Bundle (includes Popper) -->
    <script src="<?= URL_ROOT ?>/vendors/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- Local Custom JS (if any) -->
    <!-- <script src="<?= URL_ROOT ?>/js/main.js"></script> -->
</body>
</html>
