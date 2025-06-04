<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\I18n; // For using __() if needed directly in controller, or for I18n::translate

class HomeController extends Controller {

    public function __construct(){
        // parent::__construct(); // Call parent constructor if it has one
    }

    public function index($param1 = '', $param2 = '') {
        $data = [
            'page_specific_title' => __('welcome_message'), // Example for a title specific to this view
            'param1' => htmlspecialchars($param1), // Example of passing params from URL
            'param2' => htmlspecialchars($param2)
        ];
        $this->view('home/index', $data);
    }

    public function about() {
        $data = [
            'page_specific_title' => __('about_us_title')
        ];
        $this->view('home/about', $data);
    }

    public function submit_contact() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Basic sanitization (real app needs more robust validation)
            $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $message_content = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);

            // Here you would typically:
            // 1. Validate the data more thoroughly.
            // 2. Send an email, save to database, etc.
            // 3. Set a flash message for success/failure.

            // For this example, just redirect back to 'about' with a success query parameter.
            // Using AuthSession for flash messages would be better if available and integrated.
            if (class_exists('App\Core\AuthSession') && method_exists('App\Core\AuthSession', 'setFlash')) {
                \App\Core\AuthSession::setFlash(I18n::translate('form_submit_success'), 'success');
                header("Location: " . URL_ROOT . "/home/about");
                exit;
            } else {
                // Fallback if AuthSession or setFlash is not available
                $success_message = urlencode(I18n::translate('form_submit_success'));
                header("Location: " . URL_ROOT . "/home/about?message=" . $success_message);
                exit;
            }
        }
        // If not POST, redirect to the about page
        header("Location: " . URL_ROOT . "/home/about");
        exit;
    }
}
?>
