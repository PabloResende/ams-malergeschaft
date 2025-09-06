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

    public function dashboard_employee()
        {
            if (session_status() !== PHP_SESSION_ACTIVE) session_start();
            if (! isEmployee()) {
                header('Location: ' . BASE_URL . '/dashboard');
                exit;
            }

            $userId   = (int)($_SESSION['user']['id'] ?? 0);
            $empModel = new Employee();
            $emp      = $empModel->findByUserId($userId);
            $empId    = $emp['id'] ?? 0;

            // Buscar projetos diretamente com SQL
            global $pdo;
            $projects = [];
            try {
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
            } catch (Exception $e) {
                error_log("Erro ao buscar projetos: " . $e->getMessage());
                $projects = [];
            }

            require __DIR__ . '/../views/employees/dashboard_employee.php';
        }

    public function profile()
    {
        // Busca dados do funcionário logado
        $email = $_SESSION['user']['email'] ?? '';
        
        try {
            global $pdo;
            
            $stmt = $pdo->prepare("
                SELECT e.*, u.email, u.role
                FROM employees e
                JOIN users u ON e.user_id = u.id
                WHERE u.email = ?
            ");
            $stmt->execute([$email]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$employee) {
                redirect('/employees/dashboard');
                return;
            }
            
        } catch (Exception $e) {
            error_log("Erro ao carregar perfil: " . $e->getMessage());
            redirect('/employees/dashboard');
            return;
        }
        
        // USAR O ARQUIVO PROFILE_EMPLOYEE.PHP
        require __DIR__ . '/../views/employees/profile_employee.php';
    }

    public function hours_summary()
    {
        header('Content-Type: application/json; charset=UTF-8');
        
        if (!isEmployee()) {
            echo json_encode(['total' => '0.00', 'today' => '0.00', 'week' => '0.00']);
            exit;
        }

        try {
            global $pdo;
            $userId = $_SESSION['user']['id'];
            
            $empStmt = $pdo->prepare("SELECT id FROM employees WHERE user_id = ?");
            $empStmt->execute([$userId]);
            $emp = $empStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$emp) {
                echo json_encode(['total' => '0.00', 'today' => '0.00', 'week' => '0.00']);
                exit;
            }
            
            $employeeId = $emp['id'];
            
            $totalStmt = $pdo->prepare("SELECT COALESCE(SUM(total_hours), 0) as total FROM time_entries WHERE employee_id = ?");
            $totalStmt->execute([$employeeId]);
            $totalHours = (float)$totalStmt->fetchColumn();
            
            $today = date('Y-m-d');
            $todayStmt = $pdo->prepare("SELECT COALESCE(SUM(total_hours), 0) as today FROM time_entries WHERE employee_id = ? AND date = ?");
            $todayStmt->execute([$employeeId, $today]);
            $todayHours = (float)$todayStmt->fetchColumn();
            
            $startOfWeek = date('Y-m-d', strtotime('monday this week'));
            $endOfWeek = date('Y-m-d', strtotime('sunday this week'));
            
            $weekStmt = $pdo->prepare("SELECT COALESCE(SUM(total_hours), 0) as week FROM time_entries WHERE employee_id = ? AND date BETWEEN ? AND ?");
            $weekStmt->execute([$employeeId, $startOfWeek, $endOfWeek]);
            $weekHours = (float)$weekStmt->fetchColumn();

            echo json_encode([
                'total' => number_format($totalHours, 2, '.', ''),
                'today' => number_format($todayHours, 2, '.', ''),
                'week' => number_format($weekHours, 2, '.', ''),
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['total' => '0.00', 'today' => '0.00', 'week' => '0.00']);
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

            // ===== CORREÇÃO: Passa parâmetros individuais para create() =====
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

    /** Atualiza funcionário - VERSÃO CORRIGIDA */
    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            exit;
        }

        $employeeId = (int)($_POST['employee_id'] ?? 0);
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

            $userId = $employee['user_id'];

            // Atualiza tabela employees
            $employeeFields = [
                'name', 'last_name', 'function', 'address', 'zip_code', 'city',
                'sex', 'birth_date', 'nationality', 'permission_type', 'ahv_number',
                'phone', 'religion', 'marital_status', 'start_date', 'about', 'active', 'role_id'
            ];

            $setClause = implode(' = ?, ', $employeeFields) . ' = ?';
            $values = array_values(array_intersect_key($data, array_flip($employeeFields)));
            $values[] = $employeeId;

            $updateEmployeeSQL = "UPDATE employees SET {$setClause} WHERE id = ?";
            $stmt = $pdo->prepare($updateEmployeeSQL);
            
            if (!$stmt->execute($values)) {
                throw new Exception('Erro ao atualizar dados do funcionário');
            }

            // Atualiza tabela users se necessário
            if ($userId) {
                if ($password) {
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
                        $userId
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
                        $userId
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
            
            echo json_encode(['success' => true, 'message' => 'Funcionário excluído com sucesso']);

        } catch (Exception $e) {
            $pdo->rollback();
            error_log("EmployeeController::delete error: " . $e->getMessage());
            
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                header('Location: ' . BASE_URL . '/employees?error=' . urlencode($e->getMessage()));
                exit;
            }
            
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /** Busca dados de um funcionário para o modal de detalhes */
    public function get()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            exit;
        }

        $employeeId = (int)($_GET['id'] ?? 0);
        if (!$employeeId) {
            echo json_encode(['success' => false, 'message' => 'ID do funcionário não informado']);
            exit;
        }

        try {
            global $pdo;
            
            // Busca funcionário com dados do usuário e role
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

            // Busca transações do funcionário
            $transStmt = $pdo->prepare("
                SELECT 
                    date,
                    type,
                    amount,
                    description
                FROM financial_transactions 
                WHERE employee_id = ? 
                ORDER BY date DESC 
                LIMIT 10
            ");
            $transStmt->execute([$employeeId]);
            $transactions = $transStmt->fetchAll(PDO::FETCH_ASSOC);

            // Formata os dados para o frontend
            $data = [
                'id' => $employee['id'],
                'name' => $employee['name'] ?? '',
                'last_name' => $employee['last_name'] ?? '',
                'function' => $employee['function'] ?? '',
                'address' => $employee['address'] ?? '',
                'zip_code' => $employee['zip_code'] ?? '',
                'city' => $employee['city'] ?? '',
                'sex' => $employee['sex'] ?? 'male',
                'birth_date' => $employee['birth_date'] ?? '',
                'nationality' => $employee['nationality'] ?? '',
                'permission_type' => $employee['permission_type'] ?? '',
                'ahv_number' => $employee['ahv_number'] ?? '',
                'phone' => $employee['phone'] ?? '',
                'religion' => $employee['religion'] ?? '',
                'marital_status' => $employee['marital_status'] ?? 'single',
                'start_date' => $employee['start_date'] ?? '',
                'about' => $employee['about'] ?? '',
                'active' => (bool)($employee['active'] ?? 1),
                'user_id' => $employee['user_id'] ?? null,
                'role_id' => $employee['role_id'] ?? 1,
                'role_name' => $employee['role_name'] ?? 'employee',
                'email' => $employee['email'] ?? '',
                'login_email' => $employee['email'] ?? '',
                'transactions' => $transactions
            ];

            echo json_encode(['success' => true, 'data' => $data]);
            
        } catch (Exception $e) {
            error_log("EmployeeController::get error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro interno']);
        }
        exit;
    }

    /** API: Detalhes completos de um funcionário específico */
    public function getEmployeeDetails()
    {
        header('Content-Type: application/json; charset=UTF-8');
        
        $employeeId = (int)($_GET['id'] ?? 0);
        if (!$employeeId) {
            echo json_encode(['error' => 'ID do funcionário é obrigatório']);
            exit;
        }

        try {
            global $pdo;
            
            $stmt = $pdo->prepare("
                SELECT 
                    e.*,
                    u.email,
                    u.role,
                    u.id as user_id
                FROM employees e
                LEFT JOIN users u ON e.user_id = u.id
                WHERE e.id = ?
            ");
            $stmt->execute([$employeeId]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$employee) {
                echo json_encode(['error' => 'Funcionário não encontrado']);
                exit;
            }

            echo json_encode($employee);

        } catch (Exception $e) {
            error_log("EmployeeController::getEmployeeDetails error: " . $e->getMessage());
            echo json_encode(['error' => 'Erro interno do servidor']);
        }
        exit;
    }

    /** API: Resumo de horas de um funcionário específico */
    public function getEmployeeHoursSummary()
    {
        header('Content-Type: application/json; charset=UTF-8');
        
        $employeeId = (int)($_GET['id'] ?? 0);
        if (!$employeeId) {
            echo json_encode(['total' => '0.00', 'today' => '0.00', 'week' => '0.00']);
            exit;
        }

        try {
            $summary = $this->calculateEmployeeSummary($employeeId);
            echo json_encode($summary);

        } catch (Exception $e) {
            error_log("EmployeeController::getEmployeeHoursSummary error: " . $e->getMessage());
            echo json_encode(['total' => '0.00', 'today' => '0.00', 'week' => '0.00']);
        }
        exit;
    }

    /** API: Registros detalhados de horas de um funcionário */
    public function getEmployeeHours()
    {
        header('Content-Type: application/json; charset=UTF-8');
        
        $employeeId = (int)($_GET['id'] ?? 0);
        $filter = $_GET['filter'] ?? 'all';
        
        if (!$employeeId) {
            echo json_encode([
                'entries' => [],
                'total_hours' => '0.00',
                'error' => 'ID do funcionário é obrigatório'
            ]);
            exit;
        }

        try {
            global $pdo;
            
            // Define filtro de data
            $whereClause = $this->buildDateFilter($filter);
            
            // Busca registros do sistema NOVO (time_entries)
            $stmt = $pdo->prepare("
                SELECT 
                    te.*,
                    p.name as project_name
                FROM time_entries te
                LEFT JOIN projects p ON te.project_id = p.id
                WHERE te.employee_id = ? {$whereClause}
                ORDER BY te.date DESC
            ");
            $stmt->execute([$employeeId]);
            $timeEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Processa entradas do sistema novo por data/projeto
            $groupedEntries = [];
            foreach ($timeEntries as $entry) {
                $key = $entry['date'] . '_' . ($entry['project_id'] ?? 0);
                if (!isset($groupedEntries[$key])) {
                    $groupedEntries[$key] = [
                        'date' => $entry['date'],
                        'project_name' => $entry['project_name'] ?? 'Projeto não definido',
                        'entries' => [],
                        'total_hours' => 0
                    ];
                }
                
                $groupedEntries[$key]['entries'][] = [
                    'time' => $entry['time'],
                    'type' => $entry['type']
                ];
                
                $groupedEntries[$key]['total_hours'] += (float)($entry['total_hours'] ?? 0);
            }

            // Formata entradas do sistema novo
            foreach ($groupedEntries as &$group) {
                $group['formatted_display'] = $this->formatTimeEntryDisplay($group['entries'], $group['date']);
            }

            // Converte para array e ordena por data
            $allEntries = array_values($groupedEntries);
            usort($allEntries, function($a, $b) {
                return strcmp($b['date'], $a['date']);
            });

            // Calcula total geral
            $totalHours = 0;
            foreach ($allEntries as $entry) {
                $totalHours += $entry['total_hours'];
            }

            echo json_encode([
                'entries' => $allEntries,
                'total_hours' => number_format($totalHours, 2, '.', ''),
                'employee_name' => 'Funcionário'
            ]);

        } catch (Exception $e) {
            error_log("EmployeeController::getEmployeeHours error: " . $e->getMessage());
            echo json_encode([
                'entries' => [],
                'total_hours' => '0.00',
                'error' => 'Erro interno do servidor'
            ]);
        }
        exit;
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
                'marital_status' => $_POST['marital_status'] ?? 'single',
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
            
            // Buscar funcionário existente
            $employee = $this->employeeModel->getById($employeeId);
            if (!$employee) {
                echo json_encode(['success' => false, 'message' => 'Funcionário não encontrado']);
                exit;
            }
            
            // Preparar dados para atualização (sem obrigar campos de hora)
            $updateData = [
                'name' => trim($_POST['name'] ?? $employee['name']),
                'last_name' => trim($_POST['last_name'] ?? $employee['last_name']),
                'email' => trim($_POST['email'] ?? $employee['email']),
                'phone' => trim($_POST['phone'] ?? $employee['phone']),
                'position' => trim($_POST['position'] ?? $employee['position']),
                'birth_date' => $_POST['birth_date'] ?? $employee['birth_date'],
                'nationality' => trim($_POST['nationality'] ?? $employee['nationality']),
                'passport' => trim($_POST['passport'] ?? $employee['passport']),
                'gender' => $_POST['gender'] ?? $employee['gender'],
                'marital_status' => $_POST['marital_status'] ?? $employee['marital_status'],
                'role' => $_POST['role'] ?? $employee['role']
            ];
            
            // Validações básicas
            if (empty($updateData['name'])) {
                echo json_encode(['success' => false, 'message' => 'Nome é obrigatório']);
                exit;
            }
            
            if (empty($updateData['email']) || !filter_var($updateData['email'], FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'E-mail válido é obrigatório']);
                exit;
            }
            
            // Verificar se email já existe para outro funcionário
            global $pdo;
            $stmt = $pdo->prepare("SELECT id FROM employees WHERE email = ? AND id != ?");
            $stmt->execute([$updateData['email'], $employeeId]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Este e-mail já está em uso']);
                exit;
            }
            
            // Atualizar funcionário
            $stmt = $pdo->prepare("
                UPDATE employees SET 
                    name = ?, last_name = ?, email = ?, phone = ?, position = ?,
                    birth_date = ?, nationality = ?, passport = ?, gender = ?, 
                    marital_status = ?, role = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $success = $stmt->execute([
                $updateData['name'],
                $updateData['last_name'], 
                $updateData['email'],
                $updateData['phone'],
                $updateData['position'],
                $updateData['birth_date'] ?: null,
                $updateData['nationality'],
                $updateData['passport'],
                $updateData['gender'],
                $updateData['marital_status'],
                $updateData['role'],
                $employeeId
            ]);
            
            if (!$success) {
                echo json_encode(['success' => false, 'message' => 'Erro ao atualizar funcionário']);
                exit;
            }
            
            // Atualizar senha se fornecida
            if (!empty($_POST['password'])) {
                $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
                
                // Buscar user_id do funcionário
                $stmt = $pdo->prepare("SELECT user_id FROM employees WHERE id = ?");
                $stmt->execute([$employeeId]);
                $userId = $stmt->fetchColumn();
                
                if ($userId) {
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashedPassword, $userId]);
                }
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
            
            // Sistema antigo
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(hours), 0) FROM project_work_logs WHERE employee_id = ?");
            $stmt->execute([$employeeId]);
            $totalHours += (float)$stmt->fetchColumn();

            // Horas hoje
            $todayHours = 0;
            
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_hours), 0) FROM time_entries WHERE employee_id = ? AND date = ?");
            $stmt->execute([$employeeId, $today]);
            $todayHours += (float)$stmt->fetchColumn();
            
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(hours), 0) FROM project_work_logs WHERE employee_id = ? AND date = ?");
            $stmt->execute([$employeeId, $today]);
            $todayHours += (float)$stmt->fetchColumn();

            // Horas da semana
            $weekHours = 0;
            
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_hours), 0) FROM time_entries WHERE employee_id = ? AND date BETWEEN ? AND ?");
            $stmt->execute([$employeeId, $startOfWeek, $endOfWeek]);
            $weekHours += (float)$stmt->fetchColumn();
            
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(hours), 0) FROM project_work_logs WHERE employee_id = ? AND date BETWEEN ? AND ?");
            $stmt->execute([$employeeId, $startOfWeek, $endOfWeek]);
            $weekHours += (float)$stmt->fetchColumn();

            return [
                'total' => number_format($totalHours, 2, '.', ''),
                'today' => number_format($todayHours, 2, '.', ''),
                'week' => number_format($weekHours, 2, '.', ''),
            ];
            
        } catch (Exception $e) {
            return [
                'total' => '0.00',
                'today' => '0.00', 
                'week' => '0.00'
            ];
        }
    }

    /** Constrói filtro de data baseado no parâmetro */
    private function buildDateFilter(string $filter): string
    {
        switch ($filter) {
            case 'all':
                return "AND date >= '2023-01-01'";
            case 'week':
                return "AND date >= '" . date('Y-m-d', strtotime('monday this week')) . "' AND date <= '" . date('Y-m-d', strtotime('sunday this week')) . "'";
            case 'month':
                return "AND MONTH(date) = MONTH(CURRENT_DATE()) AND YEAR(date) = YEAR(CURRENT_DATE())";
            case 'period':
                return "";
            default:
                return "AND date = CURRENT_DATE()";
        }
    }

    /** Formata exibição do registro de ponto */
    private function formatTimeEntryDisplay(array $entries, string $date): string
    {
        if (empty($entries)) {
            return 'Registro vazio';
        }

        usort($entries, function($a, $b) {
            return strcmp($a['time'] ?? '', $b['time'] ?? '');
        });
        
        $pairs = [];
        $currentEntry = null;
        
        foreach ($entries as $entry) {
            if (($entry['type'] ?? '') === 'entry') {
                if ($currentEntry) {
                    $pairs[] = "entrada {$currentEntry} saída ?";
                }
                $currentEntry = $entry['time'] ?? '';
            } elseif (($entry['type'] ?? '') === 'exit') {
                if ($currentEntry) {
                    $pairs[] = "entrada {$currentEntry} saída {$entry['time']}";
                    $currentEntry = null;
                } else {
                    $pairs[] = "entrada ? saída {$entry['time']}";
                }
            }
        }
        
        if ($currentEntry) {
            $pairs[] = "entrada {$currentEntry} saída ?";
        }
        
        if (empty($pairs)) {
            return 'Registro sem pares válidos';
        }
        
        $dateFormatted = date('d/m/Y', strtotime($date));
        return implode(' - ', $pairs) . " {$dateFormatted}";
    }

    /** API: Ranking de funcionários por horas */
    public function getRanking()
    {
        header('Content-Type: application/json; charset=UTF-8');

        try {
            global $pdo;
            
            $stmt = $pdo->query("
                SELECT e.*, u.email
                FROM employees e
                LEFT JOIN users u ON e.user_id = u.id
                WHERE e.active = 1
                ORDER BY e.name, e.last_name
            ");
            $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $ranking = [];

            foreach ($employees as $emp) {
                $empId = $emp['id'];
                $totalHours = 0;

                // Sistema novo
                $newSystemHours = $this->timeEntryModel->getTotalHoursByEmployee($empId);
                
                // Sistema antigo  
                $oldSystemHours = $this->workLogModel->getTotalHoursByEmployee($empId);

                $totalHours = $oldSystemHours + $newSystemHours;

                if ($totalHours > 0) {
                    $ranking[] = [
                        'id' => $empId,
                        'name' => trim($emp['name'] . ' ' . $emp['last_name']),
                        'position' => $emp['function'] ?? 'Não definido',
                        'total_hours' => $totalHours,
                        'old_system_hours' => $oldSystemHours,
                        'new_system_hours' => $newSystemHours,
                    ];
                }
            }

            // Ordena por total de horas (decrescente)
            usort($ranking, function ($a, $b) {
                return $b['total_hours'] <=> $a['total_hours'];
            });

            echo json_encode($ranking);

        } catch (Exception $e) {
            error_log("EmployeeController::getRanking error: " . $e->getMessage());
            echo json_encode([]);
        }
        exit;
    }
}