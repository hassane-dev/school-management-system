<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\I18n;
use App\Models\ProgrammeScolaireModel;
use App\Models\ProgrammeDetailModel;
use App\Models\AnneeAcademiqueModel;
use App\Models\MatiereModel;

class ProgrammesScolairesController extends Controller {
    private $psModel; // ProgrammeScolaireModel
    private $pdModel; // ProgrammeDetailModel
    private $anneeModel;
    private $matiereModel;

    private $programmeStatuts = ['brouillon', 'actif', 'archive'];

    public function __construct() {
        Auth::requirePermission('view_programmes_scolaires');
        $this->psModel = $this->model('ProgrammeScolaireModel');
        $this->pdModel = $this->model('ProgrammeDetailModel');
        $this->anneeModel = $this->model('AnneeAcademiqueModel');
        $this->matiereModel = $this->model('MatiereModel');
    }

    public function index() {
        $filters = [
            'search' => trim($_GET['search'] ?? ''),
            'annee_academique_id' => trim($_GET['annee_academique_id'] ?? ''),
            'statut' => trim($_GET['statut'] ?? '')
        ];
        $page = (int)($_GET['page'] ?? 1);
        $perPage = 15;

        $totalProgrammes = $this->psModel->getTotalCount($filters);
        $totalPages = ceil($totalProgrammes / $perPage);
        $offset = ($page - 1) * $perPage;

        $options = [
            'limit' => $perPage,
            'offset' => $offset,
            'orderBy' => $_GET['orderBy'] ?? 'aa.date_debut DESC, ps.nom',
            'orderDir' => $_GET['orderDir'] ?? 'ASC'
        ];

        $programmes = $this->psModel->getAll($filters, $options);
        $annees = $this->anneeModel->getAll([], ['orderBy' => 'date_debut', 'orderDir' => 'DESC']);
        $statutsResult = $this->psModel->getDistinctStatuts();
        $statutsList = array_map(function($s){ return $s->statut; }, $statutsResult);


        $this->view('admin/programmes_scolaires/index', [
            'programmes' => $programmes,
            'title' => __('ps_title_list', 'Curricula Management'),
            'filters' => $filters,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalProgrammes' => $totalProgrammes,
            'perPage' => $perPage,
            'orderBy' => $options['orderBy'],
            'orderDir' => $options['orderDir'],
            'anneesAcademiques' => $annees,
            'statutsList' => $statutsList
        ], 'admin_default');
    }

    private function validateProgrammeData($data, $isEditMode = false, $currentId = null) {
        $errors = [];
        if (empty($data['nom'])) {
            $errors['nom'] = I18n::translate('validation.required', ['field' => __('ps_form_label_nom', 'Name')]);
        }
        if (empty($data['annee_academique_id'])) {
            $errors['annee_academique_id'] = I18n::translate('validation.required_select', ['field' => __('ps_form_label_annee', 'Academic Year')]);
        }
        if (empty($data['statut']) || !in_array($data['statut'], $this->programmeStatuts)) {
             $errors['statut'] = I18n::translate('validation.invalid_selection', ['field' => __('ps_form_label_statut', 'Status')]);
        }

        // Check for unique nom globally (DB constraint) or per year (application logic)
        // Current DB schema has UNIQUE(nom) globally.
        // If we want unique per year, DB schema and this check must align.
        // For now, using findByNomAndYear to allow same name in different years if DB was changed,
        // but model's create/update will use simple 'nom' for duplicate check based on current schema.
        // This implies the DB unique constraint on 'nom' is global.
        $existing = $this->psModel->findByNomAndYear($data['nom'], $data['annee_academique_id'], $currentId);
        if ($existing) {
             $errors['nom'] = I18n::translate('ps_error_duplicate_nom_in_year', 'A curriculum with this name already exists for the selected academic year.');
        }
        // Or, if global name uniqueness is enforced by DB and that's the primary concern:
        // $globalExisting = $this->psModel->findByName($data['nom'], $currentId); // Assuming findByName(name, excludeId)
        // if ($globalExisting) {
        //     $errors['nom'] = I18n::translate('ps_error_duplicate_nom', 'A curriculum with this name already exists globally.');
        // }

        return $errors;
    }

    public function add_programme() {
        Auth::requirePermission('create_programme_scolaire');
        $formData = ['nom' => '', 'annee_academique_id' => '', 'description' => '', 'statut' => 'brouillon', 'errors' => []];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST_filtered = filter_input_array(INPUT_POST, [
                'nom' => FILTER_SANITIZE_STRING,
                'annee_academique_id' => FILTER_SANITIZE_NUMBER_INT,
                'description' => FILTER_DEFAULT, // Allows more, but should be properly cleaned/purified if HTML
                'statut' => FILTER_SANITIZE_STRING
            ]);
            $formData = array_merge($formData, $_POST_filtered);
            $formData['errors'] = $this->validateProgrammeData($formData);

            if (empty($formData['errors'])) {
                $result = $this->psModel->create($formData);
                if (is_numeric($result) && $result > 0) {
                    $_SESSION['flash_message'] = ['text' => __('ps_add_success_msg', 'Curriculum created successfully.'), 'type' => 'success'];
                    redirectTo('/admin/programmesscolaires/manage_details/' . $result);
                } else {
                    $errorMessage = ($result === 'duplicate_nom') ? __('ps_error_duplicate_nom', 'A curriculum with this name already exists.') : __('ps_add_error_msg', 'Error creating curriculum.');
                    $_SESSION['flash_message'] = ['text' => $errorMessage, 'type' => 'danger'];
                }
            } else {
                 $_SESSION['flash_message'] = ['text' => I18n::translate('validation.form_errors_detail'), 'type' => 'danger'];
            }
        }
        $annees = $this->anneeModel->getAll([], ['orderBy' => 'date_debut', 'orderDir' => 'DESC']);
        $this->view('admin/programmes_scolaires/form_programme', [
            'data' => $formData,
            'title' => __('ps_title_add', 'Add New Curriculum'),
            'mode' => 'add',
            'anneesAcademiques' => $annees,
            'statutsList' => $this->programmeStatuts
        ], 'admin_default');
    }

    public function edit_programme($id) {
        Auth::requirePermission('edit_programme_scolaire');
        $id = (int)$id;
        $programme = $this->psModel->getById($id);
        if (!$programme) {
            $_SESSION['flash_message'] = ['text' => __('ps_not_found_msg', 'Curriculum not found.'), 'type' => 'warning'];
            redirectTo('/admin/programmesscolaires');
        }

        $formData = (array)$programme;
        $formData['errors'] = [];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST_filtered = filter_input_array(INPUT_POST, [
                'nom' => FILTER_SANITIZE_STRING,
                'annee_academique_id' => FILTER_SANITIZE_NUMBER_INT,
                'description' => FILTER_DEFAULT,
                'statut' => FILTER_SANITIZE_STRING
            ]);
            $formData = array_merge($formData, $_POST_filtered);
            $formData['errors'] = $this->validateProgrammeData($formData, true, $id);

            if (empty($formData['errors'])) {
                $result = $this->psModel->update($id, $formData);
                if ($result === true) {
                    $_SESSION['flash_message'] = ['text' => __('ps_edit_success_msg', 'Curriculum updated successfully.'), 'type' => 'success'];
                    redirectTo('/admin/programmesscolaires');
                } else {
                    $errorMessage = ($result === 'duplicate_nom') ? __('ps_error_duplicate_nom', 'A curriculum with this name already exists.') : __('ps_edit_error_msg', 'Error updating curriculum.');
                    $_SESSION['flash_message'] = ['text' => $errorMessage, 'type' => 'danger'];
                }
            } else {
                 $_SESSION['flash_message'] = ['text' => I18n::translate('validation.form_errors_detail'), 'type' => 'danger'];
            }
        }
        $annees = $this->anneeModel->getAll([], ['orderBy' => 'date_debut', 'orderDir' => 'DESC']);
        $this->view('admin/programmes_scolaires/form_programme', [
            'data' => $formData,
            'title' => __('ps_title_edit', 'Edit Curriculum') . ': ' . htmlspecialchars($programme->nom),
            'mode' => 'edit', 'programmeId' => $id,
            'anneesAcademiques' => $annees,
            'statutsList' => $this->programmeStatuts
        ], 'admin_default');
    }

    public function delete_programme($id) {
        Auth::requirePermission('delete_programme_scolaire');
        $id = (int)$id;
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if ($this->psModel->delete($id)) {
                $_SESSION['flash_message'] = ['text' => __('ps_delete_success_msg', 'Curriculum deleted successfully.'), 'type' => 'success'];
            } else {
                $_SESSION['flash_message'] = ['text' => __('ps_delete_error_msg', 'Error deleting curriculum.'), 'type' => 'danger'];
            }
        } else {
            $_SESSION['flash_message'] = ['text' => I18n::translate('global.invalid_request_method'), 'type' => 'warning'];
        }
        redirectTo('/admin/programmesscolaires');
    }

    public function manage_details($programmeId) {
        Auth::requirePermission('manage_programme_details');
        $programmeId = (int)$programmeId;
        $programme = $this->psModel->getById($programmeId);
        if (!$programme) {
            $_SESSION['flash_message'] = ['text' => __('ps_not_found_msg', 'Curriculum not found.'), 'type' => 'warning'];
            redirectTo('/admin/programmesscolaires');
        }

        $details = $this->pdModel->getDetailsForProgramme($programmeId, ['orderBy' => 'pd.ordre ASC, m.nom ASC']);
        $allMatieres = $this->matiereModel->getAll([], ['orderBy' => 'nom', 'orderDir' => 'ASC']);

        $currentMatiereIds = array_map(function($d){ return $d->matiere_id; }, $details);
        $availableMatieres = array_filter($allMatieres, function($m) use ($currentMatiereIds) {
            return !in_array($m->id, $currentMatiereIds);
        });

        $this->view('admin/programmes_scolaires/manage_details', [
            'programme' => $programme,
            'details' => $details,
            'availableMatieres' => $availableMatieres,
            'title' => __('ps_title_manage_details', 'Manage Curriculum Details') . ': ' . htmlspecialchars($programme->nom)
        ], 'admin_default');
    }

    public function add_detail_to_programme($programmeId) {
        Auth::requirePermission('manage_programme_details');
        $programmeId = (int)$programmeId;
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST_filtered = filter_input_array(INPUT_POST, [
                'matiere_id' => FILTER_SANITIZE_NUMBER_INT,
                'notes_coefficient' => ['filter' => FILTER_SANITIZE_NUMBER_FLOAT, 'flags' => FILTER_FLAG_ALLOW_FRACTION],
                'heures_par_semaine' => ['filter' => FILTER_SANITIZE_NUMBER_FLOAT, 'flags' => FILTER_FLAG_ALLOW_FRACTION],
                'description_detail' => FILTER_DEFAULT, // More lenient, clean in view
                'ordre' => FILTER_SANITIZE_NUMBER_INT
            ]);

            $data = [
                'programme_id' => $programmeId,
                'matiere_id' => trim($_POST_filtered['matiere_id'] ?? ''),
                'notes_coefficient' => !empty($_POST_filtered['notes_coefficient']) ? (float)$_POST_filtered['notes_coefficient'] : null,
                'heures_par_semaine' => !empty($_POST_filtered['heures_par_semaine']) ? (float)$_POST_filtered['heures_par_semaine'] : null,
                'description_detail' => trim($_POST_filtered['description_detail'] ?? ''),
                'ordre' => !empty($_POST_filtered['ordre']) ? (int)$_POST_filtered['ordre'] : 0,
                'errors' => []
            ];

            if (empty($data['matiere_id'])) $data['errors']['matiere_id'] = I18n::translate('validation.required_select', ['field' => __('matieres_col_nom')]);
            // Add more validation for coeff, heures if needed (e.g. numeric, range)

            if (empty($data['errors'])) {
                $result = $this->pdModel->addMatiereToProgramme($data);
                if (is_numeric($result) && $result > 0) {
                    $_SESSION['flash_message'] = ['text' => __('ps_detail_add_success_msg', 'Subject added to curriculum successfully.'), 'type' => 'success'];
                } else {
                     $errorMessageKey = ($result === 'duplicate_matiere_in_programme') ? 'ps_error_duplicate_matiere_in_programme' : 'ps_detail_add_error_msg';
                    $_SESSION['flash_message'] = ['text' => __($errorMessageKey, 'Error adding subject to curriculum.'), 'type' => 'danger'];
                }
            } else {
                 $_SESSION['flash_message'] = ['text' => implode('; ', $data['errors']), 'type' => 'danger'];
            }
        }
        redirectTo('/admin/programmesscolaires/manage_details/' . $programmeId);
    }

    // This method handles updates from the manage_details page (e.g., inline edits submitted via POST)
    public function update_detail_in_programme($detailId) {
        Auth::requirePermission('manage_programme_details');
        $detailId = (int)$detailId;

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Assuming programme_id is part of the POST for redirection
            $programmeId = filter_input(INPUT_POST, 'programme_id', FILTER_SANITIZE_NUMBER_INT);

            $_POST_filtered = filter_input_array(INPUT_POST, [
                // matiere_id is generally not changed in an "edit detail" context. If it is, validation is needed.
                // 'matiere_id' => FILTER_SANITIZE_NUMBER_INT,
                'notes_coefficient' => ['filter' => FILTER_SANITIZE_NUMBER_FLOAT, 'flags' => FILTER_FLAG_ALLOW_FRACTION],
                'heures_par_semaine' => ['filter' => FILTER_SANITIZE_NUMBER_FLOAT, 'flags' => FILTER_FLAG_ALLOW_FRACTION],
                'description_detail' => FILTER_DEFAULT,
                'ordre' => FILTER_SANITIZE_NUMBER_INT
            ]);

            $detail = $this->pdModel->getById($detailId);
            if (!$detail) {
                 $_SESSION['flash_message'] = ['text' => __('ps_detail_not_found_msg', 'Curriculum detail not found.'), 'type' => 'danger'];
                 redirectTo('/admin/programmesscolaires'); // Or a more relevant page
            }

            // We MUST include matiere_id in the data array for updateMatiereInProgramme,
            // even if it's not being changed, because the unique key check in model might need it.
            // The model's updateMatiereInProgramme was simplified to not update matiere_id.
            $data = [
                'matiere_id' => $detail->matiere_id, // Keep existing matiere_id
                'notes_coefficient' => (isset($_POST_filtered['notes_coefficient']) && $_POST_filtered['notes_coefficient'] !== '') ? (float)$_POST_filtered['notes_coefficient'] : null,
                'heures_par_semaine' => (isset($_POST_filtered['heures_par_semaine']) && $_POST_filtered['heures_par_semaine'] !== '') ? (float)$_POST_filtered['heures_par_semaine'] : null,
                'description_detail' => trim($_POST_filtered['description_detail'] ?? ''),
                'ordre' => (isset($_POST_filtered['ordre']) && $_POST_filtered['ordre'] !== '') ? (int)$_POST_filtered['ordre'] : $detail->ordre
            ];
            // Add validation for $data here if needed

            $result = $this->pdModel->updateMatiereInProgramme($detailId, $data);
            if ($result === true) {
                $_SESSION['flash_message'] = ['text' => __('ps_detail_edit_success_msg', 'Curriculum subject detail updated.'), 'type' => 'success'];
            } else {
                // updateMatiereInProgramme currently doesn't return 'duplicate_matiere_in_programme'
                // because it doesn't change programme_id or matiere_id.
                $_SESSION['flash_message'] = ['text' => __('ps_detail_edit_error_msg', 'Error updating curriculum subject detail.'), 'type' => 'danger'];
            }

            if ($programmeId) {
                redirectTo('/admin/programmesscolaires/manage_details/' . $programmeId);
            } else {
                redirectTo('/admin/programmesscolaires'); // Fallback
            }
        } else {
            // Typically an edit action would have a GET part to show a form,
            // but for inline edits, this might only handle POST.
            redirectTo('/admin/programmesscolaires');
        }
    }


    public function remove_detail_from_programme($detailId) {
        Auth::requirePermission('manage_programme_details');
        $detailId = (int)$detailId;
        $programmeId = null;

        // Fetch the detail first to get programme_id for redirection, before deleting
        $detail = $this->pdModel->getById($detailId);
        if ($detail) {
            $programmeId = $detail->programme_id;
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') { // Require POST for delete
            if ($this->pdModel->removeMatiereFromProgramme($detailId)) {
                $_SESSION['flash_message'] = ['text' => __('ps_detail_delete_success_msg', 'Subject removed from curriculum.'), 'type' => 'success'];
            } else {
                $_SESSION['flash_message'] = ['text' => __('ps_detail_delete_error_msg', 'Error removing subject from curriculum.'), 'type' => 'danger'];
            }
        } else {
             $_SESSION['flash_message'] = ['text' => I18n::translate('global.invalid_request_method'), 'type' => 'warning'];
        }

        if ($programmeId) {
            redirectTo('/admin/programmesscolaires/manage_details/' . $programmeId);
        } else {
            redirectTo('/admin/programmesscolaires'); // Fallback if programmeId couldn't be determined
        }
    }
}
?>
