<?php
// app/controllers/WorkLogController.php - ARQUIVO COMPLETO ATUALIZADO

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/Employees.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/WorkLogModel.php';
require_once __DIR__ . '/../models/TimeEntryModel.php';

class WorkLogController
{
    private WorkLogModel $workLogModel;
    private TimeEntryModel $timeEntryModel;
    private Employee $employeeModel;
    private UserModel $userModel;
    private array $langText;
    private string $baseUrl;

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        global $pdo;
        $this->workLogModel = new WorkLogModel();
        $this->timeEntryModel = new TimeEntryModel();
        $this->employeeModel = new Employee();
        $this->userModel = new UserModel();
        $this->baseUrl = BASE_URL;

        // linguagem
        $lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'pt';
        $_SESSION['lang'] = $lang;
        $lf = __DIR__ . "/../lang/$lang.php";
        $this->langText = file_exists($lf) 
                        ? require $lf 
                        : require __DIR__ . '/../lang/pt.php';
    }

    /** GET /work_logs?project_id=… (MANTIDO PARA COMPATIBILIDADE) */
    public function index()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $projId = max(0, (int)($_GET['project_id'] ?? 0));
        if (!$projId) {
            echo json_encode([]);
            exit;
        }

        // Determina o employee_id
        $empId = 0;

        // Caso admin, aceita employee_id vindo do formulário
        if ($_SESSION['user']['role'] === 'admin' && isset($_POST['employee_id'])) {
            $empId = (int) $_POST['employee_id'];
        } else {
            // Caso funcionário, pega pelo e-mail da sessão
            $email = $_SESSION['user']['email'] ?? '';
            $user = $this->userModel->findByEmail($email);
            $emp = $this->employeeModel->findByUserId((int)($user['id'] ?? 0));
            $empId = $emp['id'] ?? 0;
        }

        $logs = $this->workLogModel->getByEmployeeAndProject($empId, $projId);
        echo json_encode($logs);
        exit;
    }

    /** POST /work_logs/store (MANTIDO PARA COMPATIBILIDADE) */
    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido']);
            exit;
        }

        // identifica funcionário pela sessão
        $email = $_SESSION['user']['email'] ?? '';
        $user = $this->userModel->findByEmail($email);
        $emp = $this->employeeModel->findByUserId((int)($user['id'] ?? 0));
        $empId = $emp['id'] ?? 0;

        $projId = max(0, (int)($_POST['project_id'] ?? 0));
        $hours = floatval($_POST['hours'] ?? 0);
        $date = $_POST['date'] ?? date('Y-m-d');

        if (!$empId || !$projId || $hours <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Dados inválidos.']);
            exit;
        }

        $ok = $this->workLogModel->create([
            'employee_id' => $empId,
            'project_id' => $projId,
            'hours' => $hours,
            'date' => $date
        ]);

        if (!$ok) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Erro ao salvar horas.']);
            exit;
        }

        echo json_encode(['success' => true]);
        exit;
    }

    /** API: Admin cria registro de tempo para funcionário */
    public function adminCreateTimeEntry()
    {
        header('Content-Type: application/json; charset=UTF-8');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            exit;
        }
        
        // Verifica se é admin
        if (!isAdmin()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acesso negado']);
            exit;
        }
        
        $employeeId = (int)($_POST['employee_id'] ?? 0);
        $projectId = (int)($_POST['project_id'] ?? 1); // Projeto padrão
        $date = $_POST['date'] ?? date('Y-m-d');
        $time = $_POST['time'] ?? date('H:i');
        $entryType = $_POST['entry_type'] ?? 'entry';
        
        if (!$employeeId || !$date || !$time) {
            echo json_encode(['success' => false, 'message' => 'Dados obrigatórios faltando']);
            exit;
        }
        
        try {
            $success = $this->timeEntryModel->addTimeEntry($employeeId, $projectId, $date, $entryType, $time);
            
            if ($success) {
                // Atualiza totais do projeto
                $this->updateProjectTotalHours($projectId);
                echo json_encode(['success' => true, 'message' => 'Registro criado com sucesso']);
            } else {
                throw new Exception('Erro ao salvar registro');
            }
            
        } catch (Exception $e) {
            error_log("WorkLogController::adminCreateTimeEntry error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
        }
        exit;
    }
    
    public function getEmployeeProjects()
    {
        header('Content-Type: application/json; charset=UTF-8');
        
        $employeeId = (int)($_GET['employee_id'] ?? 0);
        
        if (!$employeeId) {
            echo json_encode([]);
            exit;
        }
        
        try {
            global $pdo;
            
            // Estratégia 1: Busca projetos onde o funcionário está explicitamente alocado
            $stmt = $pdo->prepare("
                SELECT DISTINCT p.id, p.name, p.status
                FROM projects p
                INNER JOIN project_allocations pa ON p.id = pa.project_id
                WHERE pa.employee_id = ? AND p.status IN ('in_progress', 'pending')
                ORDER BY p.name ASC
            ");
            $stmt->execute([$employeeId]);
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Estratégia 2: Se não encontrou alocações, busca projetos onde já registrou horas
            if (empty($projects)) {
                $stmt = $pdo->prepare("
                    SELECT DISTINCT p.id, p.name, p.status
                    FROM projects p
                    INNER JOIN time_entries te ON p.id = te.project_id
                    WHERE te.employee_id = ? AND p.status IN ('in_progress', 'pending')
                    ORDER BY p.name ASC
                ");
                $stmt->execute([$employeeId]);
                $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // Estratégia 3: Se ainda não encontrou, busca projetos ativos (fallback)
            if (empty($projects)) {
                $stmt = $pdo->prepare("
                    SELECT id, name, status
                    FROM projects
                    WHERE status IN ('in_progress', 'pending')
                    ORDER BY name ASC
                    LIMIT 10
                ");
                $stmt->execute();
                $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            echo json_encode($projects);
            
        } catch (Exception $e) {
            error_log("Error getting employee projects: " . $e->getMessage());
            echo json_encode([]);
        }
        exit;
    }

    /**
     * API: Lista registros de ponto de um funcionário - CORRIGIDO
     * GET /api/worklog/time-entries?employee_id=X&filter=Y
     */
    public function time_entries()
    {
        header('Content-Type: application/json; charset=UTF-8');
        
        // Determina o employee_id - CORRIGIDO PARA ADMINS
        $employeeId = 0;
        
        // Se é admin e veio employee_id no parâmetro (CASO DO MODAL DE FUNCIONÁRIOS)
        if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin' && isset($_GET['employee_id'])) {
            $employeeId = (int)$_GET['employee_id'];
            error_log("Admin mode - employee_id from GET: $employeeId");
        } else {
            // Funcionário logado (dashboard do funcionário)
            $email = $_SESSION['user']['email'] ?? '';
            $user = $this->userModel->findByEmail($email);
            $emp = $this->employeeModel->findByUserId((int)($user['id'] ?? 0));
            $employeeId = $emp['id'] ?? 0;
            error_log("Employee mode - found employeeId: $employeeId");
        }
        
        if (!$employeeId) {
            echo json_encode(['entries' => [], 'total_hours' => 0]);
            exit;
        }
        
        $filter = $_GET['filter'] ?? 'all';
        
        try {
            global $pdo;
            
            // Define filtro de data
            $whereClause = '';
            $params = [$employeeId];
            
            switch ($filter) {
                case 'today':
                    $whereClause = ' AND DATE(te.date) = CURDATE()';
                    break;
                case 'week':
                    $whereClause = ' AND YEARWEEK(te.date, 1) = YEARWEEK(CURDATE(), 1)';
                    break;
                case 'month':
                    $whereClause = ' AND YEAR(te.date) = YEAR(CURDATE()) AND MONTH(te.date) = MONTH(CURDATE())';
                    break;
            }
            
            // Busca registros da tabela time_entries
            $stmt = $pdo->prepare("
                SELECT 
                    te.id,
                    te.date,
                    te.time_records,
                    te.total_hours,
                    te.created_at,
                    p.name as project_name,
                    p.id as project_id
                FROM time_entries te
                LEFT JOIN projects p ON te.project_id = p.id
                WHERE te.employee_id = ? {$whereClause}
                ORDER BY te.date DESC, te.created_at DESC
            ");
            
            $stmt->execute($params);
            $timeEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Found " . count($timeEntries) . " time entries for employee $employeeId");
            
            // Processa os registros para compatibilidade com o frontend
            $entries = [];
            $totalHours = 0;
            
            foreach ($timeEntries as $timeEntry) {
                $records = json_decode($timeEntry['time_records'], true);
                $entryRecords = $records['entries'] ?? [];
                
                // Cada entrada/saída vira um item separado para o frontend
                foreach ($entryRecords as $record) {
                    $entries[] = [
                        'id' => $timeEntry['id'] . '_' . $record['time'],
                        'date' => $timeEntry['date'],
                        'time' => $record['time'],
                        'entry_type' => $record['type'],
                        'project_name' => $timeEntry['project_name'],
                        'project_id' => $timeEntry['project_id'],
                        'calculated_hours' => 0 // Será calculado abaixo
                    ];
                }
                
                $totalHours += floatval($timeEntry['total_hours']);
            }
            
            // Adiciona horas calculadas por data
            $entriesByDate = [];
            foreach ($entries as &$entry) {
                $date = $entry['date'];
                if (!isset($entriesByDate[$date])) {
                    $entriesByDate[$date] = [];
                }
                $entriesByDate[$date][] = &$entry;
            }
            
            foreach ($entriesByDate as $date => $dateEntries) {
                $hoursForDate = 0;
                foreach ($timeEntries as $timeEntry) {
                    if ($timeEntry['date'] === $date) {
                        $hoursForDate = floatval($timeEntry['total_hours']);
                        break;
                    }
                }
                
                // Distribui as horas entre as entradas do dia
                $entryCount = count($dateEntries);
                if ($entryCount > 0) {
                    $hoursPerEntry = $hoursForDate / $entryCount;
                    foreach ($dateEntries as &$entry) {
                        $entry['calculated_hours'] = round($hoursPerEntry, 2);
                    }
                }
            }
            
            error_log("Returning " . count($entries) . " processed entries with total $totalHours hours");
            
            echo json_encode([
                'entries' => $entries,
                'total_hours' => round($totalHours, 2)
            ]);
            
        } catch (Exception $e) {
            error_log("WorkLogController::time_entries error: " . $e->getMessage());
            echo json_encode(['entries' => [], 'total_hours' => 0]);
        }
        exit;
    }

    /**
     * API: Adiciona registro de entrada/saída - VERSÃO FINAL
     * POST /api/worklog/add-time-entry
     */
    public function addTimeEntry()
    {
        header('Content-Type: application/json; charset=UTF-8');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            exit;
        }
        
        try {
            // Log dos dados recebidos
            error_log("=== addTimeEntry DEBUG ===");
            error_log("Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'undefined'));
            error_log("POST data: " . print_r($_POST, true));
            
            // Detecta se os dados vieram via JSON ou FormData
            $data = [];
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            
            if (strpos($contentType, 'application/json') !== false) {
                $input = json_decode(file_get_contents('php://input'), true);
                $data = $input ?: [];
            } else {
                $data = $_POST;
            }
            
            // Determina o employee_id
            $employeeId = 0;
            
            if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin') {
                if (isset($data['employee_id']) && $data['employee_id']) {
                    $employeeId = (int)$data['employee_id'];
                    error_log("Admin mode - employee_id: $employeeId");
                } else {
                    echo json_encode(['success' => false, 'message' => 'Employee ID é obrigatório para admins']);
                    exit;
                }
            } else {
                $email = $_SESSION['user']['email'] ?? '';
                $user = $this->userModel->findByEmail($email);
                $emp = $this->employeeModel->findByUserId((int)($user['id'] ?? 0));
                $employeeId = $emp['id'] ?? 0;
                error_log("Employee mode - employee_id: $employeeId");
            }
            
            // Extrai outros parâmetros
            $projectId = (int)($data['project_id'] ?? 0);
            $date = trim($data['date'] ?? '');
            $time = trim($data['time'] ?? '');
            $entryType = trim($data['type'] ?? $data['entry_type'] ?? 'entry');
            
            error_log("Params - projectId: $projectId, date: $date, time: $time, type: $entryType");
            
            // Validações
            if (!$employeeId) {
                echo json_encode(['success' => false, 'message' => 'Employee ID não encontrado']);
                exit;
            }
            
            if (!$projectId) {
                echo json_encode(['success' => false, 'message' => 'Projeto é obrigatório']);
                exit;
            }
            
            if (empty($date) || empty($time)) {
                echo json_encode(['success' => false, 'message' => 'Data e horário são obrigatórios']);
                exit;
            }
            
            if (!in_array($entryType, ['entry', 'exit'])) {
                echo json_encode(['success' => false, 'message' => "Tipo inválido: '$entryType'"]);
                exit;
            }
            
            global $pdo;
            
            // Verifica se o projeto existe
            $stmt = $pdo->prepare("SELECT id, name FROM projects WHERE id = ?");
            $stmt->execute([$projectId]);
            $project = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$project) {
                echo json_encode(['success' => false, 'message' => 'Projeto não encontrado']);
                exit;
            }
            
            // Verifica se já existe registro para este funcionário, projeto e data
            $stmt = $pdo->prepare("
                SELECT id, time_records, total_hours 
                FROM time_entries 
                WHERE employee_id = ? AND project_id = ? AND date = ?
            ");
            $stmt->execute([$employeeId, $projectId, $date]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                error_log("Updating existing record ID: " . $existing['id']);
                
                // Atualiza registro existente
                $timeRecords = json_decode($existing['time_records'], true);
                if (!isset($timeRecords['entries']) || !is_array($timeRecords['entries'])) {
                    $timeRecords = ['entries' => []];
                }
                
                $timeRecords['entries'][] = ['type' => $entryType, 'time' => $time];
                $totalHours = $this->calculateHoursFromTimeRecords($timeRecords['entries']);
                
                $updateStmt = $pdo->prepare("
                    UPDATE time_entries 
                    SET time_records = ?, total_hours = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                
                $success = $updateStmt->execute([
                    json_encode($timeRecords, JSON_UNESCAPED_UNICODE),
                    $totalHours,
                    $existing['id']
                ]);
                
            } else {
                error_log("Creating new record");
                
                // Cria novo registro
                $timeRecords = ['entries' => [['type' => $entryType, 'time' => $time]]];
                $totalHours = $this->calculateHoursFromTimeRecords($timeRecords['entries']);
                
                $insertStmt = $pdo->prepare("
                    INSERT INTO time_entries (employee_id, project_id, date, time_records, total_hours, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                
                $success = $insertStmt->execute([
                    $employeeId, $projectId, $date,
                    json_encode($timeRecords, JSON_UNESCAPED_UNICODE),
                    $totalHours
                ]);
            }
            
            if ($success) {
                error_log("SUCCESS - Record saved");
                echo json_encode(['success' => true, 'message' => 'Ponto registrado com sucesso']);
            } else {
                throw new Exception('Erro ao inserir/atualizar no banco de dados');
            }
            
        } catch (Exception $e) {
            error_log("ERROR in addTimeEntry: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Calcula total de horas baseado nos registros de entrada/saída
     */
    private function calculateHoursFromTimeRecords(array $entries): float
    {
        $totalMinutes = 0;
        $entryTime = null;
        
        // Ordena por horário
        usort($entries, function($a, $b) {
            return strcmp($a['time'], $b['time']);
        });
        
        foreach ($entries as $entry) {
            if ($entry['type'] === 'entry') {
                $entryTime = $entry['time'];
            } elseif ($entry['type'] === 'exit' && $entryTime) {
                try {
                    $start = new DateTime($entryTime);
                    $end = new DateTime($entry['time']);
                    $diff = $start->diff($end);
                    $minutes = ($diff->h * 60) + $diff->i;
                    $totalMinutes += $minutes;
                    $entryTime = null;
                } catch (Exception $e) {
                    error_log("Error calculating time difference: " . $e->getMessage());
                }
            }
        }
        
        return round($totalMinutes / 60, 2);
    }

    /** API: Excluir registro de tempo */
    public function deleteTimeEntry()
    {
        header('Content-Type: application/json; charset=UTF-8');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            exit;
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $entryId = (int)($input['entry_id'] ?? 0);
            
            if (!$entryId) {
                echo json_encode(['success' => false, 'message' => 'ID do registro obrigatório']);
                exit;
            }
            
            global $pdo;
            $success = false;
            
            // Tenta excluir da nova tabela primeiro
            try {
                $stmt = $pdo->prepare("DELETE FROM time_entries WHERE id = ?");
                $success = $stmt->execute([$entryId]);
            } catch (Exception $e) {
                // Se falhar, tenta da tabela antiga
                $stmt = $pdo->prepare("DELETE FROM project_work_logs WHERE id = ?");
                $success = $stmt->execute([$entryId]);
            }
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Registro excluído com sucesso']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao excluir registro']);
            }
            
        } catch (Exception $e) {
            error_log("WorkLogController::deleteTimeEntry error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro interno']);
        }
        exit;
    }

    /** Atualiza total de horas do projeto */
    private function updateProjectTotalHours($projectId)
    {
        if (!$projectId) return;
        
        try {
            global $pdo;
            
            // Calcula total de horas do sistema novo
            $stmt = $pdo->prepare("
                SELECT COALESCE(SUM(total_hours), 0) as total
                FROM time_entries 
                WHERE project_id = ?
            ");
            $stmt->execute([$projectId]);
            $newSystemHours = (float)$stmt->fetchColumn();
            
            // Calcula total de horas do sistema antigo
            $stmt = $pdo->prepare("
                SELECT COALESCE(SUM(hours), 0) as total
                FROM project_work_logs 
                WHERE project_id = ?
            ");
            $stmt->execute([$projectId]);
            $oldSystemHours = (float)$stmt->fetchColumn();
            
            $totalHours = $newSystemHours + $oldSystemHours;
            
            // Atualiza projeto
            $stmt = $pdo->prepare("
                UPDATE projects 
                SET total_hours = ? 
                WHERE id = ?
            ");
            $stmt->execute([$totalHours, $projectId]);
            
        } catch (Exception $e) {
            error_log("WorkLogController::updateProjectTotalHours error: " . $e->getMessage());
        }
    }

    /** API: Lista projetos ativos */
    public function getProjectsList()
    {
        header('Content-Type: application/json; charset=UTF-8');
        
        global $pdo;
        try {
            $stmt = $pdo->query("
                SELECT id, name, client_id, status 
                FROM projects 
                WHERE status IN ('in_progress', 'pending')
                ORDER BY name ASC
            ");
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($projects);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao carregar projetos']);
        }
        exit;
    }
        /**
         * Aplica filtros de tempo aos registros
         */
        private function applyTimeFilter(array $entries, string $filter): array
        {
            $now = new DateTime();
            
            switch ($filter) {
                case 'today':
                    $today = $now->format('Y-m-d');
                    return array_filter($entries, function($entry) use ($today) {
                        return $entry['date'] === $today;
                    });
                    
                case 'week':
                    $startOfWeek = $now->modify('monday this week')->format('Y-m-d');
                    $endOfWeek = $now->modify('sunday this week')->format('Y-m-d');
                    return array_filter($entries, function($entry) use ($startOfWeek, $endOfWeek) {
                        return $entry['date'] >= $startOfWeek && $entry['date'] <= $endOfWeek;
                    });
                    
                case 'month':
                    $startOfMonth = $now->format('Y-m-01');
                    $endOfMonth = $now->format('Y-m-t');
                    return array_filter($entries, function($entry) use ($startOfMonth, $endOfMonth) {
                        return $entry['date'] >= $startOfMonth && $entry['date'] <= $endOfMonth;
                    });
                    
                default:
                    return $entries;
            }
        }

    /** NOVO: POST /work_logs/store_time_entry */
    public function store_time_entry()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            exit;
        }
        
        // Identifica funcionário pela sessão
        $email = $_SESSION['user']['email'] ?? '';
        $user = $this->userModel->findByEmail($email);
        $emp = $this->employeeModel->findByUserId((int)($user['id'] ?? 0));
        $empId = $emp['id'] ?? 0;
        
        $projId = max(0, (int)($_POST['project_id'] ?? 0));
        $date = $_POST['date'] ?? date('Y-m-d');
        $entryType = $_POST['entry_type'] ?? 'entry';
        $time = $_POST['time'] ?? date('H:i');
        
        if (!$empId || !$projId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
            exit;
        }
        
        $success = $this->timeEntryModel->addTimeEntry($empId, $projId, $date, $entryType, $time);
        
        if ($success) {
            // Atualiza total_hours na tabela projects (manter compatibilidade)
            $this->updateProjectTotalHours($projId);
            
            echo json_encode(['success' => true, 'message' => 'Ponto registrado com sucesso']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao registrar ponto']);
        }
        exit;
    }

    /**
     * NOVO: API: Admin atualiza registro de tempo  
     * PUT /api/work_logs/time_entries/{id}
     */
    public function updateTimeEntry($entryId = null)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            exit;
        }
        
        // Verifica se é admin
        if ($_SESSION['user']['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acesso negado']);
            exit;
        }
        
        // Parseia dados PUT
        parse_str(file_get_contents("php://input"), $_PUT);
        
        $id = $entryId ?? (int)($_PUT['id'] ?? 0);
        $projectId = (int)($_PUT['project_id'] ?? 0);
        $date = $_PUT['date'] ?? '';
        $entryTime = $_PUT['entry_time'] ?? '';
        $exitTime = $_PUT['exit_time'] ?? '';
        
        if (!$id || !$projectId || !$date) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Dados obrigatórios faltando']);
            exit;
        }
        
        try {
            global $pdo;
            
            // Monta novo registro JSON
            $records = ['entries' => []];
            
            if ($entryTime) {
                $records['entries'][] = ['type' => 'entry', 'time' => $entryTime];
            }
            
            if ($exitTime) {
                $records['entries'][] = ['type' => 'exit', 'time' => $exitTime];
            }
            
            // Calcula total de horas
            $totalHours = 0;
            if ($entryTime && $exitTime) {
                $start = strtotime($entryTime);
                $end = strtotime($exitTime);
                if ($end > $start) {
                    $totalHours = ($end - $start) / 3600; // Converte para horas
                }
            }
            
            // Atualiza registro
            $stmt = $pdo->prepare("
                UPDATE time_entries 
                SET project_id = ?, date = ?, time_records = ?, total_hours = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $success = $stmt->execute([
                $projectId,
                $date,
                json_encode($records, JSON_UNESCAPED_UNICODE),
                $totalHours,
                $id
            ]);
            
            if ($success) {
                // Atualiza totais do projeto
                $this->updateProjectTotalHours($projectId);
                echo json_encode(['success' => true, 'message' => 'Registro atualizado com sucesso']);
            } else {
                throw new Exception('Erro ao atualizar registro');
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
        }
        exit;
    }

    /** opcional, se precisar no admin (MANTIDO) */
    public function project_totals()
    {
        header('Content-Type: application/json; charset=UTF-8');
        $projId = max(0, (int)($_GET['project_id'] ?? 0));
        if (!$projId) {
            echo json_encode([]);
            exit;
        }
        $data = $this->workLogModel->getProjectTotals($projId);
        echo json_encode($data);
        exit;
    }
}