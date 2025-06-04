<?php
// This view expects to be wrapped by layouts/main.php
// It expects $data['users'] to be an array of user objects (e.g., from Utilisateur_model->getAllUsersWithRoles())
// It also expects $data['roles'] for a potential filter or for add/edit modal (though form is separate)

$users = $data['users'] ?? []; // Ensure $users is defined
?>

<div class="container-fluid mt-4">
    <div class="row mb-3">
        <div class="col">
            <h2><?php echo __('users.user_management_title', 'User Management'); ?></h2>
        </div>
        <div class="col text-right">
            <a href="<?php echo base_url('admin/users/create'); ?>" class="btn btn-success">
                <i class="fas fa-plus"></i> <?php echo __('users.add_user_button', 'Add User'); ?>
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <?php echo __('users.user_list_title', 'User List'); ?>
        </div>
        <div class="card-body">
            <table class="table table-striped table-bordered table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>ID</th>
                        <th><?php echo __('users.name', 'Name'); ?></th>
                        <th><?php echo __('users.email', 'Email'); ?></th>
                        <th><?php echo __('users.role', 'Role'); ?></th>
                        <th><?php echo __('users.preferred_language', 'Preferred Lang.'); ?></th>
                        <th><?php echo __('users.date_creation', 'Date Created'); ?></th>
                        <th><?php echo __('users.actions', 'Actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user->id); ?></td>
                                <td><?php echo htmlspecialchars($user->nom); ?></td>
                                <td><?php echo htmlspecialchars($user->email); ?></td>
                                <td><?php echo htmlspecialchars($user->role_nom ?? __('users.role_not_assigned', 'Not Assigned')); ?></td>
                                <td><?php echo htmlspecialchars($user->langue_preferee); ?></td>
                                <td><?php echo htmlspecialchars( (new DateTime($user->date_creation))->format('Y-m-d H:i') ); ?></td>
                                <td class="action-buttons">
                                    <a href="<?php echo base_url('admin/users/edit/' . $user->id); ?>" class="btn btn-sm btn-warning" title="<?php echo __('users.edit_button', 'Edit'); ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php // Prevent deleting oneself, or the primary super_admin if applicable (logic should be in controller too) ?>
                                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $user->id): ?>
                                    <form action="<?php echo base_url('admin/users/delete/' . $user->id); ?>" method="POST" class="d-inline" onsubmit="return confirm('<?php echo __('users.confirm_delete', 'Are you sure you want to delete this user?'); ?>');">
                                        <button type="submit" class="btn btn-sm btn-danger" title="<?php echo __('users.delete_button', 'Delete'); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    <a href="<?php echo base_url('admin/users/reset_password/' . $user->id); ?>" class="btn btn-sm btn-info" title="<?php echo __('users.reset_password_button', 'Reset Password'); ?>">
                                        <i class="fas fa-key"></i>
                                    </a>
                                    <!-- More actions like view details, activate/deactivate -->
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center"><?php echo __('users.no_users_found', 'No users found.'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Font Awesome if not already in main.php for icons -->
<!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css"> -->
