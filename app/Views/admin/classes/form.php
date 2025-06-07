<?php
// Expected data:
// $data['data'] (array of form values: nom, niveau, cycle, description, salle_par_defaut, capacite, errors)
// $data['title'] (string)
// $data['mode'] ('add' or 'edit')
// $data['classeId'] (int, only for 'edit' mode)
// $data['cyclesList'] (array of strings for cycle dropdown)
// $data['niveauxListPerCycleJson'] (JSON string for dynamic niveau dropdown)

$formData = $data['data'] ?? ['nom' => '', 'niveau' => '', 'cycle' => '', 'description' => '', 'salle_par_defaut' => '', 'capacite' => '', 'errors' => []];
$title = $data['title'] ?? ($data['mode'] === 'edit' ? __('classes_title_edit_default', 'Edit Class') : __('classes_title_add_default', 'Add New Class'));
$mode = $data['mode'] ?? 'add';
$classeId = $data['classeId'] ?? null;

$cyclesList = $data['cyclesList'] ?? ['Maternelle', 'Primaire', 'Collège', 'Lycée', 'Supérieur', 'Formation Professionnelle']; // Fallback if not passed
$niveauxListPerCycleJson = $data['niveauxListPerCycleJson'] ?? '{}';

$action_url = ($mode === 'edit' && $classeId) ? URL_ROOT . '/admin/classes/edit/' . $classeId : URL_ROOT . '/admin/classes/add';
?>

<div class="container-fluid">
    <h1><?php echo htmlspecialchars($title); ?></h1>

    <form action="<?php echo $action_url; ?>" method="POST" class="needs-validation" novalidate>
        <div class="card mt-4">
            <div class="card-header"><?php echo __('classes_form_section_details', 'Class Details'); ?></div>
            <div class="card-body">
                <div class="form-group">
                    <label for="nom"><?php echo __('classes_form_label_nom', 'Class Name'); ?>: <span class="text-danger">*</span></label>
                    <input type="text" id="nom" name="nom" class="form-control <?php echo !empty($formData['errors']['nom']) ? 'is-invalid' : ''; ?>"
                           value="<?php echo htmlspecialchars($formData['nom'] ?? ''); ?>" required>
                    <?php if (!empty($formData['errors']['nom'])): ?><div class="invalid-feedback"><?php echo htmlspecialchars($formData['errors']['nom']); ?></div><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="cycle"><?php echo __('classes_form_label_cycle', 'Cycle'); ?>:</label>
                    <select id="cycle" name="cycle" class="form-control <?php echo !empty($formData['errors']['cycle']) ? 'is-invalid' : ''; ?>">
                        <option value=""><?php echo __('global_select_an_option', '-- Select a cycle --'); ?></option>
                        <?php foreach($cyclesList as $cycle): ?>
                            <option value="<?php echo htmlspecialchars($cycle); ?>" <?php echo (($formData['cycle'] ?? '') === $cycle) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(ucfirst($cycle)); // Or use __("cycle_".$cycle) if types are translatable keys ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($formData['errors']['cycle'])): ?><div class="invalid-feedback"><?php echo htmlspecialchars($formData['errors']['cycle']); ?></div><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="niveau"><?php echo __('classes_form_label_niveau', 'Level'); ?>:</label>
                    <select id="niveau" name="niveau" class="form-control <?php echo !empty($formData['errors']['niveau']) ? 'is-invalid' : ''; ?>">
                        <option value=""><?php echo __('global_select_an_option', '-- Select a level --'); ?></option>
                        <?php // Options will be populated by JavaScript based on cycle selection ?>
                    </select>
                    <?php if (!empty($formData['errors']['niveau'])): ?><div class="invalid-feedback"><?php echo htmlspecialchars($formData['errors']['niveau']); ?></div><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="description"><?php echo __('classes_form_label_description', 'Description'); ?>:</label>
                    <textarea id="description" name="description" class="form-control" rows="3"><?php echo htmlspecialchars($formData['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="salle_par_defaut"><?php echo __('classes_form_label_salle', 'Default Classroom'); ?>:</label>
                    <input type="text" id="salle_par_defaut" name="salle_par_defaut" class="form-control"
                           value="<?php echo htmlspecialchars($formData['salle_par_defaut'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="capacite"><?php echo __('classes_form_label_capacite', 'Capacity'); ?>:</label>
                    <input type="number" id="capacite" name="capacite" class="form-control <?php echo !empty($formData['errors']['capacite']) ? 'is-invalid' : ''; ?>"
                           value="<?php echo htmlspecialchars($formData['capacite'] ?? ''); ?>" min="0" max="500">
                           <?php // Max 500 for this example, adjust as needed ?>
                    <?php if (!empty($formData['errors']['capacite'])): ?><div class="invalid-feedback"><?php echo htmlspecialchars($formData['errors']['capacite']); ?></div><?php endif; ?>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">
                <?php echo ($mode == 'edit' ? __('global_save_button', 'Save Changes') : __('global_add_button', 'Add Class')); ?>
            </button>
            <a href="<?php echo URL_ROOT; ?>/admin/classes" class="btn btn-secondary">
                <?php echo __('global_cancel_button', 'Cancel'); ?>
            </a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const niveauxPerCycle = <?php echo $niveauxListPerCycleJson ?? '{}'; ?>;
    const cycleSelect = document.getElementById('cycle');
    const niveauSelect = document.getElementById('niveau');
    const currentSelectedNiveau = "<?php echo htmlspecialchars($formData['niveau'] ?? ''); ?>";

    function populateNiveaux() {
        const selectedCycle = cycleSelect.value;
        const currentValNiveau = (niveauSelect.value && cycleSelect.value === (<?php echo json_encode($formData['cycle'] ?? ''); ?>)) ? currentSelectedNiveau : ''; // Preserve if cycle hasn't changed

        niveauSelect.innerHTML = '<option value=""><?php echo __('global_select_an_option', '-- Select a level --'); ?></option>'; // Clear existing and add placeholder

        if (selectedCycle && niveauxPerCycle[selectedCycle]) {
            niveauxPerCycle[selectedCycle].forEach(niveau => {
                const option = document.createElement('option');
                option.value = niveau;
                option.textContent = niveau; // Consider using translated keys: __("niveau_" + niveau.toLowerCase().replace(/\s+/g, '_'))
                if (niveau === currentValNiveau) {
                    option.selected = true;
                }
                niveauSelect.appendChild(option);
            });
        }
    }

    if (cycleSelect) {
        cycleSelect.addEventListener('change', populateNiveaux);
        // Populate on page load based on current cycle (for edit mode or if form reloaded with errors)
        populateNiveaux();
    }
});
</script>
