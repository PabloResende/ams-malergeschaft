<?php

// system/public/index.php — Front controller ATUALIZADO

// 1) Inicia sessão
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// 2) Carrega env (opcional)
if (file_exists(__DIR__.'/../config/env.php')) {
    $env = require __DIR__.'/../config/env.php';
}

// 3) Conexão com o banco
require_once __DIR__.'/../config/database.php';

// 4) Helpers (url(), asset(), isLoggedIn(), isEmployee(), isAdminOrFinance()…)
require_once __DIR__.'/../app/helpers.php';

// 5) Idioma e traduções
$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'pt';
$_SESSION['lang'] = $lang;
$langFile = __DIR__."/../app/lang/{$lang}.php";
$langText = file_exists($langFile) ? require $langFile : [];

// 6) BASE_URL
if (!defined('BASE_URL')) {
    if (!empty($env['base_url'])) {
        define('BASE_URL', rtrim($env['base_url'], '/'));
    } else {
        define('BASE_URL', 'https://ams.swiss/system');
    }
}

// 7) Carrega controllers
require_once __DIR__.'/../app/controllers/UserController.php';
require_once __DIR__.'/../app/controllers/ProjectController.php';
require_once __DIR__.'/../app/controllers/InventoryController.php';
require_once __DIR__.'/../app/controllers/EmployeeController.php';
require_once __DIR__.'/../app/controllers/ClientsController.php';
require_once __DIR__.'/../app/controllers/CalendarController.php';
require_once __DIR__.'/../app/controllers/AnalyticsController.php';
require_once __DIR__.'/../app/controllers/FinancialController.php';
require_once __DIR__.'/../app/controllers/WorkLogController.php';
require_once __DIR__.'/../app/controllers/CarController.php';

// 8) Resolve rota
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
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
    '/employees/get',
];

if ($route === '/employees/update') {
    if (!(isAdminOrFinance() || isEmployee())) {
        redirect('/');
    }
} elseif (in_array($route, $employeeCrudNoUpdate, true)) {
    if (!isAdminOrFinance()) {
        redirect('/');
    }
}

// Finance só acessa aba financeira e análises
$financeOnly = ['/finance', '/analytics', '/analytics/stats'];
if (in_array($route, $financeOnly, true) && !isFinance() && !isAdmin()) {
    redirect('/');
}

/* ===== FIM DO BLOCO DE VALIDAÇÃO POR ROLES ===== */

// 10) Instancia controllers
$userController = new UserController();
$projectController = new ProjectController();
$inventoryController = new InventoryController();
$employeeController = new EmployeeController();
$clientsController = new ClientsController();
$calendarController = new CalendarController();
$analyticsController = new AnalyticsController();
$financialController = new FinancialController();
$workLogController = new WorkLogController();
$carController = new CarController();

// 11) Dispatcher de rotas
switch (true) {
    // USUÁRIO
    case $route === '/' || $route === '/login':
        $userController->login();
        break;
    case $route === '/auth':
        $userController->authenticate();
        break;
    case $route === '/register':
        $userController->register();
        break;
    case $route === '/store':
        $userController->store();
        break;
    case $route === '/dashboard':
        $userController->dashboard();
        break;
    case $route === '/employees/dashboard':
        $userController->employeeDashboard();
        break;
    case $route === '/employees/profile':
        $employeeController->profile();
        break;
    case $route === '/logout':
        $userController->logout();
        break;

    // PROJETOS
    case $route === '/projects':
        $projectController->index();
        break;
    case $route === '/projects/store':
        $projectController->store();
        break;
    case $route === '/projects/update':
        $projectController->update();
        break;
    case $route === '/projects/delete':
        $projectController->delete();
        break;
    case $route === '/projects/show':
        $projectController->show();
        break;
    case $route === '/projects/checkEmployee':
        $projectController->checkEmployee();
        break;
    case $route === '/projects/transactions':
        $projectController->transactions();
        break;

    // WORK LOGS
    case $route === '/work_logs/index':
        $workLogController->index();
        break;
    case $route === '/work_logs/store':
        $workLogController->store();
        break;
    case $route === '/work_logs/store_time_entry':
        $workLogController->store_time_entry();
        break;
    case $route === '/work_logs/project_totals':
        $workLogController->project_totals();
        break;

    // FUNCIONÁRIOS CRUD
    case $route === '/employees':
        $employeeController->list();
        break;
    case $route === '/employees/store':
        $employeeController->store();
        break;
    case $route === '/employees/update':
        $employeeController->update();
        break;
    case $route === '/employees/delete':
        $employeeController->delete();
        break;
    case $route === '/employees/get':
        $employeeController->get();
        break;

    // API HORAS DOS FUNCIONÁRIOS - NOVAS ROTAS
    case preg_match('/^\/api\/employees\/hours\/(\d+)$/', $route, $matches):
        $employeeController->getEmployeeHours((int) $matches[1]);
        break;
    case $route === '/api/employees/hours-summary':
        $employeeController->getEmployeeHoursSummary();
        break;

    // API WORK LOGS
    case preg_match('/^\/api\/work_logs\/time_entries\/(\d+)$/', $route, $matches):
        $workLogController->time_entries((int) $matches[1]);
        break;

    // CLIENTES
    case $route === '/clients':
        $clientsController->list();
        break;
    case $route === '/clients/create':
        $clientsController->create();
        break;
    case $route === '/clients/show':
        $clientsController->show();
        break;
    case $route === '/clients/edit':
        $clientsController->edit();
        break;
    case $route === '/clients/save':
        $clientsController->save();
        break;
    case $route === '/clients/delete':
        $clientsController->delete();
        break;

    // INVENTÁRIO
    case $route === '/inventory':
        $inventoryController->index();
        break;
    case $route === '/inventory/create':
        $inventoryController->create();
        break;
    case $route === '/inventory/store':
        $inventoryController->store();
        break;
    case $route === '/inventory/edit':
        $inventoryController->edit();
        break;
    case $route === '/inventory/update':
        $inventoryController->update();
        break;
    case $route === '/inventory/delete':
        $inventoryController->delete();
        break;
    case $route === '/inventory/control/store':
        $inventoryController->storeControl();
        break;
    case $route === '/inventory/history':
        $inventoryController->history();
        break;
    case $route === '/inventory/history/details':
        $inventoryController->historyDetails();
        break;

    // INVENTÁRIO AJAX
    case $route === '/inventory/add-quantity-ajax':
        $inventoryController->addQuantityAjax();
        break;
    case $route === '/inventory/delete-ajax':
        $inventoryController->deleteAjax();
        break;
    case $route === '/inventory/update-description-ajax':
        $inventoryController->updateDescriptionAjax();
        break;

     // CARROS
    case $route === '/cars':
        $carController->index();
        break;
    case $route === '/cars/store':
        $carController->store();
        break;
    case $route === '/cars/get':
        $carController->get();
        break;
    case $route === '/cars/update':
        $carController->update();
        break;
    case $route === '/cars/delete':
        $carController->delete();
        break;
    case $route === '/cars/usage/store':
        $carController->storeUsage();
        break;

    // CALENDÁRIO
    case $route === '/calendar':
        $calendarController->index();
        break;
    case $route === '/calendar/store':
        $calendarController->store();
        break;
    case $route === '/calendar/fetch':
        $calendarController->fetch();
        break;

    // ANALYTICS
    case $route === '/analytics':
        $analyticsController->index();
        break;
    case $route === '/analytics/stats':
        $analyticsController->stats();
        break;
    case $route === '/analytics/exportPdf':
        $analyticsController->exportPdf();
        break;
    case $route === '/analytics/exportExcel':
        $analyticsController->exportExcel();
        break;
    case $route === '/analytics/sendEmail':
        $analyticsController->sendEmail();
        break;

    // FINANCEIRO
    case $route === '/finance':
        $financialController->index();
        break;
    case $route === '/finance/create':
        $financialController->create();
        break;
    case $route === '/finance/store':
        $financialController->store();
        break;
    case $route === '/finance/edit':
        $financialController->edit();
        break;
    case $route === '/finance/update':
        $financialController->update();
        break;
    case $route === '/finance/delete':
        $financialController->delete();
        break;
    case $route === '/finance/report':
        $financialController->report();
        break;
    case $route === '/finance/attachment/download':
        $financialController->downloadAttachment();
        break;

    default:
        http_response_code(404);
        echo '404 - Página não encontrada.';
        break;
}