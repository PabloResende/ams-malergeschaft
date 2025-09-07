<?php
// system/public/index.php — Front controller CORRIGIDO COMPLETO

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

// DEBUG TEMPORÁRIO (remover após correção)
error_log("DEBUG - Route: $route");
error_log("DEBUG - User role: " . ($_SESSION['user']['role'] ?? 'not set'));
error_log("DEBUG - Is employee: " . (isEmployee() ? 'yes' : 'no'));

// Rotas públicas
$publicRoutes = ['/', '/login', '/auth', '/register', '/store'];

// CORREÇÃO CRÍTICA: Roteamento baseado em roles
if (!isLoggedIn()) {
    // Se não está logado, só pode acessar rotas públicas
    if (!in_array($route, $publicRoutes, true)) {
        redirect('/login');
    }
} else {
    // Se está logado, aplicar lógica de redirecionamento por role
    $userRole = $_SESSION['user']['role'] ?? '';
    
    // Se funcionário tenta acessar dashboard geral, redirecionar para dashboard de funcionário
    if ($route === '/dashboard' && $userRole === 'employee') {
        redirect('/employees/dashboard');
        exit;
    }
    
    // Se admin/finance tenta acessar área de funcionário sem permissão
    if (in_array($route, ['/employees/dashboard', '/employees/profile'], true) && !isEmployee() && !isFinance()) {
        redirect('/dashboard');
        exit;
    }
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

// 11) Dispatcher de rotas - SWITCH COMPLETO CORRIGIDO
switch (true) {
    // ===== USUÁRIO =====
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
    case $route === '/logout':
        $userController->logout();
        break;

    // ===== FUNCIONÁRIOS - DASHBOARDS =====
    case $route === '/employees/dashboard':
        $employeeController->dashboard_employee();
        break;
    case $route === '/employees/profile':
        $employeeController->profile();
        break;

    // ===== DEBUG TEMPORÁRIO =====
    case $route === '/debug':
        ?>
        <!DOCTYPE html>
        <html><head><title>Debug</title></head><body>
        <h1>Debug Info</h1>
        <h2>Session:</h2><pre><?= print_r($_SESSION, true) ?></pre>
        <h2>Route:</h2><p><?= $route ?></p>
        <h2>Functions:</h2>
        <ul>
            <li>isLoggedIn(): <?= isLoggedIn() ? 'true' : 'false' ?></li>
            <li>isEmployee(): <?= isEmployee() ? 'true' : 'false' ?></li>
            <li>isFinance(): <?= isFinance() ? 'true' : 'false' ?></li>
            <li>isAdmin(): <?= isAdmin() ? 'true' : 'false' ?></li>
        </ul>
        <h2>Links:</h2>
        <ul>
            <li><a href="<?= BASE_URL ?>/employees/dashboard">Dashboard Funcionário</a></li>
            <li><a href="<?= BASE_URL ?>/employees/profile">Perfil Funcionário</a></li>
            <li><a href="<?= BASE_URL ?>/dashboard">Dashboard Admin</a></li>
        </ul>
        </body></html>
        <?php
        break;

    // ===== API PROJETOS =====
    case $route === '/api/projects/list':
        $workLogController->getProjectsList();
        break;
    case $route === '/api/projects/active':
        $projectController->getActiveProjects();
        break;
    case preg_match('/^\/api\/projects\/(\d+)$/', $route, $matches):
        $projectController->getProjectDetails((int) $matches[1]);
        break;

    // ===== API ADMIN TIME TRACKING =====
    case $route === '/api/work_logs/admin_time_entry':
        $workLogController->adminCreateTimeEntry();
        break;
    case $route === '/api/employee-hours':
        $workLogController->getEmployeeHours();
        break;

    // ===== FUNCIONÁRIOS - CRUD =====
    case $route === '/employees':
        $employeeController->list();
        break;
    case $route === '/employees/store':
        $employeeController->store();
        break;
    case $route === '/employees/update':
        $employeeController->updateEmployee();
        break;
    case $route === '/employees/delete':
        $employeeController->delete();
        break;
    case $route === '/employees/get':
        $employeeController->get();
        break;

    // ===== API FUNCIONÁRIOS - HORAS =====
    case $route === '/api/employees/hours-summary':
        $employeeController->hours_summary();
        break;
    case preg_match('/^\/api\/employees\/hours\/(\d+)$/', $route, $matches):
        $employeeController->getEmployeeHours((int) $matches[1]);
        break;
    case $route === '/api/employees/ranking':
        $employeeController->getRanking();
        break;
    case preg_match('/^\/api\/employees\/(\d+)\/details$/', $route, $matches):
        $_GET['id'] = $matches[1];
        $employeeController->getEmployeeDetails();
        break;
    case preg_match('/^\/api\/employees\/(\d+)\/hours-summary$/', $route, $matches):
        $_GET['id'] = $matches[1];
        $employeeController->getEmployeeHoursSummary();
        break;
    case preg_match('/^\/api\/employees\/(\d+)\/hours$/', $route, $matches):
        $_GET['id'] = $matches[1];
        $employeeController->getEmployeeHours();
        break;
    case preg_match('/^\/api\/employees\/(\d+)\/update$/', $route, $matches):
        $_POST['employee_id'] = $matches[1];
        $employeeController->updateEmployee();
        break;
    case preg_match('/^\/api\/employees\/(\d+)\/delete$/', $route, $matches):
        $_POST['employee_id'] = $matches[1];
        $employeeController->deleteEmployee();
        break;
    case $route === '/api/employees/create':
        $employeeController->createEmployee();
        break;

    // ===== CLIENTES =====
    case $route === '/clients':
        $clientsController->index();
        break;
    case $route === '/clients/store':
        $clientsController->store();
        break;
    case $route === '/clients/update':
        $clientsController->update();
        break;
    case $route === '/clients/delete':
        $clientsController->delete();
        break;
    case $route === '/clients/get':
        $clientsController->get();
        break;

    // ===== INVENTÁRIO =====
    case $route === '/inventory':
        $inventoryController->index();
        break;
    case $route === '/inventory/store':
        $inventoryController->store();
        break;
    case $route === '/inventory/update':
        $inventoryController->update();
        break;
    case $route === '/inventory/delete':
        $inventoryController->delete();
        break;
    case $route === '/inventory/get':
        $inventoryController->get();
        break;

    // ===== FINANCEIRO =====
    case $route === '/finance':
        $financialController->index();
        break;
    case $route === '/finance/store':
        $financialController->store();
        break;
    case $route === '/finance/update':
        $financialController->update();
        break;
    case $route === '/finance/delete':
        $financialController->delete();
        break;

    // ===== PROJETOS =====
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
    case $route === '/projects/get':
        $projectController->get();
        break;

    // ===== WORK LOGS =====
    case $route === '/work_logs':
        $workLogController->index();
        break;
    case $route === '/work_logs/store':
        $workLogController->store();
        break;
    case $route === '/work_logs/store_time_entry':
        $workLogController->storeTimeEntry();
        break;

    // ===== CARROS =====
    case $route === '/cars':
        $carController->index();
        break;
    case $route === '/cars/store':
        $carController->store();
        break;
    case $route === '/cars/update':
        $carController->update();
        break;
    case $route === '/cars/delete':
        $carController->delete();
        break;

    // ===== CALENDÁRIO =====
    case $route === '/calendar':
        $calendarController->index();
        break;

    // ===== ANALYTICS =====
    case $route === '/analytics':
        $analyticsController->index();
        break;
    case $route === '/analytics/stats':
        $analyticsController->stats();
        break;

    // ===== API WORK LOGS =====
    case preg_match('/^\/api\/work_logs\/time_entries\/(\d+)$/', $route, $matches):
        $workLogController->getTimeEntriesByProject((int) $matches[1]);
        break;

    // ===== ROTA PADRÃO =====
    default:
        http_response_code(404);
        echo '<h1>404 - Página não encontrada</h1>';
        echo '<p>Rota: ' . htmlspecialchars($route) . '</p>';
        echo '<p><a href="' . BASE_URL . '">Voltar ao início</a></p>';
        break;
}