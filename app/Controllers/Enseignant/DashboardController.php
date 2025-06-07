<?php
namespace App\Controllers\Enseignant;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\I18n;

class DashboardController extends Controller {
    public function __construct() {
        Auth::requirePermission('access_enseignant_dashboard'); // Example permission
        // Or, more simply, check if user has 'Enseignant' role if permissions aren't granular yet for this.
        // if (!Auth::isLoggedIn() || !in_array('Enseignant', Auth::getCurrentUserRoleNames())) {
        //     $_SESSION['flash_message'] = ['text' => I18n::translate('global.access_denied'), 'type' => 'danger'];
        //     redirectTo('/auth/login');
        // }
    }

    public function index() {
        $data = [
            'title' => I18n::translate('enseignant_dashboard_title', 'Teacher Dashboard'),
            'user' => Auth::getCurrentUser()
        ];
        $this->view('enseignant/dashboard/index', $data, 'default'); // Using general 'default' layout
    }
}
?>
