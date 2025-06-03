<?php // Data available: $parametres_ecole from index.php which gets it from controller ?>

<div class="pt-3">
    <h4><?php echo __('school_settings_title', 'Informations de l’École'); ?></h4>

    <div id="display-ecole" class="display-section">
        <?php if ($parametres_ecole): ?>
            <dl class="row">
                <dt class="col-sm-3"><?php echo __('school_name', 'Nom de l’école'); ?></dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($parametres_ecole->nom_ecole ?? ''); ?></dd>

                <dt class="col-sm-3"><?php echo __('school_acronym', 'Sigle'); ?></dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($parametres_ecole->sigle ?? ''); ?></dd>

                <dt class="col-sm-3"><?php echo __('school_email', 'Email'); ?></dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($parametres_ecole->email ?? ''); ?></dd>

                <dt class="col-sm-3"><?php echo __('school_phone', 'Téléphone'); ?></dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($parametres_ecole->telephone ?? ''); ?></dd>

                <dt class="col-sm-3"><?php echo __('school_website', 'Site Web'); ?></dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($parametres_ecole->site_web ?? ''); ?></dd>

                <dt class="col-sm-3"><?php echo __('study_cycle', 'Cycle d’étude'); ?></dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($parametres_ecole->cycle_etude ?? ''); ?></dd>
            </dl>
        <?php else: ?>
            <p><?php echo __('no_school_settings_found', 'Aucun paramètre d’école trouvé. Veuillez les configurer.'); ?></p>
        <?php endif; ?>
        <button class="btn btn-primary toggle-edit-form" data-section-id="ecole">
             <?php echo $parametres_ecole ? __('edit_btn', 'Modifier') : __('configure_btn', 'Configurer'); ?>
        </button>
    </div>

    <div id="edit-ecole" class="edit-form-section">
        <h5><?php echo $parametres_ecole ? __('edit_school_settings_form_title', 'Modifier les Paramètres de l’École') : __('configure_school_settings_form_title', 'Configurer les Paramètres de l’École'); ?></h5>
        <form action="<?php echo base_url('parametres/update_ecole'); ?>" method="POST">
            <div class="form-group">
                <label for="ecole_nom_ecole"><?php echo __('school_name', 'Nom de l’école'); ?></label>
                <input type="text" class="form-control" id="ecole_nom_ecole" name="nom_ecole" value="<?php echo htmlspecialchars($parametres_ecole->nom_ecole ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="ecole_sigle"><?php echo __('school_acronym', 'Sigle'); ?></label>
                <input type="text" class="form-control" id="ecole_sigle" name="sigle" value="<?php echo htmlspecialchars($parametres_ecole->sigle ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="ecole_email"><?php echo __('school_email', 'Email'); ?></label>
                <input type="email" class="form-control" id="ecole_email" name="email" value="<?php echo htmlspecialchars($parametres_ecole->email ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="ecole_telephone"><?php echo __('school_phone', 'Téléphone'); ?></label>
                <input type="text" class="form-control" id="ecole_telephone" name="telephone" value="<?php echo htmlspecialchars($parametres_ecole->telephone ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="ecole_site_web"><?php echo __('school_website', 'Site Web (Optionnel)'); ?></label>
                <input type="url" class="form-control" id="ecole_site_web" name="site_web" value="<?php echo htmlspecialchars($parametres_ecole->site_web ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="ecole_cycle_etude"><?php echo __('study_cycle', 'Cycle d’étude'); ?></label>
                <input type="text" class="form-control" id="ecole_cycle_etude" name="cycle_etude" value="<?php echo htmlspecialchars($parametres_ecole->cycle_etude ?? ''); ?>" required>
            </div>
            <button type="submit" class="btn btn-success"><?php echo __('save_changes_btn', 'Enregistrer'); ?></button>
            <button type="button" class="btn btn-secondary cancel-edit-form" data-section-id="ecole"><?php echo __('cancel_btn', 'Annuler'); ?></button>
        </form>
    </div>
</div>
