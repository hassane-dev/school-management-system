<?php
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
<h1><?= $title ?? __('compta_eleve_title_select_student') ?></h1>
<?php display_flash_messages(); ?>

<form method="GET" action="<?= URL_ROOT ?>/admin/comptabiliteeleve/index" class="filter-form responsive-form">
    <fieldset>
        <legend><?= __('eleves_filter_title') ?></legend>
        <div class="form-row">
            <div class="form-group">
                <label for="search"><?= __('eleves_filter_search_label') ?>:</label>
                <input type="text" name="search" id="search" value="<?= htmlspecialchars($filters['search'] ?? '') ?>" placeholder="<?= __('eleves_filter_search_placeholder_matricule_nom') ?>">
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="button"><?= __('eleves_filter_button') ?></button>
            <a href="<?= URL_ROOT ?>/admin/comptabiliteeleve/index" class="button-link"><?= __('eleves_filter_clear_button') ?></a>
        </div>
    </fieldset>
</form>

<?php if (!empty($eleves)): ?>
<div class="table-responsive">
    <table>
        <thead>
            <tr>
                <th><?= __('eleves_col_matricule') ?></th>
                <th><?= __('eleves_col_nom_complet') ?></th>
                <th><?= __('eleves_col_classe_actuelle') ?></th>
                <th><?= __('compta_eleve_action_view_account') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($eleves as $eleve): ?>
            <tr>
                <td data-label="<?= __('eleves_col_matricule') ?>"><?= htmlspecialchars($eleve->matricule) ?></td>
                <td data-label="<?= __('eleves_col_nom_complet') ?>"><?= htmlspecialchars($eleve->nom_famille . ' ' . $eleve->prenom) ?></td>
                <td data-label="<?= __('eleves_col_classe_actuelle') ?>">
                    <?= htmlspecialchars($eleve->classe_nom ?? __('global_none')) ?>
                    <?php if($eleve->annee_academique_libelle): ?>
                        <small>(<?= htmlspecialchars($eleve->annee_academique_libelle) ?>)</small>
                    <?php endif; ?>
                </td>
                <td data-label="<?= __('compta_eleve_action_view_account') ?>" class="actions-cell">
                    <a href="<?= URL_ROOT ?>/admin/comptabiliteeleve/voir_compte/<?= $eleve->id ?>" class="button-link" title="<?= __('compta_eleve_link_view_account') ?>"><i class="fas fa-eye"></i> <?= __('compta_eleve_link_view_account') ?></a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
$totalRecords = $totalEleves ?? 0; // Assuming $totalEleves is passed from controller
if (($totalPages ?? 0) > 1):
    $currentFilters = $filters ?? [];
    unset($currentFilters['page']); // Remove page from filters for query building
?>
<nav class="pagination">
    <ul>
        <?php if ($currentPage > 1): ?>
            <li><a href="<?= URL_ROOT ?>/admin/comptabiliteeleve/index?<?= http_build_query(array_merge($currentFilters, ['page' => $currentPage - 1])) ?>">&laquo; <?= __('pagination_previous') ?></a></li>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($i == $currentPage): ?>
                <li class="active"><span><?= $i ?></span></li>
            <?php else: ?>
                <li><a href="<?= URL_ROOT ?>/admin/comptabiliteeleve/index?<?= http_build_query(array_merge($currentFilters, ['page' => $i])) ?>"><?= $i ?></a></li>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($currentPage < $totalPages): ?>
            <li><a href="<?= URL_ROOT ?>/admin/comptabiliteeleve/index?<?= http_build_query(array_merge($currentFilters, ['page' => $currentPage + 1])) ?>"><?= __('pagination_next') ?> &raquo;</a></li>
        <?php endif; ?>
    </ul>
</nav>
<p class="current-page-info"><?= sprintf(__('pagination_page_x_of_y'), $currentPage, $totalPages) ?></p>
<p><?= sprintf(__('pagination_total_records'), $totalRecords) ?></p>
<?php endif; ?>

<?php else: ?>
    <p><?= __('eleves_no_eleves_found') ?></p>
<?php endif; ?>
<br>
<hr>
<p><small><em><?= __('compta_eleve_index_note') ?></em></small></p>
