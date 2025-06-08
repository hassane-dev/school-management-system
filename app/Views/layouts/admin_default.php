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
    <!-- Font Awesome for icons (if not already loaded globally) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="admin-wrapper">
        <aside class="admin-sidebar">
            <h3><?php echo __('admin_menu_title', 'Admin Menu'); ?></h3>
            <nav>
                <ul>
                    <li><a href="<?php echo URL_ROOT; ?>/admin/dashboard"><i class="fas fa-tachometer-alt fa-fw"></i> <?php echo __('admin_dashboard_link', 'Dashboard'); ?></a></li>

                    <li><hr></li>
                    <li><strong><?php echo __('admin_settings_group_title', 'Settings'); ?></strong></li>
                    <?php if (Auth::can('manage_general_settings') || (method_exists('Auth','can') && Auth::can('view_general_settings'))): ?>
                        <li><a href="<?php echo URL_ROOT; ?>/admin/parametresgeneraux"><i class="fas fa-cogs fa-fw"></i> <?php echo __('admin_nav_general_settings', 'General'); ?></a></li>
                    <?php endif; ?>
                    <?php if (Auth::can('view_academic_years')): ?>
                        <li><a href="<?php echo URL_ROOT; ?>/admin/anneesacademiques"><i class="fas fa-calendar-alt fa-fw"></i> <?php echo __('admin_nav_academic_years', 'Academic Years'); ?></a></li>
                    <?php endif; ?>
                    <?php if (Auth::can('manage_school_settings') || (method_exists('Auth','can') && Auth::can('view_school_settings'))): ?>
                        <li><a href="<?php echo URL_ROOT; ?>/admin/parametresecole"><i class="fas fa-school fa-fw"></i> <?php echo __('admin_nav_school_settings', 'School Info'); ?></a></li>
                    <?php endif; ?>

                    <?php
                    // Academic Management Group
                    $canViewAcademicManagement = Auth::can('view_matieres') || Auth::can('view_classes') || Auth::can('view_enseignements') || Auth::can('view_programmes_scolaires');
                    ?>
                    <?php if ($canViewAcademicManagement): ?>
                        <li><hr></li>
                        <li><strong><?php echo __('admin_academic_management_group_title', 'Academic Management'); ?></strong></li>
                        <?php if (Auth::can('view_matieres')): ?>
                            <li><a href="<?php echo URL_ROOT; ?>/admin/matieres"><i class="fas fa-book fa-fw"></i> <?php echo __('admin_nav_subjects', 'Subjects'); ?></a></li>
                        <?php endif; ?>
                        <?php if (Auth::can('view_classes')): ?>
                            <li><a href="<?php echo URL_ROOT; ?>/admin/classes"><i class="fas fa-chalkboard-teacher fa-fw"></i> <?php echo __('admin_nav_classes', 'Classes'); ?></a></li>
                        <?php endif; ?>
                        <?php if (Auth::can('view_enseignements')): ?>
                            <li><a href="<?php echo URL_ROOT; ?>/admin/enseignements"><i class="fas fa-user-graduate fa-fw"></i> <?php echo __('admin_nav_assignments', 'Teacher Assignments'); ?></a></li>
                        <?php endif; ?>
                        <?php if (Auth::can('view_programmes_scolaires')): ?>
                            <li><a href="<?php echo URL_ROOT; ?>/admin/programmesscolaires"><i class="fas fa-drafting-compass fa-fw"></i> <?php echo __('admin_nav_curricula', 'Curricula'); ?></a></li>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php
                    // Group for Student Management
                    $canViewStudentManagement = Auth::can('view_eleves'); // Base permission for the group
                    ?>
                    <?php if ($canViewStudentManagement): ?>
                        <li><hr></li>
                        <li><strong><?= __('admin_student_management_group_title') ?></strong></li>
                        <?php if (Auth::can('view_eleves')): ?>
                            <li><a href="<?= URL_ROOT ?>/admin/eleves"><i class="fas fa-user-graduate fa-fw"></i> <?= __('admin_nav_students') ?></a></li>
                        <?php endif; ?>
                        <?php // Link to assign_classe is usually part of student edit, not separate nav item ?>
                        <?php // Link to import/export could be here or on student list page
                        if (Auth::can('import_export_eleves')): ?>
                            <!-- <li><a href="<?= URL_ROOT ?>/admin/eleves/manage_imports_exports"><i class="fas fa-file-import fa-fw"></i> <?= __('admin_nav_student_import_export') ?></a></li> -->
                        <?php endif; ?>
                    <?php endif; ?>


                    <?php
                    // Group for Student Accounting
                    $canViewStudentAccounting = Auth::can('view_comptabilite_eleve') || Auth::can('view_reduction_types');
                    ?>
                    <?php if ($canViewStudentAccounting): ?>
                        <li><hr></li>
                        <li><strong><?= __('admin_student_accounting_group_title') ?></strong></li>
                        <?php if (Auth::can('view_comptabilite_eleve')): ?>
                            <li><a href="<?= URL_ROOT ?>/admin/comptabiliteeleve"><i class="fas fa-cash-register fa-fw"></i> <?= __('admin_nav_student_accounts') ?></a></li>
                        <?php endif; ?>
                        <?php if (Auth::can('view_reduction_types')): ?>
                             <li><a href="<?= URL_ROOT ?>/admin/reductiontypes"><i class="fas fa-tags fa-fw"></i> <?= __('admin_nav_reduction_types') ?></a></li>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php
                    // Users & Roles Group
                    $canViewUserManagement = Auth::can('view_users') || Auth::can('view_roles');
                    ?>
                    <?php if ($canViewUserManagement): ?>
                        <li><hr></li>
                        <li><strong><?php echo __('admin_users_roles_group_title', 'Users & Roles'); ?></strong></li>
                        <?php if (Auth::can('view_users')): ?>
                            <li><a href="<?php echo URL_ROOT; ?>/admin/users"><i class="fas fa-users fa-fw"></i> <?php echo __('admin_nav_users', 'Users'); ?></a></li>
                        <?php endif; ?>
                        <?php if (Auth::can('view_roles')): ?>
                            <li><a href="<?php echo URL_ROOT; ?>/admin/roles"><i class="fas fa-user-tag fa-fw"></i> <?php echo __('admin_nav_roles', 'Roles'); ?></a></li>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if (Auth::can('access_superadmin_interface')): ?>
                        <li><hr style="border-color: #4a627a;"></li>
                        <li><strong><?php echo __('admin_superadmin_group_title', 'SuperAdmin Zone'); ?></strong></li>
                        <li><a href="<?php echo URL_ROOT; ?>/superadmin/dashboard" style="color: #f1c40f;"><i class="fas fa-star fa-fw"></i> <?php echo __('admin_nav_superadmin_dashboard', 'SuperAdmin Panel'); ?></a></li>
                    <?php endif; ?>

                    <li><hr></li>
                    <li><a href="<?php echo URL_ROOT; ?>/auth/logout" style="color: #e74c3c;"><i class="fas fa-sign-out-alt fa-fw"></i> <?php echo __('admin_logout_link', 'Logout'); ?></a></li>
                    <li><hr></li>
                    <li><a href="<?php echo URL_ROOT; ?>/"><i class="fas fa-home fa-fw"></i> <?php echo __('admin_back_to_site_link', 'Back to Main Site'); ?></a></li>
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
                // Flash message display (using Auth::displayFlash() is preferred if it exists and is robust)
                if (class_exists('App\Core\Auth') && method_exists('App\Core\Auth', 'displayFlash')) {
                    echo \App\Core\Auth::displayFlash();
                } elseif (isset($_SESSION['flash_message'])) {
                    $flash = $_SESSION['flash_message'];
                    $alertType = is_array($flash) ? ($flash['type'] ?? 'info') : 'info';
                    $alertMessage = is_array($flash) ? ($flash['text'] ?? '') : $flash;
                    unset($_SESSION['flash_message']);
                    echo '<div class="alert alert-' . htmlspecialchars($alertType) . '" role="alert">' . htmlspecialchars($alertMessage) . '</div>';
                }
              ?>
              <?php echo $content_for_layout ?? '<!-- Page Content Here -->'; ?>
            </div>
        </main>
    </div>
    <script>
        const URL_ROOT = "<?php echo URL_ROOT; ?>";
        // Other global JS variables can be defined here
    </script>
    <?php // Global JS scripts can be loaded here if needed for admin panel, e.g. Bootstrap JS for dropdowns ?>
    <!-- <script src="https_//code.jquery.com/jquery-3.5.1.slim.min.js"></script> -->
    <!-- <script src="https_//cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script> -->
    <!-- <script src="https_//stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script> -->
</body>
</html>
