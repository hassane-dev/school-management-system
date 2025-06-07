<?php
namespace App\Controllers\Parent; // PHP convention is singular for namespaces usually, but 'Parent' is okay.

use App\Core\Controller;
use App\Core\Auth;
use App\Core\I18n;

class DashboardController extends Controller {
    public function __construct() {
        Auth::requirePermission('access_parent_dashboard'); // Example permission
        // if (!Auth::isLoggedIn() || !in_array('Parent', Auth::getCurrentUserRoleNames())) {
        //     $_SESSION['flash_message'] = ['text' => I18n::translate('global.access_denied'), 'type' => 'danger'];
        //     redirectTo('/auth/login');
        // }
    }

    public function index() {
        $data = [
            'title' => I18n::translate('parent_dashboard_title', "Parent's Dashboard"),
            'user' => Auth::getCurrentUser()
        ];
        $this->view('parent/dashboard/index', $data, 'default');
    }
}
?>
