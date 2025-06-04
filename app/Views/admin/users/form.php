<?php
// Expected data:
// $data['data'] (array of form values: nom, email, langue_preferee, statut_compte, roles (array of IDs), errors (array))
// $data['title'] (string)
// $data['mode'] ('add' or 'edit')
// $data['allRoles'] (array of role objects)
// $data['availableLanguages'] (array of lang codes)
// $data['accountStatuses'] (array of status strings)
// $data['userId'] (int, only for 'edit' mode)

$formData = $data['data'] ?? ['nom' => '', 'email' => '', 'langue_preferee' => (defined('DEFAULT_LANG')?DEFAULT_LANG:'fr'), 'statut_compte' => 'actif', 'roles' => [], 'errors' => []];
$title = $data['title'] ?? ($data['mode'] === 'edit' ? __('users_title_edit_default', 'Edit User') : __('users_title_add_default', 'Add New User'));
$mode = $data['mode'] ?? 'add';
$userId = $data['userId'] ?? null;

$allRoles = $data['allRoles'] ?? [];
$availableLanguages = $data['availableLanguages'] ?? [(defined('DEFAULT_LANG')?DEFAULT_LANG:'fr')];
$accountStatuses = $data['accountStatuses'] ?? ['actif', 'inactif', 'suspendu'];

$action_url = ($mode === 'edit' && $userId) ? URL_ROOT . '/admin/users/edit/' . $userId : URL_ROOT . '/admin/users/add';

$current_user_is_superadmin = auth_can('access_superadmin_interface'); // Or a more specific check if needed
?>

<div class="container-fluid">
    <h1><?php echo htmlspecialchars($title); ?></h1>

    <?php // Flash messages are typically handled by the layout ?>

    <form action="<?php echo $action_url; ?>" method="POST" class="needs-validation" novalidate>
        <?php if ($mode === 'edit' && $userId): ?>
            <input type="hidden" name="id" value="<?php echo $userId; ?>">
        <?php endif; ?>

        <div class="card mt-4">
            <div class="card-header"><?php echo __('users_form_section_account_details', 'Account Details'); ?></div>
            <div class="card-body">
                <div class="form-group">
                    <label for="nom"><?php echo __('users_form_label_name', 'Full Name'); ?>: <span class="text-danger">*</span></label>
                    <input type="text" id="nom" name="nom" class="form-control <?php echo !empty($formData['errors']['nom']) ? 'is-invalid' : ''; ?>"
                           value="<?php echo htmlspecialchars($formData['nom'] ?? ''); ?>" required>
                    <?php if (!empty($formData['errors']['nom'])): ?><div class="invalid-feedback"><?php echo htmlspecialchars($formData['errors']['nom']); ?></div><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="email"><?php echo __('users_form_label_email', 'Email Address'); ?>: <span class="text-danger">*</span></label>
                    <input type="email" id="email" name="email" class="form-control <?php echo !empty($formData['errors']['email']) ? 'is-invalid' : ''; ?>"
                           value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>" required>
                    <?php if (!empty($formData['errors']['email'])): ?><div class="invalid-feedback"><?php echo htmlspecialchars($formData['errors']['email']); ?></div><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="mot_de_passe"><?php echo __('users_form_label_password', 'Password'); ?>: <?php echo ($mode == 'add' ? '<span class="text-danger">*</span>' : ''); ?></label>
                    <input type="password" id="mot_de_passe" name="mot_de_passe" class="form-control <?php echo !empty($formData['errors']['mot_de_passe']) ? 'is-invalid' : ''; ?>"
                           <?php echo ($mode == 'add' ? 'required' : ''); ?>>
                    <?php if ($mode == 'edit'): ?><small class="form-text text-muted"><?php echo __('users_form_password_edit_hint', 'Leave blank to keep current password.'); ?></small><?php endif; ?>
                    <?php if (!empty($formData['errors']['mot_de_passe'])): ?><div class="invalid-feedback"><?php echo htmlspecialchars($formData['errors']['mot_de_passe']); ?></div><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="mot_de_passe_confirm"><?php echo __('users_form_label_password_confirm', 'Confirm Password'); ?>: <?php echo ($mode == 'add' || !empty($formData['mot_de_passe'])) ? '<span class="text-danger">*</span>' : ''; ?></label>
                    <input type="password" id="mot_de_passe_confirm" name="mot_de_passe_confirm" class="form-control <?php echo !empty($formData['errors']['mot_de_passe_confirm']) ? 'is-invalid' : ''; ?>"
                           <?php echo ($mode == 'add' || !empty($formData['mot_de_passe'])) ? 'required' : ''; ?>>
                    <?php if (!empty($formData['errors']['mot_de_passe_confirm'])): ?><div class="invalid-feedback"><?php echo htmlspecialchars($formData['errors']['mot_de_passe_confirm']); ?></div><?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header"><?php echo __('users_form_section_preferences_status', 'Preferences & Status'); ?></div>
            <div class="card-body">
                <div class="form-group">
                    <label for="langue_preferee"><?php echo __('users_form_label_langue_preferee', 'Preferred Language'); ?>: <span class="text-danger">*</span></label>
                    <select id="langue_preferee" name="langue_preferee" class="form-control <?php echo !empty($formData['errors']['langue_preferee']) ? 'is-invalid' : ''; ?>" required>
                        <?php foreach($availableLanguages as $lang_code): ?>
                            <option value="<?php echo $lang_code; ?>" <?php echo (($formData['langue_preferee'] ?? DEFAULT_LANG) == $lang_code) ? 'selected' : ''; ?>>
                                <?php echo strtoupper($lang_code); // Or a translated language name e.g., __('lang_name_'.$lang_code) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($formData['errors']['langue_preferee'])): ?><div class="invalid-feedback"><?php echo htmlspecialchars($formData['errors']['langue_preferee']); ?></div><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="statut_compte"><?php echo __('users_form_label_statut_compte', 'Account Status'); ?>: <span class="text-danger">*</span></label>
                    <select id="statut_compte" name="statut_compte" class="form-control <?php echo !empty($formData['errors']['statut_compte']) ? 'is-invalid' : ''; ?>" required
                           <?php echo ($mode === 'edit' && $userId === auth_id()) ? 'disabled' : ''; // Prevent user from changing their own status directly here ?> >
                        <?php foreach($accountStatuses as $status): ?>
                            <option value="<?php echo $status; ?>" <?php echo (($formData['statut_compte'] ?? 'actif') === $status) ? 'selected' : ''; ?>>
                                <?php echo __("user_status_$status", ucfirst(str_replace('_', ' ', $status))); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($mode === 'edit' && $userId === auth_id()): ?>
                        <small class="form-text text-muted"><?php echo __('users_status_self_edit_disabled_hint', 'Your own account status cannot be changed here.'); ?></small>
                    <?php endif; ?>
                    <?php if (!empty($formData['errors']['statut_compte'])): ?><div class="invalid-feedback"><?php echo htmlspecialchars($formData['errors']['statut_compte']); ?></div><?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (auth_can('assign_roles_to_user')): ?>
        <div class="card mt-4">
            <div class="card-header"><?php echo __('users_form_section_roles', 'Assign Roles'); ?>: <span class="text-danger">*</span></div>
            <div class="card-body roles-checkbox-group">
            <?php if (!empty($allRoles)): ?>
                <div class="row">
                <?php foreach($allRoles as $role):
                    // Logic to disable SuperAdmin role changes for non-SuperAdmins or if it's the only SuperAdmin
                    $isSuperAdminRole = ($role->nom === 'SuperAdmin');
                    $canManageSuperAdminRole = $current_user_is_superadmin; // Only SuperAdmins can assign/unassign SuperAdmin role

                    $disabled = '';
                    if ($isSuperAdminRole && !$canManageSuperAdminRole) {
                        $disabled = 'disabled';
                    }
                    // Prevent user from removing their own SuperAdmin role if they are one (logic might be more complex)
                    if ($mode === 'edit' && $isSuperAdminRole && $userId === auth_id() && in_array($role->id, $formData['roles'] ?? [])) {
                        // This is complex: what if they are the ONLY superadmin? Should not be able to uncheck.
                        // For now, if it's self and user is SA, and this is SA role, disable unchecking.
                        // $disabled = 'disabled onclick="return false;"'; // A bit hacky
                    }
                ?>
                    <div class="col-md-4 form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="roles[]"
                               id="role_<?php echo $role->id; ?>" value="<?php echo $role->id; ?>"
                               <?php echo (in_array($role->id, $formData['roles'] ?? [])) ? 'checked' : ''; ?> <?php echo $disabled; ?>>
                        <label class="form-check-label" for="role_<?php echo $role->id; ?>">
                            <?php echo htmlspecialchars($role->nom); ?>
                            <?php if(!empty($role->description)): ?>
                                <small class="text-muted d-block"><?php echo htmlspecialchars($role->description); ?></small>
                            <?php endif; ?>
                        </label>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p><?php echo __('roles_no_roles_found', 'No roles available for assignment.'); ?></p>
            <?php endif; ?>
            <?php if (!empty($formData['errors']['roles'])): ?><div class="text-danger mt-2" style="font-size: 0.875em;"><?php echo htmlspecialchars($formData['errors']['roles']); ?></div><?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">
                <?php echo ($mode == 'edit' ? __('global_save_button', 'Save Changes') : __('global_add_button', 'Add User')); ?>
            </button>
            <a href="<?php echo URL_ROOT; ?>/admin/users" class="btn btn-secondary">
                <?php echo __('global_cancel_button', 'Cancel'); ?>
            </a>
        </div>
    </form>
</div>

<style>
/* .required { color: red; } already in admin_style.css potentially */
/* .error { color: red; font-size: 0.9em; display: block; } */
/* fieldset { margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; } */
/* legend { font-weight: bold; } */
/* .roles-checkbox-group .form-check { margin-bottom: 10px; } */
/* .roles-checkbox-group .form-check-label small { font-weight: normal; color: #6c757d; } */
</style>
