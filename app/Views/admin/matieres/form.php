<?php
// Expected data:
// $data['data'] (array of form values: nom, code, description, coefficient, type_matiere, errors)
// $data['title'] (string)
// $data['mode'] ('add' or 'edit')
// $data['matiereId'] (int, only for 'edit' mode)
// $data['matiereTypes'] (array of strings for type dropdown)

$formData = $data['data'] ?? ['nom' => '', 'code' => '', 'description' => '', 'coefficient' => 1.00, 'type_matiere' => '', 'errors' => []];
$title = $data['title'] ?? ($data['mode'] === 'edit' ? __('matieres_title_edit_default', 'Edit Subject') : __('matieres_title_add_default', 'Add New Subject'));
$mode = $data['mode'] ?? 'add';
$matiereId = $data['matiereId'] ?? null;
$matiereTypes = $data['matiereTypes'] ?? ['Fondamentale', 'Optionnelle', 'Atelier', 'Projet', 'Sportive', 'Culturelle']; // Fallback if not passed

$action_url = ($mode === 'edit' && $matiereId) ? URL_ROOT . '/admin/matieres/edit/' . $matiereId : URL_ROOT . '/admin/matieres/add';
?>

<div class="container-fluid">
    <h1><?php echo htmlspecialchars($title); ?></h1>

    <form action="<?php echo $action_url; ?>" method="POST" class="needs-validation" novalidate>
        <div class="card mt-4">
            <div class="card-header"><?php echo __('matieres_form_section_details', 'Subject Details'); ?></div>
            <div class="card-body">
                <div class="form-group">
                    <label for="nom"><?php echo __('matieres_form_label_nom', 'Subject Name'); ?>: <span class="text-danger">*</span></label>
                    <input type="text" id="nom" name="nom" class="form-control <?php echo !empty($formData['errors']['nom']) ? 'is-invalid' : ''; ?>"
                           value="<?php echo htmlspecialchars($formData['nom'] ?? ''); ?>" required>
                    <?php if (!empty($formData['errors']['nom'])): ?><div class="invalid-feedback"><?php echo htmlspecialchars($formData['errors']['nom']); ?></div><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="code"><?php echo __('matieres_form_label_code', 'Subject Code'); ?>:</label>
                    <input type="text" id="code" name="code" class="form-control <?php echo !empty($formData['errors']['code']) ? 'is-invalid' : ''; ?>"
                           value="<?php echo htmlspecialchars($formData['code'] ?? ''); ?>">
                    <?php if (!empty($formData['errors']['code'])): ?><div class="invalid-feedback"><?php echo htmlspecialchars($formData['errors']['code']); ?></div><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="description"><?php echo __('matieres_form_label_description', 'Description'); ?>:</label>
                    <textarea id="description" name="description" class="form-control" rows="3"><?php echo htmlspecialchars($formData['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="coefficient"><?php echo __('matieres_form_label_coefficient', 'Coefficient'); ?>:</label>
                    <input type="number" step="0.01" id="coefficient" name="coefficient" class="form-control <?php echo !empty($formData['errors']['coefficient']) ? 'is-invalid' : ''; ?>"
                           value="<?php echo htmlspecialchars(number_format((float)($formData['coefficient'] ?? 1.00), 2, '.', '')); ?>">
                     <?php if (!empty($formData['errors']['coefficient'])): ?><div class="invalid-feedback"><?php echo htmlspecialchars($formData['errors']['coefficient']); ?></div><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="type_matiere"><?php echo __('matieres_form_label_type', 'Subject Type'); ?>:</label>
                    <select id="type_matiere" name="type_matiere" class="form-control <?php echo !empty($formData['errors']['type_matiere']) ? 'is-invalid' : ''; ?>">
                        <option value=""><?php echo __('global_select_an_option', '-- Select a type --'); ?></option>
                        <?php foreach($matiereTypes as $type): ?>
                            <option value="<?php echo htmlspecialchars($type); ?>" <?php echo (($formData['type_matiere'] ?? '') === $type) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(ucfirst($type)); // Or use __("matiere_type_".$type) if types are translatable keys ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                     <?php if (!empty($formData['errors']['type_matiere'])): ?><div class="invalid-feedback"><?php echo htmlspecialchars($formData['errors']['type_matiere']); ?></div><?php endif; ?>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">
                <?php echo ($mode == 'edit' ? __('global_save_button', 'Save Changes') : __('global_add_button', 'Add Subject')); ?>
            </button>
            <a href="<?php echo URL_ROOT; ?>/admin/matieres" class="btn btn-secondary">
                <?php echo __('global_cancel_button', 'Cancel'); ?>
            </a>
        </div>
    </form>
</div>
