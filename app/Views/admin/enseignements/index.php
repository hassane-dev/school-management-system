<?php
// Expected data from EnseignementController:
// $assignments (array of assignment objects with joined data)
// $title (string)
// $teachers, $classes, $academicYears, $matieres (for filter dropdowns)
// $currentFilters (array of current GET params for repopulating filters)

$assignments = $data['assignments'] ?? [];
$title = $data['title'] ?? __('enseignements_title_list_default', 'Teaching Assignments');
$teachers = $data['teachers'] ?? [];
$classes = $data['classes'] ?? [];
$academicYears = $data['academicYears'] ?? [];
$matieres = $data['matieres'] ?? [];
$currentFilters = $data['currentFilters'] ?? [];
?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-9">
            <h1><?php echo htmlspecialchars($title); ?></h1>
        </div>
        <div class="col-md-3 text-right">
            <?php if (auth_can('create_enseignement')): ?>
                <a href="<?php echo URL_ROOT; ?>/admin/enseignements/add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> <?php echo __('enseignements_add_new_button', 'Add New Assignment'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><?php echo __('enseignements_filter_title', 'Filters'); ?></div>
        <div class="card-body">
            <form method="GET" action="<?php echo URL_ROOT; ?>/admin/enseignements/index" class="filter-form">
                <div class="row">
                    <div class="col-md-3 form-group">
                        <label for="filter_teacher_id"><?php echo __('enseignements_teacher', 'Teacher'); ?>:</label>
                        <select name="teacher_id" id="filter_teacher_id" class="form-control">
                            <option value=""><?php echo __('global_all_teachers', 'All Teachers'); ?></option>
                            <?php foreach($teachers as $teacher): ?>
                                <option value="<?php echo $teacher->id; ?>" <?php echo (($currentFilters['e.utilisateur_id'] ?? '') == $teacher->id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($teacher->nom); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="filter_class_id"><?php echo __('enseignements_class', 'Class'); ?>:</label>
                        <select name="class_id" id="filter_class_id" class="form-control">
                            <option value=""><?php echo __('global_all_classes', 'All Classes'); ?></option>
                            <?php foreach($classes as $classe): ?>
                                <option value="<?php echo $classe->id; ?>" <?php echo (($currentFilters['e.classe_id'] ?? '') == $classe->id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($classe->nom); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="filter_year_id"><?php echo __('enseignements_academic_year', 'Academic Year'); ?>:</label>
                        <select name="year_id" id="filter_year_id" class="form-control">
                            <option value=""><?php echo __('global_all_years', 'All Years'); ?></option>
                            <?php foreach($academicYears as $year): ?>
                                <option value="<?php echo $year->id; ?>" <?php echo (($currentFilters['e.annee_id'] ?? '') == $year->id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($year->libelle); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                     <div class="col-md-3 form-group">
                        <label for="filter_subject_id"><?php echo __('enseignements_subject', 'Subject'); ?>:</label>
                        <select name="subject_id" id="filter_subject_id" class="form-control">
                            <option value=""><?php echo __('global_all_subjects', 'All Subjects'); ?></option>
                            <?php foreach($matieres as $matiere): ?>
                                <option value="<?php echo $matiere->id; ?>" <?php echo (($currentFilters['e.matiere_id'] ?? '') == $matiere->id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($matiere->nom); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-12 form-group d-flex align-items-end">
                        <button type="submit" class="btn btn-info mr-2"><?php echo __('enseignements_filter_button', 'Filter'); ?></button>
                        <a href="<?php echo URL_ROOT; ?>/admin/enseignements/index" class="btn btn-secondary"><?php echo __('enseignements_filter_clear_button', 'Clear'); ?></a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if (!empty($assignments)): ?>
    <div class="table-responsive">
        <table class="table table-striped table-bordered table-hover">
            <thead class="thead-light">
                <tr>
                    <th><?php echo __('enseignements_teacher', 'Teacher'); ?></th>
                    <th><?php echo __('enseignements_subject', 'Subject'); ?></th>
                    <th><?php echo __('enseignements_class', 'Class'); ?></th>
                    <th><?php echo __('enseignements_academic_year', 'Academic Year'); ?></th>
                    <th><?php echo __('enseignements_actions', 'Actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($assignments as $assignment): ?>
                <tr>
                    <td><?php echo htmlspecialchars($assignment->enseignant_nom ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($assignment->matiere_nom ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($assignment->classe_nom ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($assignment->annee_libelle ?? 'N/A'); ?></td>
                    <td class="action-buttons">
                        <?php if (auth_can('edit_enseignement')): ?>
                            <a href="<?php echo URL_ROOT; ?>/admin/enseignements/edit/<?php echo $assignment->id; ?>" class="btn btn-sm btn-warning" title="<?php echo __('global_edit_button', 'Edit'); ?>"><i class="fas fa-edit"></i></a>
                        <?php endif; ?>
                        <?php if (auth_can('delete_enseignement')): ?>
                            <form method="POST" action="<?php echo URL_ROOT; ?>/admin/enseignements/delete/<?php echo $assignment->id; ?>" style="display:inline;" onsubmit="return confirm('<?php echo __('global_confirm_delete_generic', 'Are you sure you want to delete this item?'); ?>')">
                                <button type="submit" class="btn btn-sm btn-danger" title="<?php echo __('global_delete_button', 'Delete'); ?>"><i class="fas fa-trash"></i></button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php // Simple pagination placeholder - a real pagination system would be more complex ?>
    <?php /*
    if (isset($totalPages) && $totalPages > 1) {
        echo '<nav><ul class="pagination">';
        for ($i = 1; $i <= $totalPages; $i++) {
            $activeClass = ($i == ($currentPage ?? 1)) ? 'active' : '';
            // Preserve filters in pagination links
            $queryString = http_build_query(array_merge($currentFilters ?? [], ['page' => $i]));
            echo "<li class='page-item {$activeClass}'><a class='page-link' href='?{$queryString}'>{$i}</a></li>";
        }
        echo '</ul></nav>';
    }
    */?>
    <?php else: ?>
        <p><?php echo __('enseignements_no_assignments_found', 'No teaching assignments found matching your criteria.'); ?></p>
    <?php endif; ?>
</div>
