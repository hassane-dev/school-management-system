<?php // File: app/Views/auth/login.php
// This view is rendered within a layout (e.g., default.php or a specific auth_layout.php)
// $title is passed from AuthController, e.g., $data['title']
// $data['email_repopulate'] might be passed if controller re-renders form on some types of errors.
?>

<div class="login-container">
    <h2><?php echo htmlspecialchars($title ?? __('login_page_title', 'Login')); ?></h2>

    <?php // Flash messages are expected to be handled by the main layout (e.g., default.php or admin_default.php) ?>

    <form action="<?php echo URL_ROOT; ?>/auth/login" method="POST" class="login-form">
        <div class="form-group">
            <label for="email"><?php echo __('login_label_email', 'Email Address'); ?>:</label>
            <input type="email" id="email" name="email" class="form-control" required
                   value="<?php echo htmlspecialchars($data['email_repopulate'] ?? ($_POST['email'] ?? '')); // Repopulate on error if controller sends back POST data ?>">
            <?php if (isset($data['errors']['email'])): ?>
                <span class="error-message"><?php echo htmlspecialchars($data['errors']['email']); ?></span>
            <?php endif; ?>
        </div>
        <div class="form-group">
            <label for="password"><?php echo __('login_label_password', 'Password'); ?>:</label>
            <input type="password" id="password" name="password" class="form-control" required>
            <?php if (isset($data['errors']['password'])): ?>
                <span class="error-message"><?php echo htmlspecialchars($data['errors']['password']); ?></span>
            <?php endif; ?>
        </div>

        <?php // Optional: Remember Me checkbox (Future consideration) ?>
        <!--
        <div class="form-group form-check">
            <input type="checkbox" class="form-check-input" id="remember_me" name="remember_me">
            <label class="form-check-label" for="remember_me"><?= __('login_label_remember_me', 'Remember Me') ?></label>
        </div>
        -->

        <button type="submit" class="btn btn-primary btn-block mt-3">
            <?php echo __('login_button_submit', 'Login'); ?>
        </button>
    </form>

    <div class="login-links mt-3 text-center">
        <?php
        // Example: Link to registration if self-registration is enabled in config
        // And potentially if a 'public_can_register' permission exists and is true.
        // For now, a simple check for a hypothetical config constant.
        if (defined('ALLOW_SELF_REGISTRATION') && ALLOW_SELF_REGISTRATION === true):
        ?>
            <p><a href="<?php echo URL_ROOT; ?>/auth/register"><?php echo __('login_link_register', "Don't have an account? Register"); ?></a></p>
        <?php endif; ?>

        <?php // Link to password reset (future feature) ?>
        <!--
        <p><a href="<?= URL_ROOT ?>/auth/forgot_password"><?= __('login_link_forgot_password', 'Forgot your password?') ?></a></p>
        -->
    </div>
</div>

<?php // Minimal CSS specific to this form, assuming a base CSS (like Bootstrap or custom style.css) handles .form-group, .form-control, .btn etc. ?>
<style>
    .login-container {
        width: 100%;
        max-width: 420px; /* Slightly wider for better spacing */
        margin: 60px auto; /* More top margin */
        padding: 30px; /* More padding */
        border: 1px solid #ccc; /* Lighter border */
        border-radius: 8px; /* Softer radius */
        background-color: #ffffff; /* White background */
        box-shadow: 0 4px 8px rgba(0,0,0,0.1); /* Softer shadow */
    }
    .login-container h2 {
        text-align: center;
        margin-bottom: 25px;
        color: #333;
    }
    .login-form .form-group {
        margin-bottom: 20px; /* More space between fields */
    }
    .login-form label {
        display: block;
        margin-bottom: 8px; /* More space for label */
        font-weight: 500; /* Slightly bolder labels */
        color: #555;
    }
    /* .form-control is assumed to be styled by admin_style.css or style.css */
    /* .btn, .btn-primary, .btn-block are also assumed from global CSS */

    .login-links {
        margin-top: 20px;
    }
    .login-links p {
        margin: 8px 0;
    }
    .login-links a {
        color: #007bff;
        text-decoration: none;
    }
    .login-links a:hover {
        text-decoration: underline;
    }
    .error-message { /* For inline errors if controller passes them back */
        color: #dc3545;
        font-size: 0.875em;
        display: block;
        margin-top: .25rem;
    }
    /* Alert styling should be handled by the layout's main CSS. */
</style>
