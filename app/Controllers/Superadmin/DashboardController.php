<?php
namespace App\Controllers\Superadmin;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\I18n; // For translations

class DashboardController extends Controller {
    public function __construct() {
        // Protection for the Superadmin area
        $isUltimateSuperAdmin = isset($_SESSION['is_ultimate_superadmin']) && $_SESSION['is_ultimate_superadmin'] === true;
        $hasSuperAdminRole = Auth::isLoggedIn() && in_array('SuperAdmin', Auth::getCurrentUserRoleNames());
        $canAccessInterface = Auth::can('access_superadmin_interface'); // General permission for SA dashboard

        // User must be the specific email OR have SuperAdmin role AND the specific permission
        // The email check is an override for the specific user.
        // All other SuperAdmins must have the 'access_superadmin_interface' permission.
        if ($isUltimateSuperAdmin) {
            // If it's the special email, ensure they also get the 'access_superadmin_interface' contextually
            // This might mean loadUserAuthContext should grant it if email matches, or we assume role is also set.
            // For now, if email matches, we trust the session flag set at login.
            // However, for Auth::can() to work, the permission must be in their session permission list.
            // The `Auth::loadUserAuthContext` already gives all permissions to SuperAdmin role.
            // So, if hasmixione@gmail.com has SuperAdmin role, this will work.
            // If hasmixione@gmail.com does NOT have SuperAdmin role but is treated as SA by email,
            // then Auth::can('access_superadmin_interface') might fail unless this perm is given by another role.
            // For simplicity, assuming hasmixione@gmail.com IS a SuperAdmin role holder.
        } elseif (!$hasSuperAdminRole || !$canAccessInterface) {
             // If not the special email, then must have SuperAdmin role AND the permission
            $_SESSION['flash_message'] = ['text' => I18n::translate('global.access_denied'), 'type' => 'danger'];
            redirectTo('/auth/login'); // Or to a less privileged dashboard if logged in
        }
        // An alternative simpler check: Auth::requirePermission('access_superadmin_interface');
        // This relies on the 'hasmixione@gmail.com' user having the SuperAdmin role which then has this permission.
        Auth::requirePermission('access_superadmin_interface');

    }

    public function index() {
        $this->view('superadmin/dashboard/index',
            ['title' => __('superadmin_dashboard_title', 'SuperAdmin Dashboard')],
            'admin_default'); // Can reuse admin_default layout or create superadmin_default
    }
}
?>
