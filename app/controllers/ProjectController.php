<?php
// app/controllers/ProjectController.php

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../models/Project.php';
require_once __DIR__ . '/../models/Clients.php';

class ProjectController
{
    public function index()
    {
        $projects = ProjectModel::getAll();
        $clients  = Client::all();
        require_once __DIR__ . '/../views/projects/index.php';
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /ams-malergeschaft/public/projects");
            exit;
        }

        $data = [
            'name'           => $_POST['name']           ?? '',
            'client_id'      => isset($_POST['client_id']) && $_POST['client_id'] !== '' ? (int)$_POST['client_id'] : null,
            'location'       => $_POST['location']       ?? '',
            'description'    => $_POST['description']    ?? '',
            'start_date'     => $_POST['start_date']     ?? null,
            'end_date'       => $_POST['end_date']       ?? null,
            'total_hours'    => $_POST['total_hours']    ?? 0,
            'budget'         => $_POST['budget']         ?? 0,
            'employee_count' => $_POST['employee_count'] ?? 0,
            'status'         => $_POST['status']         ?? 'pending',
            'progress'       => $_POST['progress']       ?? 0,
        ];

        $tasks     = json_decode($_POST['tasks']     ?? '[]', true);
        $employees = json_decode($_POST['employees'] ?? '[]', true);

        if (ProjectModel::create($data, $tasks, $employees)) {
            if ($data['client_id']) {
                $count = Client::countProjects($data['client_id']);
                Client::setPoints($data['client_id'], $count);
            }
            header("Location: /ams-malergeschaft/public/projects");
            exit;
        } else {
            echo "Erro ao salvar o projeto.";
        }
    }

    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /ams-malergeschaft/public/projects");
            exit;
        }

        $id = $_POST['id'] ?? null;
        if (!$id) {
            echo "ID do projeto não fornecido.";
            exit;
        }

        $existing = ProjectModel::find($id);
        if (!$existing) {
            echo "Projeto não encontrado.";
            exit;
        }

        $clientId = isset($_POST['client_id']) && $_POST['client_id'] !== ''
                    ? (int)$_POST['client_id']
                    : $existing['client_id'];

        $startDate = !empty($_POST['start_date'])
                     ? $_POST['start_date']
                     : $existing['start_date'];
        $endDate   = !empty($_POST['end_date'])
                     ? $_POST['end_date']
                     : $existing['end_date'];

        $data = [
            'name'           => $_POST['name']           ?? $existing['name'],
            'client_id'      => $clientId,
            'location'       => $_POST['location']       ?? $existing['location'],
            'description'    => $_POST['description']    ?? $existing['description'],
            'start_date'     => $startDate,
            'end_date'       => $endDate,
            'total_hours'    => $_POST['total_hours']    ?? $existing['total_hours'],
            'budget'         => $_POST['budget']         ?? $existing['budget'],
            'employee_count' => $_POST['employee_count'] ?? $existing['employee_count'],
            'status'         => $_POST['status']         ?? $existing['status'],
            'progress'       => $_POST['progress']       ?? $existing['progress'],
        ];

        $tasks     = json_decode($_POST['tasks']     ?? '[]', true);
        $employees = json_decode($_POST['employees'] ?? '[]', true);

        if (ProjectModel::update($id, $data, $tasks, $employees)) {
            if ($data['client_id']) {
                $count = Client::countProjects($data['client_id']);
                Client::setPoints($data['client_id'], $count);
            }
            header("Location: /ams-malergeschaft/public/projects");
            exit;
        } else {
            echo "Erro ao atualizar o projeto.";
        }
    }

    public function show()
    {
        header('Content-Type: application/json; charset=UTF-8');
        if (!isset($_GET['id'])) {
            echo json_encode(['error' => 'ID não fornecido']);
            exit;
        }
        $id = (int) $_GET['id'];

        $project = ProjectModel::find($id);
        if (!$project) {
            echo json_encode(['error' => 'Projeto não encontrado']);
            exit;
        }

        $pdo = Database::connect();

        $stmt = $pdo->prepare("SELECT id, description, completed FROM tasks WHERE project_id = ?");
        $stmt->execute([$id]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("
            SELECT pr.resource_id AS id, e.name, e.last_name
            FROM project_resources pr
            JOIN employees e ON pr.resource_id = e.id
            WHERE pr.project_id = ? AND pr.resource_type = 'employee'
        ");
        $stmt->execute([$id]);
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("
            SELECT pr.quantity, i.name
            FROM project_resources pr
            JOIN inventory i ON pr.resource_id = i.id
            WHERE pr.project_id = ? AND pr.resource_type = 'inventory'
        ");
        $stmt->execute([$id]);
        $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $output = $project;
        $output['tasks']     = $tasks;
        $output['employees'] = $employees;
        $output['inventory'] = $inventory;

        echo json_encode($output);
        exit;
    }

    public function checkEmployee()
    {
        header('Content-Type: application/json; charset=UTF-8');
        if (!isset($_GET['id'])) {
            echo json_encode(['error' => 'ID não fornecido']);
            exit;
        }
        $empId = (int) $_GET['id'];
        $pdo = Database::connect();
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT pr.project_id) AS cnt
            FROM project_resources pr
            JOIN projects p ON pr.project_id = p.id
            WHERE pr.resource_type = 'employee'
              AND pr.resource_id = ?
              AND p.status = 'in_progress'
        ");
        $stmt->execute([$empId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $count = $row['cnt'] ?? 0;
        echo json_encode(['count' => (int)$count]);
        exit;
    }

    public function delete()
    {
        if (!isset($_GET['id'])) {
            echo "ID do projeto não fornecido.";
            exit;
        }

        if (ProjectModel::delete($_GET['id'])) {
            header("Location: /ams-malergeschaft/public/projects");
            exit;
        } else {
            echo "Erro ao deletar o projeto.";
        }
    }
}
