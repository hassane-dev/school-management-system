<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\I18n; // For translations

class DashboardController extends Controller {

    public function __construct() {
        // Basic ACL placeholder - router should protect /admin paths
        // Actual role/permission check should be here using AuthSession
        // if (!\App\Core\AuthSession::isLoggedIn() || !\App\Core\AuthSession::hasRole(['admin', 'super_admin', 'manager'])) { // Example roles
        //     $_SESSION['flash_message'] = ['text' => I18n::translate('global.access_denied_admin_area'), 'type' => 'danger'];
        //     header("Location: " . URL_ROOT . "/auth/login");
        //     exit;
        // }
    }

    public function index() {
        $data = [
            'title' => I18n::translate('admin_dashboard_title', 'Admin Dashboard')
            // You can fetch some stats or summary data here later
        ];
        $this->view('admin/dashboard/index', $data, 'admin_default');
    }
}
?>
