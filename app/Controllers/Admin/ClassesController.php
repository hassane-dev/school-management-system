<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\I18n;
use App\Models\ClasseModel;
use App\Models\MatiereModel;
use App\Models\ClasseMatiereEligibiliteModel;

class ClassesController extends Controller {
    private $classeModel;
    private $matiereModel; // New dependency
    private $classeMatiereEligibiliteModel; // New dependency

    // Example cycle/level data for dropdowns in form, could be from config or other models
    private $cyclesList = ['Maternelle', 'Primaire', 'Collège', 'Lycée', 'Supérieur', 'Formation Professionnelle'];
    private $niveauxListPerCycle = [
        'Maternelle' => ['Petite Section', 'Moyenne Section', 'Grande Section'],
        'Primaire' => ['CI', 'CP', 'CE1', 'CE2', 'CM1', 'CM2'],
        'Collège' => ['6ème', '5ème', '4ème', '3ème'],
        'Lycée' => ['Seconde', 'Première', 'Terminale'],
        'Supérieur' => ['Licence 1', 'Licence 2', 'Licence 3', 'Master 1', 'Master 2', 'Doctorat'],
        'Formation Professionnelle' => ['Niveau 1', 'Niveau 2', 'Niveau 3', 'Spécialisation']
    ];

    public function __construct() {
        Auth::requirePermission('view_classes');
        $this->classeModel = $this->model('ClasseModel');
        $this->matiereModel = $this->model('MatiereModel');
        $this->classeMatiereEligibiliteModel = $this->model('ClasseMatiereEligibiliteModel');
    }

    public function index() {
        $filters = [
            'search' => trim($_GET['search'] ?? ''),
            'niveau' => trim($_GET['niveau'] ?? ''),
            'cycle' => trim($_GET['cycle'] ?? '')
        ];
        $page = (int)($_GET['page'] ?? 1);
        $perPage = 15;

        $totalClasses = $this->classeModel->getTotalCount($filters);
        $totalPages = ceil($totalClasses / $perPage);
        $offset = ($page - 1) * $perPage;

        $options = [
            'limit' => $perPage,
            'offset' => $offset,
            'orderBy' => $_GET['orderBy'] ?? 'cycle, niveau, nom',
            'orderDir' => $_GET['orderDir'] ?? 'ASC'
        ];

        $classes = $this->classeModel->getAll($filters, $options);
        $distinctNiveauxResult = $this->classeModel->getDistinctNiveaux();
        $distinctCyclesResult = $this->classeModel->getDistinctCycles();

        $distinctNiveaux = array_map(function($n){ return $n->niveau; }, $distinctNiveauxResult);
        $distinctCycles = array_map(function($c){ return $c->cycle; }, $distinctCyclesResult);

        $this->view('admin/classes/index', [
            'classes' => $classes,
            'title' => __('classes_title_list', 'Classes Management'),
            'filters' => $filters,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalClasses' => $totalClasses,
            'perPage' => $perPage,
            'orderBy' => $options['orderBy'],
            'orderDir' => $options['orderDir'],
            'distinctNiveaux' => $distinctNiveaux,
            'distinctCycles' => $distinctCycles
        ], 'admin_default');
    }

    private function validateClasseData($data, $isEditMode = false, $currentId = null) {
        $errors = [];
        if (empty($data['nom'])) {
            $errors['nom'] = I18n::translate('validation.required', ['field' => __('classes_form_label_nom', 'Name')]);
        } else {
            $existingByName = $this->classeModel->findByNom($data['nom'], $currentId);
            if ($existingByName) {
                $errors['nom'] = I18n::translate('validation.unique', ['field' => __('classes_form_label_nom', 'Name'), 'value' => $data['nom']]);
            }
        }

        if (isset($data['capacite']) && $data['capacite'] !== '' && !filter_var($data['capacite'], FILTER_VALIDATE_INT, ["options" => ["min_range"=>1, "max_range"=>500]])) {
            $errors['capacite'] = I18n::translate('validation.integer_between', ['field' => __('classes_form_label_capacite', 'Capacity'), 'min'=>1, 'max'=>500]);
        }

        if (!empty($data['cycle']) && !in_array($data['cycle'], $this->cyclesList)) {
            // $errors['cycle'] = I18n::translate('validation.invalid_selection', ['field' => __('classes_form_label_cycle', 'Cycle')]);
        }
        if (!empty($data['cycle']) && !empty($data['niveau']) && isset($this->niveauxListPerCycle[$data['cycle']]) && !in_array($data['niveau'], $this->niveauxListPerCycle[$data['cycle']])) {
            // $errors['niveau'] = I18n::translate('validation.invalid_level_for_cycle', ['level' => $data['niveau'], 'cycle' => $data['cycle']]);
        }
        return $errors;
    }

    public function add() {
        Auth::requirePermission('create_classe');
        $formData = ['nom' => '', 'niveau' => '', 'cycle' => '', 'description' => '', 'salle_par_defaut' => '', 'capacite' => '', 'errors' => []];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST_filtered = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
            $formData = array_merge($formData, $_POST_filtered);
            $formData['capacite'] = !empty($_POST_filtered['capacite']) ? (int)$_POST_filtered['capacite'] : null;
            $formData['errors'] = $this->validateClasseData($formData);

            if (empty($formData['errors'])) {
                $result = $this->classeModel->create($formData);
                if (is_numeric($result)) {
                    $_SESSION['flash_message'] = ['text' => __('classes_add_success_msg', 'Class added successfully.'), 'type' => 'success'];
                    redirectTo('/admin/classes');
                } else {
                    $errorMessage = ($result === 'duplicate_nom') ? I18n::translate('validation.unique', ['field' => __('classes_form_label_nom'), 'value' => $formData['nom']]) : __('classes_add_error_msg', 'Error adding class.');
                    $_SESSION['flash_message'] = ['text' => $errorMessage, 'type' => 'danger'];
                }
            } else {
                $_SESSION['flash_message'] = ['text' => I18n::translate('validation.form_errors_detail'), 'type' => 'danger'];
            }
        }

        $this->view('admin/classes/form', [
            'data' => $formData,
            'title' => __('classes_title_add', 'Add New Class'),
            'mode' => 'add',
            'cyclesList' => $this->cyclesList,
            'niveauxListPerCycleJson' => json_encode($this->niveauxListPerCycle),
            'allMatieres' => [], // Not needed for 'add' mode for eligibilities
            'classeId' => null
        ], 'admin_default');
    }

    public function edit($id) {
        Auth::requirePermission('edit_classe');
        $id = (int)$id;
        $classe = $this->classeModel->getById($id);
        if (!$classe) {
            $_SESSION['flash_message'] = ['text' => __('classes_not_found_msg', 'Class not found.'), 'type' => 'warning'];
            redirectTo('/admin/classes');
        }

        $formData = (array)$classe;
        $formData['errors'] = [];
        // Fetch eligible subjects for this class to pre-check boxes
        $formData['eligible_matiere_ids'] = $this->classeMatiereEligibiliteModel->getEligibleMatieresForClasse($id);


        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST_filtered = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);

            $formDataFromPost = [
                'nom' => trim($_POST_filtered['nom'] ?? $classe->nom),
                'niveau' => trim($_POST_filtered['niveau'] ?? $classe->niveau),
                'cycle' => trim($_POST_filtered['cycle'] ?? $classe->cycle),
                'description' => trim($_POST_filtered['description'] ?? $classe->description),
                'salle_par_defaut' => trim($_POST_filtered['salle_par_defaut'] ?? $classe->salle_par_defaut),
                'capacite' => !empty($_POST_filtered['capacite']) ? (int)$_POST_filtered['capacite'] : $classe->capacite,
            ];
            $formData = array_merge($formData, $formDataFromPost);
            $formData['errors'] = $this->validateClasseData($formDataFromPost, true, $id);

            $selectedMatiereIds = $_POST['eligible_matieres'] ?? []; // Array of matiere_id from form

            if (empty($formData['errors'])) {
                $classUpdateResult = $this->classeModel->update($id, $formDataFromPost);
                $eligibilitySyncResult = true; // Assume true if not managing

                if (Auth::can('manage_classe_matiere_eligibilite')) {
                    $eligibilitySyncResult = $this->classeMatiereEligibiliteModel->syncEligibleMatieresForClasse($id, $selectedMatiereIds);
                }

                if ($classUpdateResult === true && $eligibilitySyncResult === true) {
                    $_SESSION['flash_message'] = ['text' => __('classes_edit_success_msg', 'Class updated successfully.'), 'type' => 'success'];
                    redirectTo('/admin/classes');
                } else {
                    $errorMessage = ($classUpdateResult === 'duplicate_nom') ?
                                    I18n::translate('validation.unique', ['field' => __('classes_form_label_nom'), 'value' => $formDataFromPost['nom']]) :
                                    __('classes_edit_error_msg', 'Error updating class or no changes made.');
                    if ($eligibilitySyncResult !== true) {
                        $errorMessage .= ' ' . __('classes_error_eligibility_update', 'Error updating subject eligibilities.');
                    }
                    $_SESSION['flash_message'] = ['text' => $errorMessage, 'type' => 'danger'];
                    // Repopulate with submitted eligible IDs if there was an error
                    $formData['eligible_matiere_ids'] = $selectedMatiereIds;
                }
            } else {
                 $_SESSION['flash_message'] = ['text' => I18n::translate('validation.form_errors_detail'), 'type' => 'danger'];
                 $formData['eligible_matiere_ids'] = $selectedMatiereIds; // Repopulate checkbox states on validation error
            }
        }

        $allMatieres = $this->matiereModel->getAll([], ['orderBy' => 'nom', 'orderDir' => 'ASC']);
        $this->view('admin/classes/form', [
            'data' => $formData,
            'title' => __('classes_title_edit', 'Edit Class') . ': ' . htmlspecialchars($classe->nom),
            'mode' => 'edit',
            'classeId' => $id,
            'cyclesList' => $this->cyclesList,
            'niveauxListPerCycleJson' => json_encode($this->niveauxListPerCycle),
            'allMatieres' => $allMatieres
        ], 'admin_default');
    }

    public function delete($id) {
        Auth::requirePermission('delete_classe');
        $id = (int)$id;
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $result = $this->classeModel->delete($id);
            if ($result === true) {
                $_SESSION['flash_message'] = ['text' => __('classes_delete_success_msg', 'Class deleted successfully.'), 'type' => 'success'];
            } elseif (is_string($result) && strpos($result, 'in_use') !== false) {
                $type = substr($result, strlen('in_use_')); // e.g. 'enseignement'
                $_SESSION['flash_message'] = ['text' => I18n::translate('classes_delete_error_in_use', ['type' => $type], 'Cannot delete class as it is used in {type} records.'), 'type' => 'danger'];
            } else {
                $_SESSION['flash_message'] = ['text' => __('classes_delete_error_msg', 'Error deleting class.'), 'type' => 'danger'];
            }
        } else {
            $_SESSION['flash_message'] = ['text' => I18n::translate('global.invalid_request_method'), 'type' => 'warning'];
        }
        redirectTo('/admin/classes');
    }
}
?>
