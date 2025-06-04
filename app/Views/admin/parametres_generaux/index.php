<?php // File: app/Views/admin/parametres_generaux/index.php
// This view is rendered by Admin\ParametresGenerauxController@index
// It receives $data['settings'] (object) and $data['title'] (string)
// It will be wrapped by the main layout: app/Views/layouts/default.php
?>

<div class="container mt-4">
    <h1><?php echo htmlspecialchars($title ?? __('pg_title_default', 'General Settings')); ?></h1>

    <?php
    // Flash message display (already in default.php layout, but can be here if specific placement needed)
    // For this example, relying on default.php layout for flash messages.
    // if (isset($_SESSION['flash_message'])) {
    //    echo '<div class="alert alert-' . htmlspecialchars($_SESSION['flash_message']['type']) . '">' . htmlspecialchars($_SESSION['flash_message']['text']) . '</div>';
    //    unset($_SESSION['flash_message']);
    // }
    ?>

    <form action="<?php echo URL_ROOT; ?>/admin/parametresgeneraux/update" method="POST" class="mt-3">
        <div class="form-group">
            <label for="nom_application"><?php echo __('pg_label_nom_application', 'Application Name'); ?>:</label>
            <input type="text" id="nom_application" name="nom_application" class="form-control"
                   value="<?php echo htmlspecialchars($settings->nom_application ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="langue_site_par_defaut"><?php echo __('pg_label_langue_site_par_defaut', 'Default Site Language'); ?>:</label>
            <select id="langue_site_par_defaut" name="langue_site_par_defaut" class="form-control">
                <?php
                // Assuming I18n class is loaded and provides available languages
                // And DEFAULT_LANG is defined in config.php
                $current_default_lang = $settings->langue_site_par_defaut ?? DEFAULT_LANG;
                if (class_exists('App\Core\I18n')) {
                    $available_langs = App\Core\I18n::getAvailableLanguages();
                    foreach($available_langs as $lang_code): ?>
                        <option value="<?php echo $lang_code; ?>" <?php echo ($current_default_lang == $lang_code) ? 'selected' : ''; ?>>
                            <?php echo strtoupper($lang_code); ?>
                        </option>
                    <?php endforeach;
                } else { // Fallback if I18n class not available for some reason
                    echo '<option value="' . htmlspecialchars($current_default_lang) . '" selected>' . strtoupper(htmlspecialchars($current_default_lang)) . '</option>';
                }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label for="theme_defaut"><?php echo __('pg_label_theme_defaut', 'Default Theme'); ?>:</label>
            <input type="text" id="theme_defaut" name="theme_defaut" class="form-control"
                   value="<?php echo htmlspecialchars($settings->theme_defaut ?? 'default'); ?>">
        </div>

        <div class="form-group">
            <label for="devise_monnaie"><?php echo __('pg_label_devise_monnaie', 'Currency'); ?>:</label>
            <input type="text" id="devise_monnaie" name="devise_monnaie" class="form-control"
                   value="<?php echo htmlspecialchars($settings->devise_monnaie ?? 'XOF'); ?>">
        </div>

        <div class="form-group">
            <label for="pays_par_defaut"><?php echo __('pg_label_pays_par_defaut', 'Default Country'); ?>:</label>
            <input type="text" id="pays_par_defaut" name="pays_par_defaut" class="form-control"
                   value="<?php echo htmlspecialchars($settings->pays_par_defaut ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="ville_par_defaut"><?php echo __('pg_label_ville_par_defaut', 'Default City'); ?>:</label>
            <input type="text" id="ville_par_defaut" name="ville_par_defaut" class="form-control"
                   value="<?php echo htmlspecialchars($settings->ville_par_defaut ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="fuseau_horaire"><?php echo __('pg_label_fuseau_horaire', 'Timezone'); ?>:</label>
            <input type="text" id="fuseau_horaire" name="fuseau_horaire" class="form-control"
                   value="<?php echo htmlspecialchars($settings->fuseau_horaire ?? 'UTC'); ?>">
            <?php // In a real app, this would ideally be a select dropdown populated with PHP's timezone identifiers ?>
        </div>

        <div class="form-group">
            <label for="format_date"><?php echo __('pg_label_format_date', 'Date Format'); ?>:</label>
            <input type="text" id="format_date" name="format_date" class="form-control"
                   value="<?php echo htmlspecialchars($settings->format_date ?? 'd/m/Y'); ?>">
            <?php // Could be a select with common formats: 'd/m/Y', 'm/d/Y', 'Y-m-d' ?>
        </div>

        <div class="form-group">
            <label for="format_heure"><?php echo __('pg_label_format_heure', 'Time Format'); ?>:</label>
            <input type="text" id="format_heure" name="format_heure" class="form-control"
                   value="<?php echo htmlspecialchars($settings->format_heure ?? 'H:i'); ?>">
             <?php // Could be a select: 'H:i', 'h:i A' ?>
        </div>

        <div class="form-group">
            <label for="email_contact_general"><?php echo __('pg_label_email_contact_general', 'General Contact Email'); ?>:</label>
            <input type="email" id="email_contact_general" name="email_contact_general" class="form-control"
                   value="<?php echo htmlspecialchars($settings->email_contact_general ?? ''); ?>">
        </div>

        <button type="submit" class="btn btn-primary mt-3">
            <?php echo __('global_save_button', 'Save Changes'); // Assuming this key exists from Sprint 1 ?>
        </button>
    </form>
</div>
