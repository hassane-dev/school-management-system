<?php
// Expected data from ClassesController:
// $classes (array of classe objects)
// $title (string)
// $filters (array: search, niveau, cycle)
// $currentPage, $totalPages, $totalClasses, $perPage
// $orderBy, $orderDir
// $distinctNiveaux, $distinctCycles (arrays of strings for filter dropdowns)

$classes = $data['classes'] ?? [];
$title = $data['title'] ?? __('classes_title_list_default', 'Classes List');
$filters = $data['filters'] ?? ['search' => '', 'niveau' => '', 'cycle' => ''];
$currentPage = $data['currentPage'] ?? 1;
$totalPages = $data['totalPages'] ?? 1;
$orderBy = $data['orderBy'] ?? 'cycle, niveau, nom';
$orderDir = $data['orderDir'] ?? 'ASC';
$distinctNiveaux = $data['distinctNiveaux'] ?? [];
$distinctCycles = $data['distinctCycles'] ?? [];
$totalClasses = $data['totalClasses'] ?? 0;

// Build query strings for sorting and pagination to preserve filters
$baseQuery = $_GET; // Start with all current GET params
unset($baseQuery['orderBy'], $baseQuery['orderDir'], $baseQuery['page']); // Remove these for new sort/page links
$queryStringForSort = http_build_query($baseQuery);

unset($baseQuery['orderBy'], $baseQuery['orderDir']); // Keep filters for pagination
$queryStringForPagination = http_build_query($baseQuery);

?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-9">
            <h1><?php echo htmlspecialchars($title); ?></h1>
        </div>
        <div class="col-md-3 text-right">
            <?php if (auth_can('create_classe')): ?>
                <a href="<?php echo URL_ROOT; ?>/admin/classes/add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> <?php echo __('classes_add_new_button', 'Add New Class'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><?php echo __('classes_filter_title', 'Filters'); ?></div>
        <div class="card-body">
            <form method="GET" action="<?php echo URL_ROOT; ?>/admin/classes/index" class="filter-form">
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label for="search"><?php echo __('classes_filter_search_label', 'Search (Name, Level, Cycle)'); ?>:</label>
                        <input type="text" name="search" id="search" class="form-control" value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>" placeholder="<?php echo __('classes_filter_search_placeholder', 'Enter keyword...'); ?>">
                    </div>
                     <div class="col-md-3 form-group">
                        <label for="filter_cycle"><?php echo __('classes_filter_cycle_label', 'Cycle'); ?>:</label>
                        <select name="cycle" id="filter_cycle" class="form-control">
                            <option value=""><?php echo __('global_all_cycles', 'All Cycles'); ?></option>
                            <?php foreach($distinctCycles as $cycle): ?>
                                <option value="<?php echo htmlspecialchars($cycle); ?>" <?php echo (($filters['cycle'] ?? '') === $cycle) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(ucfirst($cycle)); // Or use __("cycle_".$cycle) if types are translatable keys ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="filter_niveau"><?php echo __('classes_filter_niveau_label', 'Level'); ?>:</label>
                        <select name="niveau" id="filter_niveau" class="form-control">
                            <option value=""><?php echo __('global_all_niveaux', 'All Levels'); ?></option>
                            <?php foreach($distinctNiveaux as $niveau): ?>
                                <option value="<?php echo htmlspecialchars($niveau); ?>" <?php echo (($filters['niveau'] ?? '') === $niveau) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(ucfirst($niveau)); // Or use __("niveau_".$niveau) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 form-group d-flex align-items-end">
                        <button type="submit" class="btn btn-info mr-2"><?php echo __('classes_filter_button', 'Filter'); ?></button>
                        <a href="<?php echo URL_ROOT; ?>/admin/classes/index" class="btn btn-secondary"><?php echo __('classes_filter_clear_button', 'Clear'); ?></a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <p><?php echo sprintf(__('classes_total_found', 'Total classes found: %d'), $totalClasses); ?></p>

    <?php if (!empty($classes)): ?>
    <div class="table-responsive">
        <table class="table table-striped table-bordered table-hover">
            <thead class="thead-light">
                <tr>
                    <?php
                        $sortable_columns = [
                            'nom' => __('classes_col_nom', 'Name'),
                            'cycle' => __('classes_col_cycle', 'Cycle'),
                            'niveau' => __('classes_col_niveau', 'Level'),
                            'salle_par_defaut' => __('classes_col_salle', 'Default Room'),
                            'capacite' => __('classes_col_capacite', 'Capacity')
                        ];
                    ?>
                    <?php foreach($sortable_columns as $col_key => $col_val):
                        $newOrderDir = ($orderBy == $col_key && $orderDir == 'ASC') ? 'DESC' : 'ASC';
                        // Construct query string for sorting, preserving existing filters
                        $sortQuery = $queryStringForSort . '&orderBy=' . $col_key . '&orderDir=' . $newOrderDir;
                    ?>
                        <th><a href="<?php echo URL_ROOT; ?>/admin/classes/index?<?php echo $sortQuery; ?>"><?php echo $col_val; ?></a></th>
                    <?php endforeach; ?>
                    <th><?php echo __('classes_col_actions', 'Actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($classes as $classe): ?>
                <tr>
                    <td><?php echo htmlspecialchars($classe->nom); ?></td>
                    <td><?php echo htmlspecialchars($classe->cycle ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($classe->niveau ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($classe->salle_par_defaut ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($classe->capacite ?? ''); ?></td>
                    <td class="action-buttons">
                        <?php if (auth_can('edit_classe')): ?>
                            <a href="<?php echo URL_ROOT; ?>/admin/classes/edit/<?php echo $classe->id; ?>" class="btn btn-sm btn-warning" title="<?php echo __('global_edit_button', 'Edit'); ?>"><i class="fas fa-edit"></i></a>
                        <?php endif; ?>
                        <?php if (auth_can('delete_classe')): ?>
                            <form method="POST" action="<?php echo URL_ROOT; ?>/admin/classes/delete/<?php echo $classe->id; ?>" style="display:inline;" onsubmit="return confirm('<?php echo __('global_confirm_delete_generic', 'Are you sure you want to delete this item?'); ?>')">
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
                    <li class="page-item"><a class="page-link" href="<?php echo URL_ROOT; ?>/admin/classes/index?<?php echo $queryStringForPagination . '&page=' . ($currentPage - 1); ?>"><?php echo __('pagination_previous', '&laquo; Previous'); ?></a></li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo ($i == $currentPage) ? 'active' : ''; ?>">
                        <a class="page-link" href="<?php echo URL_ROOT; ?>/admin/classes/index?<?php echo $queryStringForPagination . '&page=' . $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <li class="page-item"><a class="page-link" href="<?php echo URL_ROOT; ?>/admin/classes/index?<?php echo $queryStringForPagination . '&page=' . ($currentPage + 1); ?>"><?php echo __('pagination_next', 'Next &raquo;'); ?></a></li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>

    <?php else: ?>
        <p><?php echo __('classes_no_classes_found', 'No classes found matching your criteria.'); ?></p>
    <?php endif; ?>
</div>
