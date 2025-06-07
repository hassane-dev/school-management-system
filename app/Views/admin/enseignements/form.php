<?php
// Expected data from EnseignementController:
// $data['data'] (array of form values: utilisateur_id, matiere_id, classe_id, annee_id, errors)
// $data['title'] (string)
// $data['mode'] ('add' or 'edit')
// $data['assignmentId'] (int, only for 'edit' mode)
// $data['teachers'], $data['matieres'] (potentially pre-filtered for edit/error), $data['classes'], $data['academicYears']
// $data['all_matieres_for_empty_state'] (optional, if needed when no class is selected)

$formData = $data['data'] ?? ['utilisateur_id' => '', 'matiere_id' => '', 'classe_id' => '', 'annee_id' => '', 'errors' => []];
$title = $data['title'] ?? ($data['mode'] === 'edit' ? __('enseignements_title_edit_default', 'Edit Assignment') : __('enseignements_title_add_default', 'Add New Assignment'));
$mode = $data['mode'] ?? 'add';
$assignmentId = $data['assignmentId'] ?? null;

$teachers = $data['teachers'] ?? [];
$matieres_initial = $data['matieres'] ?? []; // Matieres passed by controller (could be pre-filtered for selected class)
$all_matieres_for_empty_state = $data['all_matieres_for_empty_state'] ?? []; // Fallback for no-JS or initial state
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
                    <label for="annee_id"><?php echo __('enseignements_academic_year', 'Academic Year'); ?>: <span class="text-danger">*</span></label>
                    <select name="annee_id" id="annee_id_select" class="form-control <?php echo !empty($formData['errors']['annee_id']) ? 'is-invalid' : ''; ?>" required>
                        <option value=""><?php echo __('global_select_an_option', '-- Select Academic Year --'); ?></option>
                        <?php foreach($academicYears as $year): ?>
                            <option value="<?php echo $year->id; ?>" <?php echo (($formData['annee_id'] ?? '') == $year->id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($year->libelle); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($formData['errors']['annee_id'])): ?><div class="invalid-feedback"><?php echo htmlspecialchars($formData['errors']['annee_id']); ?></div><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="classe_id_select"><?php echo __('enseignements_class', 'Class'); ?>: <span class="text-danger">*</span></label>
                    <select name="classe_id" id="classe_id_select" class="form-control <?php echo !empty($formData['errors']['classe_id']) ? 'is-invalid' : ''; ?>" required>
                        <option value=""><?php echo __('global_select_an_option', '-- Select Class --'); ?></option>
                        <?php foreach($classes as $classe): ?>
                            <option value="<?php echo $classe->id; ?>" <?php echo (($formData['classe_id'] ?? '') == $classe->id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($classe->nom); ?> <?php echo $classe->niveau ? '(' . htmlspecialchars($classe->niveau) . ')' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($formData['errors']['classe_id'])): ?><div class="invalid-feedback"><?php echo htmlspecialchars($formData['errors']['classe_id']); ?></div><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="matiere_id_select"><?php echo __('enseignements_subject', 'Subject'); ?>: <span class="text-danger">*</span></label>
                    <select name="matiere_id" id="matiere_id_select" class="form-control <?php echo !empty($formData['errors']['matiere_id']) ? 'is-invalid' : ''; ?>" required>
                        <option value=""><?php echo __('enseignements_select_class_first', 'Select a class first...'); ?></option>
                        <?php
                        // Pre-populate if $formData['matiere_id'] is set and $matieres_initial (eligible ones) are passed
                        if (!empty($matieres_initial) && !empty($formData['matiere_id'])):
                            foreach($matieres_initial as $matiere): ?>
                                <option value="<?php echo $matiere->id; ?>" <?php echo (($formData['matiere_id'] ?? '') == $matiere->id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($matiere->nom); ?> <?php echo $matiere->code ? '(' . htmlspecialchars($matiere->code) . ')' : ''; ?>
                                </option>
                            <?php endforeach;
                        endif;
                        ?>
                    </select>
                    <small id="matiere_loading_message" style="display:none;"><?php echo __('enseignements_loading_matieres', 'Loading subjects...'); ?></small>
                    <?php if (!empty($formData['errors']['matiere_id'])): ?><div class="invalid-feedback"><?php echo htmlspecialchars($formData['errors']['matiere_id']); ?></div><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="utilisateur_id_select"><?php echo __('enseignements_teacher', 'Teacher'); ?>: <span class="text-danger">*</span></label>
                    <select name="utilisateur_id" id="utilisateur_id_select" class="form-control <?php echo !empty($formData['errors']['utilisateur_id']) ? 'is-invalid' : ''; ?>" required>
                        <option value=""><?php echo __('global_select_an_option', '-- Select Teacher --'); ?></option>
                        <?php foreach($teachers as $teacher): ?>
                            <option value="<?php echo $teacher->id; ?>" <?php echo (($formData['utilisateur_id'] ?? '') == $teacher->id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($teacher->nom); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($formData['errors']['utilisateur_id'])): ?><div class="invalid-feedback"><?php echo htmlspecialchars($formData['errors']['utilisateur_id']); ?></div><?php endif; ?>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const classeSelect = document.getElementById('classe_id_select');
    const matiereSelect = document.getElementById('matiere_id_select');
    const matiereLoadingMsg = document.getElementById('matiere_loading_message');

    // Storing initial values from PHP for pre-selection logic
    const initialClasseId = "<?php echo htmlspecialchars($formData['classe_id'] ?? ''); ?>";
    const initialMatiereId = "<?php echo htmlspecialchars($formData['matiere_id'] ?? ''); ?>";

    function fetchMatieresForClasse(classeId, preselectMatiereId) {
        if (!classeId) {
            matiereSelect.innerHTML = '<option value=""><?php echo __('enseignements_select_class_first', 'Select a class first...'); ?></option>';
            return;
        }

        if(matiereLoadingMsg) matiereLoadingMsg.style.display = 'inline';
        matiereSelect.disabled = true;

        // URL_ROOT should be defined globally in your layout for JS access
        const ajaxUrl = `${URL_ROOT}/admin/enseignement/get_matieres_for_classe_ajax/${classeId}`;

        fetch(ajaxUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                matiereSelect.innerHTML = '<option value=""><?php echo __('global_select_an_option', '-- Select Subject --'); ?></option>';
                if (data && data.length > 0) {
                    data.forEach(matiere => {
                        const option = document.createElement('option');
                        option.value = matiere.id;
                        option.textContent = `${matiere.nom} ${matiere.code ? '(' + matiere.code + ')' : ''}`;
                        if (matiere.id == preselectMatiereId) { // Use passed preselectMatiereId
                            option.selected = true;
                        }
                        matiereSelect.appendChild(option);
                    });
                } else {
                    matiereSelect.innerHTML = '<option value=""><?php echo __('enseignements_no_eligible_matieres', 'No eligible subjects for this class.'); ?></option>';
                }
            })
            .catch(error => {
                console.error('Error fetching matieres:', error);
                matiereSelect.innerHTML = '<option value=""><?php echo __('enseignements_error_loading_matieres', 'Error loading subjects.'); ?></option>';
            })
            .finally(() => {
                if(matiereLoadingMsg) matiereLoadingMsg.style.display = 'none';
                matiereSelect.disabled = false;
            });
    }

    if (classeSelect) {
        classeSelect.addEventListener('change', function() {
            // When class changes, we don't pre-select any matiere unless it's part of further logic
            fetchMatieresForClasse(this.value, null);
        });

        // Initial population if a class is already selected (e.g., on edit page load or form validation error)
        if (initialClasseId) {
            fetchMatieresForClasse(initialClasseId, initialMatiereId);
        }
    }
});
</script>
