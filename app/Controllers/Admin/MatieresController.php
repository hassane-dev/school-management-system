<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\I18n;

class MatieresController extends Controller {
    private $matiereModel;
    // Example types, can also be fetched from DB distinct values or a config
    private $matiereTypes = ['Fondamentale', 'Optionnelle', 'Atelier', 'Projet', 'Sportive', 'Culturelle'];

    public function __construct() {
        Auth::requirePermission('view_matieres'); // Base permission for this controller
        $this->matiereModel = $this->model('MatiereModel');
    }

    public function index() {
        $filters = [
            'search' => trim($_GET['search'] ?? ''),
            'type_matiere' => trim($_GET['type_matiere'] ?? '')
        ];
        $page = (int)($_GET['page'] ?? 1);
        $perPage = 15; // Configurable

        $totalMatieres = $this->matiereModel->getTotalCount($filters);
        $totalPages = ceil($totalMatieres / $perPage);
        $offset = ($page - 1) * $perPage;

        $options = [
            'limit' => $perPage,
            'offset' => $offset,
            'orderBy' => $_GET['orderBy'] ?? 'nom',
            'orderDir' => $_GET['orderDir'] ?? 'ASC'
        ];

        $matieres = $this->matiereModel->getAll($filters, $options);
        $distinctTypesResult = $this->matiereModel->getDistinctTypes();
        $distinctTypes = array_map(function($t){ return $t->type_matiere; }, $distinctTypesResult);

        $this->view('admin/matieres/index', [
            'matieres' => $matieres,
            'title' => __('matieres_title_list', 'Subjects Management'),
            'filters' => $filters,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalMatieres' => $totalMatieres,
            'perPage' => $perPage,
            'orderBy' => $options['orderBy'],
            'orderDir' => $options['orderDir'],
            'distinctTypes' => $distinctTypes, // For filter dropdown
            'allMatiereTypes' => $this->matiereTypes // For form dropdown
        ], 'admin_default');
    }

    private function validateMatiereData($data, $isEditMode = false, $currentId = null) {
        $errors = [];
        if (empty($data['nom'])) {
            $errors['nom'] = I18n::translate('validation.required', ['field' => __('matieres_form_label_nom', 'Name')]);
        } else {
            $existingByName = $this->matiereModel->findByName($data['nom'], $currentId);
            if ($existingByName) {
                $errors['nom'] = I18n::translate('validation.unique', ['field' => __('matieres_form_label_nom', 'Name'), 'value' => $data['nom']]);
            }
        }

        if (!empty($data['code'])) {
            $existingByCode = $this->matiereModel->findByCode($data['code'], $currentId);
            if ($existingByCode) {
                $errors['code'] = I18n::translate('validation.unique', ['field' => __('matieres_form_label_code', 'Code'), 'value' => $data['code']]);
            }
        }

        if (isset($data['coefficient']) && $data['coefficient'] !== '' && !is_numeric($data['coefficient'])) {
            $errors['coefficient'] = I18n::translate('validation.numeric', ['field' => __('matieres_form_label_coefficient', 'Coefficient')]);
        } elseif (isset($data['coefficient']) && $data['coefficient'] !== '') {
            $coeff = floatval($data['coefficient']);
            if ($coeff < 0 || $coeff > 99.99) { // Max 99.99 due to DECIMAL(5,2)
                 $errors['coefficient'] = I18n::translate('validation.between.numeric', ['field' => __('matieres_form_label_coefficient', 'Coefficient'), 'min' => 0, 'max' => 99.99]);
            }
        }

        if (!empty($data['type_matiere']) && !in_array($data['type_matiere'], $this->matiereTypes)) {
            $errors['type_matiere'] = I18n::translate('validation.invalid_selection', ['field' => __('matieres_form_label_type', 'Type')]);
        }
        return $errors;
    }

    public function add() {
        Auth::requirePermission('create_matiere');
        $formData = ['nom' => '', 'code' => '', 'description' => '', 'coefficient' => 1.00, 'type_matiere' => '', 'errors' => []];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING, ['description' => FILTER_DEFAULT] + array_fill_keys(array_keys($_POST), FILTER_SANITIZE_STRING));

            $formData = [
                'nom' => trim($_POST['nom'] ?? ''),
                'code' => trim($_POST['code'] ?? null), // Code can be null
                'description' => trim($_POST['description'] ?? null),
                'coefficient' => !empty($_POST['coefficient']) ? (float)$_POST['coefficient'] : 1.00,
                'type_matiere' => trim($_POST['type_matiere'] ?? null),
                'errors' => []
            ];
            $formData['errors'] = $this->validateMatiereData($formData);

            if (empty($formData['errors'])) {
                $result = $this->matiereModel->create($formData);
                if (is_numeric($result)) {
                    $_SESSION['flash_message'] = ['text' => __('matieres_add_success_msg', 'Subject added successfully.'), 'type' => 'success'];
                    redirectTo('/admin/matieres');
                } else {
                    $userMessage = __('matieres_add_error_msg', 'Error adding subject.');
                    if ($result === 'duplicate_nom') $userMessage = I18n::translate('validation.unique', ['field' => __('matieres_form_label_nom'), 'value' => $formData['nom']]);
                    if ($result === 'duplicate_code' && !empty($formData['code'])) $userMessage = I18n::translate('validation.unique', ['field' => __('matieres_form_label_code'), 'value' => $formData['code']]);
                    $_SESSION['flash_message'] = ['text' => $userMessage, 'type' => 'danger'];
                }
            } else {
                 $_SESSION['flash_message'] = ['text' => I18n::translate('validation.form_errors_detail'), 'type' => 'danger'];
            }
        }
        $this->view('admin/matieres/form', ['data' => $formData, 'title' => __('matieres_title_add', 'Add New Subject'), 'mode' => 'add', 'matiereTypes' => $this->matiereTypes], 'admin_default');
    }

    public function edit($id) {
        Auth::requirePermission('edit_matiere');
        $id = (int)$id;
        $matiere = $this->matiereModel->getById($id);
        if (!$matiere) {
            $_SESSION['flash_message'] = ['text' => __('matieres_not_found_msg', 'Subject not found.'), 'type' => 'warning'];
            redirectTo('/admin/matieres');
        }
        $formData = (array)$matiere;
        $formData['errors'] = [];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
             $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING, ['description' => FILTER_DEFAULT] + array_fill_keys(array_keys($_POST), FILTER_SANITIZE_STRING));
             $formData = [
                'nom' => trim($_POST['nom'] ?? $matiere->nom),
                'code' => trim($_POST['code'] ?? $matiere->code),
                'description' => trim($_POST['description'] ?? $matiere->description),
                'coefficient' => !empty($_POST['coefficient']) ? (float)$_POST['coefficient'] : $matiere->coefficient,
                'type_matiere' => trim($_POST['type_matiere'] ?? $matiere->type_matiere),
                'errors' => []
            ];
            $formData['errors'] = $this->validateMatiereData($formData, true, $id);

            if (empty($formData['errors'])) {
                $result = $this->matiereModel->update($id, $formData);
                if ($result === true) {
                    $_SESSION['flash_message'] = ['text' => __('matieres_edit_success_msg', 'Subject updated successfully.'), 'type' => 'success'];
                    redirectTo('/admin/matieres');
                } else {
                    $userMessage = __('matieres_edit_error_msg', 'Error updating subject or no changes made.');
                    if ($result === 'duplicate_nom') $userMessage = I18n::translate('validation.unique', ['field' => __('matieres_form_label_nom'), 'value' => $formData['nom']]);
                    if ($result === 'duplicate_code' && !empty($formData['code'])) $userMessage = I18n::translate('validation.unique', ['field' => __('matieres_form_label_code'), 'value' => $formData['code']]);
                    $_SESSION['flash_message'] = ['text' => $userMessage, 'type' => 'danger'];
                }
            } else {
                 $_SESSION['flash_message'] = ['text' => I18n::translate('validation.form_errors_detail'), 'type' => 'danger'];
            }
        }
        $this->view('admin/matieres/form', ['data' => $formData, 'title' => __('matieres_title_edit', 'Edit Subject') . ': ' . htmlspecialchars($matiere->nom), 'mode' => 'edit', 'matiereId' => $id, 'matiereTypes' => $this->matiereTypes], 'admin_default');
    }

    public function delete($id) {
        Auth::requirePermission('delete_matiere');
        $id = (int)$id;
        if ($_SERVER['REQUEST_METHOD'] == 'POST') { // Ensure deletion is via POST
            $result = $this->matiereModel->delete($id);
            if ($result === true) {
                $_SESSION['flash_message'] = ['text' => __('matieres_delete_success_msg', 'Subject deleted successfully.'), 'type' => 'success'];
            } elseif ($result === 'in_use') {
                $_SESSION['flash_message'] = ['text' => __('matieres_delete_error_in_use', 'Cannot delete subject as it is used in teaching assignments.'), 'type' => 'danger'];
            } else {
                $_SESSION['flash_message'] = ['text' => __('matieres_delete_error_msg', 'Error deleting subject.'), 'type' => 'danger'];
            }
        } else {
            $_SESSION['flash_message'] = ['text' => I18n::translate('global.invalid_request_method'), 'type' => 'warning'];
        }
        redirectTo('/admin/matieres');
    }
}
?>
