<?php
// Expected data:
// $data['user'] (object: the teacher being managed)
// $data['userCurrentRoles'] (array of role objects the user currently has)
// $data['assignableRoles'] (array of role objects the user does not currently have)
// $data['page_title']

$user = $data['user'] ?? null;
$userCurrentRoles = $data['userCurrentRoles'] ?? [];
$assignableRoles = $data['assignableRoles'] ?? []; // Roles not yet assigned to user
$page_title = $data['page_title'] ?? __('enseignants.manage_accreditations_default_title', 'Manage Accreditations');

if (!$user) {
    echo "<p>" . __('users.user_not_found', 'User not found.') . "</p>";
    // Optionally include a link back or further instructions
    return; // Stop rendering if no user
}
?>

<div class="container-fluid mt-4">
    <div class="row mb-3">
        <div class="col">
            <h2><?php echo htmlspecialchars($page_title); ?></h2>
            <p><?php echo __('enseignants.managing_accreditations_for', 'Managing accreditations for: ') . '<strong>' . htmlspecialchars($user->nom) . '</strong>'; ?></p>
        </div>
    </div>

    <div class="row">
        <!-- Current Accreditations Section -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4><?php echo __('enseignants.current_accreditations', 'Current Accreditations'); ?></h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($userCurrentRoles)): ?>
                        <ul class="list-group">
                            <?php foreach ($userCurrentRoles as $role): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?php echo htmlspecialchars($role->nom); ?>
                                    <a href="<?php echo base_url('admin/enseignant/removeAccreditation/' . $user->id . '/' . $role->id); ?>"
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('<?php echo __('enseignants.confirm_remove_accreditation', 'Are you sure you want to remove this accreditation?'); ?>');"
                                       title="<?php echo __('enseignants.remove_accreditation_button', 'Remove Accreditation'); ?>">
                                        <i class="fas fa-trash-alt"></i> <?php echo __('remove_btn', 'Remove'); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p><?php echo __('enseignants.user_has_no_current_accreditations', 'This user currently has no accreditations.'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Add New Accreditation Section -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4><?php echo __('enseignants.add_new_accreditation', 'Add New Accreditation'); ?></h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($assignableRoles)): ?>
                        <form action="<?php echo base_url('admin/enseignant/addAccreditation/' . $user->id); ?>" method="POST">
                            <div class="form-group">
                                <label for="role_id"><?php echo __('enseignants.select_role_to_add', 'Select Role to Add:'); ?></label>
                                <select name="role_id" id="role_id" class="form-control" required>
                                    <option value=""><?php echo __('enseignants.please_select_role', '-- Please select a role --'); ?></option>
                                    <?php foreach ($assignableRoles as $role): ?>
                                        <option value="<?php echo htmlspecialchars($role->id); ?>">
                                            <?php echo htmlspecialchars($role->nom); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-plus-circle"></i> <?php echo __('enseignants.add_accreditation_button', 'Add Accreditation'); ?>
                            </button>
                        </form>
                    <?php else: ?>
                        <p><?php echo __('enseignants.user_all_roles_assigned', 'This user has all available roles assigned or no roles available to assign.'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <a href="<?php echo base_url('admin/enseignant'); ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> <?php echo __('back_to_teachers_list_btn', 'Back to Teachers List'); ?>
        </a>
    </div>
</div>
