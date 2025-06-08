<?php
// Expected data:
// $data['data'] (form values: nom, annee_academique_id, description, statut, errors)
// $data['title']
// $data['mode'] ('add' or 'edit')
// $data['programmeId'] (for edit mode)
// $data['anneesAcademiques'] (list for dropdown)
// $data['statutsList'] (list for dropdown)

$formData = $data['data'] ?? ['nom' => '', 'annee_academique_id' => '', 'description' => '', 'statut' => 'brouillon', 'errors' => []];
$title = $data['title'] ?? ($data['mode'] === 'edit' ? __('ps_title_edit_default', 'Edit Curriculum') : __('ps_title_add_default', 'Add New Curriculum'));
$mode = $data['mode'] ?? 'add';
$programmeId = $data['programmeId'] ?? null;

$anneesAcademiques = $data['anneesAcademiques'] ?? [];
$statutsList = $data['statutsList'] ?? ['brouillon', 'actif', 'archive']; // Fallback

$action_url = ($mode === 'edit' && $programmeId) ? URL_ROOT . '/admin/programmesscolaires/edit_programme/' . $programmeId : URL_ROOT . '/admin/programmesscolaires/add_programme';
?>

<div class="container-fluid">
    <h1><?php echo htmlspecialchars($title); ?></h1>

    <form action="<?php echo $action_url; ?>" method="POST" class="needs-validation" novalidate>
        <div class="card mt-4">
            <div class="card-header"><?php echo __('ps_form_section_details', 'Curriculum Details'); ?></div>
            <div class="card-body">
                <div class="form-group">
                    <label for="nom"><?php echo __('ps_form_label_nom', 'Curriculum Name'); ?>: <span class="text-danger">*</span></label>
                    <input type="text" id="nom" name="nom" class="form-control <?php echo !empty($formData['errors']['nom']) ? 'is-invalid' : ''; ?>"
                           value="<?php echo htmlspecialchars($formData['nom'] ?? ''); ?>" required>
                    <?php if (!empty($formData['errors']['nom'])): ?><div class="invalid-feedback"><?php echo htmlspecialchars($formData['errors']['nom']); ?></div><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="annee_academique_id"><?php echo __('ps_form_label_annee', 'Academic Year'); ?>: <span class="text-danger">*</span></label>
                    <select id="annee_academique_id" name="annee_academique_id" class="form-control <?php echo !empty($formData['errors']['annee_academique_id']) ? 'is-invalid' : ''; ?>" required>
                        <option value=""><?php echo __('global_select_an_option', '-- Select Academic Year --'); ?></option>
                        <?php foreach($anneesAcademiques as $annee): ?>
                        <option value="<?php echo $annee->id; ?>" <?php echo (($formData['annee_academique_id'] ?? '') == $annee->id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($annee->libelle); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($formData['errors']['annee_academique_id'])): ?><div class="invalid-feedback"><?php echo htmlspecialchars($formData['errors']['annee_academique_id']); ?></div><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="statut"><?php echo __('ps_form_label_statut', 'Status'); ?>: <span class="text-danger">*</span></label>
                    <select id="statut" name="statut" class="form-control <?php echo !empty($formData['errors']['statut']) ? 'is-invalid' : ''; ?>" required>
                        <?php foreach($statutsList as $statutVal): ?>
                        <option value="<?php echo htmlspecialchars($statutVal); ?>" <?php echo (($formData['statut'] ?? 'brouillon') === $statutVal) ? 'selected' : ''; ?>>
                            <?php echo __("ps_statut_$statutVal", ucfirst($statutVal)); // e.g., "ps_statut_brouillon" ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($formData['errors']['statut'])): ?><div class="invalid-feedback"><?php echo htmlspecialchars($formData['errors']['statut']); ?></div><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="description"><?php echo __('ps_form_label_description', 'Description'); ?>:</label>
                    <textarea id="description" name="description" class="form-control" rows="4"><?php echo htmlspecialchars($formData['description'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">
                <?php echo ($mode == 'edit' ? __('global_save_button', 'Save Changes') : __('global_add_button', 'Create Curriculum')); ?>
            </button>
            <a href="<?php echo URL_ROOT; ?>/admin/programmesscolaires" class="btn btn-secondary">
                <?php echo __('global_cancel_button', 'Cancel'); ?>
            </a>
        </div>
    </form>
</div>
