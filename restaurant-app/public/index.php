<?php
require_once '../src/utils/Database.php';
require_once '../src/controllers/AdminController.php';
require_once '../src/controllers/ClientController.php';
require_once '../src/controllers/ReservationController.php';

// Initialize the database connection
$db = new Database();
$conn = $db->getConnection();

// Start the session
session_start();

// Routing logic
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Define routes
switch ($requestUri) {
    case '/':
        require '../src/views/home.php';
        break;
    case '/menu':
        require '../src/views/menu.php';
        break;
    case '/reservations':
        $clientController = new ClientController($conn);
        $clientController->viewReservations();
        break;
    case '/login':
        require '../src/views/auth/login.php';
        break;
    case '/register':
        require '../src/views/auth/register.php';
        break;
    case '/reserve':
        $reservationController = new ReservationController($conn);
        $reservationController->createReservation();
        break;
    case '/admin/dashboard':
        $adminController = new AdminController($conn);
        $adminController->dashboard();
        break;
    case '/admin/manage_gallery':
        $adminController = new AdminController($conn);
        $adminController->manageGallery();
        break;
    case '/admin/manage_menu':
        $adminController = new AdminController($conn);
        $adminController->manageMenu();
        break;
    case '/admin/manage_reservations':
        $adminController = new AdminController($conn);
        $adminController->manageReservations();
        break;
    default:
        http_response_code(404);
        require '../src/views/404.php'; // Assuming a 404 view exists
        break;
}
?>