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

<h1><?= $title ?? __('eleves_title_list') ?></h1>

<div class="admin-actions">
    <?php if (auth_can('create_eleve')): ?>
        <a href="<?= URL_ROOT ?>/admin/eleves/add" class="button primary-button"><?= __('eleves_add_new_button') ?></a>
    <?php endif; ?>
</div>

<?php display_flash_messages(); ?>

<form method="GET" action="<?= URL_ROOT ?>/admin/eleves/index" class="filter-form responsive-form">
    <fieldset>
        <legend><?= __('eleves_filter_title') ?></legend>
        <div class="form-row">
            <div class="form-group">
                <label for="search"><?= __('eleves_filter_search_label') ?>:</label>
                <input type="text" name="search" id="search" value="<?= htmlspecialchars($filters['search'] ?? '') ?>" placeholder="<?= __('eleves_filter_search_placeholder_matricule_nom_email') ?>">
            </div>
            <div class="form-group">
                <label for="statut_eleve"><?= __('eleves_filter_status_label') ?>:</label>
                <select name="statut_eleve" id="statut_eleve">
                    <option value=""><?= __('global_all_statuses') ?></option>
                    <?php foreach($statutsList as $status): ?>
                        <option value="<?= $status ?>" <?= (($filters['statut_eleve'] ?? '') === $status) ? 'selected' : '' ?>><?= __("eleve_statut_$status") ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="classe_id"><?= __('eleves_filter_classe_label') ?>:</label>
                <select name="classe_id" id="classe_id">
                    <option value=""><?= __('global_all_classes') ?></option>
                    <?php if (!empty($classesList)): foreach($classesList as $classe): ?>
                        <option value="<?= $classe->id ?>" <?= (($filters['classe_id'] ?? '') == $classe->id) ? 'selected' : '' ?>><?= htmlspecialchars($classe->nom) ?></option>
                    <?php endforeach; endif; ?>
                </select>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="button"><?= __('eleves_filter_button') ?></button>
            <a href="<?= URL_ROOT ?>/admin/eleves/index" class="button-link"><?= __('eleves_filter_clear_button') ?></a>
        </div>
    </fieldset>
</form>

<p><?= sprintf(__('eleves_total_found'), $totalEleves ?? 0) ?></p>

<div class="table-responsive">
    <table>
        <thead>
            <tr>
                <th><a href="<?= URL_ROOT ?>/admin/eleves?<?= http_build_query(array_merge($filters, ['orderBy'=>'e.matricule', 'orderDir'=>($orderBy=='e.matricule'&&$orderDir=='ASC'?'DESC':'ASC')])) ?>"><?= __('eleves_col_matricule') ?></a></th>
                <th><a href="<?= URL_ROOT ?>/admin/eleves?<?= http_build_query(array_merge($filters, ['orderBy'=>'e.nom_famille, e.prenom', 'orderDir'=>($orderBy=='e.nom_famille, e.prenom'&&$orderDir=='ASC'?'DESC':'ASC')])) ?>"><?= __('eleves_col_nom_complet') ?></a></th>
                <th><?= __('eleves_col_classe_actuelle') ?></th>
                <th><?= __('eleves_col_statut') ?></th>
                <th><?= __('eleves_col_actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($eleves)): foreach($eleves as $eleve): ?>
            <tr>
                <td data-label="<?= __('eleves_col_matricule') ?>"><?= htmlspecialchars($eleve->matricule) ?></td>
                <td data-label="<?= __('eleves_col_nom_complet') ?>"><?= htmlspecialchars($eleve->nom_famille . ' ' . $eleve->prenom) ?></td>
                <td data-label="<?= __('eleves_col_classe_actuelle') ?>"><?= htmlspecialchars($eleve->classe_nom ?? __('global_none')) ?>
                    <?php if($eleve->annee_academique_libelle): ?>
                        <small>(<?= htmlspecialchars($eleve->annee_academique_libelle) ?>)</small>
                    <?php endif; ?>
                </td>
                <td data-label="<?= __('eleves_col_statut') ?>"><?= __("eleve_statut_" . $eleve->statut_eleve) ?></td>
                <td data-label="<?= __('eleves_col_actions') ?>" class="actions-cell">
                    <?php if (auth_can('edit_eleve')): ?>
                        <a href="<?= URL_ROOT ?>/admin/eleves/edit/<?= $eleve->id ?>" class="button-link edit-link" title="<?= __('global_edit_button') ?>"><i class="fas fa-edit"></i></a>
                    <?php endif; ?>
                    <?php if (auth_can('delete_eleve')): ?>
                        <form method="POST" action="<?= URL_ROOT ?>/admin/eleves/delete/<?= $eleve->id ?>" class="inline-form" onsubmit="return confirm('<?= __('global_confirm_delete_generic') ?>')">
                            <button type="submit" class="button-link delete-link" title="<?= __('global_delete_button') ?>"><i class="fas fa-trash"></i></button>
                        </form>
                    <?php endif; ?>
                    <?php /* Conceptual Reactivate Button - Requires controller logic */ ?>
                    <?php if (auth_can('reactivate_user_account') && $eleve->statut_eleve !== 'actif' && $eleve->statut_eleve !== 'inscrit'): ?>
                         <form method="POST" action="<?= URL_ROOT ?>/admin/users/reactivate/<?= $eleve->utilisateur_id ?? '' ?>" class="inline-form" onsubmit="return confirm('<?= __('eleves_confirm_reactivate', ['name' => htmlspecialchars($eleve->prenom . ' ' . $eleve->nom_famille)]) ?>')">
                            <?php /* This assumes eleve has a utilisateur_id for user account linking */ ?>
                            <button type="submit" class="button-link reactivate-link" title="<?= __('eleves_action_reactivate') ?>"><i class="fas fa-user-check"></i></button>
                        </form>
                    <?php endif; ?>
                     <a href="<?= URL_ROOT ?>/admin/comptabiliteeleve/voir_compte/<?= $eleve->id ?>" class="button-link accounting-link" title="<?= __('compta_eleve_link_view_account') ?>"><i class="fas fa-dollar-sign"></i></a>
                </td>
            </tr>
            <?php endforeach; else: ?>
            <tr>
                <td colspan="5"><?= __('eleves_no_eleves_found') ?></td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if (($totalPages ?? 0) > 1): ?>
<nav class="pagination">
    <ul>
        <?php if ($currentPage > 1): ?>
            <li><a href="<?= URL_ROOT ?>/admin/eleves?<?= http_build_query(array_merge($filters, ['orderBy'=>$orderBy, 'orderDir'=>$orderDir, 'page' => $currentPage - 1])) ?>">&laquo; <?= __('pagination_previous') ?></a></li>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($i == $currentPage): ?>
                <li class="active"><span><?= $i ?></span></li>
            <?php else: ?>
                <li><a href="<?= URL_ROOT ?>/admin/eleves?<?= http_build_query(array_merge($filters, ['orderBy'=>$orderBy, 'orderDir'=>$orderDir, 'page' => $i])) ?>"><?= $i ?></a></li>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($currentPage < $totalPages): ?>
            <li><a href="<?= URL_ROOT ?>/admin/eleves?<?= http_build_query(array_merge($filters, ['orderBy'=>$orderBy, 'orderDir'=>$orderDir, 'page' => $currentPage + 1])) ?>"><?= __('pagination_next') ?> &raquo;</a></li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>
<p class="current-page-info"><?= sprintf(__('pagination_page_x_of_y'), $currentPage, $totalPages) ?></p>
<p><?= sprintf(__('pagination_total_records'), $totalEleves) ?></p>
<br>
<hr>
<p><small><em><?= __('eleves_index_note_reactivate') ?></em></small></p>
<p><small><em><?= __('eleves_index_note_accounting_link') ?></em></small></p>
