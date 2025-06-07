<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth; // For isLoggedIn and getCurrentUser
use App\Core\I18n;

class DashboardController extends Controller {
    public function __construct() {
        // This is a generic dashboard, requires login at minimum
        if (!Auth::isLoggedIn()) {
            $_SESSION['flash_message'] = ['text' => I18n::translate('global.login_required'), 'type' => 'warning'];
            redirectTo('/auth/login');
        }
    }

    public function index() {
        $user = Auth::getCurrentUser();
        $data = [
            // Title can be generic or slightly personalized
            'title' => I18n::translate('user_dashboard_title', 'My Dashboard'),
            'user_name' => $user ? htmlspecialchars($user->nom) : __('guest_user', 'Guest')
        ];
        $this->view('dashboard/index', $data, 'default'); // Use general 'default' layout
    }
}
?>
