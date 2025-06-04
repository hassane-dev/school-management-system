<?php
// This view expects to be wrapped by layouts/main.php
// Expected data:
// $data['user'] (object, optional, for editing)
// $data['roles'] (array of role objects for the dropdown)
// $data['errors'] (array, optional, for displaying validation errors)
// $data['form_action'] (string, the URL to submit the form to)
// $data['form_title'] (string, title for the form section)
// $data['submit_button_text'] (string, text for the submit button)

$user = $data['user'] ?? null; // If null, it's an "add" form
$roles = $data['roles'] ?? [];
$errors = $data['errors'] ?? [];

$form_action = $data['form_action'] ?? ($user ? base_url('admin/users/edit/' . $user->id) : base_url('admin/users/create'));
$form_title = $data['form_title'] ?? ($user ? __('users.edit_user_title', 'Edit User') : __('users.add_user_title', 'Add New User'));
$submit_button_text = $data['submit_button_text'] ?? ($user ? __('users.update_button', 'Update User') : __('users.add_button', 'Add User'));

// Default values for add form, or from $user object for edit form
$nom_value = htmlspecialchars($user->nom ?? ($_POST['nom'] ?? ''));
$email_value = htmlspecialchars($user->email ?? ($_POST['email'] ?? ''));
$role_id_value = $user->role_id ?? ($_POST['role_id'] ?? null);
$langue_preferee_value = $user->langue_preferee ?? ($_POST['langue_preferee'] ?? 'fr');

?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h4><?php echo $form_title; ?></h4>
                </div>
                <div class="card-body">
                    <form action="<?php echo $form_action; ?>" method="POST">
                        <?php if ($user && isset($user->id)): ?>
                            <input type="hidden" name="user_id" value="<?php echo $user->id; ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="nom"><?php echo __('users.name_label', 'Full Name'); ?></label>
                            <input type="text" class="form-control <?php echo isset($errors['nom']) ? 'is-invalid' : ''; ?>"
                                   id="nom" name="nom" value="<?php echo $nom_value; ?>" required>
                            <?php if (isset($errors['nom'])): ?><div class="invalid-feedback"><?php echo $errors['nom']; ?></div><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="email"><?php echo __('users.email_label', 'Email Address'); ?></label>
                            <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                                   id="email" name="email" value="<?php echo $email_value; ?>" required>
                            <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?php echo $errors['email']; ?></div><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="mot_de_passe"><?php echo __('users.password_label', 'Password'); ?></label>
                            <input type="password" class="form-control <?php echo isset($errors['mot_de_passe']) ? 'is-invalid' : ''; ?>"
                                   id="mot_de_passe" name="mot_de_passe" <?php echo !$user ? 'required' : ''; ?>>
                            <?php if (!$user): ?>
                                <small class="form-text text-muted"><?php echo __('users.password_required_for_add', 'Password is required for new users.'); ?></small>
                            <?php else: ?>
                                <small class="form-text text-muted"><?php echo __('users.password_optional_for_edit', 'Leave blank to keep current password.'); ?></small>
                            <?php endif; ?>
                            <?php if (isset($errors['mot_de_passe'])): ?><div class="invalid-feedback"><?php echo $errors['mot_de_passe']; ?></div><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="password_confirm"><?php echo __('users.password_confirm_label', 'Confirm Password'); ?></label>
                            <input type="password" class="form-control <?php echo isset($errors['password_confirm']) ? 'is-invalid' : ''; ?>"
                                   id="password_confirm" name="password_confirm" <?php echo !$user ? 'required' : ''; ?>>
                            <?php if (isset($errors['password_confirm'])): ?><div class="invalid-feedback"><?php echo $errors['password_confirm']; ?></div><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="role_id"><?php echo __('users.role_label', 'Role'); ?></label>
                            <select class="form-control <?php echo isset($errors['role_id']) ? 'is-invalid' : ''; ?>" id="role_id" name="role_id" required>
                                <option value=""><?php echo __('users.select_role_placeholder', '-- Select Role --'); ?></option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo htmlspecialchars($role->id); ?>" <?php echo ($role_id_value == $role->id) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($role->nom); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['role_id'])): ?><div class="invalid-feedback"><?php echo $errors['role_id']; ?></div><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="langue_preferee"><?php echo __('users.preferred_language_label', 'Preferred Language'); ?></label>
                            <select class="form-control <?php echo isset($errors['langue_preferee']) ? 'is-invalid' : ''; ?>" id="langue_preferee" name="langue_preferee">
                                <option value="fr" <?php echo ($langue_preferee_value == 'fr') ? 'selected' : ''; ?>><?php echo __('language_french', 'Français'); ?></option>
                                <option value="en" <?php echo ($langue_preferee_value == 'en') ? 'selected' : ''; ?>><?php echo __('language_english', 'English'); ?></option>
                                <!-- Add other available languages here -->
                            </select>
                            <?php if (isset($errors['langue_preferee'])): ?><div class="invalid-feedback"><?php echo $errors['langue_preferee']; ?></div><?php endif; ?>
                        </div>

                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary"><?php echo $submit_button_text; ?></button>
                            <a href="<?php echo base_url('admin/users'); ?>" class="btn btn-secondary"><?php echo __('cancel_btn', 'Cancel'); ?></a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
