<?php // File: app/Views/admin/annees_academiques/form.php
// $data['data'] contains current form values (libelle, date_debut, date_fin, active, errors)
// $data['title']
// $data['mode'] ('add' or 'edit')
// All text should use __() for i18n.

$form_data = $data['data'] ?? ['libelle' => '', 'date_debut' => '', 'date_fin' => '', 'active' => 0, 'errors' => []];
$title = $data['title'] ?? ($data['mode'] === 'edit' ? __('aa_title_edit_default', 'Edit Academic Year') : __('aa_title_add_default', 'Add Academic Year'));
$mode = $data['mode'] ?? 'add';
$action_url = ($mode === 'edit' && isset($form_data['id'])) ? URL_ROOT . '/admin/anneesacademiques/edit/' . $form_data['id'] : URL_ROOT . '/admin/anneesacademiques/add';
?>

<div class="container mt-4">
    <h1><?php echo htmlspecialchars($title); ?></h1>

    <form action="<?php echo $action_url; ?>" method="POST" class="mt-3 needs-validation" novalidate>
        <div class="form-group">
            <label for="libelle"><?php echo __('aa_form_label_libelle', 'Label'); ?>:</label>
            <input type="text" id="libelle" name="libelle" class="form-control <?php echo !empty($form_data['errors']['libelle']) ? 'is-invalid' : ''; ?>"
                   value="<?php echo htmlspecialchars($form_data['libelle'] ?? ''); ?>" required>
            <?php if (!empty($form_data['errors']['libelle'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($form_data['errors']['libelle']); ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="date_debut"><?php echo __('aa_form_label_date_debut', 'Start Date'); ?>:</label>
            <input type="date" id="date_debut" name="date_debut" class="form-control <?php echo !empty($form_data['errors']['date_debut']) ? 'is-invalid' : ''; ?>"
                   value="<?php echo htmlspecialchars($form_data['date_debut'] ?? ''); ?>" required>
            <?php if (!empty($form_data['errors']['date_debut'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($form_data['errors']['date_debut']); ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="date_fin"><?php echo __('aa_form_label_date_fin', 'End Date'); ?>:</label>
            <input type="date" id="date_fin" name="date_fin" class="form-control <?php echo !empty($form_data['errors']['date_fin']) ? 'is-invalid' : ''; ?>"
                   value="<?php echo htmlspecialchars($form_data['date_fin'] ?? ''); ?>" required>
            <?php if (!empty($form_data['errors']['date_fin'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($form_data['errors']['date_fin']); ?></div>
            <?php endif; ?>
        </div>

        <?php if ($mode == 'add'): // Only show 'active' checkbox when adding; activation is a separate action for existing records ?>
        <div class="form-group form-check">
            <input type="checkbox" class="form-check-input" id="active" name="active" value="1" <?php echo !empty($form_data['active']) ? 'checked' : ''; ?>>
            <label class="form-check-label" for="active"><?php echo __('aa_form_label_active', 'Set as Active Year'); ?></label>
            <small class="form-text text-muted"><?php echo __('aa_form_note_active', 'If checked, any other currently active year will be deactivated.'); ?></small>
        </div>
        <?php endif; ?>

        <button type="submit" class="btn btn-primary mt-3">
            <?php echo ($mode == 'edit' ? __('global_save_button', 'Save Changes') : __('global_add_button', 'Add Academic Year')); ?>
        </button>
        <a href="<?php echo URL_ROOT; ?>/admin/anneesacademiques" class="btn btn-secondary mt-3">
            <?php echo __('global_cancel_button', 'Cancel'); ?>
        </a>
    </form>
</div>

<script>
// Example basic client-side validation (optional, server-side is key)
(function() {
  'use strict';
  window.addEventListener('load', function() {
    var forms = document.getElementsByClassName('needs-validation');
    var validation = Array.prototype.filter.call(forms, function(form) {
      form.addEventListener('submit', function(event) {
        if (form.checkValidity() === false) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    });
  }, false);
})();
</script>
