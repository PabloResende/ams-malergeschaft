<?php
ob_start();
session_start();

$basePath = '/ams-malergeschaft/public';

require_once __DIR__ . '/../app/controllers/UserController.php';
require_once __DIR__ . '/../app/controllers/ProjectController.php';
require_once __DIR__ . '/../app/controllers/InventoryController.php';
require_once __DIR__ . '/../app/controllers/EmployeeController.php';
require_once __DIR__ . '/../app/controllers/ClientsController.php';
require_once __DIR__ . '/../app/lang/lang.php';

$uri = $_SERVER['REQUEST_URI'];
$route = str_replace($basePath, '', parse_url($uri, PHP_URL_PATH));

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
    if (!headers_sent()) {
        setcookie('lang', $_GET['lang'], time() + (86400 * 30), "/");
    }
}
$route = strtok($route, '?');

$userController = new UserController();
$projectController = new ProjectController();
$inventoryController = new InventoryController();
$employeeController = new EmployeeController();
$clientsController = new ClientsController();

switch ($route) {
    case '/':
    case '/login':
        $userController->login();
        break;
    case '/auth':
        $userController->authenticate();
        break;
    case '/register':
        $userController->register();
        break;
    case '/store':
        $userController->store();
        break;
    case '/dashboard':
        $userController->dashboard();
        break;
    case '/logout':
        $userController->logout();
        break;
    case '/profile':
        $userController->profile();
        break;
    case '/projects':
        $projectController->index();
        break;
    case '/projects/store':
        $projectController->store();
        break;
    case '/projects/update':
        $projectController->update();
        break;
    case '/projects/delete':
        $projectController->delete();
        break;
    case '/employees':
        $employeeController->list();
        break;
    case '/employees/create':
        $employeeController->create();
        break;
    case '/employees/store':
        $employeeController->store();
        break;
    case '/employees/edit':
        $employeeController->edit();
        break;
    case '/employees/update':
        $employeeController->update();
        break;
    case '/employees/delete':
        $employeeController->delete();
        break;
    case '/employees/get':
    case '/employees/getEmployee':
        $employeeController->getEmployee();
        break;
    case '/clients':
        $clientsController->list();
        break;
    case '/clients/create':
        $clientsController->create();
        break;
    case '/clients/store':
        $clientsController->store();
        break;
    case '/clients/edit':
        $clientsController->edit();
        break;
    case '/clients/update':
        $clientsController->update();
        break;
    case '/clients/delete':
        $clientsController->delete();
        break;
    case '/inventory':
        $inventoryController->index();
        break;
    case '/inventory/create':
        $inventoryController->create();
        break;
    case '/inventory/store':
        $inventoryController->store();
        break;
    case '/inventory/edit':
        $inventoryController->edit();
        break;
    case '/inventory/update':
        $inventoryController->update();
        break;
    case '/inventory/delete':
        $inventoryController->delete();
        break;
    default:
        http_response_code(404);
        echo "404 - Page not found.";
        break;
}

ob_end_flush();
?>