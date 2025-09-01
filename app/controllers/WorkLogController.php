<?php
// system/app/controllers/WorkLogController.php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/Employees.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/WorkLogModel.php';

class WorkLogController
{
    private WorkLogModel $workLogModel;
    private Employee    $employeeModel;
    private UserModel   $userModel;
    private array       $langText;
    private string      $baseUrl;

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        global $pdo;
        $this->workLogModel  = new WorkLogModel();
        $this->employeeModel = new Employee();
        $this->userModel     = new UserModel();
        $this->baseUrl       = BASE_URL;

        // linguagem
        $lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'pt';
        $_SESSION['lang'] = $lang;
        $lf = __DIR__ . "/../lang/$lang.php";
        $this->langText = file_exists($lf) 
                        ? require $lf 
                        : require __DIR__ . '/../lang/pt.php';
    }

    /** GET /work_logs?project_id=… */
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
            $user  = $this->userModel->findByEmail($email);
            $emp   = $this->employeeModel->findByUserId((int)($user['id'] ?? 0));
            $empId = $emp['id'] ?? 0;
        }


        $logs = $this->workLogModel->getByEmployeeAndProject($empId, $projId);
        echo json_encode($logs);
        exit;
    }

    /** POST /work_logs/store */
  // WorkLogController.php (método store)
public function store()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Método não permitido']);
        exit;
    }

    // identifica funcionário pela sessão
    $email  = $_SESSION['user']['email'] ?? '';
    $user   = $this->userModel->findByEmail($email);
    $emp    = $this->employeeModel->findByUserId((int)($user['id'] ?? 0));
    $empId  = $emp['id'] ?? 0;

    $projId = max(0, (int)($_POST['project_id'] ?? 0));
    $hours  = floatval($_POST['hours'] ?? 0);
    $date   = $_POST['date'] ?? date('Y-m-d');

    if (!$empId || !$projId || $hours <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Dados inválidos.']);
        exit;
    }

    $ok = $this->workLogModel->create([
        'employee_id' => $empId,
        'project_id'  => $projId,
        'hours'       => $hours,
        'date'        => $date
    ]);

    if (!$ok) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erro ao salvar horas.']);
        exit;
    }

    echo json_encode(['success' => true]);
    exit;
}

    /** opcional, se precisar no admin */
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
