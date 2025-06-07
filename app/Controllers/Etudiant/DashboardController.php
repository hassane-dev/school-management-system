<?php
namespace App\Controllers\Etudiant;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\I18n;

class DashboardController extends Controller {
    public function __construct() {
        Auth::requirePermission('access_etudiant_dashboard'); // Example permission
        // if (!Auth::isLoggedIn() || !in_array('Etudiant', Auth::getCurrentUserRoleNames())) {
        //     $_SESSION['flash_message'] = ['text' => I18n::translate('global.access_denied'), 'type' => 'danger'];
        //     redirectTo('/auth/login');
        // }
    }

    public function index() {
        $data = [
            'title' => I18n::translate('etudiant_dashboard_title', 'Student Dashboard'),
            'user' => Auth::getCurrentUser()
        ];
        $this->view('etudiant/dashboard/index', $data, 'default');
    }
}
?>
