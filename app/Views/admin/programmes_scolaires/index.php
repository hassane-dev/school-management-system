<?php
// Expected: $title, $programmes, $filters, $currentPage, $totalPages, $orderBy, $orderDir,
//           $anneesAcademiques, $statutsList, $totalProgrammes
$programmes = $data['programmes'] ?? [];
$title = $data['title'] ?? __('ps_title_list_default', 'Curricula List');
$filters = $data['filters'] ?? [];
$currentPage = $data['currentPage'] ?? 1;
$totalPages = $data['totalPages'] ?? 1;
$orderBy = $data['orderBy'] ?? 'aa.date_debut DESC, ps.nom';
$orderDir = $data['orderDir'] ?? 'ASC';
$anneesAcademiques = $data['anneesAcademiques'] ?? [];
$statutsList = $data['statutsList'] ?? [];
$totalProgrammes = $data['totalProgrammes'] ?? 0;

$baseQueryForLinks = $_GET;
unset($baseQueryForLinks['url']);
$queryStringForSort = http_build_query(array_diff_key($baseQueryForLinks, ['orderBy' => '', 'orderDir' => '', 'page' => '']));
$queryStringForPagination = http_build_query(array_diff_key($baseQueryForLinks, ['page' => '']));
?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-9">
            <h1><?php echo htmlspecialchars($title); ?></h1>
        </div>
        <div class="col-md-3 text-right">
            <?php if (auth_can('create_programme_scolaire')): ?>
                <a href="<?php echo URL_ROOT; ?>/admin/programmesscolaires/add_programme" class="btn btn-primary">
                    <i class="fas fa-plus"></i> <?php echo __('ps_add_new_button', 'Add New Curriculum'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php // Flash messages are handled by admin_default.php layout ?>

    <div class="card mb-4">
        <div class="card-header"><?php echo __('ps_filter_title', 'Filters'); ?></div>
        <div class="card-body">
            <form method="GET" action="<?php echo URL_ROOT; ?>/admin/programmesscolaires/index" class="filter-form">
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label for="search"><?php echo __('ps_filter_search_label', 'Search (Name/Desc.)'); ?>:</label>
                        <input type="text" name="search" id="search" class="form-control" value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>" placeholder="<?php echo __('ps_filter_search_placeholder', 'Enter keyword...'); ?>">
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="annee_academique_id_filter"><?php echo __('ps_filter_annee_label', 'Academic Year'); ?>:</label>
                        <select name="annee_academique_id" id="annee_academique_id_filter" class="form-control">
                            <option value=""><?php echo __('global_all_years', 'All Years'); ?></option>
                            <?php foreach($anneesAcademiques as $annee): ?>
                                <option value="<?php echo $annee->id; ?>" <?php echo (($filters['annee_academique_id'] ?? '') == $annee->id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($annee->libelle); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="statut_filter"><?php echo __('ps_filter_statut_label', 'Status'); ?>:</label>
                        <select name="statut" id="statut_filter" class="form-control">
                            <option value=""><?php echo __('global_all_statuses', 'All Statuses'); ?></option>
                            <?php foreach($statutsList as $statut): ?>
                                <option value="<?php echo htmlspecialchars($statut); ?>" <?php echo (($filters['statut'] ?? '') === $statut) ? 'selected' : ''; ?>>
                                    <?php echo __("ps_statut_$statut", ucfirst($statut)); // e.g. ps_statut_brouillon ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 form-group d-flex align-items-end">
                        <button type="submit" class="btn btn-info mr-2"><?php echo __('ps_filter_button', 'Filter'); ?></button>
                        <a href="<?php echo URL_ROOT; ?>/admin/programmesscolaires/index" class="btn btn-secondary"><?php echo __('ps_filter_clear_button', 'Clear'); ?></a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <p><?php echo sprintf(__('ps_total_found', 'Total curricula found: %d'), $totalProgrammes); ?></p>

    <?php if (!empty($programmes)): ?>
    <div class="table-responsive">
        <table class="table table-striped table-bordered table-hover">
            <thead class="thead-light">
                <tr>
                    <?php
                        $sortable_columns = [
                            'ps.nom' => __('ps_col_nom', 'Name'),
                            'aa.libelle' => __('ps_col_annee', 'Academic Year'), // Using alias from model for sorting
                            'ps.statut' => __('ps_col_statut', 'Status')
                        ];
                    ?>
                    <?php foreach($sortable_columns as $col_key => $col_val):
                        $newOrderDir = ($orderBy == $col_key && $orderDir == 'ASC') ? 'DESC' : 'ASC';
                        $sortQuery = $queryStringForSort . '&orderBy=' . $col_key . '&orderDir=' . $newOrderDir . '&page=1';
                    ?>
                        <th><a href="<?php echo URL_ROOT; ?>/admin/programmesscolaires/index?<?php echo $sortQuery; ?>"><?php echo $col_val; ?></a></th>
                    <?php endforeach; ?>
                    <th><?php echo __('ps_col_description', 'Description'); ?></th>
                    <th><?php echo __('ps_col_actions', 'Actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($programmes as $prog): ?>
                <tr>
                    <td><?php echo htmlspecialchars($prog->nom); ?></td>
                    <td><?php echo htmlspecialchars($prog->annee_academique_libelle); ?></td>
                    <td><?php echo __("ps_statut_" . $prog->statut, ucfirst($prog->statut)); ?></td>
                    <td><?php echo nl2br(htmlspecialchars(substr($prog->description ?? '', 0, 100))) . (strlen($prog->description ?? '') > 100 ? '...' : ''); ?></td>
                    <td class="action-buttons">
                        <?php if (auth_can('manage_programme_details')): ?>
                            <a href="<?php echo URL_ROOT; ?>/admin/programmesscolaires/manage_details/<?php echo $prog->id; ?>" class="btn btn-sm btn-info" title="<?php echo __('ps_action_manage_details', 'Manage Details'); ?>"><i class="fas fa-list-alt"></i></a>
                        <?php endif; ?>
                        <?php if (auth_can('edit_programme_scolaire')): ?>
                            <a href="<?php echo URL_ROOT; ?>/admin/programmesscolaires/edit_programme/<?php echo $prog->id; ?>" class="btn btn-sm btn-warning" title="<?php echo __('global_edit_button', 'Edit'); ?>"><i class="fas fa-edit"></i></a>
                        <?php endif; ?>
                        <?php if (auth_can('delete_programme_scolaire')): ?>
                            <form method="POST" action="<?php echo URL_ROOT; ?>/admin/programmesscolaires/delete_programme/<?php echo $prog->id; ?>" style="display:inline;" onsubmit="return confirm('<?php echo __('global_confirm_delete_generic', 'Are you sure you want to delete this item?'); ?>')">
                                <button type="submit" class="btn btn-sm btn-danger" title="<?php echo __('global_delete_button', 'Delete'); ?>"><i class="fas fa-trash"></i></button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php // Pagination (Adapted from users/index.php)
    if ($totalPages > 1): ?>
        <nav class="pagination-nav mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($currentPage > 1): ?>
                    <li class="page-item"><a class="page-link" href="<?php echo URL_ROOT; ?>/admin/programmesscolaires/index?<?php echo $queryStringForPagination . '&page=' . ($currentPage - 1); ?>"><?php echo __('pagination_previous', '&laquo; Previous'); ?></a></li>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo ($i == $currentPage) ? 'active' : ''; ?>">
                        <a class="page-link" href="<?php echo URL_ROOT; ?>/admin/programmesscolaires/index?<?php echo $queryStringForPagination . '&page=' . $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($currentPage < $totalPages): ?>
                    <li class="page-item"><a class="page-link" href="<?php echo URL_ROOT; ?>/admin/programmesscolaires/index?<?php echo $queryStringForPagination . '&page=' . ($currentPage + 1); ?>"><?php echo __('pagination_next', 'Next &raquo;'); ?></a></li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
    <?php else: ?>
        <p><?php echo __('ps_no_programmes_found', 'No curricula found matching your criteria.'); ?></p>
    <?php endif; ?>
</div>
