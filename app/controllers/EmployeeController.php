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

    /** Atualiza funcionário */
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
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'function' => trim($_POST['position'] ?? ''), // Mapeia position para function
            'active' => isset($_POST['active']) ? 1 : 0,
        ];

        if (!$data['name'] || !$data['last_name'] || !$data['email']) {
            echo json_encode(['success' => false, 'message' => 'Campos obrigatórios não preenchidos']);
            exit;
        }

        try {
            global $pdo;
            $pdo->beginTransaction();

            // Busca funcionário
            $employee = $this->employeeModel->find($employeeId);
            if (!$employee) {
                throw new Exception('Funcionário não encontrado');
            }

            // Atualiza funcionário
            $success = $this->employeeModel->update($employeeId, $data);
            if (!$success) {
                throw new Exception('Erro ao atualizar funcionário');
            }

            // Atualiza usuário
            $userData = [
                'name' => $data['name'],
                'email' => $data['email']
            ];

            // Atualiza senha se fornecida
            $password = trim($_POST['password'] ?? '');
            if ($password) {
                $userData['password'] = password_hash($password, PASSWORD_DEFAULT);
            }

            $userSuccess = $this->userModel->update($employee['user_id'], $userData);
            if (!$userSuccess) {
                throw new Exception('Erro ao atualizar dados do usuário');
            }

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Funcionário atualizado com sucesso']);

        } catch (Exception $e) {
            $pdo->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /** Deleta funcionário */
    public function delete()
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

        try {
            global $pdo;
            $pdo->beginTransaction();

            // Busca funcionário
            $employee = $this->employeeModel->find($employeeId);
            if (!$employee) {
                throw new Exception('Funcionário não encontrado');
            }

            // Desativa em vez de deletar (preserva histórico)
            $success = $this->employeeModel->update($employeeId, ['active' => 0]);
            if (!$success) {
                throw new Exception('Erro ao desativar funcionário');
            }

            // Desativa usuário também se existir
            if ($employee['user_id']) {
                $this->userModel->update($employee['user_id'], ['active' => 0]);
            }

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Funcionário desativado com sucesso']);

        } catch (Exception $e) {
            $pdo->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /** Busca dados de um funcionário */
    public function get()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $employeeId = (int)($_GET['id'] ?? 0);
        if (!$employeeId) {
            echo json_encode(['success' => false, 'message' => 'ID não informado']);
            exit;
        }

        try {
            global $pdo;
            
            $stmt = $pdo->prepare("
                SELECT e.*, u.email
                FROM employees e
                LEFT JOIN users u ON e.user_id = u.id
                WHERE e.id = ?
            ");
            $stmt->execute([$employeeId]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$employee) {
                echo json_encode(['success' => false, 'message' => 'Funcionário não encontrado']);
                exit;
            }

            $data = [
                'id' => $employee['id'],
                'name' => $employee['name'] ?? '',
                'last_name' => $employee['last_name'] ?? '',
                'email' => $employee['email'] ?? '',
                'phone' => $employee['phone'] ?? '',
                'position' => $employee['function'] ?? '', // Mapeia function para position
                'active' => (bool)($employee['active'] ?? 1)
            ];

            echo json_encode(['success' => true, 'data' => $data]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro interno']);
        }
        exit;
    }

    /** API: Horas de um funcionário específico */
    public function getEmployeeHours(int $employeeId)
    {
        header('Content-Type: application/json; charset=UTF-8');

        if (!$employeeId) {
            echo json_encode(['entries' => [], 'total_hours' => '0.00']);
            exit;
        }

        // Parâmetros de filtro
        $filter = $_GET['filter'] ?? 'all';
        $startDate = null;
        $endDate = null;
        $month = null;
        $year = null;

        // Define filtros baseado no parâmetro
        switch ($filter) {
            case 'today':
                $startDate = $endDate = date('Y-m-d');
                break;
            case 'week':
                $startDate = date('Y-m-d', strtotime('monday this week'));
                $endDate = date('Y-m-d', strtotime('sunday this week'));
                break;
            case 'month':
                $month = date('n');
                $year = date('Y');
                break;
            default:
                // 'all' - sem filtros
                break;
        }

        try {
            global $pdo;
            
            $allEntries = [];
            $totalHours = 0;

            // Busca funcionário
            $employee = $this->employeeModel->find($employeeId);
            $employeeName = $employee ? trim($employee['name'] . ' ' . $employee['last_name']) : 'Funcionário';

            // Sistema novo (time_entries) com formatação corrigida
            try {
                $sql = "
                    SELECT te.*, 
                           p.name as project_name
                    FROM time_entries te
                    LEFT JOIN projects p ON p.id = te.project_id  
                    WHERE te.employee_id = ?
                ";
                
                $params = [$employeeId];
                
                if ($startDate && $endDate) {
                    $sql .= " AND te.date BETWEEN ? AND ?";
                    $params[] = $startDate;
                    $params[] = $endDate;
                } elseif ($month && $year) {
                    $sql .= " AND YEAR(te.date) = ? AND MONTH(te.date) = ?";
                    $params[] = $year;
                    $params[] = $month;
                } elseif ($year) {
                    $sql .= " AND YEAR(te.date) = ?";
                    $params[] = $year;
                }
                
                $sql .= " ORDER BY te.date DESC";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $newSystemEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($newSystemEntries as $entry) {
                    $records = json_decode($entry['time_records'], true) ?? ['entries' => []];
                    
                    // Corrige a formatação dos registros
                    $formatted = $this->formatTimeEntryDisplay($records['entries'] ?? [], $entry['date']);
                    
                    $allEntries[] = [
                        'id' => $entry['id'],
                        'date' => $entry['date'],
                        'total_hours' => (float)$entry['total_hours'],
                        'project_name' => $entry['project_name'] ?? 'Projeto não encontrado',
                        'formatted_display' => $formatted,
                        'system_type' => 'new'
                    ];
                    
                    $totalHours += (float)$entry['total_hours'];
                }
            } catch (Exception $e) {
                error_log("Erro ao buscar time_entries: " . $e->getMessage());
            }
            
            // Sistema antigo (project_work_logs)
            try {
                $sql = "
                    SELECT 
                        pwl.*,
                        p.name as project_name
                    FROM project_work_logs pwl
                    LEFT JOIN projects p ON p.id = pwl.project_id
                    WHERE pwl.employee_id = ?
                ";
                
                $params = [$employeeId];
                
                if ($startDate && $endDate) {
                    $sql .= " AND pwl.date BETWEEN ? AND ?";
                    $params[] = $startDate;
                    $params[] = $endDate;
                } elseif ($month && $year) {
                    $sql .= " AND YEAR(pwl.date) = ? AND MONTH(pwl.date) = ?";
                    $params[] = $year;
                    $params[] = $month;
                } elseif ($year) {
                    $sql .= " AND YEAR(pwl.date) = ?";
                    $params[] = $year;
                }
                
                $sql .= " ORDER BY pwl.date DESC";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $oldSystemEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($oldSystemEntries as $entry) {
                    $allEntries[] = [
                        'id' => 'old_' . $entry['id'],
                        'date' => $entry['date'],
                        'total_hours' => (float)$entry['hours'],
                        'project_name' => $entry['project_name'] ?? 'Projeto não encontrado',
                        'formatted_display' => number_format($entry['hours'], 2, ',', '.') . 'h (sistema antigo)',
                        'system_type' => 'old'
                    ];
                    
                    $totalHours += (float)$entry['hours'];
                }
            } catch (Exception $e) {
                error_log("Erro ao buscar project_work_logs: " . $e->getMessage());
            }

            // Ordena por data (mais recente primeiro)
            usort($allEntries, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });

            // Calcula estatísticas adicionais
            $monthHours = 0;
            $activeProjects = [];
            $currentMonth = date('Y-m');

            foreach ($allEntries as $entry) {
                if (strpos($entry['date'], $currentMonth) === 0) {
                    $monthHours += $entry['total_hours'];
                }
                if (!in_array($entry['project_name'], $activeProjects)) {
                    $activeProjects[] = $entry['project_name'];
                }
            }

            $daysInMonth = date('t');
            $dailyAvg = $daysInMonth > 0 ? $monthHours / $daysInMonth : 0;

            echo json_encode([
                'entries' => $allEntries,
                'total_hours' => number_format($totalHours, 2, '.', ''),
                'employee_name' => $employeeName,
                'month_hours' => number_format($monthHours, 2, '.', ''),
                'daily_avg' => number_format($dailyAvg, 2, '.', ''),
                'active_projects' => count($activeProjects)
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

    /** API: Resumo geral de horas dos funcionários */
    public function getEmployeeHoursSummary()
    {
        header('Content-Type: application/json; charset=UTF-8');

        try {
            // Se for funcionário, busca apenas suas horas
            if ($_SESSION['user']['role'] === 'employee') {
                $email = $_SESSION['user']['email'] ?? '';
                $user = $this->userModel->findByEmail($email);
                $employee = $this->employeeModel->findByUserId($user['id'] ?? 0);
                $employeeId = $employee['id'] ?? 0;
                
                if ($employeeId) {
                    $summary = $this->calculateEmployeeSummary($employeeId);
                    echo json_encode($summary);
                    exit;
                }
            }

            // Para admins, retorna resumo geral
            global $pdo;

            $currentMonth = date('Y-m');
            $today = date('Y-m-d');
            $startOfWeek = date('Y-m-d', strtotime('monday this week'));
            $endOfWeek = date('Y-m-d', strtotime('sunday this week'));

            // Total geral (sistema antigo + novo)
            $totalHours = 0;
            
            // Sistema novo
            $stmt = $pdo->query("SELECT COALESCE(SUM(total_hours), 0) as total FROM time_entries");
            $totalHours += (float)$stmt->fetchColumn();
            
            // Sistema antigo
            $stmt = $pdo->query("SELECT COALESCE(SUM(hours), 0) as total FROM project_work_logs");
            $totalHours += (float)$stmt->fetchColumn();

            // Horas do mês atual
            $monthHours = 0;
            
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_hours), 0) as total FROM time_entries WHERE DATE_FORMAT(date, '%Y-%m') = ?");
            $stmt->execute([$currentMonth]);
            $monthHours += (float)$stmt->fetchColumn();
            
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(hours), 0) as total FROM project_work_logs WHERE DATE_FORMAT(date, '%Y-%m') = ?");
            $stmt->execute([$currentMonth]);
            $monthHours += (float)$stmt->fetchColumn();

            // Horas de hoje
            $todayHours = 0;
            
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_hours), 0) as total FROM time_entries WHERE date = ?");
            $stmt->execute([$today]);
            $todayHours += (float)$stmt->fetchColumn();
            
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(hours), 0) as total FROM project_work_logs WHERE date = ?");
            $stmt->execute([$today]);
            $todayHours += (float)$stmt->fetchColumn();

            // Horas da semana
            $weekHours = 0;
            
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_hours), 0) as total FROM time_entries WHERE date BETWEEN ? AND ?");
            $stmt->execute([$startOfWeek, $endOfWeek]);
            $weekHours += (float)$stmt->fetchColumn();
            
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(hours), 0) as total FROM project_work_logs WHERE date BETWEEN ? AND ?");
            $stmt->execute([$startOfWeek, $endOfWeek]);
            $weekHours += (float)$stmt->fetchColumn();

            // Funcionários ativos
            $stmt = $pdo->query("SELECT COUNT(*) FROM employees WHERE active = 1");
            $activeEmployees = (int)$stmt->fetchColumn();

            // Média por funcionário
            $avgPerEmployee = $activeEmployees > 0 ? $totalHours / $activeEmployees : 0;

            echo json_encode([
                'total' => number_format($totalHours, 2, '.', ''),
                'total_month' => number_format($monthHours, 2, '.', ''),
                'today' => number_format($todayHours, 2, '.', ''),
                'week' => number_format($weekHours, 2, '.', ''),
                'avg_per_employee' => number_format($avgPerEmployee, 2, '.', ''),
                'active_employees' => $activeEmployees,
                'active_projects_count' => 0
            ]);

        } catch (Exception $e) {
            error_log("EmployeeController::getEmployeeHoursSummary error: " . $e->getMessage());
            echo json_encode([
                'total' => '0.00',
                'total_month' => '0.00',
                'today' => '0.00',
                'week' => '0.00',
                'avg_per_employee' => '0.00',
                'active_employees' => 0,
                'active_projects_count' => 0
            ]);
        }
        exit;
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

    /** Calcula resumo individual do funcionário */
    private function calculateEmployeeSummary(int $employeeId): array
    {
        try {
            global $pdo;
            
            $today = date('Y-m-d');
            $currentMonth = date('Y-m');
            $startOfWeek = date('Y-m-d', strtotime('monday this week'));
            $endOfWeek = date('Y-m-d', strtotime('sunday this week'));

            // Total geral
            $totalHours = 0;
            $totalHours += $this->timeEntryModel->getTotalHoursByEmployee($employeeId);
            $totalHours += $this->workLogModel->getTotalHoursByEmployee($employeeId);

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

    /** Formata exibição do registro de ponto no formato solicitado */
    private function formatTimeEntryDisplay(array $entries, string $date): string
    {
        if (empty($entries)) {
            return 'Registro vazio';
        }

        // Ordena por horário
        usort($entries, function($a, $b) {
            return strcmp($a['time'] ?? '', $b['time'] ?? '');
        });
        
        $pairs = [];
        $currentEntry = null;
        
        foreach ($entries as $entry) {
            if (($entry['type'] ?? '') === 'entry') {
                if ($currentEntry) {
                    // Entrada sem saída correspondente anterior
                    $pairs[] = "entrada {$currentEntry} saída ?";
                }
                $currentEntry = $entry['time'] ?? '';
            } elseif (($entry['type'] ?? '') === 'exit') {
                if ($currentEntry) {
                    $pairs[] = "entrada {$currentEntry} saída {$entry['time']}";
                    $currentEntry = null;
                } else {
                    // Saída sem entrada correspondente  
                    $pairs[] = "entrada ? saída {$entry['time']}";
                }
            }
        }
        
        // Entrada pendente sem saída
        if ($currentEntry) {
            $pairs[] = "entrada {$currentEntry} saída ?";
        }
        
        if (empty($pairs)) {
            return 'Registro sem pares válidos';
        }
        
        $dateFormatted = date('d/m/Y', strtotime($date));
        return implode(' - ', $pairs) . " {$dateFormatted}";
    }
}