<?php
// Expected data: $data['teachers'] (array of teacher user objects)
// Each teacher object should ideally have a property like ->accreditations (array of role names)
// $data['page_title']

$teachers = $data['teachers'] ?? [];
$page_title = $data['page_title'] ?? __('enseignants.list_title_default', 'Teachers List');
?>

<div class="container-fluid mt-4">
    <div class="row mb-3">
        <div class="col">
            <h2><?php echo htmlspecialchars($page_title); ?></h2>
        </div>
        <!-- Optional: Add button if direct user creation is from here and targets a specific "teacher" role -->
    </div>

    <div class="card">
        <div class="card-header">
            <?php echo __('enseignants.teachers_section_title', 'Registered Teachers'); ?>
        </div>
        <div class="card-body">
            <?php if (!empty($teachers)): ?>
                <table class="table table-striped table-bordered table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th><?php echo __('enseignants.name', 'Name'); ?></th>
                            <th><?php echo __('enseignants.email', 'Email'); ?></th>
                            <th><?php echo __('enseignants.accreditations', 'Accreditations (Roles)'); ?></th>
                            <th><?php echo __('users.actions', 'Actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teachers as $teacher): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($teacher->nom); ?></td>
                                <td><?php echo htmlspecialchars($teacher->email); ?></td>
                                <td>
                                    <?php
                                    if (!empty($teacher->accreditations) && is_array($teacher->accreditations)) {
                                        echo htmlspecialchars(implode(', ', $teacher->accreditations));
                                    } else {
                                        echo __('enseignants.no_accreditations_found', 'No accreditations found');
                                    }
                                    ?>
                                </td>
                                <td class="action-buttons">
                                    <a href="<?php echo base_url('admin/enseignant/manageAccreditations/' . $teacher->id); ?>" class="btn btn-sm btn-primary" title="<?php echo __('enseignants.manage_accreditations_button', 'Manage Accreditations'); ?>">
                                        <i class="fas fa-user-shield"></i> <?php echo __('enseignants.manage_accreditations_short_button', 'Accreditations'); ?>
                                    </a>
                                    <a href="<?php echo base_url('admin/enseignement/index?utilisateur_id=' . $teacher->id); ?>" class="btn btn-sm btn-info" title="<?php echo __('enseignants.view_assignments_button', 'View Assignments'); ?>">
                                        <i class="fas fa-chalkboard-teacher"></i> <?php echo __('enseignants.view_assignments_short_button', 'Assignments'); ?>
                                    </a>
                                    <!-- Link to general user edit if needed -->
                                    <a href="<?php echo base_url('admin/users/edit/' . $teacher->id); ?>" class="btn btn-sm btn-secondary" title="<?php echo __('users.edit_user_profile_button', 'Edit Profile'); ?>">
                                        <i class="fas fa-user-edit"></i> <?php echo __('users.edit_profile_short_button', 'Profile'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?php echo __('enseignants.no_teachers_found', 'No users with teacher accreditations found.'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>
