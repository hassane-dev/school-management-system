<?php
// Expected data from EnseignementController:
// $data['data'] (array of form values: utilisateur_id, matiere_id, classe_id, annee_id, errors)
// $data['title'] (string)
// $data['mode'] ('add' or 'edit')
// $data['assignmentId'] (int, only for 'edit' mode)
// $data['teachers'], $data['matieres'], $data['classes'], $data['academicYears'] (for dropdowns)

$formData = $data['data'] ?? ['utilisateur_id' => '', 'matiere_id' => '', 'classe_id' => '', 'annee_id' => '', 'errors' => []];
$title = $data['title'] ?? ($data['mode'] === 'edit' ? __('enseignements_title_edit_default', 'Edit Assignment') : __('enseignements_title_add_default', 'Add New Assignment'));
$mode = $data['mode'] ?? 'add';
$assignmentId = $data['assignmentId'] ?? null;

$teachers = $data['teachers'] ?? [];
$matieres = $data['matieres'] ?? [];
$classes = $data['classes'] ?? [];
$academicYears = $data['academicYears'] ?? [];

$action_url = ($mode === 'edit' && $assignmentId) ? URL_ROOT . '/admin/enseignements/edit/' . $assignmentId : URL_ROOT . '/admin/enseignements/add';
?>

<div class="container-fluid">
    <h1><?php echo htmlspecialchars($title); ?></h1>

    <form action="<?php echo $action_url; ?>" method="POST" class="needs-validation" novalidate>
        <div class="card mt-4">
            <div class="card-header"><?php echo __('enseignements_form_section_details', 'Assignment Details'); ?></div>
            <div class="card-body">
                <div class="form-group">
                    <label for="utilisateur_id"><?php echo __('enseignements_teacher', 'Teacher'); ?>: <span class="text-danger">*</span></label>
                    <select name="utilisateur_id" id="utilisateur_id" class="form-control <?php echo !empty($formData['errors']['utilisateur_id']) ? 'is-invalid' : ''; ?>" required>
                        <option value=""><?php echo __('global_select_an_option', '-- Select Teacher --'); ?></option>
                        <?php foreach($teachers as $teacher): ?>
                            <option value="<?php echo $teacher->id; ?>" <?php echo (($formData['utilisateur_id'] ?? '') == $teacher->id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($teacher->nom); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($formData['errors']['utilisateur_id'])): ?><div class="invalid-feedback"><?php echo htmlspecialchars($formData['errors']['utilisateur_id']); ?></div><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="matiere_id"><?php echo __('enseignements_subject', 'Subject'); ?>: <span class="text-danger">*</span></label>
                    <select name="matiere_id" id="matiere_id" class="form-control <?php echo !empty($formData['errors']['matiere_id']) ? 'is-invalid' : ''; ?>" required>
                        <option value=""><?php echo __('global_select_an_option', '-- Select Subject --'); ?></option>
                        <?php foreach($matieres as $matiere): ?>
                            <option value="<?php echo $matiere->id; ?>" <?php echo (($formData['matiere_id'] ?? '') == $matiere->id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($matiere->nom); ?> <?php echo $matiere->code ? '(' . htmlspecialchars($matiere->code) . ')' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($formData['errors']['matiere_id'])): ?><div class="invalid-feedback"><?php echo htmlspecialchars($formData['errors']['matiere_id']); ?></div><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="classe_id"><?php echo __('enseignements_class', 'Class'); ?>: <span class="text-danger">*</span></label>
                    <select name="classe_id" id="classe_id" class="form-control <?php echo !empty($formData['errors']['classe_id']) ? 'is-invalid' : ''; ?>" required>
                        <option value=""><?php echo __('global_select_an_option', '-- Select Class --'); ?></option>
                        <?php foreach($classes as $classe): ?>
                            <option value="<?php echo $classe->id; ?>" <?php echo (($formData['classe_id'] ?? '') == $classe->id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($classe->nom); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($formData['errors']['classe_id'])): ?><div class="invalid-feedback"><?php echo htmlspecialchars($formData['errors']['classe_id']); ?></div><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="annee_id"><?php echo __('enseignements_academic_year', 'Academic Year'); ?>: <span class="text-danger">*</span></label>
                    <select name="annee_id" id="annee_id" class="form-control <?php echo !empty($formData['errors']['annee_id']) ? 'is-invalid' : ''; ?>" required>
                        <option value=""><?php echo __('global_select_an_option', '-- Select Academic Year --'); ?></option>
                        <?php foreach($academicYears as $year): ?>
                            <option value="<?php echo $year->id; ?>" <?php echo (($formData['annee_id'] ?? '') == $year->id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($year->libelle); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($formData['errors']['annee_id'])): ?><div class="invalid-feedback"><?php echo htmlspecialchars($formData['errors']['annee_id']); ?></div><?php endif; ?>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">
                <?php echo ($mode == 'edit' ? __('global_save_button', 'Save Changes') : __('global_add_button', 'Add Assignment')); ?>
            </button>
            <a href="<?php echo URL_ROOT; ?>/admin/enseignements" class="btn btn-secondary">
                <?php echo __('global_cancel_button', 'Cancel'); ?>
            </a>
        </div>
    </form>
</div>
