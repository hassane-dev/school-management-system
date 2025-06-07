<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\I18n;
use App\Models\EnseignementModel;
use App\Models\UtilisateurModel;
use App\Models\MatiereModel;
use App\Models\ClasseModel;
use App\Models\AnneeAcademiqueModel;
use App\Models\ClasseMatiereEligibiliteModel; // New dependency

class EnseignementController extends Controller {
    private $enseignementModel;
    private $utilisateurModel;
    private $matiereModel;
    private $classeModel;
    private $anneeModel;
    private $classeMatiereEligibiliteModel; // New property

    public function __construct() {
        // Base permission view_enseignements is checked in index, others in respective methods

        $this->enseignementModel = $this->model('EnseignementModel');
        $this->utilisateurModel = $this->model('UtilisateurModel');
        $this->matiereModel = $this->model('MatiereModel');
        $this->classeModel = $this->model('ClasseModel');
        $this->anneeModel = $this->model('AnneeAcademiqueModel');
        $this->classeMatiereEligibiliteModel = $this->model('ClasseMatiereEligibiliteModel'); // Instantiate
    }

    public function index() {
        Auth::requirePermission('view_enseignements');

        $filters = [];
        if (!empty($_GET['teacher_id'])) $filters['e.utilisateur_id'] = (int)$_GET['teacher_id'];
        if (!empty($_GET['class_id'])) $filters['e.classe_id'] = (int)$_GET['class_id'];
        if (!empty($_GET['year_id'])) $filters['e.annee_id'] = (int)$_GET['year_id'];
        if (!empty($_GET['subject_id'])) $filters['e.matiere_id'] = (int)$_GET['subject_id'];

        $assignments = $this->enseignementModel->getAffectations($filters);

        $teachers = $this->utilisateurModel->getAccreditedTeachers();
        $classes = $this->classeModel->getAll([],['orderBy' => 'nom']);
        $academicYears = $this->anneeModel->getAll([],['orderBy' => 'libelle', 'orderDir' => 'DESC']);
        $matieres = $this->matiereModel->getAll([],['orderBy' => 'nom']);

        $this->view('admin/enseignements/index', [
            'assignments' => $assignments,
            'title' => __('enseignements_title_list', 'Teaching Assignments'),
            'teachers' => $teachers,
            'classes' => $classes,
            'academicYears' => $academicYears,
            'matieres' => $matieres,
            'currentFilters' => $_GET
        ], 'admin_default');
    }

    // Updated getFormData to handle selectedClasseId for pre-populating matieres
    private function getFormData($selectedClasseId = null, $selectedMatiereId = null) {
        $matieresForDropdown = [];
        if ($selectedClasseId) {
            $matieresForDropdown = $this->classeMatiereEligibiliteModel->getEligibleMatieresForClasse($selectedClasseId, true);
        } else {
            // Optionally load all matieres if no class is selected, or keep it empty
            // $matieresForDropdown = $this->matiereModel->getAll([], ['orderBy' => 'nom', 'orderDir' => 'ASC']);
        }

        return [
            'teachers' => $this->utilisateurModel->getAccreditedTeachers(),
            'matieres' => $matieresForDropdown, // This list is now context-dependent
            'all_matieres_for_empty_state' => $this->matiereModel->getAll([],['orderBy' => 'nom']), // Fallback for no-JS or initial state
            'classes' => $this->classeModel->getAll([], ['orderBy' => 'nom', 'orderDir' => 'ASC']),
            'academicYears' => $this->anneeModel->getAll([], ['orderBy' => 'libelle', 'orderDir' => 'DESC'])
        ];
    }

    public function add() {
        Auth::requirePermission('create_enseignement');
        $formData = ['utilisateur_id' => '', 'matiere_id' => '', 'classe_id' => '', 'annee_id' => '', 'errors' => []];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST_sanitized = filter_input_array(INPUT_POST, [
                'utilisateur_id' => FILTER_SANITIZE_NUMBER_INT,
                'matiere_id' => FILTER_SANITIZE_NUMBER_INT,
                'classe_id' => FILTER_SANITIZE_NUMBER_INT,
                'annee_id' => FILTER_SANITIZE_NUMBER_INT,
            ]);

            $formData = array_merge($formData, $_POST_sanitized);

            if (empty($formData['utilisateur_id'])) $formData['errors']['utilisateur_id'] = I18n::translate('validation.required_select', ['field' => __('enseignements_teacher', 'Teacher')]);
            if (empty($formData['matiere_id'])) $formData['errors']['matiere_id'] = I18n::translate('validation.required_select', ['field' => __('enseignements_subject', 'Subject')]);
            if (empty($formData['classe_id'])) $formData['errors']['classe_id'] = I18n::translate('validation.required_select', ['field' => __('enseignements_class', 'Class')]);
            if (empty($formData['annee_id'])) $formData['errors']['annee_id'] = I18n::translate('validation.required_select', ['field' => __('enseignements_academic_year', 'Academic Year')]);

            if (empty($formData['errors'])) {
                $modelData = [
                    'utilisateur_id' => $formData['utilisateur_id'],
                    'matiere_id' => $formData['matiere_id'],
                    'classe_id' => $formData['classe_id'],
                    'annee_id' => $formData['annee_id']
                ];
                $result = $this->enseignementModel->createAffectation($modelData);

                if ($result === 'duplicate') {
                     $_SESSION['flash_message'] = ['text' => __('enseignements_error_unique_constraint', 'This assignment already exists.'), 'type' => 'danger'];
                } elseif (is_numeric($result) && $result > 0) {
                    $_SESSION['flash_message'] = ['text' => __('enseignements_add_success_msg', 'Assignment created successfully.'), 'type' => 'success'];
                    redirectTo('/admin/enseignements');
                } else {
                    $_SESSION['flash_message'] = ['text' => __('enseignements_add_error_msg', 'Error creating assignment.'), 'type' => 'danger'];
                }
            } else {
                 $_SESSION['flash_message'] = ['text' => I18n::translate('validation.form_errors_detail'), 'type' => 'danger'];
            }
        }

        $viewData = array_merge(['data' => $formData, 'title' => __('enseignements_title_add', 'Add New Assignment'), 'mode' => 'add'], $this->getFormData($formData['classe_id'] ?? null));
        $this->view('admin/enseignements/form', $viewData, 'admin_default');
    }

    public function edit($id) {
        Auth::requirePermission('edit_enseignement');
        $id = (int)$id;
        $assignment = $this->enseignementModel->getAffectationById($id);
        if (!$assignment) {
            $_SESSION['flash_message'] = ['text' => __('enseignements_not_found', 'Assignment not found.'), 'type' => 'warning'];
            redirectTo('/admin/enseignements');
        }

        $formData = [
            'utilisateur_id' => $assignment->utilisateur_id ?? '',
            'matiere_id' => $assignment->matiere_id ?? '',
            'classe_id' => $assignment->classe_id ?? '',
            'annee_id' => $assignment->annee_id ?? '',
            'errors' => []
        ];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
             $_POST_sanitized = filter_input_array(INPUT_POST, [
                'utilisateur_id' => FILTER_SANITIZE_NUMBER_INT,
                'matiere_id' => FILTER_SANITIZE_NUMBER_INT,
                'classe_id' => FILTER_SANITIZE_NUMBER_INT,
                'annee_id' => FILTER_SANITIZE_NUMBER_INT,
            ]);
            $formData = array_merge($formData, $_POST_sanitized);

            if (empty($formData['utilisateur_id'])) $formData['errors']['utilisateur_id'] = I18n::translate('validation.required_select', ['field' => __('enseignements_teacher')]);
            if (empty($formData['matiere_id'])) $formData['errors']['matiere_id'] = I18n::translate('validation.required_select', ['field' => __('enseignements_subject')]);
            if (empty($formData['classe_id'])) $formData['errors']['classe_id'] = I18n::translate('validation.required_select', ['field' => __('enseignements_class')]);
            if (empty($formData['annee_id'])) $formData['errors']['annee_id'] = I18n::translate('validation.required_select', ['field' => __('enseignements_academic_year')]);

            if (empty($formData['errors'])) {
                 $modelData = [
                    'utilisateur_id' => $formData['utilisateur_id'],
                    'matiere_id' => $formData['matiere_id'],
                    'classe_id' => $formData['classe_id'],
                    'annee_id' => $formData['annee_id']
                ];
                $result = $this->enseignementModel->updateAffectation($id, $modelData);
                if ($result === true) {
                    $_SESSION['flash_message'] = ['text' => __('enseignements_edit_success_msg', 'Assignment updated successfully.'), 'type' => 'success'];
                    redirectTo('/admin/enseignements');
                } else {
                    $errorMessageKey = ($result === 'duplicate') ? 'enseignements_error_unique_constraint' : 'enseignements_edit_error_msg';
                    $_SESSION['flash_message'] = ['text' => __($errorMessageKey, 'Error updating assignment or no changes made.'), 'type' => 'danger'];
                }
            } else {
                 $_SESSION['flash_message'] = ['text' => I18n::translate('validation.form_errors_detail'), 'type' => 'danger'];
            }
        }

        $viewData = array_merge(['data' => $formData, 'title' => __('enseignements_title_edit', 'Edit Assignment'), 'mode' => 'edit', 'assignmentId' => $id], $this->getFormData($formData['classe_id']));
        $this->view('admin/enseignements/form', $viewData, 'admin_default');
    }

    public function delete($id) {
        Auth::requirePermission('delete_enseignement');
        $id = (int)$id;
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if ($this->enseignementModel->deleteAffectation($id)) {
                $_SESSION['flash_message'] = ['text' => __('enseignements_delete_success_msg', 'Assignment deleted successfully.'), 'type' => 'success'];
            } else {
                $_SESSION['flash_message'] = ['text' => __('enseignements_delete_error_msg', 'Error deleting assignment.'), 'type' => 'danger'];
            }
        } else {
            $_SESSION['flash_message'] = ['text' => I18n::translate('global.invalid_request_method'), 'type' => 'warning'];
        }
        redirectTo('/admin/enseignements');
    }

    // New AJAX handler method
    public function get_matieres_for_classe_ajax($classeId = 0) {
        // Permission check for AJAX endpoint
        if (!Auth::can('view_enseignements') && !Auth::can('create_enseignement') && !Auth::can('edit_enseignement')) {
            header('Content-Type: application/json');
            http_response_code(403); // Forbidden
            echo json_encode(['error' => I18n::translate('global.access_denied_permission')]);
            exit;
        }

        $classeId = (int)$classeId;
        $eligibleMatieres = [];

        if ($classeId > 0) {
            $eligibleMatieres = $this->classeMatiereEligibiliteModel->getEligibleMatieresForClasse($classeId, true);
        }
        // No "else" needed to load all matieres; if classId is 0 or invalid, an empty array is fine.
        // The JS will handle the "Select class first" message.

        header('Content-Type: application/json');
        echo json_encode($eligibleMatieres);
        exit;
    }
}
?>
