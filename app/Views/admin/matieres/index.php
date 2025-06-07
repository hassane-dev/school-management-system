<?php
// Expected data from MatieresController:
// $matieres (array of subject objects)
// $title (string)
// $filters (array: search, type_matiere)
// $currentPage, $totalPages, $totalMatieres, $perPage
// $orderBy, $orderDir
// $distinctTypes (array of strings for filter dropdown)

$matieres = $data['matieres'] ?? [];
$title = $data['title'] ?? __('matieres_title_list_default', 'Subjects List');
$filters = $data['filters'] ?? ['search' => '', 'type_matiere' => ''];
$currentPage = $data['currentPage'] ?? 1;
$totalPages = $data['totalPages'] ?? 1;
$orderBy = $data['orderBy'] ?? 'nom';
$orderDir = $data['orderDir'] ?? 'ASC';
$distinctTypes = $data['distinctTypes'] ?? [];
$totalMatieres = $data['totalMatieres'] ?? 0;

$queryStringWithoutPage = http_build_query(array_merge($_GET, ['page' => null])); // For sort links
$queryStringWithoutSort = http_build_query(array_merge($_GET, ['orderBy' => null, 'orderDir' => null])); // For pagination links

?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-9">
            <h1><?php echo htmlspecialchars($title); ?></h1>
        </div>
        <div class="col-md-3 text-right">
            <?php if (auth_can('create_matiere')): ?>
                <a href="<?php echo URL_ROOT; ?>/admin/matieres/add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> <?php echo __('matieres_add_new_button', 'Add New Subject'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><?php echo __('matieres_filter_title', 'Filters'); ?></div>
        <div class="card-body">
            <form method="GET" action="<?php echo URL_ROOT; ?>/admin/matieres/index" class="filter-form">
                <div class="row">
                    <div class="col-md-5 form-group">
                        <label for="search"><?php echo __('matieres_filter_search_label', 'Search (Name/Code)'); ?>:</label>
                        <input type="text" name="search" id="search" class="form-control" value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>" placeholder="<?php echo __('matieres_filter_search_placeholder', 'Enter name or code...'); ?>">
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="type_matiere_filter"><?php echo __('matieres_filter_type_label', 'Type'); ?>:</label>
                        <select name="type_matiere" id="type_matiere_filter" class="form-control">
                            <option value=""><?php echo __('global_all_types', 'All Types'); ?></option>
                            <?php foreach($distinctTypes as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>" <?php echo (($filters['type_matiere'] ?? '') === $type) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(ucfirst($type)); // Or __("matiere_type_".$type) if you translate types ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 form-group d-flex align-items-end">
                        <button type="submit" class="btn btn-info mr-2"><?php echo __('matieres_filter_button', 'Filter'); ?></button>
                        <a href="<?php echo URL_ROOT; ?>/admin/matieres/index" class="btn btn-secondary"><?php echo __('matieres_filter_clear_button', 'Clear'); ?></a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <p><?php echo sprintf(__('matieres_total_found', 'Total subjects found: %d'), $totalMatieres); ?></p>

    <?php if (!empty($matieres)): ?>
    <div class="table-responsive">
        <table class="table table-striped table-bordered table-hover">
            <thead class="thead-light">
                <tr>
                    <?php
                        $sortable_columns = [
                            'nom' => __('matieres_col_nom', 'Name'),
                            'code' => __('matieres_col_code', 'Code'),
                            'type_matiere' => __('matieres_col_type', 'Type'),
                            'coefficient' => __('matieres_col_coeff', 'Coefficient')
                        ];
                    ?>
                    <?php foreach($sortable_columns as $col_key => $col_val):
                        $newOrderDir = ($orderBy == $col_key && $orderDir == 'ASC') ? 'DESC' : 'ASC';
                        $sortQuery = $queryStringWithoutPage . '&orderBy=' . $col_key . '&orderDir=' . $newOrderDir;
                    ?>
                        <th><a href="<?php echo URL_ROOT; ?>/admin/matieres/index?<?php echo $sortQuery; ?>"><?php echo $col_val; ?></a></th>
                    <?php endforeach; ?>
                    <th><?php echo __('matieres_col_actions', 'Actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($matieres as $matiere): ?>
                <tr>
                    <td><?php echo htmlspecialchars($matiere->nom); ?></td>
                    <td><?php echo htmlspecialchars($matiere->code ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($matiere->type_matiere ?? ''); ?></td>
                    <td><?php echo htmlspecialchars(number_format((float)($matiere->coefficient ?? 0), 2)); ?></td>
                    <td class="action-buttons">
                        <?php if (auth_can('edit_matiere')): ?>
                            <a href="<?php echo URL_ROOT; ?>/admin/matieres/edit/<?php echo $matiere->id; ?>" class="btn btn-sm btn-warning" title="<?php echo __('global_edit_button', 'Edit'); ?>"><i class="fas fa-edit"></i></a>
                        <?php endif; ?>
                        <?php if (auth_can('delete_matiere')): ?>
                            <form method="POST" action="<?php echo URL_ROOT; ?>/admin/matieres/delete/<?php echo $matiere->id; ?>" style="display:inline;" onsubmit="return confirm('<?php echo __('global_confirm_delete_generic', 'Are you sure you want to delete this item?'); ?>')">
                                <button type="submit" class="btn btn-sm btn-danger" title="<?php echo __('global_delete_button', 'Delete'); ?>"><i class="fas fa-trash"></i></button>
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
                    <li class="page-item"><a class="page-link" href="<?php echo URL_ROOT; ?>/admin/matieres/index?<?php echo $queryStringWithoutSort . '&page=' . ($currentPage - 1); ?>"><?php echo __('pagination_previous', '&laquo; Previous'); ?></a></li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo ($i == $currentPage) ? 'active' : ''; ?>">
                        <a class="page-link" href="<?php echo URL_ROOT; ?>/admin/matieres/index?<?php echo $queryStringWithoutSort . '&page=' . $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <li class="page-item"><a class="page-link" href="<?php echo URL_ROOT; ?>/admin/matieres/index?<?php echo $queryStringWithoutSort . '&page=' . ($currentPage + 1); ?>"><?php echo __('pagination_next', 'Next &raquo;'); ?></a></li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>

    <?php else: ?>
        <p><?php echo __('matieres_no_matieres_found', 'No subjects found matching your criteria.'); ?></p>
    <?php endif; ?>
</div>
