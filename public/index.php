<?php
ob_start();
session_start();

$basePath = '/ams-malergeschaft/public';

require_once __DIR__ . '/../app/controllers/UserController.php';
require_once __DIR__ . '/../app/controllers/ProjectController.php';
require_once __DIR__ . '/../app/controllers/InventoryController.php';
require_once __DIR__ . '/../app/controllers/EmployeeController.php';
require_once __DIR__ . '/../app/controllers/ClientsController.php';
require_once __DIR__ . '/../app/controllers/CalendarController.php';
require_once __DIR__ . '/../app/controllers/AnalyticsController.php';
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

// Proteção de rotas
$publicRoutes = ['/', '/login', '/auth', '/register', '/store'];
if (!in_array($route, $publicRoutes) && !isset($_SESSION['user'])) {
    header("Location: $basePath/login");
    exit;
}

$userController = new UserController();
$projectController = new ProjectController();
$inventoryController = new InventoryController();
$employeeController = new EmployeeController();
$clientsController = new ClientsController();
$calendarController = new CalendarController();
$analyticsController = new AnalyticsController();

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
    case '/projects/edit':
        $projectController->edit();
        break;
    case '/projects/show':
        $projectController->show();
        break;
    case '/projects/delete':
        $projectController->delete();
        break;
    case '/employees':
        $employeeController->list();
        break;
    case '/employees/checkAllocation':
        $employeeController->checkAllocation();
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
        $employeeController->get();
        break;
    case '/employees/document':
        $employeeController->serveDocument();
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
    case '/clients/show':
        $clientsController->show();
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
    case '/inventory':
        $inventoryController->index();
        break;
    case '/inventory/control/store':
        $inventoryController->storeControl();
        break;
    case '/inventory/history':
        $inventoryController->history();
        break;
    case '/inventory/history/details':
        $inventoryController->historyDetails();
        break;
    case '/calendar':
        $calendarController->index();
        break;
    case '/calendar/store':
        $calendarController->store();
        break;
    case '/calendar/fetch':
        $calendarController->fetch();
        break;
    case '/analytics':
        $analyticsController->index();
        break;
    case '/analytics/stats':
        $analyticsController->stats();
        break;
    case '/analytics/exportPdf':
        $analyticsController->exportPdf();
        break;
    case '/analytics/exportExcel':
        $analyticsController->exportExcel();
        break;
    case '/analytics/sendEmail':
        $analyticsController->sendEmail();
        break;
    default:
        http_response_code(404);
        echo "404 - Page not found.";
        break;
}

ob_end_flush();
