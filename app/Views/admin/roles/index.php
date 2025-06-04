<?php
// $data['roles']
// $data['title']
// All text should use __()
// This view is rendered by Admin\RolesController@index, using 'admin_default' layout
?>
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-9">
            <h1><?php echo htmlspecialchars($title ?? __('roles_title_list_default', 'Roles Management')); ?></h1>
        </div>
        <div class="col-md-3 text-right">
            <?php if (auth_can('create_role')): // Check permission before showing button ?>
            <a href="<?php echo URL_ROOT; ?>/admin/roles/add" class="btn btn-primary">
                <i class="fas fa-plus"></i> <?php echo __('roles_add_new_button', 'Add New Role'); ?>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php // Flash messages are typically handled by the layout (admin_default.php) ?>

    <div class="card">
        <div class="card-header">
            <?php echo __('roles_list_header', 'List of Roles'); ?>
        </div>
        <div class="card-body">
            <?php if (!empty($roles)): ?>
            <table class="table table-striped table-bordered table-hover">
                <thead class="thead-light">
                    <tr>
                        <th><?php echo __('roles_col_name', 'Name'); ?></th>
                        <th><?php echo __('roles_col_description', 'Description'); ?></th>
                        <th><?php echo __('roles_col_system_role', 'System Role?'); ?></th>
                        <th><?php echo __('roles_col_actions', 'Actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($roles as $role): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($role->nom); ?></td>
                        <td><?php echo htmlspecialchars($role->description ?? ''); ?></td>
                        <td>
                            <?php if ($role->est_systeme): ?>
                                <span class="badge badge-success"><?php echo __('global_yes', 'Yes'); ?></span>
                            <?php else: ?>
                                <span class="badge badge-secondary"><?php echo __('global_no', 'No'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="action-buttons">
                            <?php if (auth_can('edit_role')): // Check permission ?>
                                <a href="<?php echo URL_ROOT; ?>/admin/roles/edit/<?php echo $role->id; ?>" class="btn btn-sm btn-warning" title="<?php echo __('global_edit_button', 'Edit'); ?>">
                                    <i class="fas fa-edit"></i>
                                </a>
                            <?php endif; ?>

                            <?php if (!$role->est_systeme && auth_can('delete_role')): // System roles cannot be deleted, also check permission ?>
                            <form method="POST" action="<?php echo URL_ROOT; ?>/admin/roles/delete/<?php echo $role->id; ?>" style="display:inline;" onsubmit="return confirm('<?php echo __('global_confirm_delete_generic', 'Are you sure you want to delete this item?'); ?>')">
                                <button type="submit" class="btn btn-sm btn-danger" title="<?php echo __('global_delete_button', 'Delete'); ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p><?php echo __('roles_no_roles_found', 'No roles found. Please add one.'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Make sure Font Awesome is loaded in admin_default.php or here -->
<!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css"> -->
<style>
/* Minimal style for .link-button if not using a global CSS class for this */
/* This was in the prompt, but a btn class is better. Keep for now if needed by other views. */
/* .link-button { background:none;border:none;color:blue;text-decoration:underline;cursor:pointer;padding:0;font-family:inherit;font-size:inherit; } */
</style>
