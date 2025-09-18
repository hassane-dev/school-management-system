<!DOCTYPE html>
<html lang="<?= getCurrentLanguage() ?>" dir="<?= get_app_direction() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(get_application_name()) ?> - <?= __('admin_area_title') ?> <?= isset($title) ? ' | ' . htmlspecialchars($title) : '' ?></title>

    <!-- Local Bootstrap CSS -->
    <link rel="stylesheet" href="<?= URL_ROOT ?>/vendors/bootstrap/css/bootstrap.min.css">
    <!-- Local Font Awesome CSS -->
    <link rel="stylesheet" href="<?= URL_ROOT ?>/vendors/fontawesome/css/all.min.css">

    <!-- Local Custom Admin CSS -->
    <link rel="stylesheet" href="<?= URL_ROOT ?>/css/admin_style.css">
    <?php if (get_app_direction() === 'rtl'): ?>
        <link rel="stylesheet" href="<?= URL_ROOT ?>/css/rtl.css"> <?php // Main RTL styles ?>
        <link rel="stylesheet" href="<?= URL_ROOT ?>/css/admin_rtl.css">
    <?php endif; ?>

    <script> const URL_ROOT = "<?= URL_ROOT ?>"; </script>
</head>
<body class="admin-body bg-light">
    <div class="admin-wrapper d-flex">
        <aside class="admin-sidebar bg-dark text-white p-3 d-flex flex-column">
            <div class="sidebar-header mb-3">
                <a href="<?= URL_ROOT ?>/admin/dashboard" class="text-white text-decoration-none">
                    <h3 class="fs-5"><i class="fas fa-school me-2"></i><?= htmlspecialchars(get_school_name() ?? get_application_name()) ?></h3>
                </a>
            </div>
            <hr class="text-secondary mt-0">

            <nav class="nav flex-column flex-grow-1">
                <a class="nav-link text-white" href="<?= URL_ROOT ?>/admin/dashboard"><i class="fas fa-home fa-fw me-2"></i><?= __('admin_dashboard_link') ?></a>

                <?php if (Auth::can('manage_general_settings') || Auth::can('view_academic_years') || Auth::can('manage_school_settings')): ?>
                    <hr class="text-secondary my-2"><strong class="text-muted nav-section-title px-2 mb-1 d-block text-uppercase small"><?= __('admin_settings_group_title') ?></strong>
                    <?php if (Auth::can('manage_general_settings')): ?><li><a class="nav-link text-white py-1" href="<?= URL_ROOT ?>/admin/parametresgeneraux"><i class="fas fa-cogs fa-fw me-2"></i><?= __('admin_nav_general_settings') ?></a></li><?php endif; ?>
                    <?php if (Auth::can('view_academic_years')): ?><li><a class="nav-link text-white py-1" href="<?= URL_ROOT ?>/admin/anneesacademiques"><i class="fas fa-calendar-alt fa-fw me-2"></i><?= __('admin_nav_academic_years') ?></a></li><?php endif; ?>
                    <?php if (Auth::can('manage_school_settings')): ?><li><a class="nav-link text-white py-1" href="<?= URL_ROOT ?>/admin/parametresecole"><i class="fas fa-landmark fa-fw me-2"></i><?= __('admin_nav_school_settings') ?></a></li><?php endif; ?>
                <?php endif; ?>

                <?php if (Auth::can('view_matieres') || Auth::can('view_classes') || Auth::can('view_enseignements') || Auth::can('view_programmes_scolaires')): ?>
                    <hr class="text-secondary my-2"><strong class="text-muted nav-section-title px-2 mb-1 d-block text-uppercase small"><?= __('admin_academic_management_group_title') ?></strong>
                    <?php if (Auth::can('view_matieres')): ?><li><a class="nav-link text-white py-1" href="<?= URL_ROOT ?>/admin/matieres"><i class="fas fa-book fa-fw me-2"></i><?= __('admin_nav_subjects') ?></a></li><?php endif; ?>
                    <?php if (Auth::can('view_classes')): ?><li><a class="nav-link text-white py-1" href="<?= URL_ROOT ?>/admin/classes"><i class="fas fa-chalkboard-teacher fa-fw me-2"></i><?= __('admin_nav_classes') ?></a></li><?php endif; ?>
                    <?php if (Auth::can('view_enseignements')): ?><li><a class="nav-link text-white py-1" href="<?= URL_ROOT ?>/admin/enseignements"><i class="fas fa-tasks fa-fw me-2"></i><?= __('admin_nav_assignments') ?></a></li><?php endif; ?>
                    <?php if (Auth::can('view_programmes_scolaires')): ?><li><a class="nav-link text-white py-1" href="<?= URL_ROOT ?>/admin/programmesscolaires"><i class="fas fa-book-open fa-fw me-2"></i><?= __('admin_nav_curricula') ?></a></li><?php endif; ?>
                <?php endif; ?>

                <?php if (Auth::can('view_eleves')): ?>
                    <hr class="text-secondary my-2"><strong class="text-muted nav-section-title px-2 mb-1 d-block text-uppercase small"><?= __('admin_student_management_group_title') ?></strong>
                    <li><a class="nav-link text-white py-1" href="<?= URL_ROOT ?>/admin/eleves"><i class="fas fa-user-graduate fa-fw me-2"></i><?= __('admin_nav_students') ?></a></li>
                     <?php if (Auth::can('import_export_eleves')): ?>
                        <!-- <li><a class="nav-link text-white py-1" href="<?= URL_ROOT ?>/admin/eleves/importer"><i class="fas fa-file-import fa-fw me-2"></i><?= __('admin_nav_student_import') ?></a></li> -->
                    <?php endif; ?>
                <?php endif; ?>

                <?php if (Auth::can('view_comptabilite_eleve') || Auth::can('view_reduction_types')): ?>
                     <hr class="text-secondary my-2"><strong class="text-muted nav-section-title px-2 mb-1 d-block text-uppercase small"><?= __('admin_student_accounting_group_title') ?></strong>
                     <?php if (Auth::can('view_comptabilite_eleve')): ?><li><a class="nav-link text-white py-1" href="<?= URL_ROOT ?>/admin/comptabiliteeleve"><i class="fas fa-cash-register fa-fw me-2"></i><?= __('admin_nav_student_accounts') ?></a></li><?php endif; ?>
                     <?php if (Auth::can('view_reduction_types')): ?><li><a class="nav-link text-white py-1" href="<?= URL_ROOT ?>/admin/reductiontypes"><i class="fas fa-percent fa-fw me-2"></i><?= __('admin_nav_reduction_types') ?></a></li><?php endif; ?>
                <?php endif; ?>

                <?php if (Auth::can('view_users') || Auth::can('view_roles')): ?>
                    <hr class="text-secondary my-2"><strong class="text-muted nav-section-title px-2 mb-1 d-block text-uppercase small"><?= __('admin_users_roles_group_title') ?></strong>
                    <?php if (Auth::can('view_users')): ?><li><a class="nav-link text-white py-1" href="<?= URL_ROOT ?>/admin/users"><i class="fas fa-users fa-fw me-2"></i><?= __('admin_nav_users') ?></a></li><?php endif; ?>
                    <?php if (Auth::can('view_roles')): ?><li><a class="nav-link text-white py-1" href="<?= URL_ROOT ?>/admin/roles"><i class="fas fa-user-tag fa-fw me-2"></i><?= __('admin_nav_roles') ?></a></li><?php endif; ?>
                <?php endif; ?>

                <?php if (Auth::can('access_superadmin_interface')): ?>
                    <hr class="text-secondary my-2"><strong class="text-warning nav-section-title px-2 mb-1 d-block text-uppercase small"><?= __('admin_superadmin_group_title') ?></strong>
                    <li><a class="nav-link text-warning py-1" href="<?= URL_ROOT ?>/superadmin/dashboard"><i class="fas fa-user-shield fa-fw me-2"></i><?= __('admin_nav_superadmin_dashboard') ?></a></li>
                <?php endif; ?>
            </nav>

            <div class="mt-auto pt-3"> <!-- Pushes to bottom -->
                <hr class="text-secondary my-2">
                <a class="nav-link text-white py-1" href="<?= URL_ROOT ?>/auth/logout"><i class="fas fa-sign-out-alt fa-fw me-2"></i><?= __('admin_logout_link') ?></a>
                <a class="nav-link text-white py-1" href="<?= URL_ROOT ?>/"><i class="fas fa-globe fa-fw me-2"></i><?= __('admin_back_to_site_link') ?></a>
                <hr class="text-secondary my-2">
                <form method="GET" action="<?= URL_ROOT . '/' . ($_GET['url'] ?? '') ?>">
                    <label for="lang_select_admin" class="visually-hidden"><?= __('language_selector_label') ?></label>
                    <select name="lang" id="lang_select_admin" class="form-select form-select-sm bg-dark text-white" onchange="this.form.submit()">
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
            </div>
        </aside>
        <main class="admin-main-content flex-grow-1 p-4">
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4 admin-top-nav">
                <div class="container-fluid">
                    <button class="btn btn-sm btn-outline-secondary me-2 d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#adminSidebarContent" aria-controls="adminSidebarContent" aria-expanded="false" aria-label="Toggle navigation">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h2 class="h4 mb-0 flex-grow-1"><?= isset($title) ? htmlspecialchars($title) : __('admin_panel_title') ?></h2>
                    <div class="d-flex align-items-center">
                        <span class="navbar-text me-3">
                           <i class="fas fa-user-circle me-1"></i> <?= htmlspecialchars(Auth::getCurrentUser()->nom ?? 'User') ?>
                        </span>
                        <a href="<?= URL_ROOT ?>/auth/logout" class="btn btn-sm btn-outline-danger"><i class="fas fa-sign-out-alt"></i> <?= __('logout_link_text', 'Logout')?></a>
                    </div>
                </div>
            </nav>
             <!-- Collapsible sidebar content for small screens -->
            <div class="collapse d-lg-none" id="adminSidebarContent">
                <!-- Content of aside duplicated here or handled via JS to move it -->
            </div>

            <div class="admin-page-content bg-white p-3 shadow-sm rounded">
                <?php
                if (isset($_SESSION['flash_message']) && is_array($_SESSION['flash_message'])) {
                    echo '<div class="alert alert-' . htmlspecialchars($_SESSION['flash_message']['type']) . ' alert-dismissible fade show" role="alert">'
                         . htmlspecialchars($_SESSION['flash_message']['text'])
                         . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                    unset($_SESSION['flash_message']);
                }
                ?>
                <?= $content_for_layout ?? '<!-- Page content will be inserted here by the controller -->'; ?>
            </div>
            <footer class="admin-footer mt-auto py-3 text-center text-muted">
                 <small>&copy; <?= date('Y') ?> <?= htmlspecialchars(get_application_name()) ?>. <?= __('admin_footer_rights_reserved', 'All rights reserved.') ?></small>
            </footer>
        </main>
    </div>
    <!-- Local Bootstrap JS Bundle (includes Popper) -->
    <script src="<?= URL_ROOT ?>/vendors/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- Local Custom Admin JS (if any) -->
    <!-- <script src="<?= URL_ROOT ?>/js/admin_main.js"></script> -->
</body>
</html>
