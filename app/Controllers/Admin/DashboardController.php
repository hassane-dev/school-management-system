<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\I18n; // For translations
use App\Core\Auth;  // For ACL

class DashboardController extends Controller {

    public function __construct() {
        // ACL: User must be logged in and have permission to view the admin dashboard.
        // The router should already protect /admin paths for general login.
        // This adds a specific permission check for this controller.
        Auth::requirePermission('view_admin_dashboard');
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
