<?php

namespace App\Controllers\Admin;

// Assuming base Controller is in global or App\Core, adjust 'use' if necessary
// For now, direct use of \Controller assumes it's globally accessible or autoloaded.
require_once __DIR__ . '/../../core/Controller.php'; // Adjust path if needed


class EnseignementController extends \Controller {
    private $enseignementModel;
    private $utilisateurModel;
    private $matiereModel;
    private $classeModel;
    private $anneesAcademiquesModel;

    public function __construct() {
        if (!class_exists('AuthSession') && file_exists(__DIR__ . '/../../core/AuthSession.php')) {
            require_once __DIR__ . '/../../core/AuthSession.php';
        }

        // ACL Checks
        if (!\AuthSession::isLoggedIn()) {
            \AuthSession::setFlash(__('global.login_required'), 'warning');
            redirectTo('/auth/login');
        }
        if (!\AuthSession::hasRole(['admin', 'super_admin'])) {
            \AuthSession::setFlash(__('global.access_denied_admin_area'), 'danger');
            redirectTo('/dashboard');
        }
        if (!\AuthSession::hasPermission('manage_enseignements')) { // Example specific permission
             \AuthSession::setFlash(__('global.access_denied', 'You do not have permission to manage teaching assignments.'), 'danger');
             redirectTo('/dashboard'); // Or admin dashboard
        }

        $this->enseignementModel = $this->model('Enseignement_model');
        $this->utilisateurModel = $this->model('Utilisateur_model');
        $this->matiereModel = $this->model('Matiere_model'); // Assumed to exist
        $this->classeModel = $this->model('Classe_model');   // Assumed to exist
        $this->anneesAcademiquesModel = $this->model('AnneesAcademiques_model');
    }

    /**
     * Lists all teaching assignments.
     */
    public function index() {
        $filters = [];
        $page_title_suffix = "";

        if (isset($_GET['utilisateur_id']) && !empty($_GET['utilisateur_id'])) {
            $filters['e.utilisateur_id'] = (int)$_GET['utilisateur_id'];
            // Optionally fetch teacher's name to add to page title
            $teacher = $this->utilisateurModel->read($filters['e.utilisateur_id']);
            if ($teacher) {
                $page_title_suffix .= ' ' . __('enseignements.for_teacher', 'for teacher') . ' ' . htmlspecialchars($teacher->nom);
            }
        }
        if (isset($_GET['classe_id']) && !empty($_GET['classe_id'])) {
            $filters['e.classe_id'] = (int)$_GET['classe_id'];
             $classe = $this->classeModel->read($filters['e.classe_id']);
            if ($classe) {
                $page_title_suffix .= ' ' . __('enseignements.for_class', 'for class') . ' ' . htmlspecialchars($classe->nom);
            }
        }
        if (isset($_GET['annee_id']) && !empty($_GET['annee_id'])) {
            $filters['e.annee_id'] = (int)$_GET['annee_id'];
            $annee = $this->anneesAcademiquesModel->read($filters['e.annee_id']);
             if ($annee) {
                $page_title_suffix .= ' ' . __('enseignements.for_year', 'for year') . ' ' . htmlspecialchars($annee->libelle);
            }
        }
        // Add more filters as needed (e.g., matiere_id)

        $assignments = $this->enseignementModel->getAffectations($filters);

        // For filter dropdowns
        $all_teachers = $this->utilisateurModel->getAccreditedTeachers(['enseignant', 'professeur principal', 'teacher']);
        $all_classes = $this->classeModel->read();
        $all_annees = $this->anneesAcademiquesModel->read();


        $data = [
            'assignments' => $assignments,
            'page_title' => __('enseignements.list_title', 'Teaching Assignments') . $page_title_suffix,
            'all_teachers' => $all_teachers,
            'all_classes' => $all_classes,
            'all_annees' => $all_annees,
            'current_filters' => $_GET // Pass current GET params to repopulate filter form
        ];
        $this->view('admin/enseignements/index', $data);
    }

    /**
     * Displays form to add a new assignment or processes the form submission.
     */
    public function add() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = [
                'utilisateur_id' => $_POST['utilisateur_id'] ?? null,
                'matiere_id' => $_POST['matiere_id'] ?? null,
                'classe_id' => $_POST['classe_id'] ?? null,
                'annee_id' => $_POST['annee_id'] ?? null,
                'errors' => []
            ];

            // Basic Validation
            if (empty($data['utilisateur_id'])) $data['errors']['utilisateur_id'] = "Teacher is required.";
            if (empty($data['matiere_id'])) $data['errors']['matiere_id'] = "Subject is required.";
            if (empty($data['classe_id'])) $data['errors']['classe_id'] = "Class is required.";
            if (empty($data['annee_id'])) $data['errors']['annee_id'] = "Academic year is required.";

            if (empty($data['errors'])) {
                $result = $this->enseignementModel->createAffectation($data);
                if ($result === 'duplicate') {
                    \AuthSession::setFlash(__('enseignements.add_error_duplicate', 'This teaching assignment (teacher, subject, class, year) already exists.'), 'danger');
                    // Repopulate form with submitted data and supporting data for dropdowns
                    $this->loadAddEditFormData($data); // Pass $data to merge with dropdowns
                } elseif ($result) { // $result is lastInsertId or true
                    \AuthSession::setFlash(__('enseignements.add_success', 'Teaching assignment added successfully.'), 'success');
                    redirectTo('/admin/enseignement'); // To index page
                } else {
                    \AuthSession::setFlash(__('enseignements.add_error_db', 'Could not add teaching assignment due to a database error.'), 'danger');
                    $this->loadAddEditFormData($data);
                }
            } else {
                \AuthSession::setFlash(__('enseignements.add_error_validation', 'Please correct the validation errors.'), 'danger');
                $this->loadAddEditFormData($data); // Pass $data with errors
            }
        } else {
            // GET request: Display form
            $this->loadAddEditFormData();
        }
    }

    /**
     * Helper to load data needed for the add/edit form.
     * @param array $formData Existing form data to repopulate the form (optional).
     */
    private function loadAddEditFormData($formData = []) {
        $teacherRoleNames = ['enseignant', 'professeur principal', 'teacher']; // Consistent with EnseignantController

        $viewData = [
            'enseignants' => $this->utilisateurModel->getAccreditedTeachers($teacherRoleNames),
            'matieres' => $this->matiereModel->read(),
            'classes' => $this->classeModel->read(),
            'annees_academiques' => $this->anneesAcademiquesModel->read(),
            'page_title' => $formData['mode'] === 'edit' ? __('enseignements.edit_title', 'Edit Assignment') : __('enseignements.add_title', 'Add Assignment'),
            'mode' => $formData['mode'] ?? 'add',
            'current_assignment' => $formData['current_assignment'] ?? null, // For edit mode
            'errors' => $formData['errors'] ?? []
        ];

        // Merge submitted form data if any (for repopulation on error)
        $viewData = array_merge($viewData, $formData);

        $this->view('admin/enseignements/form', $viewData);
    }


    /**
     * Displays form to edit an existing assignment or processes the form submission.
     * @param int $id Assignment ID.
     */
    public function edit($id) {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = [
                'id' => $id, // Keep the ID for update
                'utilisateur_id' => $_POST['utilisateur_id'] ?? null,
                'matiere_id' => $_POST['matiere_id'] ?? null,
                'classe_id' => $_POST['classe_id'] ?? null,
                'annee_id' => $_POST['annee_id'] ?? null,
                'errors' => []
            ];

            if (empty($data['utilisateur_id'])) $data['errors']['utilisateur_id'] = "Teacher is required.";
            if (empty($data['matiere_id'])) $data['errors']['matiere_id'] = "Subject is required.";
            if (empty($data['classe_id'])) $data['errors']['classe_id'] = "Class is required.";
            if (empty($data['annee_id'])) $data['errors']['annee_id'] = "Academic year is required.";

            if (empty($data['errors'])) {
                $updateResult = $this->enseignementModel->updateAffectation($id, $data);
                 if ($updateResult === 'duplicate') {
                    \AuthSession::setFlash(__('enseignements.update_error_duplicate', 'This teaching assignment combination already exists for another record.'), 'danger');
                    $data['current_assignment'] = (object)$data; // Repopulate form with attempted data
                    $data['mode'] = 'edit';
                    $this->loadAddEditFormData($data);
                } elseif ($updateResult) {
                    \AuthSession::setFlash(__('enseignements.update_success', 'Teaching assignment updated successfully.'), 'success');
                    redirectTo('/admin/enseignement');
                } else {
                    \AuthSession::setFlash(__('enseignements.update_error_db', 'Could not update teaching assignment or no changes were made.'), 'danger');
                    $data['current_assignment'] = (object)$data; // Repopulate
                    $data['mode'] = 'edit';
                    $this->loadAddEditFormData($data);
                }
            } else {
                \AuthSession::setFlash(__('enseignements.update_error_validation', 'Please correct the validation errors.'), 'danger');
                $data['current_assignment'] = (object)$data; // Repopulate
                $data['mode'] = 'edit';
                $this->loadAddEditFormData($data);
            }

        } else {
            // GET request: Display form for editing
            $assignment = $this->enseignementModel->getAffectationById($id);
            if (!$assignment) {
                \AuthSession::setFlash(__('enseignements.not_found_error', 'Teaching assignment not found.'), 'danger');
                redirectTo('/admin/enseignement');
            }
            $this->loadAddEditFormData([
                'current_assignment' => $assignment,
                'mode' => 'edit',
                // Pre-fill form data for editing from $assignment
                'utilisateur_id' => $assignment->utilisateur_id,
                'matiere_id' => $assignment->matiere_id,
                'classe_id' => $assignment->classe_id,
                'annee_id' => $assignment->annee_id,
            ]);
        }
    }

    /**
     * Deletes a teaching assignment.
     * @param int $id Assignment ID.
     */
    public function delete($id) {
        // It's better to use POST for delete operations for CSRF protection.
        // If using GET, ensure proper confirmation.
        // For simplicity, this example proceeds with deletion.
        if ($this->enseignementModel->deleteAffectation($id)) {
            \AuthSession::setFlash(__('enseignements.delete_success', 'Teaching assignment deleted successfully.'), 'success');
        } else {
            \AuthSession::setFlash(__('enseignements.delete_error', 'Could not delete teaching assignment.'), 'danger');
        }
        redirectTo('/admin/enseignement');
    }
}
?>
