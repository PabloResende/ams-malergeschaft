<?php
ob_start();
session_start();

$basePath = '/ams-malergeschaft/public';

require_once __DIR__ . '/../app/controllers/UserController.php';
require_once __DIR__ . '/../app/controllers/ProjectController.php';
require_once __DIR__ . '/../app/controllers/InventoryController.php';
require_once __DIR__ . '/../app/controllers/EmployeeController.php';
require_once __DIR__ . '/../app/lang/lang.php';

// Obtém a URI e remove o $basePath e a query string
$uri = $_SERVER['REQUEST_URI'];
$route = str_replace($basePath, '', parse_url($uri, PHP_URL_PATH));

// Gerencia o idioma
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
    if (!headers_sent()) {
        setcookie('lang', $_GET['lang'], time() + (86400 * 30), "/");
    }
}

switch ($route) {
    case '/':
    case '/login':
        $userController = new UserController();
        $userController->login();
        break;

    case '/auth':
        $userController = new UserController();
        $userController->authenticate();
        break;

    case '/register':
        $userController = new UserController();
        $userController->register();
        break;

    case '/store':
        $userController = new UserController();
        $userController->store();
        break;

    case '/dashboard':
        $userController = new UserController();
        $userController->dashboard();
        break;

    case '/logout':
        $userController = new UserController();
        $userController->logout();
        break;

    case '/profile':
        $userController = new UserController();
        $userController->profile();
        break;

    case '/projects':
        $projectController = new ProjectController();
        $projectController->index();
        break;

    case '/create_project':
        $projectController = new ProjectController();
        $projectController->create();
        break;

    case '/projects/store':
        $projectController = new ProjectController();
        $projectController->store();
        break;

    case '/projects/details':
        $projectController = new ProjectController();
        $projectController->show();
        break;

    case '/employees':
        $employeeController = new EmployeeController();
        $employeeController->list();
        break;

    case '/employees/create':
        $employeeController = new EmployeeController();
        $employeeController->create();
        break;

    case '/employees/store':
        $employeeController = new EmployeeController();
        $employeeController->store();
        break;

    case '/employees/list':
        $employeeController = new EmployeeController();
        $employeeController->list();
        break;

    case '/inventory':
        $inventoryController = new InventoryController();
        $inventoryController->index();
        break;

    case '/inventory/store':
        $inventoryController = new InventoryController();
        $inventoryController->store();
        break;

    default:
        http_response_code(404);
        echo "404 - Page not found.";
        break;
}

ob_end_flush();
?>
