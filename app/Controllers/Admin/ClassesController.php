<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\I18n;

class ClassesController extends Controller {
    private $classeModel;
    // Example cycle/level data for dropdowns in form, could be from config or other models
    private $cyclesList = ['Maternelle', 'Primaire', 'Collège', 'Lycée', 'Supérieur', 'Formation Professionnelle'];
    private $niveauxListPerCycle = [ // Example, could be more dynamic
        'Maternelle' => ['Petite Section', 'Moyenne Section', 'Grande Section'],
        'Primaire' => ['CI', 'CP', 'CE1', 'CE2', 'CM1', 'CM2'],
        'Collège' => ['6ème', '5ème', '4ème', '3ème'],
        'Lycée' => ['Seconde', 'Première', 'Terminale'],
        'Supérieur' => ['Licence 1', 'Licence 2', 'Licence 3', 'Master 1', 'Master 2', 'Doctorat'],
        'Formation Professionnelle' => ['Niveau 1', 'Niveau 2', 'Niveau 3', 'Spécialisation']
    ];

    public function __construct() {
        Auth::requirePermission('view_classes'); // Base permission for this controller
        $this->classeModel = $this->model('ClasseModel');
    }

    public function index() {
        $filters = [
            'search' => trim($_GET['search'] ?? ''),
            'niveau' => trim($_GET['niveau'] ?? ''),
            'cycle' => trim($_GET['cycle'] ?? '')
        ];
        $page = (int)($_GET['page'] ?? 1);
        $perPage = 15; // Configurable

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
            // This check is basic. If cyclesList is dynamic from DB, this needs adjustment.
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
            // Sanitize all string inputs, allow description to be more flexible if needed (e.g. basic HTML)
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING, ['description' => FILTER_DEFAULT] + array_fill_keys(array_keys($_POST), FILTER_SANITIZE_STRING));
            $formData = array_merge($formData, $_POST); // Merge to keep defaults
            $formData['capacite'] = !empty($_POST['capacite']) ? (int)$_POST['capacite'] : null;
            $formData['errors'] = $this->validateClasseData($formData);

            if (empty($formData['errors'])) {
                $result = $this->classeModel->create($formData);
                if (is_numeric($result)) { // ID returned
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
            'niveauxListPerCycleJson' => json_encode($this->niveauxListPerCycle)
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
        $formData = (array)$classe; // Convert object to array for form repopulation
        $formData['errors'] = [];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING, ['description' => FILTER_DEFAULT] + array_fill_keys(array_keys($_POST), FILTER_SANITIZE_STRING));
            // Merge POST data into existing $formData to preserve non-submitted fields if any
            $formDataFromPost = [
                'nom' => trim($_POST['nom'] ?? $classe->nom),
                'niveau' => trim($_POST['niveau'] ?? $classe->niveau),
                'cycle' => trim($_POST['cycle'] ?? $classe->cycle),
                'description' => trim($_POST['description'] ?? $classe->description),
                'salle_par_defaut' => trim($_POST['salle_par_defaut'] ?? $classe->salle_par_defaut),
                'capacite' => !empty($_POST['capacite']) ? (int)$_POST['capacite'] : $classe->capacite,
            ];
            $formData = array_merge($formData, $formDataFromPost); // Keep original $id, etc.
            $formData['errors'] = $this->validateClasseData($formDataFromPost, true, $id);

            if (empty($formData['errors'])) {
                $result = $this->classeModel->update($id, $formDataFromPost);
                if ($result === true) {
                    $_SESSION['flash_message'] = ['text' => __('classes_edit_success_msg', 'Class updated successfully.'), 'type' => 'success'];
                    redirectTo('/admin/classes');
                } else {
                    $errorMessage = ($result === 'duplicate_nom') ? I18n::translate('validation.unique', ['field' => __('classes_form_label_nom'), 'value' => $formDataFromPost['nom']]) : __('classes_edit_error_msg', 'Error updating class or no changes made.');
                    $_SESSION['flash_message'] = ['text' => $errorMessage, 'type' => 'danger'];
                }
            } else {
                 $_SESSION['flash_message'] = ['text' => I18n::translate('validation.form_errors_detail'), 'type' => 'danger'];
            }
        }

        $this->view('admin/classes/form', [
            'data' => $formData,
            'title' => __('classes_title_edit', 'Edit Class') . ': ' . htmlspecialchars($classe->nom),
            'mode' => 'edit',
            'classeId' => $id,
            'cyclesList' => $this->cyclesList,
            'niveauxListPerCycleJson' => json_encode($this->niveauxListPerCycle)
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
                // Example: 'in_use_enseignement' or 'in_use_student'
                $type = substr($result, strlen('in_use_'));
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
