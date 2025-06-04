<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\I18n; // Required for translations
use App\Core\Auth; // For ACL

class AnneesAcademiquesController extends Controller {
    private $anneeModel;

    public function __construct() {
        // General check for admin area access is handled by Router or a base AdminController if implemented.
        // Specific permissions are checked per method.
        $this->anneeModel = $this->model('AnneeAcademiqueModel');
    }

    public function index() {
        Auth::requirePermission('view_academic_years');
        $annees = $this->anneeModel->getAll();
        $activeYear = $this->anneeModel->getActiveYear();
        $data = [
            'annees' => $annees,
            'activeYear' => $activeYear,
            'title' => I18n::translate('aa_title_list', 'Academic Years Management')
        ];
        $this->view('admin/annees_academiques/index', $data, 'admin_default');
    }

    public function add() {
        Auth::requirePermission('create_academic_year');
        $data_form = ['libelle' => '', 'date_debut' => '', 'date_fin' => '', 'active' => 0, 'errors' => []];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            $data_form = [
                'libelle' => trim($_POST['libelle'] ?? ''),
                'date_debut' => trim($_POST['date_debut'] ?? ''),
                'date_fin' => trim($_POST['date_fin'] ?? ''),
                'active' => isset($_POST['active']) ? 1 : 0,
                'errors' => []
            ];

            if (empty($data_form['libelle'])) $data_form['errors']['libelle'] = I18n::translate('validation.required', ['field' => 'Libellé']);
            if (empty($data_form['date_debut'])) $data_form['errors']['date_debut'] = I18n::translate('validation.required', ['field' => 'Date de début']);
            if (empty($data_form['date_fin'])) $data_form['errors']['date_fin'] = I18n::translate('validation.required', ['field' => 'Date de fin']);

            if (!empty($data_form['date_debut']) && !empty($data_form['date_fin'])) {
                if (strtotime($data_form['date_fin']) <= strtotime($data_form['date_debut'])) {
                    $data_form['errors']['date_fin'] = I18n::translate('aa_error_date_fin_before_debut', 'End date must be after start date.');
                }
            }

            $existingByLibelle = $this->anneeModel->findByLibelle($data_form['libelle']);
            if ($existingByLibelle) {
                $data_form['errors']['libelle'] = I18n::translate('validation.unique', ['field' => 'Libellé']);
            }


            if (empty($data_form['errors'])) {
                $newYearId = $this->anneeModel->create($data_form);
                if ($newYearId === 'duplicate_libelle') {
                     $_SESSION['flash_message'] = ['text' => I18n::translate('validation.unique', ['field' => 'Libellé']), 'type' => 'danger'];
                } elseif ($newYearId) {
                    if ($data_form['active']) {
                        $this->anneeModel->activate($newYearId);
                    }
                    $_SESSION['flash_message'] = ['text' => I18n::translate('aa_add_success_msg', 'Academic year added successfully.'), 'type' => 'success'];
                    header("Location: " . URL_ROOT . "/admin/anneesacademiques");
                    exit;
                } else {
                    $_SESSION['flash_message'] = ['text' => I18n::translate('aa_add_error_msg', 'Failed to add academic year.'), 'type' => 'danger'];
                }
            }
            // If errors or failed insert, fall through to show form again with data and errors
        }

        $this->view('admin/annees_academiques/form', [
            'data' => $data_form,
            'title' => I18n::translate('aa_title_add', 'Add Academic Year'),
            'mode' => 'add'
        ], 'admin_default');
    }

    public function edit($id) {
        Auth::requirePermission('edit_academic_year');
        $annee = $this->anneeModel->getById($id);
        if (!$annee) {
            $_SESSION['flash_message'] = ['text' => I18n::translate('aa_not_found_msg', 'Academic year not found.'), 'type' => 'warning'];
            header("Location: " . URL_ROOT . "/admin/anneesacademiques");
            exit;
        }
        // Note: 'active' status is not edited via this form, only via activate action.
        $data_form = (array)$annee + ['errors' => []];


        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            $data_form = [
                'id' => $id,
                'libelle' => trim($_POST['libelle'] ?? ''),
                'date_debut' => trim($_POST['date_debut'] ?? ''),
                'date_fin' => trim($_POST['date_fin'] ?? ''),
                // 'active' is not part of edit form for data integrity; handled by activate()
                'errors' => []
            ];

            if (empty($data_form['libelle'])) $data_form['errors']['libelle'] = I18n::translate('validation.required', ['field' => 'Libellé']);
            if (empty($data_form['date_debut'])) $data_form['errors']['date_debut'] = I18n::translate('validation.required', ['field' => 'Date de début']);
            if (empty($data_form['date_fin'])) $data_form['errors']['date_fin'] = I18n::translate('validation.required', ['field' => 'Date de fin']);

            if (!empty($data_form['date_debut']) && !empty($data_form['date_fin'])) {
                 if (strtotime($data_form['date_fin']) <= strtotime($data_form['date_debut'])) {
                    $data_form['errors']['date_fin'] = I18n::translate('aa_error_date_fin_before_debut', 'End date must be after start date.');
                }
            }

            $existingByLibelle = $this->anneeModel->findByLibelle($data_form['libelle'], $id);
            if ($existingByLibelle) {
                $data_form['errors']['libelle'] = I18n::translate('validation.unique', ['field' => 'Libellé']);
            }

            if (empty($data_form['errors'])) {
                $updateResult = $this->anneeModel->update($id, $data_form);
                if ($updateResult === 'duplicate_libelle') {
                    $_SESSION['flash_message'] = ['text' => I18n::translate('validation.unique', ['field' => 'Libellé']), 'type' => 'danger'];
                } elseif ($updateResult) {
                    $_SESSION['flash_message'] = ['text' => I18n::translate('aa_edit_success_msg', 'Academic year updated successfully.'), 'type' => 'success'];
                    header("Location: " . URL_ROOT . "/admin/anneesacademiques");
                    exit;
                } else {
                    $_SESSION['flash_message'] = ['text' => I18n::translate('aa_edit_error_msg', 'Failed to update academic year or no changes made.'), 'type' => 'danger'];
                }
            }
             // If errors or failed update, show form again
        }

        $this->view('admin/annees_academiques/form', [
            'data' => $data_form,
            'title' => I18n::translate('aa_title_edit', 'Edit Academic Year'),
            'mode' => 'edit'
        ], 'admin_default');
    }

    public function delete($id) {
        // Recommended: Use POST for delete actions. For GET, ensure CSRF token or at least a clear confirmation step.
        // This example assumes a basic GET with JS confirmation on client-side FOR THE VIEW's LINK.
        // However, the actual deletion should ideally be POST.
        // For now, let's assume the form in the view POSTs here or a GET with CSRF is used.
        // The prompt showed a POST form in the view, which is good.
        Auth::requirePermission('delete_academic_year');

        if ($_SERVER['REQUEST_METHOD'] == 'POST') { // Expecting POST for deletion
            $annee = $this->anneeModel->getById($id);
            if (!$annee) {
                $_SESSION['flash_message'] = ['text' => I18n::translate('aa_not_found_msg', 'Academic year not found.'), 'type' => 'danger'];
            } else if ($annee->active) {
                $_SESSION['flash_message'] = ['text' => I18n::translate('aa_delete_error_active_msg', 'Cannot delete an active academic year. Please deactivate it first.'), 'type' => 'danger'];
            } else {
                $deleteResult = $this->anneeModel->delete($id);
                if ($deleteResult === 'error_linked') {
                    $_SESSION['flash_message'] = ['text' => I18n::translate('aa_delete_error_linked_msg', 'Cannot delete this academic year as it is linked to other records.'), 'type' => 'danger'];
                } elseif ($deleteResult) {
                    $_SESSION['flash_message'] = ['text' => I18n::translate('aa_delete_success_msg', 'Academic year deleted successfully.'), 'type' => 'success'];
                } else {
                    $_SESSION['flash_message'] = ['text' => I18n::translate('aa_delete_error_msg', 'Failed to delete academic year.'), 'type' => 'danger'];
                }
            }
        } else {
             $_SESSION['flash_message'] = ['text' => I18n::translate('global.invalid_request_method'), 'type' => 'warning'];
        }
        header("Location: " . URL_ROOT . "/admin/anneesacademiques");
        exit;
    }

    public function activate($id) {
        Auth::requirePermission('activate_academic_year');
        // Recommended: Use POST for actions that change state.
        // For simplicity with links in views, GET is often used, but needs CSRF.
        // Let's assume for now it can be GET for simplicity of current structure.
        if ($this->anneeModel->activate($id)) {
            $_SESSION['flash_message'] = ['text' => I18n::translate('aa_activate_success_msg', 'Academic year activated successfully.'), 'type' => 'success'];
        } else {
            $_SESSION['flash_message'] = ['text' => I18n::translate('aa_activate_error_msg', 'Failed to activate academic year.'), 'type' => 'danger'];
        }
        header("Location: " . URL_ROOT . "/admin/anneesacademiques");
        exit;
    }
}
?>
