<?php
// $data['data'] contains current form values (nom, description, est_systeme, errors, all_permissions, role_permissions)
// $data['title']
// $data['mode'] ('add' or 'edit')
// $data['role_id'] (for edit mode)
// All text should use __()

$form_data = $data['data'] ?? ['nom' => '', 'description' => '', 'est_systeme' => 0, 'errors' => []];
$all_permissions = $form_data['all_permissions'] ?? [];
$role_permissions_ids = $form_data['role_permissions'] ?? []; // Array of IDs for current role's permissions

$title = $data['title'] ?? ($data['mode'] === 'edit' ? __('roles_title_edit_default', 'Edit Role') : __('roles_title_add_default', 'Add New Role'));
$mode = $data['mode'] ?? 'add';
$role_id = $data['role_id'] ?? null; // Only in edit mode

$action_url = ($mode === 'edit' && $role_id) ? URL_ROOT . '/admin/roles/edit/' . $role_id : URL_ROOT . '/admin/roles/add';

// Group permissions for display
$groupedPermissions = [];
foreach ($all_permissions as $permission) {
    $groupName = !empty($permission->groupe) ? htmlspecialchars($permission->groupe) : __('permissions_group_other', 'Other');
    $groupedPermissions[$groupName][] = $permission;
}
ksort($groupedPermissions); // Sort groups alphabetically

$isSystemRoleAndEditMode = ($mode == 'edit' && isset($form_data['est_systeme']) && $form_data['est_systeme'] == 1);
?>

<div class="container-fluid">
    <h1><?php echo htmlspecialchars($title); ?></h1>

    <?php // Flash messages are handled by admin_default.php layout ?>

    <form action="<?php echo $action_url; ?>" method="POST" class="needs-validation" novalidate>
        <div class="card">
            <div class="card-header">
                <?php echo __('roles_form_section_details', 'Role Details'); ?>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="nom"><?php echo __('roles_form_label_name', 'Role Name'); ?>:</label>
                    <input type="text" id="nom" name="nom" class="form-control <?php echo !empty($form_data['errors']['nom']) ? 'is-invalid' : ''; ?>"
                           value="<?php echo htmlspecialchars($form_data['nom'] ?? ''); ?>"
                           <?php echo $isSystemRoleAndEditMode ? 'readonly' : 'required'; ?>>
                    <?php if ($isSystemRoleAndEditMode): ?>
                        <small class="form-text text-muted"><?php echo __('roles_form_note_system_name_readonly', 'Name of system roles cannot be changed.'); ?></small>
                    <?php endif; ?>
                    <?php if (!empty($form_data['errors']['nom'])): ?>
                        <div class="invalid-feedback"><?php echo htmlspecialchars($form_data['errors']['nom']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="description"><?php echo __('roles_form_label_description', 'Description'); ?>:</label>
                    <textarea id="description" name="description" class="form-control" rows="3"><?php echo htmlspecialchars($form_data['description'] ?? ''); ?></textarea>
                </div>

                <?php if ($mode == 'add' && Auth::can('manage_system_roles_est_systeme')): // SuperAdmin only feature, not for typical admin use ?>
                <!--
                <div class="form-group form-check">
                    <input type="checkbox" class="form-check-input" id="est_systeme" name="est_systeme" value="1" <?php echo !empty($form_data['est_systeme']) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="est_systeme"><?php echo __('roles_form_label_system_role', 'Is System Role?'); ?></label>
                    <small class="form-text text-muted"><?php echo __('roles_form_note_system_role', 'System roles have special protections (e.g., cannot be deleted). This is usually set by developers.'); ?></small>
                </div>
                -->
                <?php elseif ($mode == 'edit' && isset($form_data['est_systeme']) && $form_data['est_systeme'] == 1): ?>
                     <p><strong><?php echo __('roles_form_is_system_role_text', 'This is a system role and has some restrictions on modification.'); ?></strong></p>
                <?php endif; ?>
            </div>
        </div>

        <?php if (Auth::can('assign_permissions_to_role')): // Check if user can assign permissions ?>
        <div class="card mt-4">
            <div class="card-header">
                <?php echo __('roles_form_assign_permissions_title', 'Assign Permissions'); ?>
            </div>
            <div class="card-body">
                <?php if (empty($groupedPermissions)): ?>
                    <p><?php echo __('permissions_none_defined', 'No permissions have been defined in the system yet.'); ?></p>
                <?php else: ?>
                    <div class="permissions-grid row">
                        <?php foreach ($groupedPermissions as $groupe => $permissionsInGroup): ?>
                            <fieldset class="col-md-6 col-lg-4 mb-3">
                                <legend class="h6" style="font-size: 1em; border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom:10px;"><?php echo $groupe; ?></legend>
                                <?php foreach ($permissionsInGroup as $permission): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permissions[]"
                                           id="perm_<?php echo $permission->id; ?>"
                                           value="<?php echo $permission->id; ?>"
                                           <?php echo (in_array($permission->id, $role_permissions_ids)) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="perm_<?php echo $permission->id; ?>">
                                        <?php echo htmlspecialchars($permission->nom); ?>
                                        <?php if(!empty($permission->description)): ?>
                                            <small class="text-muted d-block"><?= htmlspecialchars($permission->description) ?></small>
                                        <?php endif; ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </fieldset>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; // end permission check for assigning permissions ?>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">
                <?php echo ($mode == 'edit' ? __('global_save_button', 'Save Changes') : __('global_add_button', 'Add Role')); ?>
            </button>
            <a href="<?php echo URL_ROOT; ?>/admin/roles" class="btn btn-secondary">
                <?php echo __('global_cancel_button', 'Cancel'); ?>
            </a>
        </div>
    </form>
</div>

<style>
/* .permissions-grid fieldset { margin-bottom:15px; padding:10px; border:1px solid #eee; border-radius: 4px;} */
/* .permissions-grid fieldset legend { font-size: 1em; font-weight: bold; } */
/* .permissions-grid .form-check { margin-bottom: 5px; } */
/* .permissions-grid .form-check-label small { font-weight: normal; } */
</style>
