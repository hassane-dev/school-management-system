<?php
// Expected data:
// $data['assignments'] (array of assignment objects with joined data)
// $data['page_title']
// $data['all_teachers'], $data['all_classes'], $data['all_annees'] (for filters)
// $data['current_filters'] (array of current GET params)

$assignments = $data['assignments'] ?? [];
$page_title = $data['page_title'] ?? __('enseignements.list_title_default', 'Teaching Assignments');

$all_teachers = $data['all_teachers'] ?? [];
$all_classes = $data['all_classes'] ?? [];
$all_annees = $data['all_annees'] ?? [];
$current_filters = $data['current_filters'] ?? [];
?>

<div class="container-fluid mt-4">
    <div class="row mb-3">
        <div class="col-md-8">
            <h2><?php echo htmlspecialchars($page_title); ?></h2>
        </div>
        <div class="col-md-4 text-right">
            <a href="<?php echo base_url('admin/enseignement/add'); ?>" class="btn btn-success">
                <i class="fas fa-plus"></i> <?php echo __('enseignements.add_new_assignment_button', 'Add New Assignment'); ?>
            </a>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="card mb-4">
        <div class="card-header">
            <?php echo __('global.filters_title', 'Filters'); ?>
        </div>
        <div class="card-body">
            <form action="<?php echo base_url('admin/enseignement/index'); ?>" method="GET" class="form-inline">
                <div class="form-group mr-2 mb-2">
                    <label for="filter_teacher_id" class="mr-2"><?php echo __('enseignements.teacher', 'Teacher'); ?>:</label>
                    <select name="utilisateur_id" id="filter_teacher_id" class="form-control form-control-sm">
                        <option value=""><?php echo __('global.all_option', 'All'); ?></option>
                        <?php foreach ($all_teachers as $teacher): ?>
                            <option value="<?php echo $teacher->id; ?>" <?php echo (isset($current_filters['utilisateur_id']) && $current_filters['utilisateur_id'] == $teacher->id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($teacher->nom); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mr-2 mb-2">
                    <label for="filter_classe_id" class="mr-2"><?php echo __('enseignements.class', 'Class'); ?>:</label>
                    <select name="classe_id" id="filter_classe_id" class="form-control form-control-sm">
                        <option value=""><?php echo __('global.all_option', 'All'); ?></option>
                        <?php foreach ($all_classes as $classe): ?>
                            <option value="<?php echo $classe->id; ?>" <?php echo (isset($current_filters['classe_id']) && $current_filters['classe_id'] == $classe->id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($classe->nom); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mr-2 mb-2">
                    <label for="filter_annee_id" class="mr-2"><?php echo __('enseignements.academic_year', 'Academic Year'); ?>:</label>
                    <select name="annee_id" id="filter_annee_id" class="form-control form_control-sm">
                        <option value=""><?php echo __('global.all_option', 'All'); ?></option>
                        <?php foreach ($all_annees as $annee): ?>
                            <option value="<?php echo $annee->id; ?>" <?php echo (isset($current_filters['annee_id']) && $current_filters['annee_id'] == $annee->id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($annee->libelle); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-sm mb-2"><?php echo __('global.filter_button', 'Filter'); ?></button>
                <a href="<?php echo base_url('admin/enseignement/index'); ?>" class="btn btn-secondary btn-sm ml-2 mb-2"><?php echo __('global.reset_filters_button', 'Reset'); ?></a>
            </form>
        </div>
    </div>


    <div class="card">
        <div class="card-header">
            <?php echo __('enseignements.assignments_list', 'Assignments List'); ?>
        </div>
        <div class="card-body">
            <?php if (!empty($assignments)): ?>
                <table class="table table-striped table-bordered table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th><?php echo __('enseignements.class', 'Class'); ?></th>
                            <th><?php echo __('enseignements.subject', 'Subject'); ?></th>
                            <th><?php echo __('enseignements.teacher', 'Teacher'); ?></th>
                            <th><?php echo __('enseignements.academic_year', 'Academic Year'); ?></th>
                            <th><?php echo __('users.actions', 'Actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assignments as $assignment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($assignment->classe_nom ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($assignment->matiere_nom ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($assignment->enseignant_nom ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($assignment->annee_libelle ?? 'N/A'); ?></td>
                                <td class="action-buttons">
                                    <a href="<?php echo base_url('admin/enseignement/edit/' . $assignment->id); ?>" class="btn btn-sm btn-warning" title="<?php echo __('users.edit_button', 'Edit'); ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="<?php echo base_url('admin/enseignement/delete/' . $assignment->id); ?>" method="POST" class="d-inline" onsubmit="return confirm('<?php echo __('global.confirm_delete_generic', 'Are you sure you want to delete this item?'); ?>');">
                                        <button type="submit" class="btn btn-sm btn-danger" title="<?php echo __('users.delete_button', 'Delete'); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?php echo __('enseignements.no_assignments_found', 'No teaching assignments found matching your criteria.'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>
