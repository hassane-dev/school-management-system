<?php // File: app/Views/admin/dashboard/index.php
// $data['title'] is passed from DashboardController
?>

<div class="container-fluid"> <?php // Using container-fluid for potentially wider content in admin ?>

    <?php if (isset($title)): ?>
        <h1 class="mt-4"><?php echo htmlspecialchars($title); ?></h1>
    <?php endif; ?>

    <p><?php echo __('admin_dashboard_welcome_message', 'Welcome to the administration panel. Here you can manage various aspects of the application.'); ?></p>

    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <?php echo __('admin_nav_general_settings', 'General Settings'); ?>
                </div>
                <div class="card-body">
                    <p class="card-text"><?php echo __('admin_dashboard_general_settings_desc', 'Configure core application settings, default language, theme, and more.'); ?></p>
                    <a href="<?php echo URL_ROOT; ?>/admin/parametresgeneraux" class="btn btn-primary"><?php echo __('go_to_settings_button', 'Go to General Settings'); ?></a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <?php echo __('admin_nav_school_settings', 'School Settings'); ?>
                </div>
                <div class="card-body">
                    <p class="card-text"><?php echo __('admin_dashboard_school_settings_desc', 'Manage school-specific details like name, logo, address, and contact information.'); ?></p>
                    <a href="<?php echo URL_ROOT; ?>/admin/parametresecole" class="btn btn-primary"><?php echo __('go_to_settings_button', 'Go to School Settings'); ?></a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <?php echo __('admin_nav_academic_years', 'Academic Years'); ?>
                </div>
                <div class="card-body">
                    <p class="card-text"><?php echo __('admin_dashboard_academic_years_desc', 'Define and manage academic years, set the active year for operations.'); ?></p>
                    <a href="<?php echo URL_ROOT; ?>/admin/anneesacademiques" class="btn btn-primary"><?php echo __('go_to_settings_button', 'Manage Academic Years'); ?></a>
                </div>
            </div>
        </div>
    </div>

    <?php
        // Example of using get_school_type for conditional content
        $schoolType = function_exists('get_school_type') ? get_school_type() : null;
    ?>
    <?php if ($schoolType): ?>
    <div class="mt-4 p-3 mb-2 bg-light text-dark border rounded">
        <p><strong><?php echo __('school_type_info_label', 'School Type Information:'); ?></strong>
        <?php if ($schoolType === 'public'): ?>
            <?= __('footer_info_public_school', 'This is a public educational institution.') ?>
        <?php elseif ($schoolType === 'prive'): ?>
            <?= __('footer_info_private_school', 'This is a private educational institution.') ?>
        <?php elseif ($schoolType === 'parapublic'): ?>
            <?= __('footer_info_parapublic_school', 'This is a semi-public educational institution.') ?>
        <?php else: ?>
            <?= htmlspecialchars($schoolType) ?>
        <?php endif; ?>
        </p>
    </div>
    <?php endif; ?>

    <?php // More dashboard widgets or summaries can be added here ?>

</div>
