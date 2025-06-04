<?php
// Expected data:
// $data['mode'] ('add' or 'edit')
// $data['current_assignment'] (object, for edit mode)
// $data['annees_academiques'], $data['classes'], $data['matieres'], $data['enseignants'] (for dropdowns)
// $data['page_title']
// $data['errors'] (optional)

$mode = $data['mode'] ?? 'add';
$current_assignment = $data['current_assignment'] ?? null;
$page_title = $data['page_title'] ?? ($mode === 'edit' ? __('enseignements.edit_title_default', 'Edit Assignment') : __('enseignements.add_title_default', 'Add New Assignment'));
$errors = $data['errors'] ?? [];

$annees_academiques = $data['annees_academiques'] ?? [];
$classes = $data['classes'] ?? [];
$matieres = $data['matieres'] ?? [];
$enseignants = $data['enseignants'] ?? []; // Teachers

// Values for form fields
$selected_annee_id = $current_assignment->annee_id ?? ($_POST['annee_id'] ?? null);
$selected_classe_id = $current_assignment->classe_id ?? ($_POST['classe_id'] ?? null);
$selected_matiere_id = $current_assignment->matiere_id ?? ($_POST['matiere_id'] ?? null);
$selected_utilisateur_id = $current_assignment->utilisateur_id ?? ($_POST['utilisateur_id'] ?? null); // Teacher

$form_action = ($mode === 'edit' && $current_assignment) ? base_url('admin/enseignement/edit/' . $current_assignment->id) : base_url('admin/enseignement/add');
$submit_button_text = ($mode === 'edit') ? __('global.save_button', 'Save Changes') : __('global.add_button', 'Add Assignment');
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h4><?php echo htmlspecialchars($page_title); ?></h4>
                </div>
                <div class="card-body">
                    <form action="<?php echo $form_action; ?>" method="POST">

                        <div class="form-group">
                            <label for="annee_id"><?php echo __('enseignements.academic_year_label', 'Academic Year'); ?></label>
                            <select name="annee_id" id="annee_id" class="form-control <?php echo isset($errors['annee_id']) ? 'is-invalid' : ''; ?>" required>
                                <option value=""><?php echo __('global.please_select_option', '-- Please select --'); ?></option>
                                <?php foreach ($annees_academiques as $annee): ?>
                                    <option value="<?php echo $annee->id; ?>" <?php echo ($selected_annee_id == $annee->id) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($annee->libelle); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['annee_id'])): ?><div class="invalid-feedback"><?php echo $errors['annee_id']; ?></div><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="classe_id"><?php echo __('enseignements.class_label', 'Class'); ?></label>
                            <select name="classe_id" id="classe_id" class="form-control <?php echo isset($errors['classe_id']) ? 'is-invalid' : ''; ?>" required>
                                <option value=""><?php echo __('global.please_select_option', '-- Please select --'); ?></option>
                                <?php foreach ($classes as $classe): ?>
                                    <option value="<?php echo $classe->id; ?>" <?php echo ($selected_classe_id == $classe->id) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($classe->nom); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['classe_id'])): ?><div class="invalid-feedback"><?php echo $errors['classe_id']; ?></div><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="matiere_id"><?php echo __('enseignements.subject_label', 'Subject'); ?></label>
                            <select name="matiere_id" id="matiere_id" class="form-control <?php echo isset($errors['matiere_id']) ? 'is-invalid' : ''; ?>" required>
                                <option value=""><?php echo __('global.please_select_option', '-- Please select --'); ?></option>
                                <?php foreach ($matieres as $matiere): ?>
                                    <option value="<?php echo $matiere->id; ?>" <?php echo ($selected_matiere_id == $matiere->id) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($matiere->nom); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['matiere_id'])): ?><div class="invalid-feedback"><?php echo $errors['matiere_id']; ?></div><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="utilisateur_id"><?php echo __('enseignements.teacher_label', 'Teacher'); ?></label>
                            <select name="utilisateur_id" id="utilisateur_id" class="form-control <?php echo isset($errors['utilisateur_id']) ? 'is-invalid' : ''; ?>" required>
                                <option value=""><?php echo __('global.please_select_option', '-- Please select --'); ?></option>
                                <?php foreach ($enseignants as $enseignant): ?>
                                    <option value="<?php echo $enseignant->id; ?>" <?php echo ($selected_utilisateur_id == $enseignant->id) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($enseignant->nom); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['utilisateur_id'])): ?><div class="invalid-feedback"><?php echo $errors['utilisateur_id']; ?></div><?php endif; ?>
                        </div>

                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary"><?php echo $submit_button_text; ?></button>
                            <a href="<?php echo base_url('admin/enseignement'); ?>" class="btn btn-secondary">
                                <?php echo __('cancel_btn', 'Cancel'); ?>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
