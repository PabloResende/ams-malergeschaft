<?php
// app/controllers/EmployeeController.php - VERSÃO CORRIGIDA COMPLETA

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/Employees.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/WorkLogModel.php';
require_once __DIR__ . '/../models/TimeEntryModel.php';

class EmployeeController
{
    private Employee $employeeModel;
    private UserModel $userModel;
    private WorkLogModel $workLogModel;
    private TimeEntryModel $timeEntryModel;
    private array $langText;
    private string $baseUrl;

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        global $pdo;
        $this->employeeModel = new Employee();
        $this->userModel = new UserModel();
        $this->workLogModel = new WorkLogModel();
        $this->timeEntryModel = new TimeEntryModel();
        $this->baseUrl = BASE_URL;

        // Linguagem
        $lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'pt';
        $_SESSION['lang'] = $lang;
        $lf = __DIR__ . "/../lang/$lang.php";
        $this->langText = file_exists($lf) 
                        ? require $lf 
                        : require __DIR__ . '/../lang/pt.php';
    }

    /** Lista funcionários com dados completos */
    public function list()
    {
        // Busca funcionários com dados de usuário
        global $pdo;
        
        try {
            $stmt = $pdo->query("
                SELECT 
                    e.*,
                    u.email,
                    u.role
                FROM employees e
                LEFT JOIN users u ON e.user_id = u.id
                ORDER BY e.name, e.last_name
            ");
            
            $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Garante que todos os campos necessários existam
            $employees = array_map(function($emp) {
                return [
                    'id' => $emp['id'] ?? 0,
                    'name' => $emp['name'] ?? '',
                    'last_name' => $emp['last_name'] ?? '',
                    'email' => $emp['email'] ?? '',
                    'position' => $emp['function'] ?? '', // Nota: tabela usa 'function' não 'position'
                    'active' => isset($emp['active']) ? (bool)$emp['active'] : true,
                    'user_id' => $emp['user_id'] ?? null,
                    'role' => $emp['role'] ?? 'employee'
                ];
            }, $employees);
            
        } catch (Exception $e) {
            error_log("Erro ao buscar funcionários: " . $e->getMessage());
            $employees = [];
        }
        
        // ===== CORREÇÃO: CARREGAR AS ROLES =====
        require_once __DIR__ . '/../models/Role.php';
        $roles = Role::all(); // Carrega todas as roles do banco
        
        // Passa as variáveis para a view
        require __DIR__ . '/../views/employees/index.php';
    }

    /** Perfil do funcionário */
    public function profile()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        
        // CORREÇÃO: Verificar se é funcionário ou finance
        if (!isEmployee() && !isFinance()) {
            error_log("Profile access denied - User role: " . ($_SESSION['user']['role'] ?? 'not set'));
            redirect('/dashboard');
            return;
        }

        // Busca dados do funcionário logado
        $email = $_SESSION['user']['email'] ?? '';
        
        if (empty($email)) {
            error_log("Profile error - No email in session");
            redirect('/employees/dashboard');
            return;
        }
        
        try {
            global $pdo;
            
            $stmt = $pdo->prepare("
                SELECT e.*, u.email, u.role
                FROM employees e
                JOIN users u ON e.user_id = u.id
                WHERE u.email = ?
            ");
            $stmt->execute([$email]);
            $emp = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$emp) {
                error_log("Profile error - Employee not found for email: $email");
                // Se não encontrou dados, redirecionar para dashboard de funcionário
                redirect('/employees/dashboard');
                return;
            }
            
            error_log("Profile loaded successfully for employee ID: " . $emp['id']);
            
        } catch (Exception $e) {
            error_log("Erro ao carregar perfil: " . $e->getMessage());
            redirect('/employees/dashboard');
            return;
        }
        
        // USAR O ARQUIVO PROFILE_EMPLOYEE.PHP
        require __DIR__ . '/../views/employees/profile_employee.php';
    }

    // MÉTODO dashboard_employee() TAMBÉM CORRIGIDO
    public function dashboard_employee()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        
        // CORREÇÃO: Verificar se é funcionário OU financeiro
        if (!isEmployee() && !isFinance()) {
            error_log("Dashboard access denied - User role: " . ($_SESSION['user']['role'] ?? 'not set'));
            redirect('/dashboard');
            exit;
        }

        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $empModel = new Employee();
        $emp = $empModel->findByUserId($userId);
        $empId = $emp['id'] ?? 0;
        
        error_log("Dashboard employee - UserID: $userId, EmpID: $empId");

        // CORREÇÃO: Buscar projetos com múltiplas tentativas
        global $pdo;
        $projects = [];
        
        try {
            // Primeira tentativa: project_resources (nova estrutura)
            $stmt = $pdo->prepare("
                SELECT DISTINCT 
                    p.*,
                    c.name as client_name
                FROM projects p
                LEFT JOIN client c ON p.client_id = c.id
                LEFT JOIN project_resources pr ON p.id = pr.project_id 
                WHERE pr.resource_id = ? 
                    AND pr.resource_type = 'employee'
                    AND p.status IN ('in_progress', 'pending')
                ORDER BY p.created_at DESC
            ");
            $stmt->execute([$empId]);
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Se não encontrou projetos, tentar segunda abordagem
            if (empty($projects)) {
                // Segunda tentativa: campo JSON employees
                $stmt = $pdo->prepare("
                    SELECT DISTINCT 
                        p.*,
                        c.name as client_name
                    FROM projects p
                    LEFT JOIN client c ON p.client_id = c.id
                    WHERE JSON_CONTAINS(p.employees, ?, '$')
                        AND p.status IN ('in_progress', 'pending')
                    ORDER BY p.created_at DESC
                ");
                $stmt->execute([json_encode(['id' => $empId])]);
                $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // Se ainda não encontrou, tentar terceira abordagem (busca por ID simples)
            if (empty($projects)) {
                // Terceira tentativa: busca simples por ID no JSON
                $stmt = $pdo->prepare("
                    SELECT DISTINCT 
                        p.*,
                        c.name as client_name
                    FROM projects p
                    LEFT JOIN client c ON p.client_id = c.id
                    WHERE p.employees LIKE ?
                        AND p.status IN ('in_progress', 'pending')
                    ORDER BY p.created_at DESC
                ");
                $stmt->execute(['%"id":' . $empId . '%']);
                $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            error_log("Dashboard Employee - Projetos encontrados: " . count($projects));
            
        } catch (Exception $e) {
            error_log("Erro ao buscar projetos do funcionário: " . $e->getMessage());
            $projects = [];
        }

        // Garantir que $projects é sempre um array
        if (!is_array($projects)) {
            $projects = [];
        }

        // Incluir a view com a variável $projects disponível
        require __DIR__ . '/../views/employees/dashboard_employee.php';
    }

    /** API: Obter detalhes do funcionário */
    public function getEmployeeDetails()
    {
        header('Content-Type: application/json; charset=UTF-8');
        
        $employeeId = (int)($_GET['id'] ?? 0);
        if (!$employeeId) {
            echo json_encode(['success' => false, 'message' => 'ID do funcionário é obrigatório']);
            exit;
        }

        try {
            global $pdo;
            $stmt = $pdo->prepare("
                SELECT 
                    e.*,
                    u.email,
                    u.role,
                    r.name as role_name
                FROM employees e
                LEFT JOIN users u ON e.user_id = u.id
                LEFT JOIN roles r ON e.role_id = r.id
                WHERE e.id = ?
            ");
            $stmt->execute([$employeeId]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$employee) {
                echo json_encode(['success' => false, 'message' => 'Funcionário não encontrado']);
                exit;
            }

            echo json_encode(['success' => true, 'employee' => $employee]);

        } catch (Exception $e) {
            error_log("EmployeeController::getEmployeeDetails error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
        }
        exit;
    }

    /** API: Obter funcionário por ID */
    public function get()
    {
        header('Content-Type: application/json; charset=UTF-8');
        
        $employeeId = (int)($_GET['id'] ?? 0);
        if (!$employeeId) {
            echo json_encode(['success' => false, 'message' => 'ID é obrigatório']);
            exit;
        }

        try {
            global $pdo;
            $stmt = $pdo->prepare("
                SELECT 
                    e.*,
                    u.email,
                    u.role,
                    r.name as role_name
                FROM employees e
                LEFT JOIN users u ON e.user_id = u.id
                LEFT JOIN roles r ON e.role_id = r.id
                WHERE e.id = ?
            ");
            $stmt->execute([$employeeId]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$employee) {
                echo json_encode(['success' => false, 'message' => 'Funcionário não encontrado']);
                exit;
            }

            echo json_encode(['success' => true, 'employee' => $employee]);

        } catch (Exception $e) {
            error_log("EmployeeController::get error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
        }
        exit;
    }

    /** API: Ranking de funcionários por horas */
    public function getRanking()
    {
        header('Content-Type: application/json; charset=UTF-8');
        
        try {
            global $pdo;
            
            // Busca horas dos funcionários
            $stmt = $pdo->query("
                SELECT 
                    e.id,
                    e.name,
                    e.last_name,
                    e.function as position,
                    COALESCE(SUM(te.total_hours), 0) as total_hours
                FROM employees e
                LEFT JOIN time_entries te ON e.id = te.employee_id
                WHERE e.active = 1
                GROUP BY e.id, e.name, e.last_name, e.function
                ORDER BY total_hours DESC
            ");
            
            $ranking = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Formata os dados
            $ranking = array_map(function($emp) {
                return [
                    'id' => (int)$emp['id'],
                    'name' => trim($emp['name'] . ' ' . $emp['last_name']),
                    'position' => $emp['position'] ?: 'Não definido',
                    'total_hours' => number_format((float)$emp['total_hours'], 2, '.', '')
                ];
            }, $ranking);

            echo json_encode($ranking);

        } catch (Exception $e) {
            error_log("EmployeeController::getRanking error: " . $e->getMessage());
            echo json_encode([]);
        }
        exit;
    }

    /** API: Resumo de horas dos funcionários */
    public function hours_summary()
    {
        header('Content-Type: application/json; charset=UTF-8');
        
        try {
            global $pdo;
            
            // Estatísticas gerais
            $totalHoursStmt = $pdo->query("SELECT COALESCE(SUM(total_hours), 0) FROM time_entries");
            $totalHours = (float)$totalHoursStmt->fetchColumn();

            $activeEmployeesStmt = $pdo->query("SELECT COUNT(*) FROM employees WHERE active = 1");
            $activeEmployees = (int)$activeEmployeesStmt->fetchColumn();

            $averageHours = $activeEmployees > 0 ? $totalHours / $activeEmployees : 0;

            // Horas do mês atual
            $monthHoursStmt = $pdo->query("
                SELECT COALESCE(SUM(total_hours), 0) 
                FROM time_entries 
                WHERE YEAR(date) = YEAR(CURDATE()) 
                AND MONTH(date) = MONTH(CURDATE())
            ");
            $monthHours = (float)$monthHoursStmt->fetchColumn();

            // Horas do mês anterior
            $lastMonthHoursStmt = $pdo->query("
                SELECT COALESCE(SUM(total_hours), 0) 
                FROM time_entries 
                WHERE date >= DATE_SUB(DATE_SUB(CURDATE(), INTERVAL DAY(CURDATE())-1 DAY), INTERVAL 1 MONTH)
                AND date < DATE_SUB(CURDATE(), INTERVAL DAY(CURDATE())-1 DAY)
            ");
            $lastMonthHours = (float)$lastMonthHoursStmt->fetchColumn();

            $monthChange = $lastMonthHours > 0 ? (($monthHours - $lastMonthHours) / $lastMonthHours) * 100 : 0;

            echo json_encode([
                'total_hours' => number_format($totalHours, 2, '.', ''),
                'active_employees' => $activeEmployees,
                'average_hours' => number_format($averageHours, 2, '.', ''),
                'month_hours' => number_format($monthHours, 2, '.', ''),
                'month_change' => number_format($monthChange, 1, '.', '')
            ]);

        } catch (Exception $e) {
            error_log("EmployeeController::hours_summary error: " . $e->getMessage());
            echo json_encode([
                'total_hours' => '0.00',
                'active_employees' => 0,
                'average_hours' => '0.00',
                'month_hours' => '0.00',
                'month_change' => '0.0'
            ]);
        }
        exit;
    }

    /** API: Resumo de horas individual do funcionário */
    public function getEmployeeHoursSummary($employeeId = null)
    {
        header('Content-Type: application/json; charset=UTF-8');
        
        $employeeId = $employeeId ?? (int)($_GET['employee_id'] ?? 0);
        
        if (!$employeeId) {
            echo json_encode(['total' => '0.00']);
            exit;
        }
        
        try {
            global $pdo;
            
            // Total de horas do sistema novo (entrada/saída)
            $stmt = $pdo->prepare("
                SELECT COALESCE(SUM(total_hours), 0) as new_system_total
                FROM time_entries 
                WHERE employee_id = ?
            ");
            $stmt->execute([$employeeId]);
            $newSystemTotal = (float)$stmt->fetchColumn();
            
            // Total de horas do sistema antigo (horas diretas)
            $stmt = $pdo->prepare("
                SELECT COALESCE(SUM(hours), 0) as old_system_total
                FROM work_logs 
                WHERE employee_id = ?
            ");
            $stmt->execute([$employeeId]);
            $oldSystemTotal = (float)$stmt->fetchColumn();
            
            $grandTotal = $newSystemTotal + $oldSystemTotal;
            
            echo json_encode([
                'total' => number_format($grandTotal, 2, '.', ''),
                'new_system' => number_format($newSystemTotal, 2, '.', ''),
                'old_system' => number_format($oldSystemTotal, 2, '.', ''),
                'employee_id' => $employeeId
            ]);
            
        } catch (Exception $e) {
            error_log("EmployeeController::getEmployeeHoursSummary error: " . $e->getMessage());
            echo json_encode([
                'total' => '0.00',
                'error' => 'Erro interno'
            ]);
        }
        exit;
    }

    /** API: Buscar horas de um funcionário específico */
   public function getEmployeeHours($employeeId = null)
    {
        header('Content-Type: application/json; charset=UTF-8');
        
        $employeeId = $employeeId ?? (int)($_GET['id'] ?? 0);
        if (!$employeeId) {
            echo json_encode([
                'entries' => [],
                'total_hours' => '0.00',
                'error' => 'ID do funcionário não fornecido'
            ]);
            exit;
        }

        try {
            global $pdo;
            
            // Busca registros de ponto do funcionário
            $stmt = $pdo->prepare("
                SELECT 
                    te.date,
                    te.time_records,
                    te.total_hours,
                    p.name as project_name
                FROM time_entries te
                LEFT JOIN projects p ON te.project_id = p.id
                WHERE te.employee_id = ?
                ORDER BY te.date DESC
            ");
            $stmt->execute([$employeeId]);
            $timeEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("getEmployeeHours - Found " . count($timeEntries) . " entries for employee $employeeId");

            $allEntries = [];
            $totalHours = 0;

            foreach ($timeEntries as $entry) {
                // Decodifica os registros JSON
                $records = json_decode($entry['time_records'], true);
                $entries = $records['entries'] ?? [];
                
                if (empty($entries)) continue;
                
                // Agrupa entradas e saídas
                $entradas = [];
                $saidas = [];
                
                foreach ($entries as $record) {
                    if ($record['type'] === 'entry') {
                        $entradas[] = $record['time'];
                    } elseif ($record['type'] === 'exit') {
                        $saidas[] = $record['time'];
                    }
                }
                
                // Cria o formato de exibição: "entrada X saida X - entrada Y saida Y"
                $displayPairs = [];
                $maxPairs = max(count($entradas), count($saidas));
                
                for ($i = 0; $i < $maxPairs; $i++) {
                    $entTime = isset($entradas[$i]) ? $entradas[$i] : '?';
                    $saiTime = isset($saidas[$i]) ? $saidas[$i] : '?';
                    $displayPairs[] = "entrada {$entTime} saída {$saiTime}";
                }
                
                $formatted_display = implode(' - ', $displayPairs);
                
                $allEntries[] = [
                    'date' => $entry['date'],
                    'project_name' => $entry['project_name'] ?? 'Projeto não definido',
                    'total_hours' => (float)($entry['total_hours'] ?? 0),
                    'formatted_display' => $formatted_display
                ];
                
                $totalHours += (float)($entry['total_hours'] ?? 0);
            }

            error_log("getEmployeeHours - Total hours: $totalHours");

            echo json_encode([
                'entries' => $allEntries,
                'total_hours' => number_format($totalHours, 2, '.', '')
            ]);

        } catch (Exception $e) {
            error_log("EmployeeController::getEmployeeHours error: " . $e->getMessage());
            echo json_encode([
                'entries' => [],
                'total_hours' => '0.00',
                'error' => 'Erro interno do servidor: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /** Formatar display de entrada de tempo */
    private function formatTimeEntryDisplay(array $entries, string $date): string
    {
        if (empty($entries)) {
            return 'Sem registros';
        }

        $display = [];
        foreach ($entries as $entry) {
            $time = $entry['time'] ?? '';
            $type = $entry['type'] ?? '';
            $typeLabel = $type === 'entry' ? 'Entrada' : 'Saída';
            if ($time && $type) {
                $display[] = "{$time} ({$typeLabel})";
            }
        }

        return empty($display) ? 'Sem registros válidos' : implode(', ', $display);
    }

    /** API: Criar novo funcionário */
    public function createEmployee()
    {
        header('Content-Type: application/json; charset=UTF-8');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            exit;
        }

        try {
            global $pdo;
            $pdo->beginTransaction();

            // Dados básicos do funcionário
            $employeeData = [
                'name' => trim($_POST['name'] ?? ''),
                'last_name' => trim($_POST['last_name'] ?? ''),
                'function' => trim($_POST['function'] ?? ''),
                'address' => trim($_POST['address'] ?? ''),
                'zip_code' => trim($_POST['zip_code'] ?? ''),
                'city' => trim($_POST['city'] ?? ''),
                'sex' => $_POST['sex'] ?? 'male',
                'birth_date' => $_POST['birth_date'] ?? null,
                'nationality' => trim($_POST['nationality'] ?? ''),
                'permission_type' => trim($_POST['permission_type'] ?? ''),
                'ahv_number' => trim($_POST['ahv_number'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'religion' => trim($_POST['religion'] ?? ''),
                'marital_status' => $_POST['marital_status'] ?? 'single',  // FIXED: Changed : to =>
                'start_date' => $_POST['start_date'] ?? date('Y-m-d'),
                'about' => trim($_POST['about'] ?? ''),
                'active' => 1
            ];

            // Validações básicas
            if (empty($employeeData['name']) || empty($employeeData['last_name']) || empty($employeeData['birth_date'])) {
                throw new Exception('Nome, sobrenome e data de nascimento são obrigatórios');
            }

            // Cria usuário se email foi fornecido
            $userId = null;
            $email = trim($_POST['email'] ?? '');
            if (!empty($email)) {
                // Verifica se email já existe
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    throw new Exception('Este email já está em uso');
                }

                $password = $_POST['password'] ?? '';
                if (empty($password)) {
                    $password = 'temp123'; // Senha temporária
                }

                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("
                    INSERT INTO users (email, password, role, created_at) 
                    VALUES (?, ?, ?, NOW())
                ");
                $stmt->execute([$email, $hashedPassword, 'employee']);
                $userId = $pdo->lastInsertId();
            }

            // Insere funcionário
            $employeeData['user_id'] = $userId;
            
            $columns = implode(', ', array_keys($employeeData));
            $placeholders = ':' . implode(', :', array_keys($employeeData));
            
            $stmt = $pdo->prepare("
                INSERT INTO employees ({$columns}) 
                VALUES ({$placeholders})
            ");
            $stmt->execute($employeeData);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Funcionário criado com sucesso']);

        } catch (Exception $e) {
            $pdo->rollback();
            error_log("EmployeeController::createEmployee error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function updateEmployee()
    {
        header('Content-Type: application/json; charset=UTF-8');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            exit;
        }
        
        try {
            $employeeId = (int)($_POST['id'] ?? 0);
            
            if (!$employeeId) {
                echo json_encode(['success' => false, 'message' => 'ID do funcionário inválido']);
                exit;
            }
            
            // CORREÇÃO: Usar método que existe
            global $pdo;
            $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
            $stmt->execute([$employeeId]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$employee) {
                echo json_encode(['success' => false, 'message' => 'Funcionário não encontrado']);
                exit;
            }
            
            // Preparar dados para atualização
            $updateData = [
                'name' => trim($_POST['name'] ?? $employee['name']),
                'last_name' => trim($_POST['last_name'] ?? $employee['last_name']),
                'function' => trim($_POST['function'] ?? $employee['function']),
                'address' => trim($_POST['address'] ?? $employee['address']),
                'zip_code' => trim($_POST['zip_code'] ?? $employee['zip_code']),
                'city' => trim($_POST['city'] ?? $employee['city']),
                'sex' => $_POST['sex'] ?? $employee['sex'],
                'birth_date' => $_POST['birth_date'] ?? $employee['birth_date'],
                'nationality' => trim($_POST['nationality'] ?? $employee['nationality']),
                'permission_type' => trim($_POST['permission_type'] ?? $employee['permission_type']),
                'ahv_number' => trim($_POST['ahv_number'] ?? $employee['ahv_number']),
                'phone' => trim($_POST['phone'] ?? $employee['phone']),
                'religion' => trim($_POST['religion'] ?? $employee['religion']),
                'marital_status' => $_POST['marital_status'] ?? $employee['marital_status'],
                'start_date' => $_POST['start_date'] ?? $employee['start_date'],
                'about' => trim($_POST['about'] ?? $employee['about'])
            ];
            
            // Atualizar funcionário
            $fields = [];
            $values = [];
            foreach ($updateData as $field => $value) {
                $fields[] = "$field = ?";
                $values[] = $value;
            }
            $values[] = $employeeId;
            
            $stmt = $pdo->prepare("UPDATE employees SET " . implode(', ', $fields) . " WHERE id = ?");
            $success = $stmt->execute($values);
            
            if (!$success) {
                throw new Exception('Erro ao atualizar funcionário');
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Funcionário atualizado com sucesso',
                'employee' => $updateData
            ]);
            
        } catch (Exception $e) {
            error_log("Error updating employee: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
        }
        exit;
    }

    /** API: Excluir funcionário */
    public function deleteEmployee()
    {
        header('Content-Type: application/json; charset=UTF-8');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            exit;
        }

        $employeeId = (int)($_POST['employee_id'] ?? $_POST['id'] ?? 0);
        if (!$employeeId) {
            echo json_encode(['success' => false, 'message' => 'ID do funcionário é obrigatório']);
            exit;
        }

        try {
            global $pdo;
            $pdo->beginTransaction();

            // Busca user_id antes de excluir
            $stmt = $pdo->prepare("SELECT user_id FROM employees WHERE id = ?");
            $stmt->execute([$employeeId]);
            $emp = $stmt->fetch(PDO::FETCH_ASSOC);

            // Exclui funcionário
            $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
            $stmt->execute([$employeeId]);

            // Exclui usuário se existir
            if ($emp && $emp['user_id']) {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$emp['user_id']]);
            }

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Funcionário excluído com sucesso']);

        } catch (Exception $e) {
            $pdo->rollback();
            error_log("EmployeeController::deleteEmployee error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /** Cria novo funcionário */
    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            exit;
        }

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'function' => trim($_POST['position'] ?? ''), // Mapeia position para function
            'active' => isset($_POST['active']) ? 1 : 0,
        ];

        $password = trim($_POST['password'] ?? '');
        
        if (!$data['name'] || !$data['last_name'] || !$data['email'] || !$password) {
            echo json_encode(['success' => false, 'message' => 'Campos obrigatórios não preenchidos']);
            exit;
        }

        // Verifica se e-mail já existe
        if ($this->userModel->findByEmail($data['email'])) {
            echo json_encode(['success' => false, 'message' => 'E-mail já está em uso']);
            exit;
        }

        try {
            global $pdo;
            $pdo->beginTransaction();

            // Cria usuário
            $userCreated = $this->userModel->create(
                $data['name'] . ' ' . $data['last_name'], // nome completo
                $data['email'],                           // email
                password_hash($password, PASSWORD_DEFAULT), // senha hash
                'employee'                                // role
            );
            
            if (!$userCreated) {
                throw new Exception('Erro ao criar usuário');
            }

            // Pega o ID do usuário criado
            $userId = $pdo->lastInsertId();

            // Cria funcionário
            $data['user_id'] = $userId;
            // Campos obrigatórios da tabela employees
            $data['sex'] = 'male'; // Padrão
            $data['birth_date'] = date('Y-m-d'); // Padrão hoje
            $data['start_date'] = date('Y-m-d'); // Padrão hoje
            $data['role_id'] = 1; // Padrão employee
            
            // Chama o método create do Employee (que espera array + files)
            Employee::create($data, $_FILES ?? []);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Funcionário criado com sucesso']);

        } catch (Exception $e) {
            $pdo->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /** Atualiza funcionário */
    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            exit;
        }

        // CORRIGIDO: Aceita tanto 'id' quanto 'employee_id'
        $employeeId = (int)($_POST['employee_id'] ?? $_POST['id'] ?? 0);
        if (!$employeeId) {
            echo json_encode(['success' => false, 'message' => 'ID do funcionário não informado']);
            exit;
        }

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'function' => trim($_POST['function'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'zip_code' => trim($_POST['zip_code'] ?? ''),
            'city' => trim($_POST['city'] ?? ''),
            'sex' => trim($_POST['sex'] ?? 'male'),
            'birth_date' => trim($_POST['birth_date'] ?? ''),
            'nationality' => trim($_POST['nationality'] ?? ''),
            'permission_type' => trim($_POST['permission_type'] ?? ''),
            'ahv_number' => trim($_POST['ahv_number'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'religion' => trim($_POST['religion'] ?? ''),
            'marital_status' => trim($_POST['marital_status'] ?? 'single'),
            'start_date' => trim($_POST['start_date'] ?? ''),
            'about' => trim($_POST['about'] ?? ''),
            'active' => isset($_POST['active']) ? 1 : 0,
            'role_id' => (int)($_POST['role_id'] ?? 1)
        ];

        // Dados do usuário
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (!$data['name'] || !$data['last_name'] || !$email) {
            echo json_encode(['success' => false, 'message' => 'Campos obrigatórios não preenchidos']);
            exit;
        }

        try {
            global $pdo;
            $pdo->beginTransaction();

            // Busca funcionário atual
            $stmt = $pdo->prepare("SELECT user_id FROM employees WHERE id = ?");
            $stmt->execute([$employeeId]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$employee) {
                throw new Exception('Funcionário não encontrado');
            }

            // Atualiza funcionário usando método estático
            Employee::update($employeeId, $data, $_FILES ?? []);

            // Atualiza usuário se existir
            if ($employee['user_id']) {
                $userSuccess = false;
                
                if (!empty($password)) {
                    // Com senha
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET name = ?, email = ?, password = ? 
                        WHERE id = ?
                    ");
                    $userSuccess = $stmt->execute([
                        $data['name'] . ' ' . $data['last_name'],
                        $email,
                        password_hash($password, PASSWORD_DEFAULT),
                        $employee['user_id']
                    ]);
                } else {
                    // Sem senha
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET name = ?, email = ? 
                        WHERE id = ?
                    ");
                    $userSuccess = $stmt->execute([
                        $data['name'] . ' ' . $data['last_name'],
                        $email,
                        $employee['user_id']
                    ]);
                }

                if (!$userSuccess) {
                    throw new Exception('Erro ao atualizar dados do usuário');
                }
            }

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Funcionário atualizado com sucesso']);

        } catch (Exception $e) {
            $pdo->rollback();
            error_log("EmployeeController::update error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /** Deleta funcionário */
    public function delete()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            exit;
        }

        $employeeId = (int)($_POST['employee_id'] ?? $_GET['id'] ?? 0);
        if (!$employeeId) {
            echo json_encode(['success' => false, 'message' => 'ID do funcionário não informado']);
            exit;
        }

        try {
            global $pdo;
            $pdo->beginTransaction();

            // Busca user_id antes de deletar
            $stmt = $pdo->prepare("SELECT user_id FROM employees WHERE id = ?");
            $stmt->execute([$employeeId]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$employee) {
                throw new Exception('Funcionário não encontrado');
            }

            // Deleta funcionário
            $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
            if (!$stmt->execute([$employeeId])) {
                throw new Exception('Erro ao deletar funcionário');
            }

            // Deleta usuário associado se existir
            if ($employee['user_id']) {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$employee['user_id']]);
            }

            $pdo->commit();
            
            // Redireciona para lista se foi via GET
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                header('Location: ' . BASE_URL . '/employees');
                exit;
            }
            
            echo json_encode(['success' => true, 'message' => 'Funcionário deletado com sucesso']);

        } catch (Exception $e) {
            $pdo->rollback();
            error_log("EmployeeController::delete error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /** Calcula resumo individual do funcionário */
    private function calculateEmployeeSummary(int $employeeId): array
    {
        try {
            global $pdo;
            
            $today = date('Y-m-d');
            $startOfWeek = date('Y-m-d', strtotime('monday this week'));
            $endOfWeek = date('Y-m-d', strtotime('sunday this week'));

            // Total geral (sistema antigo + novo)
            $totalHours = 0;
            
            // Sistema novo
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_hours), 0) FROM time_entries WHERE employee_id = ?");
            $stmt->execute([$employeeId]);
            $totalHours += (float)$stmt->fetchColumn();

            return [
                'total_hours' => $totalHours,
                'today_hours' => 0, // Implementar se necessário
                'week_hours' => 0   // Implementar se necessário
            ];

        } catch (Exception $e) {
            error_log("calculateEmployeeSummary error: " . $e->getMessage());
            return [
                'total_hours' => 0,
                'today_hours' => 0,
                'week_hours' => 0
            ];
        }
    }
}