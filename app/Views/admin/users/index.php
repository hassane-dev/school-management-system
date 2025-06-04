<?php
// Expected data from UsersController:
// $users (array of user objects, each potentially with a ->roles array of role names)
// $title (string)
// $filters (array: search, statut_compte, role_id)
// $currentPage, $totalPages, $perPage, $totalUsers
// $orderBy, $orderDir
// $accountStatuses (array of strings)
// $allRoles (array of role objects for filter dropdown)

// Use AppSettings helpers for date/time formats
$dateFormat = AppSettings::getDateFormat('Y-m-d');
$timeFormat = AppSettings::getTimeFormat('H:i:s');
$dateTimeFormat = $dateFormat . ' ' . $timeFormat;

// Ensure variables are set to avoid errors if not passed (though controller should pass them)
$users = $data['users'] ?? [];
$title = $data['title'] ?? __('users_title_list_default', 'User Management');
$filters = $data['filters'] ?? ['search' => '', 'statut_compte' => '', 'role_id' => ''];
$currentPage = $data['currentPage'] ?? 1;
$totalPages = $data['totalPages'] ?? 1;
$orderBy = $data['orderBy'] ?? 'u.nom';
$orderDir = $data['orderDir'] ?? 'ASC';
$accountStatuses = $data['accountStatuses'] ?? [];
$allRoles = $data['allRoles'] ?? [];
$totalUsers = $data['totalUsers'] ?? 0;

?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-9">
            <h1><?php echo htmlspecialchars($title); ?></h1>
        </div>
        <div class="col-md-3 text-right">
            <?php if (auth_can('create_user')): ?>
                <a href="<?php echo URL_ROOT; ?>/admin/users/add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> <?php echo __('users_add_new_button', 'Add New User'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php // Flash messages are handled by admin_default.php layout ?>

    <div class="card mb-4">
        <div class="card-header"><?php echo __('users_filter_title', 'Filters'); ?></div>
        <div class="card-body">
            <form method="GET" action="<?php echo URL_ROOT; ?>/admin/users/index" class="filter-form">
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label for="search"><?php echo __('users_filter_search_label', 'Search'); ?>:</label>
                        <input type="text" name="search" id="search" class="form-control" value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>" placeholder="<?php echo __('users_filter_search_placeholder', 'Name or email...'); ?>">
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="statut_compte"><?php echo __('users_filter_status_label', 'Status'); ?>:</label>
                        <select name="statut_compte" id="statut_compte" class="form-control">
                            <option value=""><?php echo __('global_all_statuses', 'All Statuses'); ?></option>
                            <?php foreach($accountStatuses as $status): ?>
                                <option value="<?php echo $status; ?>" <?php echo (($filters['statut_compte'] ?? '') === $status) ? 'selected' : ''; ?>>
                                    <?php echo __("user_status_$status", ucfirst(str_replace('_', ' ', $status))); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php /* Role filter - uncomment if $filters['role_id'] and JOINs are implemented in UserModel::getAll
                    <div class="col-md-3 form-group">
                        <label for="filter_role_id"><?= __('users_filter_role_label', 'Role') ?>:</label>
                        <select name="role_id" id="filter_role_id" class="form-control">
                            <option value=""><?= __('global_all_roles', 'All Roles') ?></option>
                            <?php foreach($allRoles as $role): ?>
                                <option value="<?= $role->id ?>" <?= (($filters['role_id'] ?? '') == $role->id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($role->nom) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    */ ?>
                    <div class="col-md-2 form-group d-flex align-items-end">
                        <button type="submit" class="btn btn-info mr-2"><?php echo __('users_filter_button', 'Filter'); ?></button>
                        <a href="<?php echo URL_ROOT; ?>/admin/users/index" class="btn btn-secondary"><?php echo __('users_filter_clear_button', 'Clear'); ?></a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <p><?php echo sprintf(__('users_total_found', 'Total users found: %d'), $totalUsers); ?></p>

    <?php if (!empty($users)): ?>
    <div class="table-responsive">
        <table class="table table-striped table-bordered table-hover">
            <thead class="thead-light">
                <tr>
                    <?php
                        $columns = [
                            'u.nom' => __('users_col_name', 'Name'),
                            'u.email' => __('users_col_email', 'Email'),
                            // 'roles' is not a DB column, handled separately
                            'u.statut_compte' => __('users_col_status', 'Status'),
                            'u.date_derniere_connexion' => __('users_col_last_login', 'Last Login')
                        ];
                    ?>
                    <?php foreach($columns as $col_key => $col_val):
                        $newOrderDir = ($orderBy == $col_key && $orderDir == 'ASC') ? 'DESC' : 'ASC';
                    ?>
                        <th><a href="<?php echo URL_ROOT; ?>/admin/users/index?<?php echo http_build_query(array_merge($_GET, ['orderBy' => $col_key, 'orderDir' => $newOrderDir, 'page' => 1])); ?>"><?php echo $col_val; ?></a></th>
                    <?php endforeach; ?>
                    <th><?php echo __('users_col_roles', 'Roles'); ?></th>
                    <th><?php echo __('users_col_actions', 'Actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($users as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user->nom); ?></td>
                    <td><?php echo htmlspecialchars($user->email); ?></td>
                    <td><?php echo !empty($user->roles) ? htmlspecialchars(implode(', ', $user->roles)) : __('global_none', 'None'); ?></td>
                    <td><?php echo __("user_status_" . $user->statut_compte, ucfirst(str_replace('_', ' ', $user->statut_compte))); ?></td>
                    <td><?php echo $user->date_derniere_connexion ? date($dateTimeFormat, strtotime($user->date_derniere_connexion)) : __('global_never', 'Never'); ?></td>
                    <td class="action-buttons">
                        <?php if (auth_can('edit_user')): ?>
                            <a href="<?php echo URL_ROOT; ?>/admin/users/edit/<?php echo $user->id; ?>" class="btn btn-sm btn-warning" title="<?php echo __('global_edit_button', 'Edit'); ?>"><i class="fas fa-edit"></i></a>
                        <?php endif; ?>
                        <?php
                        $isSelf = (auth_id() === $user->id);
                        $isSuperAdminUser = !empty($user->roles) && in_array('SuperAdmin', $user->roles);
                        $canDelete = auth_can('delete_user') && !$isSelf && !$isSuperAdminUser;
                        if ($canDelete):
                        ?>
                            <form method="POST" action="<?php echo URL_ROOT; ?>/admin/users/delete/<?php echo $user->id; ?>" style="display:inline;" onsubmit="return confirm('<?php echo __('global_confirm_delete_user', ['name' => htmlspecialchars($user->nom)], 'Are you sure you want to delete user {name}?'); ?>')">
                                <button type="submit" class="btn btn-sm btn-danger" title="<?php echo __('global_delete_button', 'Delete'); ?>"><i class="fas fa-trash"></i></button>
                            </form>
                        <?php endif; ?>
                        <?php if (auth_can('reactivate_user_account')): ?>
                            <form method="POST" action="<?php echo URL_ROOT; ?>/admin/users/reactivate/<?php echo $user->id; ?>" style="display:inline;"
                                  onsubmit="return confirm('<?php echo __('users_confirm_reactivate', ['name' => htmlspecialchars($user->nom)], 'Are you sure you want to reactivate user {name} for the current academic year? This will set their status to active and ensure/assign a role for the current year.'); ?>')">
                                <button type="submit" class="btn btn-sm btn-success" title="<?php echo __('users_reactivate_button_title', 'Reactivate for Current Year'); ?>">
                                    <i class="fas fa-user-check"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php // Pagination
    if ($totalPages > 1): ?>
        <nav class="pagination-nav mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($currentPage > 1): ?>
                    <li class="page-item"><a class="page-link" href="<?php echo URL_ROOT; ?>/admin/users/index?<?php echo http_build_query(array_merge($_GET, ['page' => $currentPage - 1])); ?>"><?php echo __('pagination_previous', '&laquo; Previous'); ?></a></li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo ($i == $currentPage) ? 'active' : ''; ?>">
                        <a class="page-link" href="<?php echo URL_ROOT; ?>/admin/users/index?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <li class="page-item"><a class="page-link" href="<?php echo URL_ROOT; ?>/admin/users/index?<?php echo http_build_query(array_merge($_GET, ['page' => $currentPage + 1])); ?>"><?php echo __('pagination_next', 'Next &raquo;'); ?></a></li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>

    <?php else: ?>
        <p><?php echo __('users_no_users_found', 'No users found matching your criteria.'); ?></p>
    <?php endif; ?>
</div>

<!-- Ensure Font Awesome is loaded in admin_default.php -->
<style>
/* .filter-form fieldset { margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; } */
/* .filter-form legend { font-weight: bold; font-size: 1.1em; margin-bottom: 10px; } */
/* .filter-controls { display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; } */
/* .filter-controls > div { display: flex; flex-direction: column; } */
/* .pagination-nav ul li.active a { z-index: 1; } */ /* Bootstrap-like active state */
</style>
