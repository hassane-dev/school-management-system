<?php
// This view will be wrapped by app/views/layouts/main.php
// $data might contain 'email' and 'errors' if form was reloaded after submission failure.
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card mt-5">
            <div class="card-header">
                <h4><?php echo __('login_form_title', 'Login'); ?></h4>
            </div>
            <div class="card-body">
                <?php
                // Display flash messages if they are set in the main layout
                // This is just a secondary check in case main layout missed it or for specific placement
                if (isset($_SESSION['flash_message']) && !isset($content_for_layout)) { // Second condition to avoid double printing if main.php is used
                    echo '<div class="alert alert-' . ($_SESSION['flash_type'] ?? 'info') . '">' . $_SESSION['flash_message'] . '</div>';
                    unset($_SESSION['flash_message']);
                    unset($_SESSION['flash_type']);
                }
                ?>
                <form action="<?php echo base_url('auth/login'); ?>" method="POST">
                    <div class="form-group">
                        <label for="email"><?php echo __('email_label', 'Email Address'); ?></label>
                        <input type="email" class="form-control <?php echo isset($data['errors']['email']) ? 'is-invalid' : ''; ?>"
                               id="email" name="email" value="<?php echo htmlspecialchars($data['email'] ?? ''); ?>" required autofocus>
                        <?php if (isset($data['errors']['email'])): ?>
                            <div class="invalid-feedback"><?php echo $data['errors']['email']; ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="password"><?php echo __('password_label', 'Password'); ?></label>
                        <input type="password" class="form-control <?php echo isset($data['errors']['password']) ? 'is-invalid' : ''; ?>"
                               id="password" name="password" required>
                        <?php if (isset($data['errors']['password'])): ?>
                            <div class="invalid-feedback"><?php echo $data['errors']['password']; ?></div>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">
                        <?php echo __('login_button', 'Login'); ?>
                    </button>
                </form>
                <p class="mt-3 text-center">
                    <?php echo __("dont_have_account", "Don't have an account?"); ?> <a href="<?php echo base_url('auth/register'); ?>"><?php echo __('register_link', 'Register here'); ?></a>
                </p>
            </div>
        </div>
    </div>
</div>
