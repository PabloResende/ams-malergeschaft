<?php
// app/controllers/ProjectController.php - VERSÃO CORRIGIDA COMPLETA

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/Project.php';
require_once __DIR__ . '/../models/Clients.php';
require_once __DIR__ . '/../models/Employees.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/WorkLogModel.php';

class ProjectController
{
    private ProjectModel $projectModel;
    private array $langText;
    private string $baseUrl;

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'pt';
        $_SESSION['lang'] = $lang;
        $lf = __DIR__ . '/../lang/' . $lang . '.php';
        $this->langText = file_exists($lf)
            ? require $lf
            : require __DIR__ . '/../lang/pt.php';

        $this->baseUrl = BASE_URL;
        $this->projectModel = new ProjectModel();
    }

    public function index()
    {
        global $pdo;
        
        if (isEmployee()) {
            // Pega usuário pelo e-mail de login
            $email = $_SESSION['user']['email'] ?? '';
            $user = (new UserModel())->findByEmail($email);
            $userId = (int) ($user['id'] ?? 0);
            // Encontra employee via user_id
            $emp = (new Employee())->findByUserId($userId);
            $empId = (int) ($emp['id'] ?? 0);
            $projects = $this->projectModel->getByEmployee($empId);
        } else {
            $projects = $this->projectModel->getAll();
        }

        // Buscar clientes ativos para o formulário
        try {
            $stmt = $pdo->query("SELECT id, name FROM client WHERE active = 1 ORDER BY name");
            $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $clients = [];
        }

        // Buscar funcionários ativos para o formulário
        try {
            $stmt = $pdo->query("SELECT id, name, last_name FROM employees WHERE active = 1 ORDER BY name");
            $activeEmployees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $activeEmployees = [];
        }

        require __DIR__ . '/../views/projects/index.php';
    }

    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$this->baseUrl}/projects");
            exit;
        }

        $clientId = isset($_POST['client_id']) && $_POST['client_id'] !== ''
            ? max(0, (int)$_POST['client_id'])
            : null;

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'client_id' => $clientId,
            'location' => trim($_POST['location'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'start_date' => $_POST['start_date'] ?? null,
            'end_date' => $_POST['end_date'] ?? null,
            'total_hours' => (int)($_POST['total_hours'] ?? 0),
            'budget' => (float)($_POST['budget'] ?? 0),
            'employee_count' => (int)($_POST['employee_count'] ?? 0),
            'status' => $_POST['status'] ?? 'pending',
            'progress' => (int)($_POST['progress'] ?? 0),
        ];

        $tasks = json_decode($_POST['tasks'] ?? '[]', true) ?: [];
        $employees = json_decode($_POST['employees'] ?? '[]', true) ?: [];

        if (!ProjectModel::create($data, $tasks, $employees)) {
            $_SESSION['error'] = $this->langText['error_creating_project'] ?? 'Erro ao criar projeto.';
            header("Location: {$this->baseUrl}/projects");
            exit;
        }

        $_SESSION['success'] = $this->langText['project_created'] ?? 'Projeto criado com sucesso.';
        header("Location: {$this->baseUrl}/projects");
        exit;
    }

    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$this->baseUrl}/projects");
            exit;
        }

        global $pdo;
        $id = max(0, (int)($_POST['id'] ?? 0));
        if (!$id) {
            header("Location: {$this->baseUrl}/projects");
            exit;
        }

        // Cliente antigo (para pontos de fidelidade)
        $stmt = $pdo->prepare("SELECT client_id FROM projects WHERE id = ?");
        $stmt->execute([$id]);
        $oldCid = (int) ($stmt->fetchColumn() ?: 0);

        $newCid = isset($_POST['client_id']) && $_POST['client_id'] !== ''
            ? max(0, (int)$_POST['client_id'])
            : null;

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'client_id' => $newCid,
            'location' => trim($_POST['location'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'start_date' => $_POST['start_date'] ?? null,
            'end_date' => $_POST['end_date'] ?? null,
            'total_hours' => (int)($_POST['total_hours'] ?? 0),
            'budget' => (float)($_POST['budget'] ?? 0),
            'employee_count' => (int)($_POST['employee_count'] ?? 0),
            'status' => $_POST['status'] ?? 'pending',
            'progress' => (int)($_POST['progress'] ?? 0),
        ];

        $tasks = json_decode($_POST['tasks'] ?? '[]', true) ?: [];
        $employees = json_decode($_POST['employees'] ?? '[]', true) ?: [];

        if (!ProjectModel::update($id, $data, $tasks, $employees)) {
            $_SESSION['error'] = $this->langText['error_updating_project'] ?? 'Erro ao atualizar projeto.';
            header("Location: {$this->baseUrl}/projects");
            exit;
        }

        if ($newCid && $newCid !== $oldCid) {
            $pdo->prepare("UPDATE client SET loyalty_points = loyalty_points + 1 WHERE id = ?")
                ->execute([$newCid]);
            $pdo->prepare("UPDATE client SET loyalty_points = loyalty_points - 1 WHERE id = ?")
                ->execute([$oldCid]);
        }

        $_SESSION['success'] = $this->langText['project_updated'] ?? 'Projeto atualizado com sucesso.';
        header("Location: {$this->baseUrl}/projects");
        exit;
    }

    public function delete(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            ProjectModel::delete($id);
        }
        header("Location: {$this->baseUrl}/projects");
        exit;
    }

    public function show()
    {
        header('Content-Type: application/json; charset=UTF-8');
        $id = max(0, (int)($_GET['id'] ?? 0));
        if (!$id) {
            echo json_encode(['error' => 'ID do projeto é obrigatório']);
            exit;
        }

        try {
            global $pdo;
            
            // Buscar dados do projeto com cliente
            $stmt = $pdo->prepare("
                SELECT p.*, c.name as client_name 
                FROM projects p 
                LEFT JOIN client c ON p.client_id = c.id 
                WHERE p.id = ?
            ");
            $stmt->execute([$id]);
            $project = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$project) {
                echo json_encode(['error' => 'Projeto não encontrado']);
                exit;
            }

            // Buscar tarefas do projeto
            $stmt = $pdo->prepare("
                SELECT id, description, completed, created_at
                FROM tasks 
                WHERE project_id = ? 
                ORDER BY id ASC
            ");
            $stmt->execute([$id]);
            $project['tasks'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // ✅ CORRIGIDO: Buscar funcionários usando project_resources
            try {
                $stmt = $pdo->prepare("
                    SELECT e.id, e.name, e.last_name
                    FROM employees e
                    INNER JOIN project_resources pr ON e.id = pr.resource_id
                    WHERE pr.project_id = ? AND pr.resource_type = 'employee'
                    ORDER BY e.name
                ");
                $stmt->execute([$id]);
                $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log("Erro ao buscar funcionários: " . $e->getMessage());
                $employees = [];
            }
            
            $project['employees'] = $employees;

            // Buscar inventário do projeto (se existir)
            try {
                $stmt = $pdo->prepare("
                    SELECT im.*, i.name as item_name
                    FROM inventory_movements im
                    LEFT JOIN inventory i ON im.inventory_id = i.id
                    WHERE im.project_id = ?
                    ORDER BY im.datetime DESC
                ");
                $stmt->execute([$id]);
                $project['inventory'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $project['inventory'] = [];
            }

            // Buscar work logs do funcionário logado (sistema híbrido)
            $workLogs = [];
            
            // Obter funcionário logado
            $email = $_SESSION['user']['email'] ?? '';
            $empId = 0;
            
            if ($email) {
                try {
                    $stmt = $pdo->prepare("
                        SELECT e.id FROM employees e 
                        INNER JOIN users u ON e.user_id = u.id 
                        WHERE u.email = ?
                    ");
                    $stmt->execute([$email]);
                    $emp = $stmt->fetch(PDO::FETCH_ASSOC);
                    $empId = (int) ($emp['id'] ?? 0);
                } catch (Exception $e) {
                    // Usuário pode não ter funcionário associado
                }
            }
            
            if ($empId > 0) {
                // Work logs do sistema antigo
                try {
                    $stmt = $pdo->prepare("
                        SELECT id, hours, date, created_at
                        FROM project_work_logs
                        WHERE employee_id = ? AND project_id = ?
                        ORDER BY date DESC
                    ");
                    $stmt->execute([$empId, $id]);
                    $oldLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($oldLogs as $log) {
                        $workLogs[] = [
                            'id' => 'old_' . $log['id'],
                            'date' => $log['date'],
                            'hours' => (float) $log['hours'],
                            'description' => 'Sistema Antigo - ' . $log['hours'] . 'h',
                            'type' => 'old'
                        ];
                    }
                } catch (Exception $e) {
                    // Sistema antigo pode não existir
                }
                
                // Time entries do sistema novo
                try {
                    $stmt = $pdo->prepare("
                        SELECT id, date, total_hours, created_at
                        FROM time_entries
                        WHERE employee_id = ? AND project_id = ?
                        ORDER BY date DESC
                    ");
                    $stmt->execute([$empId, $id]);
                    $newLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($newLogs as $log) {
                        $workLogs[] = [
                            'id' => 'new_' . $log['id'],
                            'date' => $log['date'],
                            'hours' => (float) $log['total_hours'],
                            'description' => 'Sistema Novo - ' . $log['total_hours'] . 'h',
                            'type' => 'new'
                        ];
                    }
                } catch (Exception $e) {
                    // Sistema novo pode não existir
                }
            }
            
            $project['work_logs'] = $workLogs;
            
            echo json_encode($project);

        } catch (Exception $e) {
            error_log("Erro no ProjectController::show: " . $e->getMessage());
            echo json_encode(['error' => 'Erro interno do servidor']);
        }
        exit;
    }

    // ADICIONAR/CORRIGIR o método getProjectDetails
    public function getProjectDetails($projectId = null)
    {
        header('Content-Type: application/json; charset=UTF-8');
        
        $projectId = $projectId ?? (int)($_GET['id'] ?? 0);
        
        if (!$projectId) {
            echo json_encode(['error' => 'ID do projeto não fornecido']);
            exit;
        }
        
        try {
            global $pdo;
            
            // Busca dados do projeto
            $stmt = $pdo->prepare("
                SELECT p.*, c.name as client_name
                FROM projects p
                LEFT JOIN client c ON p.client_id = c.id
                WHERE p.id = ?
            ");
            $stmt->execute([$projectId]);
            $project = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$project) {
                echo json_encode(['error' => 'Projeto não encontrado']);
                exit;
            }
            
            // NOVO: Calcula total de horas do sistema novo (entrada/saída)
            $stmt = $pdo->prepare("
                SELECT COALESCE(SUM(total_hours), 0) as new_system_hours
                FROM time_entries 
                WHERE project_id = ?
            ");
            $stmt->execute([$projectId]);
            $newSystemHours = (float)$stmt->fetchColumn();
            
            // Total de horas do sistema antigo (horas diretas)
            $stmt = $pdo->prepare("
                SELECT COALESCE(SUM(hours), 0) as old_system_hours
                FROM work_logs 
                WHERE project_id = ?
            ");
            $stmt->execute([$projectId]);
            $oldSystemHours = (float)$stmt->fetchColumn();
            
            // Soma total
            $totalProjectHours = $newSystemHours + $oldSystemHours;
            
            // Busca funcionários que trabalharam no projeto
            $stmt = $pdo->prepare("
                SELECT DISTINCT
                    e.id,
                    e.name,
                    e.last_name,
                    COALESCE(SUM(te.total_hours), 0) as hours_worked
                FROM time_entries te
                LEFT JOIN employees e ON te.employee_id = e.id
                WHERE te.project_id = ?
                GROUP BY e.id, e.name, e.last_name
                ORDER BY hours_worked DESC
            ");
            $stmt->execute([$projectId]);
            $employeesHours = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Adiciona horas totais ao projeto
            $project['total_hours_calculated'] = number_format($totalProjectHours, 2, '.', '');
            $project['employees_hours'] = $employeesHours;
            
            error_log("Project $projectId - Total hours: $totalProjectHours");
            
            echo json_encode([
                'success' => true,
                'project' => $project
            ]);
            
        } catch (Exception $e) {
            error_log("ProjectController::getProjectDetails error: " . $e->getMessage());
            echo json_encode(['error' => 'Erro interno do servidor']);
        }
        exit;
    }

    /** API: Lista projetos ativos para seleção */
    public function getActiveProjects()
    {
        header('Content-Type: application/json; charset=UTF-8');
        
        try {
            global $pdo;
            
            // Busca projetos ativos
            $stmt = $pdo->prepare("
                SELECT 
                    id, 
                    name, 
                    client_id, 
                    status,
                    description
                FROM projects 
                WHERE status IN ('in_progress', 'pending', 'active')
                ORDER BY name ASC
            ");
            
            $stmt->execute();
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Log para debug
            error_log("ProjectController::getActiveProjects - Encontrados " . count($projects) . " projetos");
            
            echo json_encode($projects);
            
        } catch (Exception $e) {
            error_log("ProjectController::getActiveProjects error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'error' => 'Erro ao carregar projetos',
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    // ✅ NOVO MÉTODO: API para projetos de um funcionário
    public function getProjectsByEmployee(int $employeeId)
    {
        header('Content-Type: application/json; charset=UTF-8');
        
        try {
            global $pdo;
            $stmt = $pdo->prepare("
                SELECT DISTINCT p.id, p.name, p.description, p.status 
                FROM projects p
                INNER JOIN project_resources pr ON p.id = pr.project_id
                WHERE pr.resource_id = ? AND pr.resource_type = 'employee'
                AND p.status IN ('pending', 'in_progress')
                ORDER BY p.name
            ");
            $stmt->execute([$employeeId]);
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($projects);
        } catch (Exception $e) {
            echo json_encode([]);
        }
        exit;
    }

    // MANTIDO PARA COMPATIBILIDADE
    public function get()
    {
        return $this->show();
    }
}