<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\I18n;
use App\Models\EleveModel;
use App\Models\MensualiteModel;
use App\Models\ReductionTypeModel;
use App\Models\EleveReductionAppliqueeModel;
use App\Models\AnneeAcademiqueModel;

class ComptabiliteEleveController extends Controller {
    private $eleveModel;
    private $mensualiteModel;
    private $reductionTypeModel;
    private $eleveReductionModel;
    private $anneeModel;

    public function __construct() {
        Auth::requireLogin();
        // Auth::requirePermission('view_comptabilite_eleve'); // Base permission - to be added after permission seeding
        $this->eleveModel = $this->model('EleveModel');
        $this->mensualiteModel = $this->model('MensualiteModel');
        $this->reductionTypeModel = $this->model('ReductionTypeModel');
        $this->eleveReductionModel = $this->model('EleveReductionAppliqueeModel');
        $this->anneeModel = $this->model('AnneeAcademiqueModel');
    }

    public function index() {
        Auth::requirePermission('view_comptabilite_eleve');
        $filters = ['search' => trim($_GET['search'] ?? '')];
        $page = (int)($_GET['page'] ?? 1);
        $perPage = 20;
        $totalEleves = $this->eleveModel->getTotalCount($filters);
        $totalPages = ceil($totalEleves / $perPage);
        $offset = ($page - 1) * $perPage;
        $options = ['limit' => $perPage, 'offset' => $offset, 'orderBy' => 'e.nom_famille, e.prenom'];
        $eleves = $this->eleveModel->getAll($filters, $options); // EleveModel->getAll fetches current class info

        $this->view('admin/comptabilite_eleve/index_eleves_list', [
            'eleves' => $eleves, 'title' => __('compta_eleve_title_select_student'),
            'filters' => $filters, 'currentPage' => $page, 'totalPages' => $totalPages
        ], 'admin_default');
    }

    public function voir_compte($eleveId) {
        Auth::requirePermission('view_comptabilite_eleve');
        $eleve = $this->eleveModel->getById((int)$eleveId);
        if (!$eleve) {
            $_SESSION['flash_message'] = ['text' => __('not_found_error', ['item'=>__('eleve')]), 'type' => 'danger'];
            redirectTo('/admin/comptabiliteeleve');
        }

        $anneeIdFilter = (int)($_GET['annee_academique_id'] ?? Auth::getActiveAcademicYearId());
        if (!$anneeIdFilter && $this->anneeModel->getActive()) { // Fallback if no active year in session somehow
             $anneeIdFilter = $this->anneeModel->getActive()->id;
        }

        $mensualites = $this->mensualiteModel->getMensualitesForStudentYear($eleveId, $anneeIdFilter);
        $appliedReductions = $this->eleveReductionModel->getAppliedReductionsForStudentYear($eleveId, $anneeIdFilter);
        $anneesList = $this->anneeModel->getAll([], ['orderBy'=>'libelle DESC']);
        $anneeCourante = $this->anneeModel->getById($anneeIdFilter);

        $totalAttendu = $this->mensualiteModel->calculateTotalDueForStudentYear($eleveId, $anneeIdFilter);
        $totalPaye = $this->mensualiteModel->calculateTotalPaidForStudentYear($eleveId, $anneeIdFilter);
        $solde = $totalAttendu - $totalPaye;


        $this->view('admin/comptabilite_eleve/voir_compte', [
            'eleve' => $eleve,
            'anneeCourante' => $anneeCourante,
            'mensualites' => $mensualites,
            'appliedReductions' => $appliedReductions,
            'title' => __('compta_eleve_title_student_account') . ': ' . htmlspecialchars($eleve->nom_complet ?: ($eleve->prenom . ' ' . $eleve->nom_famille)),
            'anneeIdFilter' => $anneeIdFilter,
            'anneesList' => $anneesList,
            'statutsPaiement' => $this->mensualiteModel->getStatutsPaiement(),
            'moisScolaires' => $this->mensualiteModel->getMoisScolaires(),
            'totalAttendu' => $totalAttendu,
            'totalPaye' => $totalPaye,
            'solde' => $solde
        ], 'admin_default');
    }

    public function manage_mensualites($eleveId, $anneeId = null) {
        Auth::requirePermission('manage_comptabilite_eleve');
        $eleve = $this->eleveModel->getById((int)$eleveId);
        if (!$eleve) {
            $_SESSION['flash_message'] = ['text' => __('not_found_error', ['item'=>__('eleve')]), 'type' => 'danger'];
            redirectTo('/admin/comptabiliteeleve');
        }

        $anneeId = (int)($anneeId ?? Auth::getActiveAcademicYearId());
        if (!$anneeId && $this->anneeModel->getActive()) {
             $anneeId = $this->anneeModel->getActive()->id;
        }
        if (!$anneeId) {
            $_SESSION['flash_message'] = ['text' => __('compta_error_no_active_year_for_manage'), 'type' => 'danger'];
            redirectTo('/admin/comptabiliteeleve/voir_compte/' . $eleveId);
            return;
        }

        $anneeCourante = $this->anneeModel->getById($anneeId);
        if (!$anneeCourante) {
            $_SESSION['flash_message'] = ['text' => __('not_found_error', ['item'=>__('annee_academique')]), 'type' => 'danger'];
            redirectTo('/admin/comptabiliteeleve/voir_compte/' . $eleveId);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action == 'record_payment' && isset($_POST['mensualite_id'])) {
                Auth::requirePermission('record_payment_eleve');
                $mensualiteId = (int)$_POST['mensualite_id'];
                $montant = (float)str_replace(',', '.', ($_POST['montant_paye'] ?? '0')); // Handle comma decimal
                $datePaiement = $_POST['date_paiement'] ?? date('Y-m-d');
                $methode = $_POST['methode_paiement'] ?? 'especes';
                $recuNumero = $_POST['numero_recu'] ?? null;
                $currentUserId = Auth::getCurrentUserId();

                // Basic Validation
                if ($montant <= 0) {
                    $_SESSION['flash_message'] = ['text' => __('compta_payment_error_negative_amount'), 'type' => 'danger'];
                } else {
                    $result = $this->mensualiteModel->recordPayment($mensualiteId, $montant, $datePaiement, $methode, $recuNumero, $currentUserId);
                    if ($result) {
                        $_SESSION['flash_message'] = ['text' => __('compta_payment_recorded_success'), 'type' => 'success'];
                    } else {
                        $_SESSION['flash_message'] = ['text' => __('compta_payment_recorded_error'), 'type' => 'danger'];
                    }
                }
            } elseif ($action == 'generate_mensualites') {
                 Auth::requirePermission('generate_mensualites_eleve');
                 // This is where the conceptual generateExpectedPayments would be called.
                 // It needs a defined fee structure (e.g., from Classe settings or general settings).
                 // For now, this is a placeholder action.
                 // $montantAnnuelAttendu = get_frais_scolarite_pour_classe_annee($eleve->classe_id, $anneeId); // Placeholder function
                 // $this->mensualiteModel->generateExpectedPayments($eleveId, $anneeId, $montantAnnuelAttendu, ...);
                 $_SESSION['flash_message'] = ['text' => __('compta_generate_mensualites_placeholder'), 'type' => 'info'];
            } elseif ($action == 'update_statut_mensualite' && isset($_POST['mensualite_id_statut'])) {
                Auth::requirePermission('edit_mensualite_eleve'); // Or a more specific permission
                $mensualiteIdStatut = (int)$_POST['mensualite_id_statut'];
                $newStatus = $_POST['new_statut_paiement'] ?? '';
                $commentaireStatut = $_POST['commentaire_statut'] ?? '';
                if (in_array($newStatus, $this->mensualiteModel->getStatutsPaiement())) {
                    if ($this->mensualiteModel->updateStatus($mensualiteIdStatut, $newStatus, $commentaireStatut)) {
                         $_SESSION['flash_message'] = ['text' => __('compta_mensualite_status_updated_success'), 'type' => 'success'];
                    } else {
                         $_SESSION['flash_message'] = ['text' => __('compta_mensualite_status_updated_error'), 'type' => 'danger'];
                    }
                } else {
                     $_SESSION['flash_message'] = ['text' => __('compta_error_invalid_status'), 'type' => 'danger'];
                }
            }
            redirectTo('/admin/comptabiliteeleve/manage_mensualites/' . $eleveId . '/' . $anneeId);
        }

        $mensualites = $this->mensualiteModel->getMensualitesForStudentYear($eleveId, $anneeId);
        $this->view('admin/comptabilite_eleve/manage_mensualites', [
            'eleve' => $eleve, 'anneeCourante' => $anneeCourante, 'mensualites' => $mensualites,
            'title' => __('compta_eleve_title_manage_payments') . ' - ' . htmlspecialchars($eleve->nom_complet ?: ($eleve->prenom . ' ' . $eleve->nom_famille)) . ' (' . htmlspecialchars($anneeCourante->libelle) . ')',
            'statutsPaiement' => $this->mensualiteModel->getStatutsPaiement(),
            'moisScolaires' => $this->mensualiteModel->getMoisScolaires(),
            'paymentMethods' => ['especes' => __('payment_method_cash'), 'cheque' => __('payment_method_cheque'), 'virement' => __('payment_method_transfer'), 'mobile' => __('payment_method_mobile'), 'autre' => __('payment_method_other')]
        ], 'admin_default');
    }

    public function manage_reductions($eleveId, $anneeId = null) {
        Auth::requirePermission('apply_reduction_eleve');
        $eleve = $this->eleveModel->getById((int)$eleveId);
        if (!$eleve) { redirectTo('/admin/comptabiliteeleve'); }

        $anneeId = (int)($anneeId ?? Auth::getActiveAcademicYearId());
         if (!$anneeId && $this->anneeModel->getActive()) { $anneeId = $this->anneeModel->getActive()->id; }
        if (!$anneeId) {
            $_SESSION['flash_message'] = ['text' => __('compta_error_no_active_year_for_manage'), 'type' => 'danger'];
            redirectTo('/admin/comptabiliteeleve/voir_compte/' . $eleveId); return;
        }
        $anneeCourante = $this->anneeModel->getById($anneeId);
        if (!$anneeCourante) {
            $_SESSION['flash_message'] = ['text' => __('not_found_error', ['item'=>__('annee_academique')]), 'type' => 'danger'];
            redirectTo('/admin/comptabiliteeleve/voir_compte/' . $eleveId); return;
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $action = $_POST['action'] ?? '';
            $currentUserId = Auth::getCurrentUserId();

            if ($action === 'apply_reduction') {
                $reductionTypeId = $_POST['reduction_type_id'] ?? null;
                $commentaire = $_POST['commentaire_reduction'] ?? '';
                // montant_calcule_reduction should ideally be calculated here or in model based on reduction type and some base amount.
                // For now, assume it's either pre-calculated or the model handles it if type is fixed/percentage.
                // Simplified: let model handle calculation if possible, or expect it in POST if complex.
                // $montantCalcule = $_POST['montant_calcule_reduction'] ?? 0; // Potentially

                if ($reductionTypeId) {
                    // Fetch reduction type details to decide on calculation (if not done by model->applyReduction)
                    $reductionType = $this->reductionTypeModel->getById($reductionTypeId);
                    $montantCalcule = 0;
                    if ($reductionType) {
                        // This is a placeholder for actual fee structure.
                        // E.g., if reduction applies to total annual fees vs monthly.
                        // For now, let's assume a fixed amount or simple percentage of a placeholder "monthly fee".
                        $baseAmountForReduction = 50000; // Placeholder, should come from fee structure
                        $montantCalcule = $this->eleveReductionModel->calculateReductionAmount($reductionTypeId, $baseAmountForReduction);
                    }

                    $result = $this->eleveReductionModel->applyReduction([
                        'eleve_id' => $eleveId,
                        'annee_academique_id' => $anneeId,
                        'reduction_type_id' => $reductionTypeId,
                        'montant_calcule_reduction' => $montantCalcule, // Send calculated amount
                        'commentaire' => $commentaire,
                        'applique_par_utilisateur_id' => $currentUserId
                    ]);

                    if (is_numeric($result)) $_SESSION['flash_message'] = ['text' => __('compta_reduction_applied_success'), 'type' => 'success'];
                    elseif ($result === 'already_applied') $_SESSION['flash_message'] = ['text' => __('compta_reduction_error_duplicate'), 'type' => 'warning'];
                    else $_SESSION['flash_message'] = ['text' => __('compta_reduction_applied_error'), 'type' => 'danger'];
                } else {
                    $_SESSION['flash_message'] = ['text' => __('compta_reduction_error_no_type_selected'), 'type' => 'danger'];
                }
            } elseif ($action === 'remove_reduction') {
                Auth::requirePermission('remove_reduction_eleve');
                $appliedReductionId = $_POST['applied_reduction_id'] ?? null;
                if ($appliedReductionId && $this->eleveReductionModel->removeReduction($appliedReductionId)) {
                     $_SESSION['flash_message'] = ['text' => __('compta_reduction_removed_success'), 'type' => 'success'];
                } else {
                     $_SESSION['flash_message'] = ['text' => __('compta_reduction_removed_error'), 'type' => 'danger'];
                }
            }
            redirectTo('/admin/comptabiliteeleve/manage_reductions/' . $eleveId . '/' . $anneeId);
        }

        $appliedReductions = $this->eleveReductionModel->getAppliedReductionsForStudentYear($eleveId, $anneeId);
        $allReductionTypes = $this->reductionTypeModel->getAll(['orderBy' => 'nom_reduction']);

        $this->view('admin/comptabilite_eleve/manage_reductions', [
            'eleve' => $eleve, 'anneeCourante' => $anneeCourante,
            'appliedReductions' => $appliedReductions, 'allReductionTypes' => $allReductionTypes,
            'title' => __('compta_eleve_title_manage_reductions') . ' - ' . htmlspecialchars($eleve->nom_complet ?: ($eleve->prenom . ' ' . $eleve->nom_famille))
        ], 'admin_default');
    }

    public function print_recu($mensualiteId) {
        Auth::requirePermission('print_recu_eleve');
        // $mensualite = $this->mensualiteModel->getByIdWithDetails($mensualiteId); // Model method needs to fetch student, class, year info
        // For now, just get basic mensualite data
        $mensualite = $this->mensualiteModel->getById((int)$mensualiteId);

        if (!$mensualite) {
            $_SESSION['flash_message'] = ['text' => __('not_found_error', ['item'=>__('mensualite')]), 'type' => 'danger'];
            redirectTo('/admin/comptabiliteeleve'); return;
        }
        $eleve = $this->eleveModel->getById($mensualite->eleve_id);
        $annee = $this->anneeModel->getById($mensualite->annee_academique_id);
        // Fetch current class assignment for display on receipt
        $affectation = $this->model('AffectationEleveClasseModel')->getCurrentAssignmentForStudent($mensualite->eleve_id, $mensualite->annee_academique_id);
        $classe = null;
        if ($affectation) {
            $classe = $this->model('ClasseModel')->getById($affectation->classe_id);
        }


        // For now, display data that would go into PDF. Actual PDF generation is complex.
        // $this->view('admin/comptabilite_eleve/recu_preview_html', ['mensualite' => $mensualite, 'eleve' => $eleve, 'annee' => $annee, 'classe' => $classe], 'simple_layout_for_print');

        // Simple echo for now for brevity
        header('Content-Type: text/html; charset=utf-8');
        echo "<h1>" . __('receipt_title') . "</h1>";
        echo "<p><strong>" . __('receipt_student_name') . ":</strong> " . htmlspecialchars($eleve->nom_complet ?? '') . "</p>";
        echo "<p><strong>" . __('receipt_academic_year') . ":</strong> " . htmlspecialchars($annee->libelle ?? '') . "</p>";
        if ($classe) echo "<p><strong>" . __('receipt_class') . ":</strong> " . htmlspecialchars($classe->nom ?? '') . "</p>";
        echo "<p><strong>" . __('receipt_month_concerned') . ":</strong> " . htmlspecialchars($mensualite->mois . ' ' . $mensualite->annee_concernee) . "</p>";
        echo "<p><strong>" . __('receipt_amount_paid') . ":</strong> " . number_format($mensualite->montant_paye, 2) . " " . get_setting('app_currency', 'XOF') . "</p>";
        echo "<p><strong>" . __('receipt_payment_date') . ":</strong> " . date_format_fr($mensualite->date_dernier_paiement) . "</p>";
        echo "<p><strong>" . __('receipt_payment_method') . ":</strong> " . htmlspecialchars($mensualite->methode_dernier_paiement ?? '') . "</p>";
        echo "<p><strong>" . __('receipt_number') . ":</strong> " . htmlspecialchars($mensualite->numero_recu_dernier_paiement ?? ('REC' . $mensualite->id)) . "</p>";
        echo "<hr><p>" . __('receipt_footer_thank_you') . "</p>";
        // In a real app, use a PDF library (FPDF, TCPDF, DomPDF etc.)
    }
}
?>
