<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\I18n;
use App\Models\EleveModel;
use App\Models\ClasseModel;
use App\Models\AnneeAcademiqueModel;
use App\Models\AffectationEleveClasseModel;

class ElevesController extends Controller {
    private $eleveModel;
    private $classeModel;
    private $anneeModel;
    private $affectationModel;
    private $eleveStatuts;

    public function __construct() {
        Auth::requireLogin(); // Ensure user is logged in
        // Auth::requirePermission('view_eleves'); // Base permission for module - to be added after permission seeding
        $this->eleveModel = $this->model('EleveModel');
        $this->classeModel = $this->model('ClasseModel');
        $this->anneeModel = $this->model('AnneeAcademiqueModel');
        $this->affectationModel = $this->model('AffectationEleveClasseModel');
        $this->eleveStatuts = $this->eleveModel->getStatutsEleve();
    }

    public function index() {
        Auth::requirePermission('view_eleves');
        $filters = [
            'search' => trim($_GET['search'] ?? ''),
            'statut_eleve' => trim($_GET['statut_eleve'] ?? ''),
            'classe_id' => trim($_GET['classe_id'] ?? ''),
        ];
        $page = (int)($_GET['page'] ?? 1);
        $perPage = 15;
        $totalEleves = $this->eleveModel->getTotalCount($filters);
        $totalPages = ceil($totalEleves / $perPage);
        $offset = ($page - 1) * $perPage;

        $options = [
            'limit' => $perPage,
            'offset' => $offset,
            'orderBy' => $_GET['orderBy'] ?? 'e.nom_famille, e.prenom',
            'orderDir' => $_GET['orderDir'] ?? 'ASC'
        ];

        $eleves = $this->eleveModel->getAll($filters, $options);

        $allClasses = $this->classeModel->getAll([],['orderBy'=>'nom']);

        $this->view('admin/eleves/index', [
            'eleves' => $eleves, 'title' => __('eleves_title_list'),
            'filters' => $filters, 'currentPage' => $page, 'totalPages' => $totalPages,
            'orderBy' => $options['orderBy'], 'orderDir' => $options['orderDir'],
            'statutsList' => $this->eleveStatuts,
            'classesList' => $allClasses
        ], 'admin_default');
    }

    private function validateEleveData($data, $isEditMode = false, $eleveId = null) {
        $errors = [];
        if (empty($data['prenom'])) $errors['prenom'] = I18n::translate('validation.required', ['field'=>__('eleves_form_label_prenom')]);
        if (empty($data['nom_famille'])) $errors['nom_famille'] = I18n::translate('validation.required', ['field'=>__('eleves_form_label_nom_famille')]);
        if (empty($data['date_naissance'])) $errors['date_naissance'] = I18n::translate('validation.required', ['field'=>__('eleves_form_label_date_naissance')]);
        // if (empty($data['lieu_naissance'])) $errors['lieu_naissance'] = I18n::translate('validation.required', ['field'=>__('eleves_form_label_lieu_naissance')]);
        if (empty($data['sexe'])) $errors['sexe'] = I18n::translate('validation.required_select', ['field'=>__('eleves_form_label_sexe')]);
        if (empty($data['nom_responsable_legal1'])) $errors['nom_responsable_legal1'] = I18n::translate('validation.required', ['field'=>__('eleves_form_label_nom_rl1')]);
        if (empty($data['telephone_responsable_legal1'])) $errors['telephone_responsable_legal1'] = I18n::translate('validation.required', ['field'=>__('eleves_form_label_tel_rl1')]);
        // if (empty($data['date_inscription_initiale'])) $errors['date_inscription_initiale'] = I18n::translate('validation.required', ['field'=>__('eleves_form_label_date_inscription')]);
         if (empty($data['statut_eleve']) || !in_array($data['statut_eleve'], $this->eleveStatuts)) {
              $errors['statut_eleve'] = I18n::translate('validation.invalid_selection', ['field' => __('eleves_form_label_statut')]);
         }
        if (!empty($data['email_eleve']) && !filter_var($data['email_eleve'], FILTER_VALIDATE_EMAIL)) {
             $errors['email_eleve'] = I18n::translate('validation.email', ['field'=>__('eleves_form_label_email_eleve')]);
        }
        return $errors;
    }

    public function add() {
        Auth::requirePermission('create_eleve');
        $formData = [
            'errors' => [],
            'statut_eleve' => 'inscrit',
            'date_inscription_initiale' => date('Y-m-d'),
            'sexe' => 'M',
            'matricule' => $this->eleveModel->generateMatricule(),
            // Initialize all other form fields to empty or default to prevent undefined index errors in view
            'prenom' => '', 'nom_famille' => '', 'nom_complet' => '', 'date_naissance' => '', 'lieu_naissance' => '',
            'nationalite' => '', 'adresse' => '', 'telephone_mobile' => '', 'email_eleve' => '',
            'nom_responsable_legal1' => '', 'telephone_responsable_legal1' => '', 'email_responsable_legal1' => '', 'profession_rl1' => '',
            'nom_responsable_legal2' => '', 'telephone_responsable_legal2' => '', 'avatar_path' => '', 'notes_medicales' => '',
            'classe_id' => null, 'annee_academique_id_affectation' => Auth::getActiveAcademicYearId()
        ];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING, ['flags' => FILTER_FLAG_NO_ENCODE_QUOTES]);
            $_POST['nom_complet'] = trim(($_POST['prenom'] ?? '')) . ' ' . trim(($_POST['nom_famille'] ?? ''));
            $formData = array_merge($formData, $_POST);
            $formData['errors'] = $this->validateEleveData($formData);

            if (empty($formData['errors'])) {
                $result = $this->eleveModel->create($formData);
                if (is_numeric($result)) { // ID returned
                    if (!empty($_POST['classe_id']) && !empty($_POST['annee_academique_id_affectation'])) {
                        if(Auth::can('assign_eleve_classe')) { // Check permission before assigning
                           $this->affectationModel->assignStudentToClass($result, $_POST['classe_id'], $_POST['annee_academique_id_affectation']);
                        } else {
                             $_SESSION['flash_message'] = ['text' => __('eleves_add_success_no_assign_permission_msg'), 'type' => 'warning'];
                             redirectTo('/admin/eleves/edit/' . $result); // Redirect to edit to allow manual assignment if permitted later
                             return; // Stop further processing
                        }
                    }
                    $_SESSION['flash_message'] = ['text' => __('eleves_add_success_msg'), 'type' => 'success'];
                    redirectTo('/admin/eleves');
                } else {
                    $errorMessageKey = 'eleves_add_error_msg';
                    if ($result === 'duplicate_matricule') $errorMessageKey = 'eleves_error_duplicate_matricule';
                    if ($result === 'duplicate_email_eleve') $errorMessageKey = 'eleves_error_duplicate_email_eleve'; // Corrected key
                    $_SESSION['flash_message'] = ['text' => __( $errorMessageKey ), 'type' => 'danger'];
                }
            }
        }
        $this->view('admin/eleves/form', [
            'data' => $formData, 'title' => __('eleves_title_add'), 'mode' => 'add',
            'statutsList' => $this->eleveStatuts,
            'classesList' => $this->classeModel->getAll([],['orderBy'=>'nom']),
            'anneesList' => $this->anneeModel->getAll([], ['orderBy'=>'libelle DESC']), // Changed from date_debut
            'activeYearId' => Auth::getActiveAcademicYearId()
        ], 'admin_default');
    }

    public function edit($id) {
        Auth::requirePermission('edit_eleve');
        $eleve = $this->eleveModel->getById((int)$id);
        if (!$eleve) {
            $_SESSION['flash_message'] = ['text' => __('not_found_error', ['item'=>__('eleve')]), 'type' => 'danger'];
            redirectTo('/admin/eleves');
        }

        $activeYearId = Auth::getActiveAcademicYearId();
        $currentAffectation = $this->affectationModel->getCurrentAssignmentForStudent($id, $activeYearId);

        $formData = (array)$eleve;
        $formData['errors'] = [];
        $formData['classe_id'] = $currentAffectation->classe_id ?? null; // classe_id for the form
        $formData['annee_academique_id_affectation'] = $currentAffectation->annee_academique_id ?? $activeYearId;


        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING, ['flags' => FILTER_FLAG_NO_ENCODE_QUOTES]);
            $_POST['nom_complet'] = trim(($_POST['prenom'] ?? '')) . ' ' . trim(($_POST['nom_famille'] ?? ''));
            $originalAffectationYear = $formData['annee_academique_id_affectation']; // Store original year for potential removal logic

            $formData = array_merge($formData, $_POST);
            $formData['errors'] = $this->validateEleveData($formData, true, $id);

            if (empty($formData['errors'])) {
                $result = $this->eleveModel->update($id, $formData);
                if ($result === true) {
                     if (Auth::can('assign_eleve_classe')) {
                        if (!empty($_POST['classe_id']) && !empty($_POST['annee_academique_id_affectation'])) {
                             $this->affectationModel->assignStudentToClass($id, $_POST['classe_id'], $_POST['annee_academique_id_affectation']);
                        } else if (empty($_POST['classe_id']) && !empty($_POST['annee_academique_id_affectation'])) {
                            // If class is cleared for the specified year, remove active assignment for that year
                            $this->affectationModel->removeStudentFromClassForYear($id, $_POST['annee_academique_id_affectation']);
                        }
                     }
                    $_SESSION['flash_message'] = ['text' => __('eleves_edit_success_msg'), 'type' => 'success'];
                    redirectTo('/admin/eleves');
                } else {
                    $errorMessageKey = 'eleves_edit_error_msg';
                    if ($result === 'duplicate_matricule') $errorMessageKey = 'eleves_error_duplicate_matricule';
                    if ($result === 'duplicate_email_eleve') $errorMessageKey = 'eleves_error_duplicate_email_eleve'; // Corrected key
                    $_SESSION['flash_message'] = ['text' => __( $errorMessageKey ), 'type' => 'danger'];
                }
            }
        }
        $this->view('admin/eleves/form', [
            'data' => $formData,
            'title' => __('eleves_title_edit') . ': ' . htmlspecialchars($eleve->nom_complet ?: ($eleve->prenom . ' ' . $eleve->nom_famille)),
            'mode' => 'edit', 'eleveId' => $id,
            'statutsList' => $this->eleveStatuts,
            'classesList' => $this->classeModel->getAll([],['orderBy'=>'nom']),
            'anneesList' => $this->anneeModel->getAll([], ['orderBy'=>'libelle DESC']), // Changed from date_debut
            'activeYearId' => $activeYearId
        ], 'admin_default');
    }

    public function delete($id) {
        Auth::requirePermission('delete_eleve');
        if ($_SERVER['REQUEST_METHOD'] == 'POST') { // Ensure it's a POST request for deletion
            $eleve = $this->eleveModel->getById((int)$id);
            if (!$eleve) {
                $_SESSION['flash_message'] = ['text' => __('not_found_error', ['item'=>__('eleve')]), 'type' => 'danger'];
                redirectTo('/admin/eleves');
                return;
            }

            // Add checks for dependencies (e.g., payments, grades) before deleting
            // For now, simple delete
            if ($this->eleveModel->delete((int)$id)) {
                $_SESSION['flash_message'] = ['text' => __('eleves_delete_success_msg'), 'type' => 'success'];
            } else {
                $_SESSION['flash_message'] = ['text' => __('eleves_delete_error_msg'), 'type' => 'danger'];
            }
        } else {
            // If not POST, perhaps show a confirmation page or redirect with error
             $_SESSION['flash_message'] = ['text' => __('invalid_request_method'), 'type' => 'danger'];
        }
        redirectTo('/admin/eleves');
    }
}
?>
