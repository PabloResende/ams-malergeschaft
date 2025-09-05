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

    public function addTimeEntry()
    {
        header('Content-Type: application/json; charset=UTF-8');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            exit;
        }
        
        if (!isEmployee()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acesso negado']);
            exit;
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $projectId = (int)($input['project_id'] ?? 0);
            $date = trim($input['date'] ?? '');
            $time = trim($input['time'] ?? '');
            $type = trim($input['type'] ?? '');
            
            if (!$projectId || !$date || !$time || !in_array($type, ['entry', 'exit'])) {
                echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
                exit;
            }
            
            global $pdo;
            $userId = $_SESSION['user']['id'];
            
            $empStmt = $pdo->prepare("SELECT id FROM employees WHERE user_id = ?");
            $empStmt->execute([$userId]);
            $emp = $empStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$emp) {
                echo json_encode(['success' => false, 'message' => 'Funcionário não encontrado']);
                exit;
            }
            
            $employeeId = $emp['id'];
            $success = $this->timeEntryModel->addTimeEntry($employeeId, $projectId, $date, $type, $time);
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Ponto registrado com sucesso']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao registrar ponto']);
            }
            
        } catch (Exception $e) {
            error_log("WorkLogController::addTimeEntry error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro interno']);
        }
        exit;
    }

   /**
     * API: Lista registros de ponto de um funcionário em um projeto
     * GET /api/worklog/time-entries?project_id=X
     */
    public function time_entries()
    {
        header('Content-Type: application/json; charset=UTF-8');
        
        if (!isEmployee()) {
            echo json_encode(['entries' => [], 'total_hours' => 0]);
            exit;
        }
        
        $projectId = max(1, (int)($_GET['project_id'] ?? 0));
        $filter = $_GET['filter'] ?? 'all';
        
        try {
            global $pdo;
            $userId = $_SESSION['user']['id'];
            
            // Busca employee_id
            $empStmt = $pdo->prepare("SELECT id FROM employees WHERE user_id = ?");
            $empStmt->execute([$userId]);
            $emp = $empStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$emp) {
                echo json_encode(['entries' => [], 'total_hours' => 0]);
                exit;
            }
            
            $employeeId = $emp['id'];
            
            // Busca registros usando o método corrigido
            $entries = $this->workLogModel->getByEmployee($employeeId, $projectId);
            
            // Aplicar filtros se necessário
            if ($filter !== 'all') {
                $entries = $this->applyTimeFilter($entries, $filter);
            }
            
            $totalHours = array_sum(array_column($entries, 'hours'));
            
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

    public function deleteTimeEntry()
    {
        header('Content-Type: application/json; charset=UTF-8');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $entryId = $input['entry_id'] ?? '';
        
        if (!$entryId) {
            echo json_encode(['success' => false, 'message' => 'ID não informado']);
            exit;
        }
        
        try {
            global $pdo;
            
            if (strpos($entryId, 'old_') === 0) {
                $realId = str_replace('old_', '', $entryId);
                $stmt = $pdo->prepare("DELETE FROM project_work_logs WHERE id = ?");
                $success = $stmt->execute([$realId]);
            } else {
                $stmt = $pdo->prepare("DELETE FROM time_entries WHERE id = ?");
                $success = $stmt->execute([$entryId]);
            }
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Registro excluído com sucesso']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao excluir registro']);
            }
            
        } catch (Exception $e) {
            error_log("WorkLogController::deleteTimeEntry error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
        }
        exit;
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
     * NOVO: API: Lista todos os projetos (para dropdown de admin)
     * GET /api/projects/list
     */
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
            $projects = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            echo json_encode($projects);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao carregar projetos']);
        }
        exit;
    }

    /**
     * NOVO: API: Admin cria/edita registro de tempo
     * POST /api/work_logs/admin_time_entry
     */
    public function adminCreateTimeEntry()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
        
        $employeeId = (int)($_POST['employee_id'] ?? 0);
        $projectId = (int)($_POST['project_id'] ?? 0);
        $date = $_POST['date'] ?? date('Y-m-d');
        $entryTime = $_POST['entry_time'] ?? '';
        $exitTime = $_POST['exit_time'] ?? '';
        
        if (!$employeeId || !$projectId || !$date) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Dados obrigatórios faltando']);
            exit;
        }
        
        try {
            // Cria os registros de entrada e saída
            $success = true;
            
            if ($entryTime) {
                $success &= $this->timeEntryModel->addTimeEntry($employeeId, $projectId, $date, 'entry', $entryTime);
            }
            
            if ($exitTime && $success) {
                $success &= $this->timeEntryModel->addTimeEntry($employeeId, $projectId, $date, 'exit', $exitTime);
            }
            
            if ($success) {
                // Atualiza totais do projeto
                $this->updateProjectTotalHours($projectId);
                echo json_encode(['success' => true, 'message' => 'Registro criado com sucesso']);
            } else {
                throw new Exception('Erro ao salvar registros');
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
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

    private function updateProjectTotalHours(int $projectId): void 
    {
        // Calcula total de todas as time_entries deste projeto
        $projectTotal = $this->timeEntryModel->getTotalHoursByProject($projectId);
        
        // Atualiza tabela projects
        global $pdo;
        $updateStmt = $pdo->prepare("UPDATE projects SET total_hours = ? WHERE id = ?");
        $updateStmt->execute([$projectTotal, $projectId]);
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