<?php
// Data available: $parametres_generaux, $parametres_ecole, $annees_academiques, $active_annee (from controller)
// Note: $annees_academiques was the name in controller, let's align if it's a list.
// Assuming $annees_academiques is the list for the _annees partial.
$annees_academiques_list = $annees_academiques; // Alias for clarity in partial
?>

<div class="mb-4">
    <h2><?php echo __('settings_management_title', 'Gestion des Paramètres'); ?></h2>
</div>

<!-- Nav tabs -->
<ul class="nav nav-tabs" id="settingsTabs" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" id="generaux-tab" data-toggle="tab" href="#generaux" role="tab" aria-controls="generaux" aria-selected="true"><?php echo __('general_settings_tab', 'Paramètres Généraux'); ?></a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="ecole-tab" data-toggle="tab" href="#ecole" role="tab" aria-controls="ecole" aria-selected="false"><?php echo __('school_settings_tab', 'Paramètres de l’École'); ?></a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="annees-tab" data-toggle="tab" href="#annees" role="tab" aria-controls="annees" aria-selected="false"><?php echo __('academic_years_tab', 'Années Académiques'); ?></a>
    </li>
</ul>

<!-- Tab content -->
<div class="tab-content" id="settingsTabsContent">
    <div class="tab-pane fade show active" id="generaux" role="tabpanel" aria-labelledby="generaux-tab">
        <?php require '_generaux.php'; ?>
    </div>
    <div class="tab-pane fade" id="ecole" role="tabpanel" aria-labelledby="ecole-tab">
        <?php require '_ecole.php'; ?>
    </div>
    <div class="tab-pane fade" id="annees" role="tabpanel" aria-labelledby="annees-tab">
        <?php require '_annees.php'; ?>
    </div>
</div>

<!-- Helper for base URL, adjust if your routing is different -->
<?php
function base_url($path = '') {
    // Replace this with your actual base URL logic if needed
    // This basic version assumes index.php is in the public root and handles URLs like /index.php?url=controller/method
    $url_prefix = '/index.php?url=';
    // For htaccess rewrite, it might be just /
    // $url_prefix = '/';
    return $url_prefix . $path;
}
?>
