<?php
// $data['title'] is passed from controller
// This view uses 'admin_default' layout
?>
<div class="container-fluid">
    <h1 class="mt-4"><?php echo htmlspecialchars($title ?? __('superadmin_dashboard_title_default', 'SuperAdmin Dashboard')); ?></h1>
    <p><?php echo __('superadmin_dashboard_welcome', 'Welcome to the SuperAdmin control panel.'); ?></p>
    <p><?php echo __('superadmin_dashboard_info', 'From here, you have ultimate control over the system, including license management and core configurations.'); ?></p>

    <?php if (Auth::can('access_license_management_interface')): // Assuming this permission exists for Sprint 12 ?>
        <p><a href="<?php echo URL_ROOT; ?>/superadmin/licenses" class="btn btn-primary"><?php echo __('superadmin_manage_licenses_link', 'Manage Licenses'); ?></a></p>
    <?php else: ?>
        <p><small>(<?php echo __('superadmin_manage_licenses_link', 'Manage Licenses'); ?> - <em><?php echo __('permission_not_granted', 'Permission not granted'); ?></em>)</small></p>
    <?php endif; ?>

    <div class="alert alert-warning mt-4">
        <p><strong><?php echo __('superadmin_warning_title', 'Warning:'); ?></strong> <?php echo __('superadmin_warning_text', 'Changes made in this area can have significant impact on the entire application. Proceed with caution.'); ?></p>
    </div>

    <?php // You can add links to other superadmin-specific sections here ?>
</div>
