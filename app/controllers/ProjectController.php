<?php
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../models/Project.php';

class ProjectController {

    public function index() {
        require_once __DIR__ . '/../views/projects/index.php';
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /ams-malergeschaft/public/projects");
            exit;
        }

        $data = [
            'name'           => $_POST['name']           ?? '',
            'location'       => $_POST['location']       ?? '',
            'description'    => $_POST['description']    ?? '',
            'start_date'     => $_POST['start_date']     ?? '',
            'end_date'       => $_POST['end_date']       ?? '',
            'total_hours'    => $_POST['total_hours']    ?? 0,
            'budget'         => $_POST['budget']         ?? 0,
            'employee_count' => $_POST['employee_count'] ?? 0,
            'status'         => $_POST['status']         ?? 'in_progress',
            'progress'       => $_POST['progress']       ?? 0,
        ];

        // tasks vêm em JSON no hidden "tasks"
        $tasks     = json_decode($_POST['tasks']     ?? '[]', true);
        // employees vêm como array via name="employees[]"
        $employees = $_POST['employees']             ?? [];

        if (empty($data['name'])) {
            echo "O nome do projeto é obrigatório.";
            return;
        }

        if (ProjectModel::create($data, $tasks, $employees)) {
            header("Location: /ams-malergeschaft/public/projects");
            exit;
        } else {
            echo "Erro ao salvar o projeto.";
        }
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /ams-malergeschaft/public/projects");
            exit;
        }

        $id = $_POST['id'] ?? '';
        if (empty($id) || empty($_POST['name'])) {
            echo "Dados obrigatórios faltando.";
            return;
        }

        $data = [
            'name'           => $_POST['name'],
            'location'       => $_POST['location']       ?? '',
            'description'    => $_POST['description']    ?? '',
            'start_date'     => $_POST['start_date']     ?? '',
            'end_date'       => $_POST['end_date']       ?? '',
            'total_hours'    => $_POST['total_hours']    ?? 0,
            'budget'         => $_POST['budget']         ?? 0,
            'employee_count' => $_POST['employee_count'] ?? 0,
            'status'         => $_POST['status']         ?? 'in_progress',
            'progress'       => $_POST['progress']       ?? 0,
        ];

        if ($data['status'] === 'completed' && $data['progress'] < 100) {
            echo "Não é possível marcar como concluído até que todas as tasks estejam finalizadas.";
            return;
        }

        // tasks vêm em JSON no hidden "tasks"
        $tasks     = json_decode($_POST['tasks']     ?? '[]', true);
        // employees vêm como array via name="employees[]"
        $employees = $_POST['employees']             ?? [];

        if (ProjectModel::update($id, $data, $tasks, $employees)) {
            header("Location: /ams-malergeschaft/public/projects");
            exit;
        } else {
            echo "Erro ao atualizar o projeto.";
        }
    }

    public function delete() {
        if (!isset($_GET['id'])) {
            header("Location: /ams-malergeschaft/public/projects");
            exit;
        }

        $id = $_GET['id'];

        // sem restauração de inventário
        if (ProjectModel::delete($id)) {
            header("Location: /ams-malergeschaft/public/projects");
            exit;
        } else {
            echo "Erro ao deletar o projeto.";
        }
    }

    public function show() {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            exit(json_encode(["error" => "Missing project ID"]));
        }

        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
        $stmt->execute([$id]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$project) {
            http_response_code(404);
            exit(json_encode(["error" => "Project not found"]));
        }

        $taskStmt = $pdo->prepare("SELECT description, completed FROM tasks WHERE project_id = ?");
        $taskStmt->execute([$id]);
        $tasks = $taskStmt->fetchAll(PDO::FETCH_ASSOC);

        $empStmt = $pdo->prepare("
            SELECT e.id, e.name, e.last_name
            FROM employees e
            JOIN project_resources pr ON pr.resource_id = e.id
            WHERE pr.project_id = ? AND pr.resource_type = 'employee'
        ");
        $empStmt->execute([$id]);
        $employees = $empStmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode([
            'id'             => $project['id'],
            'name'           => $project['name'],
            'location'       => $project['location']       ?? '',
            'description'    => $project['description']    ?? '',
            'start_date'     => $project['start_date'],
            'end_date'       => $project['end_date'],
            'total_hours'    => $project['total_hours'],
            'budget'         => $project['budget']         ?? 0,
            'employee_count' => $project['employee_count'] ?? 0,
            'status'         => $project['status'],
            'tasks'          => $tasks,
            'employees'      => $employees
        ]);
    }
}
