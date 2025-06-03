<?php // Data available: $parametres_generaux from index.php which gets it from controller ?>

<div class="pt-3">
    <h4><?php echo __('general_settings_title', 'Informations Générales'); ?></h4>

    <div id="display-generaux" class="display-section">
        <?php if ($parametres_generaux): ?>
            <dl class="row">
                <dt class="col-sm-3"><?php echo __('default_language', 'Langue par défaut'); ?></dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($parametres_generaux->langue_defaut ?? ''); ?></dd>

                <dt class="col-sm-3"><?php echo __('theme', 'Thème'); ?></dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($parametres_generaux->theme ?? ''); ?></dd>

                <dt class="col-sm-3"><?php echo __('currency', 'Devise'); ?></dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($parametres_generaux->devise_monnaie ?? ''); ?></dd>

                <dt class="col-sm-3"><?php echo __('country', 'Pays'); ?></dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($parametres_generaux->pays ?? ''); ?></dd>

                <dt class="col-sm-3"><?php echo __('region', 'Région'); ?></dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($parametres_generaux->region ?? ''); ?></dd>

                <dt class="col-sm-3"><?php echo __('city', 'Ville'); ?></dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($parametres_generaux->ville ?? ''); ?></dd>

                <dt class="col-sm-3"><?php echo __('district', 'Quartier'); ?></dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($parametres_generaux->quartier ?? ''); ?></dd>

                <dt class="col-sm-3"><?php echo __('number_of_languages', 'Nombre de langues'); ?></dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($parametres_generaux->nombre_langues ?? ''); ?></dd>

                <dt class="col-sm-3"><?php echo __('language_1', 'Langue 1'); ?></dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($parametres_generaux->langue_1 ?? ''); ?></dd>

                <dt class="col-sm-3"><?php echo __('language_2', 'Langue 2'); ?></dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($parametres_generaux->langue_2 ?? ''); ?></dd>

                <dt class="col-sm-3"><?php echo __('language_3', 'Langue 3'); ?></dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($parametres_generaux->langue_3 ?? ''); ?></dd>
            </dl>
        <?php else: ?>
            <p><?php echo __('no_general_settings_found', 'Aucun paramètre général trouvé. Veuillez les configurer.'); ?></p>
        <?php endif; ?>
        <button class="btn btn-primary toggle-edit-form" data-section-id="generaux">
            <?php echo $parametres_generaux ? __('edit_btn', 'Modifier') : __('configure_btn', 'Configurer'); ?>
        </button>
    </div>

    <div id="edit-generaux" class="edit-form-section">
        <h5><?php echo $parametres_generaux ? __('edit_general_settings_form_title', 'Modifier les Paramètres Généraux') : __('configure_general_settings_form_title', 'Configurer les Paramètres Généraux'); ?></h5>
        <form action="<?php echo base_url('parametres/update_generaux'); ?>" method="POST">
            <!-- Fields should be pre-filled with $parametres_generaux values -->
            <div class="form-group">
                <label for="generaux_langue_defaut"><?php echo __('default_language', 'Langue par défaut'); ?></label>
                <input type="text" class="form-control" id="generaux_langue_defaut" name="langue_defaut" value="<?php echo htmlspecialchars($parametres_generaux->langue_defaut ?? 'fr'); ?>">
            </div>
            <div class="form-group">
                <label for="generaux_theme"><?php echo __('theme', 'Thème'); ?></label>
                <input type="text" class="form-control" id="generaux_theme" name="theme" value="<?php echo htmlspecialchars($parametres_generaux->theme ?? 'default'); ?>">
            </div>
            <div class="form-group">
                <label for="generaux_devise_monnaie"><?php echo __('currency', 'Devise'); ?></label>
                <input type="text" class="form-control" id="generaux_devise_monnaie" name="devise_monnaie" value="<?php echo htmlspecialchars($parametres_generaux->devise_monnaie ?? 'XOF'); ?>">
            </div>
            <div class="form-group">
                <label for="generaux_pays"><?php echo __('country', 'Pays'); ?></label>
                <input type="text" class="form-control" id="generaux_pays" name="pays" value="<?php echo htmlspecialchars($parametres_generaux->pays ?? ''); ?>">
            </div>
             <div class="form-group">
                <label for="generaux_region"><?php echo __('region', 'Région'); ?></label>
                <input type="text" class="form-control" id="generaux_region" name="region" value="<?php echo htmlspecialchars($parametres_generaux->region ?? ''); ?>">
            </div>
             <div class="form-group">
                <label for="generaux_ville"><?php echo __('city', 'Ville'); ?></label>
                <input type="text" class="form-control" id="generaux_ville" name="ville" value="<?php echo htmlspecialchars($parametres_generaux->ville ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="generaux_quartier"><?php echo __('district', 'Quartier'); ?></label>
                <input type="text" class="form-control" id="generaux_quartier" name="quartier" value="<?php echo htmlspecialchars($parametres_generaux->quartier ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="generaux_nombre_langues"><?php echo __('number_of_languages', 'Nombre de langues'); ?></label>
                <input type="number" class="form-control" id="generaux_nombre_langues" name="nombre_langues" value="<?php echo htmlspecialchars($parametres_generaux->nombre_langues ?? 1); ?>" min="1" max="3">
            </div>
            <div class="form-group">
                <label for="generaux_langue_1"><?php echo __('language_1', 'Langue 1'); ?></label>
                <input type="text" class="form-control" id="generaux_langue_1" name="langue_1" value="<?php echo htmlspecialchars($parametres_generaux->langue_1 ?? 'fr'); ?>">
            </div>
            <div class="form-group">
                <label for="generaux_langue_2"><?php echo __('language_2', 'Langue 2 (Optionnel)'); ?></label>
                <input type="text" class="form-control" id="generaux_langue_2" name="langue_2" value="<?php echo htmlspecialchars($parametres_generaux->langue_2 ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="generaux_langue_3"><?php echo __('language_3', 'Langue 3 (Optionnel)'); ?></label>
                <input type="text" class="form-control" id="generaux_langue_3" name="langue_3" value="<?php echo htmlspecialchars($parametres_generaux->langue_3 ?? ''); ?>">
            </div>
            <button type="submit" class="btn btn-success"><?php echo __('save_changes_btn', 'Enregistrer'); ?></button>
            <button type="button" class="btn btn-secondary cancel-edit-form" data-section-id="generaux"><?php echo __('cancel_btn', 'Annuler'); ?></button>
        </form>
    </div>
</div>
