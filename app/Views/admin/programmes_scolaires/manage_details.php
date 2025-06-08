<?php
// Expected data:
// $data['programme'] (object: the parent curriculum header)
// $data['details'] (array of objects: subjects already in this curriculum, with joined matiere_nom etc.)
// $data['availableMatieres'] (array of matiere objects: subjects not yet in this curriculum)
// $data['title']

$programme = $data['programme'] ?? null;
$details = $data['details'] ?? [];
$availableMatieres = $data['availableMatieres'] ?? [];
$title = $data['title'] ?? __('ps_title_manage_details_default', 'Manage Curriculum Details');

if (!$programme) {
    echo "<p>" . __('ps_not_found_msg', 'Curriculum not found.') . "</p>";
    echo "<p><a href='" . URL_ROOT . "/admin/programmesscolaires'>" . __('ps_back_to_list_button', 'Back to Curricula List') . "</a></p>";
    return;
}

// For repopulating add form on error (if controller re-renders this view with errors for add_detail)
$addDetailFormData = $_SESSION['add_detail_form_data'] ?? ['matiere_id' => '', 'notes_coefficient' => '', 'heures_par_semaine' => '', 'description_detail' => '', 'ordre' => '', 'errors' => []];
if (isset($_SESSION['add_detail_form_data'])) unset($_SESSION['add_detail_form_data']);

$dateFormat = AppSettings::getDateFormat('Y-m-d'); // For displaying dates if any
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1><?php echo htmlspecialchars($title); ?></h1>
        <a href="<?php echo URL_ROOT; ?>/admin/programmesscolaires" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> <?php echo __('ps_back_to_list_button', 'Back to Curricula List'); ?>
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5><?php echo __('ps_programme_info', 'Curriculum Information'); ?></h5>
        </div>
        <div class="card-body">
            <p><strong><?php echo __('ps_col_nom', 'Name'); ?>:</strong> <?php echo htmlspecialchars($programme->nom); ?></p>
            <p><strong><?php echo __('ps_col_annee', 'Academic Year'); ?>:</strong> <?php echo htmlspecialchars($programme->annee_academique_libelle); ?></p>
            <p><strong><?php echo __('ps_col_statut', 'Status'); ?>:</strong> <?php echo __("ps_statut_" . $programme->statut, ucfirst($programme->statut)); ?></p>
            <p><strong><?php echo __('ps_col_description', 'Description'); ?>:</strong> <?php echo nl2br(htmlspecialchars($programme->description ?? '')); ?></p>
        </div>
    </div>

    <?php // Flash messages are handled by admin_default.php layout ?>

    <div class="card mb-4">
        <div class="card-header">
            <h5><?php echo __('ps_details_current_subjects_title', 'Subjects in this Curriculum'); ?></h5>
        </div>
        <div class="card-body">
            <?php if (!empty($details)): ?>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead class="thead-light">
                        <tr>
                            <th><?php echo __('ps_details_col_ordre', 'Order'); ?></th>
                            <th><?php echo __('matieres_col_nom', 'Subject Name'); ?></th>
                            <th><?php echo __('matieres_col_code', 'Code'); ?></th>
                            <th><?php echo __('ps_details_col_coeff', 'Coefficient'); ?></th>
                            <th><?php echo __('ps_details_col_heures', 'Hours/Week'); ?></th>
                            <th><?php echo __('ps_details_col_description', 'Specific Notes'); ?></th>
                            <th><?php echo __('ps_col_actions', 'Actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach($details as $detail): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($detail->ordre ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($detail->matiere_nom); ?></td>
                            <td><?php echo htmlspecialchars($detail->matiere_code ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($detail->notes_coefficient ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($detail->heures_par_semaine ?? ''); ?></td>
                            <td><?php echo nl2br(htmlspecialchars(substr($detail->description_detail ?? '', 0, 70))) . (strlen($detail->description_detail ?? '') > 70 ? '...' : ''); ?></td>
                            <td class="action-buttons">
                                <?php // Edit button would ideally open a modal or a small inline form to POST to edit_detail_in_programme ?>
                                <!-- For now, direct edit of details is simplified. A modal would be better. -->
                                <!-- <button type="button" class="btn btn-sm btn-warning edit-detail-btn" data-detail-id="<?= $detail->id ?>" ...> <i class="fas fa-edit"></i></button> -->

                                <?php if (auth_can('manage_programme_details')): // Same perm for remove as add/edit details ?>
                                <form method="POST" action="<?php echo URL_ROOT; ?>/admin/programmesscolaires/remove_detail_from_programme/<?php echo $detail->id; ?>" style="display:inline;" onsubmit="return confirm('<?php echo __('global_confirm_delete_generic', 'Are you sure?'); ?>')">
                                    <input type="hidden" name="programme_id_for_redirect" value="<?php echo $programme->id; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" title="<?php echo __('global_delete_button', 'Remove'); ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <p><?php echo __('ps_details_no_subjects_msg', 'No subjects have been added to this curriculum yet.'); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <?php if (auth_can('manage_programme_details')): ?>
    <div class="card">
        <div class="card-header">
            <h5><?php echo __('ps_details_add_subject_title', 'Add Subject to Curriculum'); ?></h5>
        </div>
        <div class="card-body">
            <?php if (!empty($availableMatieres)): ?>
            <form action="<?php echo URL_ROOT; ?>/admin/programmesscolaires/add_detail_to_programme/<?php echo $programme->id; ?>" method="POST" class="needs-validation" novalidate>
                <div class="form-row">
                    <div class="col-md-4 form-group">
                        <label for="matiere_id"><?php echo __('matieres_col_nom', 'Subject'); ?>: <span class="text-danger">*</span></label>
                        <select id="matiere_id" name="matiere_id" class="form-control <?php echo !empty($addDetailFormData['errors']['matiere_id']) ? 'is-invalid' : ''; ?>" required>
                            <option value=""><?php echo __('global_select_an_option', '-- Select Subject --'); ?></option>
                            <?php foreach($availableMatieres as $matiere): ?>
                            <option value="<?php echo $matiere->id; ?>" <?php echo (($addDetailFormData['matiere_id'] ?? '') == $matiere->id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($matiere->nom); ?> <?php echo $matiere->code ? '(' . htmlspecialchars($matiere->code) . ')' : ''; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($addDetailFormData['errors']['matiere_id'])): ?><div class="invalid-feedback"><?php echo htmlspecialchars($addDetailFormData['errors']['matiere_id']); ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-2 form-group">
                        <label for="notes_coefficient"><?php echo __('ps_details_form_label_coeff', 'Coefficient'); ?>:</label>
                        <input type="number" step="0.01" id="notes_coefficient" name="notes_coefficient" class="form-control" value="<?php echo htmlspecialchars($addDetailFormData['notes_coefficient'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2 form-group">
                        <label for="heures_par_semaine"><?php echo __('ps_details_form_label_heures', 'Hours/Week'); ?>:</label>
                        <input type="number" step="0.1" id="heures_par_semaine" name="heures_par_semaine" class="form-control" value="<?php echo htmlspecialchars($addDetailFormData['heures_par_semaine'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2 form-group">
                        <label for="ordre"><?php echo __('ps_details_form_label_ordre', 'Order'); ?>:</label>
                        <input type="number" step="1" id="ordre" name="ordre" class="form-control" value="<?php echo htmlspecialchars($addDetailFormData['ordre'] ?? '0'); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="description_detail"><?php echo __('ps_details_form_label_description', 'Specific Notes/Syllabus'); ?>:</label>
                    <textarea id="description_detail" name="description_detail" class="form-control" rows="2"><?php echo htmlspecialchars($addDetailFormData['description_detail'] ?? ''); ?></textarea>
                </div>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-plus-circle"></i> <?php echo __('global_add_button', 'Add Subject to Curriculum'); ?>
                </button>
            </form>
            <?php else: ?>
                <p><?php echo __('ps_details_all_matieres_added', 'All available subjects are already in this curriculum, or no subjects exist in the system.'); ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
// Potential JavaScript for inline editing of details would go here or in a global admin JS file.
// For example, clicking an "Edit" button in a row could transform cells into input fields
// and then make an AJAX POST to `edit_detail_in_programme`.
// This is currently simplified in the controller to be a direct POST target.
?>
