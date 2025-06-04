<?php // File: app/Views/admin/parametres_ecole/index.php
// $data['settings']
// $data['title']
// All text should use __() for i18n.
// FCPATH should be defined in public/index.php for file_exists checks if used here.
// URL_ROOT for displaying image.
?>

<div class="container mt-4">
    <h1><?php echo htmlspecialchars($title ?? __('pe_title_default', 'School Parameters')); ?></h1>

    <?php
    // Flash message display (already in default.php layout, but can be here for specific placement)
    // if (isset($_SESSION['flash_message'])) {
    //    echo '<div class="alert alert-' . htmlspecialchars($_SESSION['flash_message']['type']) . '">' . htmlspecialchars($_SESSION['flash_message']['text']) . '</div>';
    //    unset($_SESSION['flash_message']);
    // }
    ?>

    <form action="<?php echo URL_ROOT; ?>/admin/parametresecole/update" method="POST" enctype="multipart/form-data" class="mt-3">
        <div class="form-group">
            <label for="nom_ecole"><?php echo __('pe_label_nom_ecole', 'School Name'); ?>:</label>
            <input type="text" id="nom_ecole" name="nom_ecole" class="form-control"
                   value="<?php echo htmlspecialchars($settings->nom_ecole ?? ''); ?>" required>
        </div>

        <div class="form-group">
            <label for="type_etablissement"><?php echo __('pe_label_type_etablissement', 'Type of Establishment'); ?>:</label>
            <select id="type_etablissement" name="type_etablissement" class="form-control">
                <?php
                $types = ['public', 'prive', 'parapublic'];
                $current_type = $settings->type_etablissement ?? 'prive';
                ?>
                <?php foreach($types as $type): ?>
                    <option value="<?php echo $type; ?>" <?php echo ($current_type == $type) ? 'selected' : ''; ?>>
                        <?php echo __("school_type_$type", ucfirst($type)); // e.g., school_type_public, school_type_prive ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="logo_upload"><?php echo __('pe_label_logo_upload', 'School Logo'); ?>:</label>
            <input type="file" id="logo_upload" name="logo_upload" class="form-control-file">
            <?php if (!empty($settings->logo_path)): ?>
                <?php
                // Construct absolute path for file_exists check if FCPATH is available
                // FCPATH should end with a slash. logo_path is relative like 'uploads/logos/file.png'
                $logo_system_path = (defined('FCPATH') ? FCPATH : '') . $settings->logo_path;
                if (file_exists($logo_system_path)):
                ?>
                    <p class="mt-2"><?php echo __('pe_current_logo', 'Current Logo:'); ?> <br>
                        <img src="<?php echo URL_ROOT . '/' . htmlspecialchars($settings->logo_path); ?>"
                             alt="<?php echo __('pe_logo_alt', 'School Logo'); ?>" style="max-height: 100px; border: 1px solid #ddd; padding: 5px;">
                    </p>
                <?php elseif (!empty($settings->logo_path)): ?>
                    <p class="text-danger mt-2"><?php echo __('pe_logo_path_invalid', 'Current logo path seems invalid or file is missing.'); ?> (<?php echo htmlspecialchars($settings->logo_path); ?>)</p>
                <?php endif; ?>
            <?php else: ?>
                <p class="mt-2 text-muted"><?php echo __('pe_no_logo_uploaded', 'No logo currently uploaded.'); ?></p>
            <?php endif; ?>
            <small class="form-text text-muted"><?php echo __('pe_logo_upload_hint', 'Upload a new logo to replace the current one. Allowed types: JPG, PNG, GIF.'); ?></small>
        </div>

        <div class="form-group">
            <label for="adresse_physique"><?php echo __('pe_label_adresse_physique', 'Physical Address'); ?>:</label>
            <textarea id="adresse_physique" name="adresse_physique" class="form-control" rows="3"><?php echo htmlspecialchars($settings->adresse_physique ?? ''); ?></textarea>
        </div>

        <div class="form-group">
            <label for="boite_postale"><?php echo __('pe_label_boite_postale', 'P.O. Box'); ?>:</label>
            <input type="text" id="boite_postale" name="boite_postale" class="form-control"
                   value="<?php echo htmlspecialchars($settings->boite_postale ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="telephone_contact"><?php echo __('pe_label_telephone_contact', 'Contact Phone'); ?>:</label>
            <input type="tel" id="telephone_contact" name="telephone_contact" class="form-control"
                   value="<?php echo htmlspecialchars($settings->telephone_contact ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="email_contact"><?php echo __('pe_label_email_contact', 'Contact Email'); ?>:</label>
            <input type="email" id="email_contact" name="email_contact" class="form-control"
                   value="<?php echo htmlspecialchars($settings->email_contact ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="site_web"><?php echo __('pe_label_site_web', 'Website'); ?>:</label>
            <input type="url" id="site_web" name="site_web" class="form-control"
                   value="<?php echo htmlspecialchars($settings->site_web ?? ''); ?>" placeholder="https://www.example.com">
        </div>

        <button type="submit" class="btn btn-primary mt-3">
            <?php echo __('global_save_button', 'Save Changes'); ?>
        </button>
    </form>
</div>
