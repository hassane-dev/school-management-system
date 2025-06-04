<?php
// This view will be wrapped by app/views/layouts/main.php
// $data might contain form values and 'errors' if reloaded after submission failure.
?>
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card mt-5">
            <div class="card-header">
                <h4><?php echo __('register_form_title', 'Register'); ?></h4>
            </div>
            <div class="card-body">
                <?php
                if (isset($_SESSION['flash_message']) && !isset($content_for_layout)) {
                    echo '<div class="alert alert-' . ($_SESSION['flash_type'] ?? 'info') . '">' . $_SESSION['flash_message'] . '</div>';
                    unset($_SESSION['flash_message']);
                    unset($_SESSION['flash_type']);
                }
                ?>
                <form action="<?php echo base_url('auth/register'); ?>" method="POST">
                    <div class="form-group">
                        <label for="nom"><?php echo __('name_label', 'Full Name'); ?></label>
                        <input type="text" class="form-control <?php echo isset($data['errors']['nom']) ? 'is-invalid' : ''; ?>"
                               id="nom" name="nom" value="<?php echo htmlspecialchars($data['nom'] ?? ''); ?>" required>
                        <?php if (isset($data['errors']['nom'])): ?>
                            <div class="invalid-feedback"><?php echo $data['errors']['nom']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="email"><?php echo __('email_label', 'Email Address'); ?></label>
                        <input type="email" class="form-control <?php echo isset($data['errors']['email']) ? 'is-invalid' : ''; ?>"
                               id="email" name="email" value="<?php echo htmlspecialchars($data['email'] ?? ''); ?>" required>
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

                    <div class="form-group">
                        <label for="password_confirm"><?php echo __('confirm_password_label', 'Confirm Password'); ?></label>
                        <input type="password" class="form-control <?php echo isset($data['errors']['password_confirm']) ? 'is-invalid' : ''; ?>"
                               id="password_confirm" name="password_confirm" required>
                        <?php if (isset($data['errors']['password_confirm'])): ?>
                            <div class="invalid-feedback"><?php echo $data['errors']['password_confirm']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="langue_preferee"><?php echo __('preferred_language_label', 'Preferred Language'); ?></label>
                        <select class="form-control <?php echo isset($data['errors']['langue_preferee']) ? 'is-invalid' : ''; ?>" id="langue_preferee" name="langue_preferee">
                            <option value="fr" <?php echo (isset($data['langue_preferee']) && $data['langue_preferee'] == 'fr') ? 'selected' : ''; ?>><?php echo __('language_french', 'Français'); ?></option>
                            <option value="en" <?php echo (isset($data['langue_preferee']) && $data['langue_preferee'] == 'en') ? 'selected' : ''; ?>><?php echo __('language_english', 'English'); ?></option>
                            <!-- Add other languages as needed -->
                        </select>
                        <?php if (isset($data['errors']['langue_preferee'])): ?>
                            <div class="invalid-feedback"><?php echo $data['errors']['langue_preferee']; ?></div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <?php echo __('register_button', 'Register'); ?>
                    </button>
                </form>
                <p class="mt-3 text-center">
                    <?php echo __("already_have_account", "Already have an account?"); ?> <a href="<?php echo base_url('auth/login'); ?>"><?php echo __('login_link', 'Login here'); ?></a>
                </p>
            </div>
        </div>
    </div>
</div>
