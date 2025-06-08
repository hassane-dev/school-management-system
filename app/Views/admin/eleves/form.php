<?php
// Assume $this->getFlashMessages() is available from a base view or controller
if (!function_exists('display_flash_messages')) {
    function display_flash_messages() {
        if (isset($_SESSION['flash_message'])) {
            $message = $_SESSION['flash_message'];
            unset($_SESSION['flash_message']);
            echo '<div class="alert alert-' . htmlspecialchars($message['type']) . '">' . htmlspecialchars($message['text']) . '</div>';
        }
    }
}
?>
<h1><?= $title ?></h1>
<?php display_flash_messages(); ?>

<form action="<?= URL_ROOT ?>/admin/eleves/<?= ($mode == 'edit' ? 'edit/' . ($eleveId ?? '') : 'add') ?>" method="POST" enctype="multipart/form-data" class="responsive-form">
    <fieldset>
        <legend><?= __('eleves_form_section_id') ?></legend>
        <div class="form-row">
            <div class="form-group">
                <label for="matricule"><?= __('eleves_form_label_matricule') ?>: <?= ($mode=='add' ? '<span class="required">*</span>' : '') ?></label>
                <input type="text" id="matricule" name="matricule" value="<?= htmlspecialchars($data['matricule'] ?? '') ?>" <?= ($mode=='add' ? '' : 'readonly') ?>>
                <span class="error"><?= htmlspecialchars($data['errors']['matricule'] ?? '') ?></span>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="prenom"><?= __('eleves_form_label_prenom') ?>: <span class="required">*</span></label>
                <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($data['prenom'] ?? '') ?>" required>
                <span class="error"><?= htmlspecialchars($data['errors']['prenom'] ?? '') ?></span>
            </div>
            <div class="form-group">
                <label for="nom_famille"><?= __('eleves_form_label_nom_famille') ?>: <span class="required">*</span></label>
                <input type="text" id="nom_famille" name="nom_famille" value="<?= htmlspecialchars($data['nom_famille'] ?? '') ?>" required>
                <span class="error"><?= htmlspecialchars($data['errors']['nom_famille'] ?? '') ?></span>
            </div>
        </div>
         <div class="form-row">
            <div class="form-group">
                <label for="date_naissance"><?= __('eleves_form_label_date_naissance') ?>: <span class="required">*</span></label>
                <input type="date" id="date_naissance" name="date_naissance" value="<?= htmlspecialchars($data['date_naissance'] ?? '') ?>" required>
                <span class="error"><?= htmlspecialchars($data['errors']['date_naissance'] ?? '') ?></span>
            </div>
            <div class="form-group">
                <label for="lieu_naissance"><?= __('eleves_form_label_lieu_naissance') ?>: <span class="required">*</span></label>
                <input type="text" id="lieu_naissance" name="lieu_naissance" value="<?= htmlspecialchars($data['lieu_naissance'] ?? '') ?>" required>
                <span class="error"><?= htmlspecialchars($data['errors']['lieu_naissance'] ?? '') ?></span>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="sexe"><?= __('eleves_form_label_sexe') ?>: <span class="required">*</span></label>
                <select id="sexe" name="sexe" required>
                    <option value="M" <?= (($data['sexe'] ?? '') == 'M') ? 'selected' : '' ?>><?= __('global_male') ?></option>
                    <option value="F" <?= (($data['sexe'] ?? '') == 'F') ? 'selected' : '' ?>><?= __('global_female') ?></option>
                    <option value="Autre" <?= (($data['sexe'] ?? '') == 'Autre') ? 'selected' : '' ?>><?= __('global_other') ?></option>
                </select>
                <span class="error"><?= htmlspecialchars($data['errors']['sexe'] ?? '') ?></span>
            </div>
            <div class="form-group">
                <label for="nationalite"><?= __('eleves_form_label_nationalite') ?>:</label>
                <input type="text" id="nationalite" name="nationalite" value="<?= htmlspecialchars($data['nationalite'] ?? '') ?>">
            </div>
        </div>
    </fieldset>

    <fieldset>
        <legend><?= __('eleves_form_section_contact') ?></legend>
        <div class="form-group">
            <label for="adresse"><?= __('eleves_form_label_adresse') ?>:</label>
            <textarea id="adresse" name="adresse" rows="3"><?= htmlspecialchars($data['adresse'] ?? '') ?></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="telephone_mobile"><?= __('eleves_form_label_tel_mobile') ?>:</label>
                <input type="tel" id="telephone_mobile" name="telephone_mobile" value="<?= htmlspecialchars($data['telephone_mobile'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="email_eleve"><?= __('eleves_form_label_email_eleve') ?>:</label>
                <input type="email" id="email_eleve" name="email_eleve" value="<?= htmlspecialchars($data['email_eleve'] ?? '') ?>">
                <span class="error"><?= htmlspecialchars($data['errors']['email_eleve'] ?? '') ?></span>
            </div>
        </div>
    </fieldset>

    <fieldset>
        <legend><?= __('eleves_form_section_parents') ?></legend>
        <h4><?= __('eleves_form_subtitle_rl1') ?></h4>
        <div class="form-row">
            <div class="form-group">
                <label for="nom_responsable_legal1"><?= __('eleves_form_label_nom_rl1') ?>: <span class="required">*</span></label>
                <input type="text" id="nom_responsable_legal1" name="nom_responsable_legal1" value="<?= htmlspecialchars($data['nom_responsable_legal1'] ?? '') ?>" required>
                <span class="error"><?= htmlspecialchars($data['errors']['nom_responsable_legal1'] ?? '') ?></span>
            </div>
            <div class="form-group">
                <label for="telephone_responsable_legal1"><?= __('eleves_form_label_tel_rl1') ?>: <span class="required">*</span></label>
                <input type="tel" id="telephone_responsable_legal1" name="telephone_responsable_legal1" value="<?= htmlspecialchars($data['telephone_responsable_legal1'] ?? '') ?>" required>
                <span class="error"><?= htmlspecialchars($data['errors']['telephone_responsable_legal1'] ?? '') ?></span>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="email_responsable_legal1"><?= __('eleves_form_label_email_rl1') ?>:</label>
                <input type="email" id="email_responsable_legal1" name="email_responsable_legal1" value="<?= htmlspecialchars($data['email_responsable_legal1'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="profession_rl1"><?= __('eleves_form_label_prof_rl1') ?>:</label>
                <input type="text" id="profession_rl1" name="profession_rl1" value="<?= htmlspecialchars($data['profession_rl1'] ?? '') ?>">
            </div>
        </div>

        <h4><?= __('eleves_form_subtitle_rl2') ?></h4>
         <div class="form-row">
            <div class="form-group">
                <label for="nom_responsable_legal2"><?= __('eleves_form_label_nom_rl2') ?>:</label>
                <input type="text" id="nom_responsable_legal2" name="nom_responsable_legal2" value="<?= htmlspecialchars($data['nom_responsable_legal2'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="telephone_responsable_legal2"><?= __('eleves_form_label_tel_rl2') ?>:</label>
                <input type="tel" id="telephone_responsable_legal2" name="telephone_responsable_legal2" value="<?= htmlspecialchars($data['telephone_responsable_legal2'] ?? '') ?>">
            </div>
        </div>
    </fieldset>

    <fieldset>
        <legend><?= __('eleves_form_section_school_info') ?></legend>
        <div class="form-row">
            <div class="form-group">
                <label for="date_inscription_initiale"><?= __('eleves_form_label_date_inscription') ?>: <span class="required">*</span></label>
                <input type="date" id="date_inscription_initiale" name="date_inscription_initiale" value="<?= htmlspecialchars($data['date_inscription_initiale'] ?? date('Y-m-d')) ?>" required>
                <span class="error"><?= htmlspecialchars($data['errors']['date_inscription_initiale'] ?? '') ?></span>
            </div>
            <div class="form-group">
                <label for="statut_eleve"><?= __('eleves_form_label_statut') ?>: <span class="required">*</span></label>
                <select id="statut_eleve" name="statut_eleve" required>
                    <?php foreach($statutsList as $status): ?>
                        <option value="<?= $status ?>" <?= (($data['statut_eleve'] ?? 'inscrit') === $status) ? 'selected' : '' ?>><?= __("eleve_statut_$status") ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="error"><?= htmlspecialchars($data['errors']['statut_eleve'] ?? '') ?></span>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="avatar_upload"><?= __('eleves_form_label_avatar') ?>:</label>
                <input type="file" id="avatar_upload" name="avatar_upload">
                <?php if ($mode == 'edit' && !empty($data['avatar_path'])): ?>
                    <p><?= __('eleves_form_current_avatar') ?>: <img src="<?= URL_ROOT . '/uploads/avatars/' . htmlspecialchars($data['avatar_path']) ?>" alt="<?= __('eleves_form_avatar_alt') ?>" style="max-width:100px; max-height:100px;"></p>
                <?php endif; ?>
            </div>
        </div>
        <div class="form-group">
            <label for="notes_medicales"><?= __('eleves_form_label_notes_medicales') ?>:</label>
            <textarea id="notes_medicales" name="notes_medicales" rows="3"><?= htmlspecialchars($data['notes_medicales'] ?? '') ?></textarea>
        </div>
    </fieldset>

    <?php if (auth_can('assign_eleve_classe')): ?>
    <fieldset>
        <legend><?= __('eleves_form_section_affectation_classe') ?></legend>
        <div class="form-row">
            <div class="form-group">
                <label for="annee_academique_id_affectation"><?= __('eleves_form_label_annee_affectation') ?>:</label>
                <select name="annee_academique_id_affectation" id="annee_academique_id_affectation">
                    <option value=""><?= __('global_select_an_option') ?></option>
                    <?php if (!empty($anneesList)): foreach($anneesList as $annee): ?>
                    <option value="<?= $annee->id ?>" <?= (($data['annee_academique_id_affectation'] ?? $activeYearId ?? '') == $annee->id) ? 'selected' : '' ?>><?= htmlspecialchars($annee->libelle) ?></option>
                    <?php endforeach; endif; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="classe_id"><?= __('eleves_form_label_classe_affectation') ?>:</label>
                <select name="classe_id" id="classe_id">
                    <option value=""><?= __('global_none') ?> (<?= __('eleves_form_no_classe_assign') ?>)</option>
                    <?php if (!empty($classesList)): foreach($classesList as $classe): ?>
                    <option value="<?= $classe->id ?>" <?= (($data['classe_id'] ?? null) == $classe->id) ? 'selected' : '' ?>><?= htmlspecialchars($classe->nom) ?></option>
                    <?php endforeach; endif; ?>
                </select>
            </div>
        </div>
    </fieldset>
    <?php endif; ?>

    <div class="form-actions">
        <button type="submit" class="button primary-button"><?= ($mode == 'edit' ? __('global_save_button') : __('global_add_button')) ?></button>
        <a href="<?= URL_ROOT ?>/admin/eleves" class="button-link"><?= __('global_cancel_button') ?></a>
    </div>
</form>
<br>
<hr>
<p><small><em><?= __('eleves_form_note_matricule_add_mode') ?></em></small></p>
<p><small><em><?= __('eleves_form_note_avatar_upload') ?></em></small></p>
<p><small><em><?= __('eleves_form_note_affectation') ?></em></small></p>
