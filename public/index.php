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

   // ===== PROJETOS =====
    case $route === '/projects':
        $projectController->index();
        break;

    // PÁGINA HTML para visualizar projeto específico (para navegação direta)
    case $route === '/projects/show':
        $projectController->show();
        break;

    // API JSON para obter dados do projeto (usado por AJAX) - NOVA ROTA
    case $route === '/projects/data':
        $projectController->getProjectData();
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

    // ===== API PROJETOS =====
    case $route === '/api/projects/list':
        $workLogController->getProjectsList();
        break;
    case $route === '/api/projects/active':
        $projectController->getActiveProjects();
        break;
    case preg_match('/^\/api\/projects\/by-employee\/(\d+)$/', $route, $matches):
        $projectController->getProjectsByEmployee((int) $matches[1]);
        break;
    case preg_match('/^\/api\/projects\/(\d+)$/', $route, $matches):
        $projectController->getProjectDetails((int) $matches[1]);
        break;

    // ===== API WORK LOGS =====
    case $route === '/api/work_logs/admin_time_entry':
        $workLogController->adminCreateTimeEntry();
        break;
    case $route === '/api/employees/hours-summary':
        $workLogController->getEmployeeHours();
        break;
    case preg_match('/^\/api\/employees\/(\d+)\/hours$/', $route, $matches):
        $employeeController->getEmployeeHours((int) $matches[1]);
        break;
    case preg_match('/^\/api\/work_logs\/time_entries\/(\d+)$/', $route, $matches):
        $workLogController->getTimeEntriesByProject((int) $matches[1]);
        break;
    case preg_match('/^\/api\/employees\/monthly-hours\/(\d+)$/', $route, $matches):
        // Nova rota para horas mensais por funcionário
        header('Content-Type: application/json; charset=UTF-8');
        try {
            require_once __DIR__ . '/../app/models/TimeEntryModel.php';
            $timeEntryModel = new TimeEntryModel();
            $hours = $timeEntryModel->getMonthlyHoursByEmployee((int) $matches[1]);
            echo json_encode(['hours' => number_format($hours, 2)]);
        } catch (Exception $e) {
            echo json_encode(['hours' => '0.00']);
        }
        exit;
        break;
    case preg_match('/^\/api\/employees\/hours\/(\d+)$/', $route, $matches):
        // Nova rota para horas detalhadas por funcionário
        header('Content-Type: application/json; charset=UTF-8');
        try {
            require_once __DIR__ . '/../app/models/TimeEntryModel.php';
            $timeEntryModel = new TimeEntryModel();
            $employeeId = (int) $matches[1];
            $filter = $_GET['filter'] ?? 'all';
            
            global $pdo;
            
            // Constrói a query baseada no filtro
            $whereClause = "WHERE employee_id = ?";
            $params = [$employeeId];
            
            switch ($filter) {
                case 'today':
                    $whereClause .= " AND date = CURDATE()";
                    break;
                case 'week':
                    $whereClause .= " AND WEEK(date) = WEEK(CURDATE()) AND YEAR(date) = YEAR(CURDATE())";
                    break;
                case 'month':
                    $whereClause .= " AND MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())";
                    break;
            }
            
            $stmt = $pdo->prepare("
                SELECT 
                    te.id, te.date, te.time_records, te.total_hours,
                    p.name as project_name
                FROM time_entries te
                LEFT JOIN projects p ON te.project_id = p.id
                $whereClause
                ORDER BY te.date DESC, te.id DESC
            ");
            $stmt->execute($params);
            $timeEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $entries = [];
            $totalHours = 0;
            
            foreach ($timeEntries as $timeEntry) {
                $entryRecords = json_decode($timeEntry['time_records'], true);
                $entryRecords = $entryRecords['entries'] ?? [];
                
                foreach ($entryRecords as $record) {
                    $entries[] = [
                        'id' => $timeEntry['id'] . '_' . $record['time'],
                        'date' => $timeEntry['date'],
                        'time' => $record['time'],
                        'entry_type' => $record['type'],
                        'project_name' => $timeEntry['project_name'],
                        'calculated_hours' => round(floatval($timeEntry['total_hours']) / count($entryRecords), 2)
                    ];
                }
                
                $totalHours += floatval($timeEntry['total_hours']);
            }
            
            echo json_encode([
                'entries' => $entries,
                'total_hours' => round($totalHours, 2)
            ]);
            
        } catch (Exception $e) {
            error_log("API employees hours error: " . $e->getMessage());
            echo json_encode(['entries' => [], 'total_hours' => 0]);
        }
        exit;
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

    // ===== DEBUG (REMOVER EM PRODUÇÃO) =====
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

    // ===== ROTA PADRÃO =====
    default:
        http_response_code(404);
        echo '<h1>404 - Página não encontrada</h1>';
        echo '<p>Rota: ' . htmlspecialchars($route) . '</p>';
        echo '<p><a href="' . BASE_URL . '">Voltar ao início</a></p>';
        break;
}