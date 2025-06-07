<?php
// $data['title']
// $data['user_name']
?>
<div class="container mt-4">
    <h1><?php echo htmlspecialchars($title ?? __('user_dashboard_title_default', 'Dashboard')); ?></h1>
    <p><?php echo __('user_dashboard_welcome', ['name' => htmlspecialchars($user_name ?? '')]); ?></p>
    <p><?php echo __('user_dashboard_info', 'This is your general dashboard. Specific features may be available based on your role.'); ?></p>

    <?php
        $userRoles = auth_role_names(); // Using the helper function
        if (empty($userRoles)):
    ?>
        <p><?php echo __('user_dashboard_no_roles', 'You currently have no specific roles assigned. Please contact an administrator if you believe this is an error.'); ?></p>
    <?php else: ?>
        <p><?php echo __('user_dashboard_your_roles_are', 'Your current roles:'); ?> <strong><?php echo htmlspecialchars(implode(', ', $userRoles)); ?></strong></p>
    <?php endif; ?>

    <?php // Could add common links here if any, or direct to profile page etc. ?>
    <p class="mt-3">
        <a href="<?php echo URL_ROOT; ?>/auth/logout" class="btn btn-secondary"><?php echo __('logout_button', 'Logout'); ?></a>
    </p>
</div>
