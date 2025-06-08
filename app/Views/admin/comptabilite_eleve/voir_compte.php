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
$app_currency = get_setting('app_currency', 'XOF'); // Default to XOF if not set
$date_format = get_setting('format_date', 'd/m/Y'); // Default date format
?>
<h1><?= $title // __('compta_eleve_title_student_account') . ': ' . $eleve->nom_complet ?></h1>
<?php display_flash_messages(); ?>

<div class="admin-actions">
    <a href="<?= URL_ROOT ?>/admin/comptabiliteeleve" class="button-link">&laquo; <?= __('compta_eleve_back_to_student_list') ?></a>
</div>

<div class="student-info-header">
    <h2><?= htmlspecialchars($eleve->nom_complet ?? ($eleve->prenom . ' ' . $eleve->nom_famille)) ?></h2>
    <p><strong><?= __('eleves_col_matricule') ?>:</strong> <?= htmlspecialchars($eleve->matricule) ?></p>
    <?php if (isset($anneeCourante) && $anneeCourante): ?>
        <p><strong><?= __('compta_eleve_current_year_selected') ?>:</strong> <?= htmlspecialchars($anneeCourante->libelle) ?></p>
    <?php endif; ?>
</div>


<form method="GET" action="<?= URL_ROOT ?>/admin/comptabiliteeleve/voir_compte/<?= $eleve->id ?>" class="filter-form responsive-form">
    <fieldset>
        <legend><?= __('compta_eleve_filter_by_year_title') ?></legend>
        <div class="form-row">
            <div class="form-group">
                <label for="annee_academique_id"><?= __('compta_eleve_filter_by_year') ?>:</label>
                <select name="annee_academique_id" id="annee_academique_id" onchange="this.form.submit()">
                    <?php if(!empty($anneesList)): foreach($anneesList as $annee): ?>
                    <option value="<?= $annee->id ?>" <?= (($anneeIdFilter ?? '') == $annee->id ? 'selected' : '') ?>><?= htmlspecialchars($annee->libelle) ?></option>
                    <?php endforeach; else: ?>
                    <option value=""><?= __('global_no_options') ?></option>
                    <?php endif; ?>
                </select>
            </div>
        </div>
    </fieldset>
</form>

<div class="dashboard-sections">
    <section id="applied-reductions" class="dashboard-section">
        <h3><?= __('compta_eleve_section_applied_reductions') ?></h3>
        <?php if (auth_can('apply_reduction_eleve')): ?>
            <p><a href="<?= URL_ROOT ?>/admin/comptabiliteeleve/manage_reductions/<?= $eleve->id ?>/<?= $anneeIdFilter ?? '' ?>" class="button primary-button"><i class="fas fa-tags"></i> <?= __('compta_eleve_button_manage_reductions') ?></a></p>
        <?php endif; ?>
        <?php if(!empty($appliedReductions)): ?>
            <ul class="info-list">
            <?php foreach($appliedReductions as $ar): ?>
                <li>
                    <strong><?= htmlspecialchars($ar->nom_reduction) ?>:</strong>
                    <?php if ($ar->pourcentage_reduction > 0): ?>
                        <?= htmlspecialchars(number_format((float)$ar->pourcentage_reduction, 2)) ?>%
                    <?php elseif ($ar->montant_fixe_reduction > 0): ?>
                        <?= htmlspecialchars(number_format((float)$ar->montant_fixe_reduction, 0, '.', ' ')) ?> <?= htmlspecialchars($app_currency) ?>
                    <?php else: ?>
                         <!-- This case means reduction was likely manually entered or calculation error -->
                        <?= htmlspecialchars(number_format((float)($ar->montant_calcule_reduction ?? 0), 0, '.', ' ')) ?> <?= htmlspecialchars($app_currency) ?> (<?= __('compta_eleve_reduction_custom_amount') ?>)
                    <?php endif; ?>

                    <?php if(!empty($ar->commentaire)): ?>
                        <small>(<?= htmlspecialchars($ar->commentaire) ?>)</small>
                    <?php endif; ?>
                     <br><small><?= __('compta_eleve_reduction_applied_on') ?> <?= date($date_format, strtotime($ar->date_application)) ?>
                     <?php if($ar->utilisateur_nom_complet): ?> <?= __('compta_eleve_reduction_by') ?> <?= htmlspecialchars($ar->utilisateur_nom_complet) ?> <?php endif; ?>
                     </small>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p><?= __('compta_eleve_no_reductions_applied') ?></p>
        <?php endif; ?>
    </section>

    <section id="payment-summary" class="dashboard-section">
        <h3><?= __('compta_eleve_section_summary') ?></h3>
        <table class="summary-table">
            <tr><td><?= __('compta_eleve_summary_total_attendu') ?>:</td><td><?= htmlspecialchars(number_format($totalAttendu ?? 0, 0, '.', ' ')) ?> <?= htmlspecialchars($app_currency) ?></td></tr>
            <tr><td><?= __('compta_eleve_summary_total_reduction') ?>:</td><td><?= htmlspecialchars(number_format($totalReductionAnnee ?? 0, 0, '.', ' ')) ?> <?= htmlspecialchars($app_currency) ?></td></tr>
            <tr><td><strong><?= __('compta_eleve_summary_net_a_payer') ?>:</strong></td><td><strong><?= htmlspecialchars(number_format(($totalAttendu ?? 0) - ($totalReductionAnnee ?? 0), 0, '.', ' ')) ?> <?= htmlspecialchars($app_currency) ?></strong></td></tr>
            <tr><td><?= __('compta_eleve_summary_total_paye') ?>:</td><td><?= htmlspecialchars(number_format($totalPaye ?? 0, 0, '.', ' ')) ?> <?= htmlspecialchars($app_currency) ?></td></tr>
            <tr><td><strong><?= __('compta_eleve_summary_solde') ?>:</strong></td><td class="<?= ($solde ?? 0) >= 0 ? 'solde-positif' : 'solde-negatif' ?>"><strong><?= htmlspecialchars(number_format($solde ?? 0, 0, '.', ' ')) ?> <?= htmlspecialchars($app_currency) ?></strong></td></tr>
        </table>
    </section>
</div>


<section id="payment-history">
    <h3><?= __('compta_eleve_section_payment_history') ?></h3>
    <?php if (auth_can('manage_comptabilite_eleve')): ?>
            <p><a href="<?= URL_ROOT ?>/admin/comptabiliteeleve/manage_mensualites/<?= $eleve->id ?>/<?= $anneeIdFilter ?? '' ?>" class="button primary-button"><i class="fas fa-cash-register"></i> <?= __('compta_eleve_button_manage_payments') ?></a></p>
    <?php endif; ?>

    <?php if(!empty($mensualites)): ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= __('compta_eleve_col_mois') ?></th>
                    <th><?= __('compta_eleve_col_annee_concernee') ?></th>
                    <th><?= __('compta_eleve_col_montant_attendu') ?></th>
                    <th><?= __('compta_eleve_col_reduction_appliquee') ?></th>
                    <th><?= __('compta_eleve_col_montant_a_payer') ?></th>
                    <th><?= __('compta_eleve_col_montant_paye') ?></th>
                    <th><?= __('compta_eleve_col_solde_mois') ?></th>
                    <th><?= __('compta_eleve_col_statut_paiement') ?></th>
                    <th><?= __('compta_eleve_col_date_dernier_paiement') ?></th>
                    <th><?= __('compta_eleve_col_recu') ?></th>
                </tr>
            </thead>
            <tbody>
            <?php
            $runningTotalAttenduAnnee = 0; $runningTotalReductionAnnee = 0;
            $runningTotalAPayerAnnee = 0; $runningTotalPayeAnnee = 0;
            foreach($mensualites as $m):
                $montantAPayerMois = ($m->montant_attendu ?? 0) - ($m->montant_reduction_applique ?? 0);
                $soldeMois = $montantAPayerMois - ($m->montant_paye ?? 0);

                $runningTotalAttenduAnnee += ($m->montant_attendu ?? 0);
                $runningTotalReductionAnnee += ($m->montant_reduction_applique ?? 0);
                $runningTotalAPayerAnnee += $montantAPayerMois;
                $runningTotalPayeAnnee += ($m->montant_paye ?? 0);
            ?>
            <tr>
                <td data-label="<?= __('compta_eleve_col_mois') ?>"><?= htmlspecialchars($m->mois) ?></td>
                <td data-label="<?= __('compta_eleve_col_annee_concernee') ?>"><?= htmlspecialchars($m->annee_concernee) ?></td>
                <td data-label="<?= __('compta_eleve_col_montant_attendu') ?>"><?= htmlspecialchars(number_format((float)($m->montant_attendu ?? 0), 0, '.', ' ')) ?></td>
                <td data-label="<?= __('compta_eleve_col_reduction_appliquee') ?>"><?= htmlspecialchars(number_format((float)($m->montant_reduction_applique ?? 0), 0, '.', ' ')) ?></td>
                <td data-label="<?= __('compta_eleve_col_montant_a_payer') ?>"><strong><?= htmlspecialchars(number_format((float)$montantAPayerMois, 0, '.', ' ')) ?></strong></td>
                <td data-label="<?= __('compta_eleve_col_montant_paye') ?>"><?= htmlspecialchars(number_format((float)($m->montant_paye ?? 0), 0, '.', ' ')) ?></td>
                <td data-label="<?= __('compta_eleve_col_solde_mois') ?>" style="color: <?= $soldeMois > 0 ? 'red' : ($soldeMois < 0 ? 'darkorange' : 'green') ?>;">
                    <?= htmlspecialchars(number_format((float)$soldeMois, 0, '.', ' ')) ?>
                </td>
                <td data-label="<?= __('compta_eleve_col_statut_paiement') ?>"><?= __("payment_status_" . ($m->statut_paiement ?? 'unknown')) ?></td>
                <td data-label="<?= __('compta_eleve_col_date_dernier_paiement') ?>"><?= $m->date_dernier_paiement ? date($date_format, strtotime($m->date_dernier_paiement)) : '-' ?></td>
                <td data-label="<?= __('compta_eleve_col_recu') ?>">
                    <?php if($m->numero_recu_dernier_paiement && auth_can('print_recu_eleve')): ?>
                        <a href="<?= URL_ROOT ?>/admin/comptabiliteeleve/print_recu/<?= $m->id ?>" target="_blank" title="<?= __('compta_eleve_print_receipt_for') . ' ' . htmlspecialchars($m->numero_recu_dernier_paiement) ?>"><i class="fas fa-print"></i> <?= htmlspecialchars($m->numero_recu_dernier_paiement) ?></a>
                    <?php elseif($m->numero_recu_dernier_paiement):
                        echo htmlspecialchars($m->numero_recu_dernier_paiement);
                    elseif ($m->montant_paye > 0 && auth_can('print_recu_eleve')): // Generic print if paid but no number
                         echo '<a href="'.URL_ROOT.'/admin/comptabiliteeleve/print_recu/'.$m->id.'" target="_blank" title="'.__('compta_eleve_print_receipt').'"><i class="fas fa-print"></i></a>';
                    endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2"><strong><?= __('global_total') ?></strong></td>
                    <td><strong><?= htmlspecialchars(number_format($runningTotalAttenduAnnee, 0, '.', ' ')) ?></strong></td>
                    <td><strong><?= htmlspecialchars(number_format($runningTotalReductionAnnee, 0, '.', ' ')) ?></strong></td>
                    <td><strong><?= htmlspecialchars(number_format($runningTotalAPayerAnnee, 0, '.', ' ')) ?></strong></td>
                    <td><strong><?= htmlspecialchars(number_format($runningTotalPayeAnnee, 0, '.', ' ')) ?></strong></td>
                    <td colspan="4" style="color: <?= ($runningTotalAPayerAnnee - $runningTotalPayeAnnee) > 0 ? 'red':'green'?>;">
                        <strong><?= __('compta_eleve_summary_solde_annuel') ?>: <?= htmlspecialchars(number_format($runningTotalAPayerAnnee - $runningTotalPayeAnnee, 0, '.', ' ')) ?> <?= htmlspecialchars($app_currency) ?></strong>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php else: ?>
        <p><?= __('compta_eleve_no_payments_for_year') ?></p>
        <?php if (auth_can('manage_comptabilite_eleve')): // Suggestion to generate payments ?>
             <p><?= __('compta_eleve_suggest_generate_payments') ?></p>
             <form method="POST" action="<?= URL_ROOT ?>/admin/comptabiliteeleve/manage_mensualites/<?= $eleve->id ?>/<?= $anneeIdFilter ?? '' ?>">
                 <input type="hidden" name="action" value="generate_mensualites">
                 <button type="submit" class="button"><i class="fas fa-cogs"></i> <?= __('compta_eleve_button_generate_expected_payments') ?></button>
             </form>
        <?php endif; ?>
    <?php endif; ?>
</section>
<br>
<hr>
<p><small><em><?= __('compta_eleve_voir_compte_note') ?></em></small></p>
