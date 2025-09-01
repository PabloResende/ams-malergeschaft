<?php
// system/public/index.php — Front controller

// 1) Inicia sessão
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// 2) Carrega env (opcional)
if (file_exists(__DIR__ . '/../config/env.php')) {
    $env = require __DIR__ . '/../config/env.php';
}

// 3) Conexão com o banco
require_once __DIR__ . '/../config/database.php';

// 4) Helpers (url(), asset(), isLoggedIn(), isEmployee(), isAdminOrFinance()…)
require_once __DIR__ . '/../app/helpers.php';

// 5) Idioma e traduções
$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'pt';
$_SESSION['lang'] = $lang;
$langFile = __DIR__ . "/../app/lang/{$lang}.php";
$langText = file_exists($langFile) ? require $langFile : [];

// 6) BASE_URL
if (! defined('BASE_URL')) {
    if (! empty($env['base_url'])) {
        define('BASE_URL', rtrim($env['base_url'], '/'));
    } else {
        define('BASE_URL', 'https://ams.swiss/system');
    }
}

// 7) Carrega controllers
require_once __DIR__ . '/../app/controllers/UserController.php';
require_once __DIR__ . '/../app/controllers/ProjectController.php';
require_once __DIR__ . '/../app/controllers/InventoryController.php';
require_once __DIR__ . '/../app/controllers/EmployeeController.php';
require_once __DIR__ . '/../app/controllers/ClientsController.php';
require_once __DIR__ . '/../app/controllers/CalendarController.php';
require_once __DIR__ . '/../app/controllers/AnalyticsController.php';
require_once __DIR__ . '/../app/controllers/FinancialController.php';
require_once __DIR__ . '/../app/controllers/WorkLogController.php';
require_once __DIR__ . '/../app/controllers/CarController.php'; 

// 8) Resolve rota
$uri   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$route = preg_replace('#^/system#', '', $uri);
$route = rtrim($route, '/');
if ($route === '') {
    $route = '/';
}

// Rotas públicas
$publicRoutes = ['/', '/login', '/auth', '/register', '/store'];
if (!in_array($route, $publicRoutes, true) && !isLoggedIn()) {
    redirect('/login');
}

// Só employee e finance acessa seu dashboard e profile
if (in_array($route, ['/employees/dashboard', '/employees/profile'], true)
    && !isEmployee() && !isFinance()
) {
    redirect('/dashboard');
}

// ─── AUTORIZAÇÃO PARA CRUD DE FUNCIONÁRIOS ───────────────────────────────
$employeeCrudNoUpdate = [
    '/employees',
    '/employees/store',
    '/employees/delete',
    '/employees/get'
];

if ($route === '/employees/update') {
    if (! (isAdminOrFinance() || isEmployee())) {
        redirect('/');
    }
} elseif (in_array($route, $employeeCrudNoUpdate, true)) {
    if (!isAdminOrFinance()) {
        redirect('/');
    }
}

// Finance só acessa aba financeira e análises
$financeOnly = ['/finance','/analytics','/analytics/stats'];
if (in_array($route, $financeOnly, true) && !isFinance() && !isAdmin()) {
    redirect('/');
}

/* ===== FIM DO BLOCO DE VALIDAÇÃO POR ROLES ===== */

// 10) Instancia controllers
$userController      = new UserController();
$projectController   = new ProjectController();
$inventoryController = new InventoryController();
$employeeController  = new EmployeeController();
$clientsController   = new ClientsController();
$calendarController  = new CalendarController();
$analyticsController = new AnalyticsController();
$financialController = new FinancialController();
$workLogController   = new WorkLogController();
$carController       = new CarController(); // <--- Adicionado

// 11) Dispatcher de rotas
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
    case '/employees/dashboard':
        $userController->employeeDashboard();
        break;
    case '/employees/profile':
        $employeeController->profile();
        break;
    case '/logout':
        $userController->logout();
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
    case '/projects/delete':
        $projectController->delete();
        break;
    case '/projects/show':
        $projectController->show();
        break;
    case '/projects/checkEmployee':
        $projectController->checkEmployee();
        break;
    case '/projects/transactions':
        $projectController->transactions();
        break;

    // WORK LOGS
    case '/work_logs/index':
        $workLogController->index();
        break;
    case '/work_logs/store':
        $workLogController->store();
        break;
    case '/work_logs/project_totals':
        $workLogController->project_totals();
        break;

    // FUNCIONÁRIOS CRUD
    case '/employees':
        $employeeController->list();
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
    case '/clients/show':
        $clientsController->show();
        break;
    case '/clients/edit':
        $clientsController->edit();
        break;
    case '/clients/save':
        $clientsController->save();
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

    // INVENTÁRIO AJAX (Novas rotas)
    case '/inventory/add-quantity-ajax':
        $inventoryController->addQuantityAjax();
        break;
    case '/inventory/delete-ajax':
        $inventoryController->deleteAjax();
        break;
    case '/inventory/update-description-ajax':
        $inventoryController->updateDescriptionAjax();
        break;

     // CARROS
    case '/cars':
        $carController->index();
        break;
    case '/cars/store':
        $carController->store();
        break;
    case '/cars/get':
        $carController->get();
        break;
    case '/cars/update':
        $carController->update();
        break;
    case '/cars/delete':
        $carController->delete();
        break;
    case '/cars/usage/store':     
        $carController->storeUsage();
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