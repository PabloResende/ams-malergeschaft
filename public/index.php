<?php
ob_start();

session_start();

require_once __DIR__ . '/../app/controllers/UserController.php';
require_once __DIR__ . '/../app/controllers/ProjectController.php';

require_once __DIR__ . '/../app/lang/lang.php';

$uri = $_SERVER['REQUEST_URI'];
$basePath = '/ams-malergeschaft/public';
$route = str_replace($basePath, '', $uri);

// Gerencia o idioma
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
    if (!headers_sent()) {
        setcookie('lang', $_GET['lang'], time() + (86400 * 30), "/");
    }
}
$route = strtok($route, '?');

$userController = new UserController();
$projectController = new ProjectController();

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
    case '/create_project':
        $projectController->create();
        break;
    case '/projects/store':
        $projectController->store();
        break;
    case '/projects/details':
        $projectController->show();
        break;
    case '/employees':
        require_once __DIR__ . '/../app/views/employees/index.php';
        break;
    case '/employees':
        require_once __DIR__ . '/../app/views/employees/index.php';
        break;
    case '/employees/create':
        require_once __DIR__ . '/../app/controllers/EmployeeController.php';
        $employeeController = new EmployeeController();
        $employeeController->create();
        break;
    case '/employees/store':
        require_once __DIR__ . '/../app/controllers/EmployeeController.php';
        $employeeController = new EmployeeController();
        $employeeController->store();
        break;
    case '/employees/list':
        require_once __DIR__ . '/../app/controllers/EmployeeController.php';
        $employeeController = new EmployeeController();
        $employeeController->list();
        break;    
    default:
        http_response_code(404);
        echo "404 - Page not found.";
        break;
}

ob_end_flush();
?>
