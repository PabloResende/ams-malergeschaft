<?php
// system/app/controllers/ProjectController.php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/Project.php';
require_once __DIR__ . '/../models/Clients.php';
require_once __DIR__ . '/../models/Employees.php';     // <-- plural, seu arquivo Employee
require_once __DIR__ . '/../models/User.php';          // para buscar pelo e-mail de login
require_once __DIR__ . '/../models/WorkLogModel.php';  // logs de horas

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

        $this->baseUrl      = BASE_URL;
        $this->projectModel = new ProjectModel();
    }

    public function index()
    {
        if (isEmployee()) {
            // pega usuário pelo e-mail de login
            $email     = $_SESSION['user']['email'] ?? '';
            $user      = (new UserModel())->findByEmail($email);
            $userId    = (int) ($user['id'] ?? 0);
            // encontra employee via user_id
            $emp       = (new Employee())->findByUserId($userId);
            $empId     = (int) ($emp['id'] ?? 0);
            $projects  = $this->projectModel->getByEmployee($empId);
        } else {
            $projects = $this->projectModel->getAll();
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
            'name'           => trim($_POST['name']           ?? ''),
            'client_id'      => $clientId,
            'location'       => trim($_POST['location']       ?? ''),
            'description'    => trim($_POST['description']    ?? ''),
            'start_date'     => $_POST['start_date']          ?? null,
            'end_date'       => $_POST['end_date']            ?? null,
            'total_hours'    => (int)($_POST['total_hours']    ?? 0),
            'budget'         => (float)($_POST['budget']       ?? 0),
            'employee_count' => (int)($_POST['employee_count'] ?? 0),
            'status'         => $_POST['status']              ?? 'pending',
            'progress'       => (int)($_POST['progress']       ?? 0),
        ];

        $tasks     = json_decode($_POST['tasks']     ?? '[]', true) ?: [];
        $employees = json_decode($_POST['employees'] ?? '[]', true) ?: [];

        if (ProjectModel::create($data, $tasks, $employees)) {
            $_SESSION['success'] = $this->langText['project_created'] ?? 'Projeto criado com sucesso.';
            header("Location: {$this->baseUrl}/projects");
            exit;
        }

        $_SESSION['error'] = $this->langText['error_saving_project'] ?? 'Erro ao salvar o projeto.';
        header("Location: {$this->baseUrl}/projects/create");
        exit;
    }

    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$this->baseUrl}/projects");
            exit;
        }

        global $pdo;

        $id     = max(0, (int)($_POST['id'] ?? 0));
        $oldCid = max(0, (int)($_POST['old_client_id'] ?? 0));
        $newCid = isset($_POST['client_id']) && $_POST['client_id'] !== ''
                  ? max(0, (int)$_POST['client_id'])
                  : null;

        $data = [
            'name'           => trim($_POST['name']        ?? ''),
            'client_id'      => $newCid,
            'location'       => trim($_POST['location']    ?? ''),
            'description'    => trim($_POST['description'] ?? ''),
            'start_date'     => $_POST['start_date']       ?? null,
            'end_date'       => $_POST['end_date']         ?? null,
            'total_hours'    => (int)($_POST['total_hours']    ?? 0),
            'budget'         => (float)($_POST['budget']       ?? 0),
            'employee_count' => (int)($_POST['employee_count'] ?? 0),
            'status'         => $_POST['status']              ?? 'pending',
            'progress'       => (int)($_POST['progress']       ?? 0),
        ];

        $tasks     = json_decode($_POST['tasks']     ?? '[]', true) ?: [];
        $employees = json_decode($_POST['employees'] ?? '[]', true) ?: [];

        if (! ProjectModel::update($id, $data, $tasks, $employees)) {
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
            echo json_encode(['error' => $this->langText['error_project_id_missing']]);
            exit;
        }

        $project = ProjectModel::find($id);
        if (!$project) {
            echo json_encode(['error' => $this->langText['error_project_not_found']]);
            exit;
        }

        $project['tasks']     = ProjectModel::getTasks($id);
        $project['employees'] = ProjectModel::getEmployees($id);
        $project['inventory'] = ProjectModel::getInventory($id);

        // logs de horas do funcionário logado
        $email   = $_SESSION['user']['email'] ?? '';
        $user    = (new UserModel())->findByEmail($email);
        $userId  = (int) ($user['id'] ?? 0);
        $emp     = (new Employee())->findByUserId($userId);
        $empId   = (int) ($emp['id'] ?? 0);
        $project['work_logs'] = (new WorkLogModel())->getByEmployeeAndProject($empId, $id);

        echo json_encode($project);
        exit;
    }

    public function checkEmployee()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $empId = max(0, (int)($_GET['id'] ?? 0));
        if (!$empId) {
            echo json_encode(['count' => 0]);
            exit;
        }

        $exclude = isset($_GET['project_id'])
            ? max(0, (int)$_GET['project_id'])
            : null;

        $count = ProjectModel::countProjectsByEmployee($empId, $exclude);
        echo json_encode(['count' => $count]);
        exit;
    }

    public function transactions()
    {
        header('Content-Type: application/json; charset=UTF-8');
        $id = max(0, (int)($_GET['id'] ?? 0));
        if (!$id) {
            echo json_encode([]);
            exit;
        }

        $transactions = ProjectModel::getTransactions($id);
        echo json_encode($transactions);
        exit;
    }
}
