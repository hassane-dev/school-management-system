<?php
// This view expects to be wrapped by layouts/main.php
// It also expects $_SESSION['user_nom'] to be set for personalization.
?>

<div class="container mt-4">
    <div class="jumbotron">
        <h1 class="display-4">
            <?php
            $userName = htmlspecialchars($_SESSION['user_nom'] ?? __('guest_user', 'Guest'));
            echo __("dashboard.welcome", "Welcome, {name}!", ['name' => $userName]);
            ?>
        </h1>
        <p class="lead"><?php echo __('dashboard.welcome_message_lead', 'This is your main dashboard. More features will be added soon.'); ?></p>
        <hr class="my-4">
        <p><?php echo __('dashboard.explore_options_text', 'You can explore available options through the navigation menu (if available) or use the links below.'); ?></p>

        <?php if (isset($_SESSION['role_nom']) && $_SESSION['role_nom'] === 'admin' || $_SESSION['role_nom'] === 'super_admin' ): ?>
            <a class="btn btn-primary btn-lg mr-2" href="<?php echo base_url('admin/users'); ?>" role="button">
                <i class="fas fa-users-cog"></i> <?php echo __('manage_users_link', 'Manage Users'); ?>
            </a>
            <a class="btn btn-secondary btn-lg" href="<?php echo base_url('parametres/index'); ?>" role="button">
                <i class="fas fa-cogs"></i> <?php echo __('manage_settings_link', 'Manage Settings'); ?>
            </a>
        <?php endif; ?>

        <!-- Add more role-specific links here -->

    </div>

    <h4><?php echo __('quick_info_title', 'Quick Information:'); ?></h4>
    <ul>
        <li><?php echo __('your_role_is', 'Your current role:'); ?> <strong><?php echo htmlspecialchars($_SESSION['role_nom'] ?? 'N/A'); ?></strong></li>
        <li><?php echo __('your_language_preference_is', 'Your language preference:'); ?> <strong><?php echo htmlspecialchars($_SESSION['lang'] ?? CURRENT_LANG); ?></strong></li>
         <li><?php echo __('current_system_language_is', 'Current system language:'); ?> <strong><?php echo CURRENT_LANG; ?></strong></li>
    </ul>

</div>
