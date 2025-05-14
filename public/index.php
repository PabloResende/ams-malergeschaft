<?php
// public/index.php — Front controller do sistema

// 1) Inicia sessão
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// 2) Carrega config de DB, uploads, timezone
require_once __DIR__ . '/../config/database.php';

// 3) (Opcional) Carrega variáveis de ambiente
// Se não tiver env.php, pode omitir esta linha
$env = require __DIR__ . '/../config/env.php';

// 4) Carrega controllers e traduções
require_once __DIR__ . '/../app/controllers/UserController.php';
require_once __DIR__ . '/../app/controllers/ProjectController.php';
require_once __DIR__ . '/../app/controllers/InventoryController.php';
require_once __DIR__ . '/../app/controllers/EmployeeController.php';
require_once __DIR__ . '/../app/controllers/ClientsController.php';
require_once __DIR__ . '/../app/controllers/CalendarController.php';
require_once __DIR__ . '/../app/controllers/AnalyticsController.php';
require_once __DIR__ . '/../app/controllers/FinancialController.php';
require_once __DIR__ . '/../app/lang/lang.php';

// 5) Helpers de URL e assets
function url(string $path = ''): string {
    return BASE_URL . '/' . ltrim($path, '/');
}
function asset(string $path = ''): string {
    return BASE_URL . '/public/' . ltrim($path, '/');
}

// 6) Monta a rota atual (remove /system do início)
$uri   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$route = preg_replace('#^/system#', '', $uri);
$route = rtrim($route, '/');
if ($route === '') {
    $route = '/';
}

// 7) Troca de idioma
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
    if (! headers_sent()) {
        setcookie('lang', $_GET['lang'], time() + 86400 * 30, "/system/");
    }
}

// 8) Verifica acesso (público vs. privado)
$publicRoutes = ['/', '/login', '/auth', '/register', '/store'];
if (! in_array($route, $publicRoutes, true) && ! isset($_SESSION['user'])) {
    header("Location: " . url('login'));
    exit;
}

// 9) Instancia controllers
$userController      = new UserController();
$projectController   = new ProjectController();
$inventoryController = new InventoryController();
$employeeController  = new EmployeeController();
$clientsController   = new ClientsController();
$calendarController  = new CalendarController();
$analyticsController = new AnalyticsController();
$financialController = new FinancialController();

// Dispatcher de rotas
switch ($route) {
    // USUÁRIO
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

    // PROJETOS
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
    case '/projects/transactions':
        $projectController->transactions();
        break;
    case '/projects/checkEmployee':
        $projectController->checkEmployee();
        break;

    // FUNCIONÁRIOS
    case '/employees':
        $employeeController->list();
        break;
    case '/employees/checkAllocation':
        $employeeController->checkAllocation();
        break;
    case '/employees/store':
        $employeeController->store();
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

    // CLIENTES
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

    // INVENTÁRIO
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
    case '/inventory/control/store':
        $inventoryController->storeControl();
        break;
    case '/inventory/history':
        $inventoryController->history();
        break;
    case '/inventory/history/details':
        $inventoryController->historyDetails();
        break;

    // CALENDÁRIO
    case '/calendar':
        $calendarController->index();
        break;
    case '/calendar/store':
        $calendarController->store();
        break;
    case '/calendar/fetch':
        $calendarController->fetch();
        break;

    // ANALYTICS
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

    // FINANCEIRO
    case '/finance':
        $financialController->index();
        break;
    case '/finance/create':
        $financialController->create();
        break;
    case '/finance/store':
        $financialController->store();
        break;
    case '/finance/edit':
        $financialController->edit();
        break;
    case '/finance/update':
        $financialController->update();
        break;
    case '/finance/delete':
        $financialController->delete();
        break;
    case '/finance/report':
        $financialController->report();
        break;
    case '/finance/attachment/download':
        $financialController->downloadAttachment();
        break;

    default:
        http_response_code(404);
        echo "404 - Página não encontrada.";
        break;
}
