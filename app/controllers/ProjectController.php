<?php
// app/controllers/ProjectController.php

require_once __DIR__ . '/../../config/Database.php';
// carrega a lógica de idioma (define $langText)
require_once __DIR__ . '/../lang/lang.php';

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
            'client_id'      => ($_POST['client_id'] !== '') ? (int)$_POST['client_id'] : null,
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

        $tasks     = json_decode($_POST['tasks'] ?? '[]', true);
        $employees = json_decode($_POST['employees'] ?? '[]', true);

        if (ProjectModel::create($data, $tasks, $employees)) {
            // atualiza pontos do cliente, se houver
            if ($data['client_id']) {
                $count = Client::countProjects($data['client_id']);
                Client::setPoints($data['client_id'], $count);
            }
            header("Location: /ams-malergeschaft/public/projects");
            exit;
        } else {
            echo $langText['error_saving_project'] ?? 'Erro ao salvar o projeto.';
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
            echo $langText['error_project_id_missing'] ?? 'ID do projeto não fornecido.';
            exit;
        }

        $existing = ProjectModel::find($id);
        if (!$existing) {
            echo $langText['error_project_not_found'] ?? 'Projeto não encontrado.';
            exit;
        }

        $clientId = ($_POST['client_id'] !== '') ? (int)$_POST['client_id'] : $existing['client_id'];

        $data = [
            'name'           => $_POST['name']           ?? $existing['name'],
            'client_id'      => $clientId,
            'location'       => $_POST['location']       ?? $existing['location'],
            'description'    => $_POST['description']    ?? $existing['description'],
            'start_date'     => $_POST['start_date']     ?? $existing['start_date'],
            'end_date'       => $_POST['end_date']       ?? $existing['end_date'],
            'total_hours'    => $_POST['total_hours']    ?? $existing['total_hours'],
            'budget'         => $_POST['budget']         ?? $existing['budget'],
            'employee_count' => $_POST['employee_count'] ?? $existing['employee_count'],
            'status'         => $_POST['status']         ?? $existing['status'],
            'progress'       => $_POST['progress']       ?? $existing['progress'],
        ];

        $tasks     = json_decode($_POST['tasks'] ?? '[]', true);
        $employees = json_decode($_POST['employees'] ?? '[]', true);

        if (ProjectModel::update($id, $data, $tasks, $employees)) {
            if ($data['client_id']) {
                $count = Client::countProjects($data['client_id']);
                Client::setPoints($data['client_id'], $count);
            }
            header("Location: /ams-malergeschaft/public/projects");
            exit;
        } else {
            echo $langText['error_updating_project'] ?? 'Erro ao atualizar o projeto.';
        }
    }

    public function show()
    {
        header('Content-Type: application/json; charset=UTF-8');
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            echo json_encode(['error' => $langText['error_project_id_missing'] ?? 'ID não fornecido']);
            exit;
        }

        $project = ProjectModel::find($id);
        if (!$project) {
            echo json_encode(['error' => $langText['error_project_not_found'] ?? 'Projeto não encontrado']);
            exit;
        }

        // traz tarefas, funcionários e inventário
        $tasks     = ProjectModel::getTasks($id);
        $employees = ProjectModel::getEmployees($id);
        $pdo       = Database::connect();
        $stmtInv   = $pdo->prepare("
            SELECT pr.quantity, i.name
            FROM project_resources pr
            JOIN inventory i ON pr.resource_id = i.id
            WHERE pr.project_id = ? AND pr.resource_type = 'inventory'
        ");
        $stmtInv->execute([$id]);
        $inventory = $stmtInv->fetchAll(PDO::FETCH_ASSOC);

        $project['tasks']     = $tasks;
        $project['employees'] = $employees;
        $project['inventory'] = $inventory;

        echo json_encode($project);
        exit;
    }

    public function checkEmployee()
    {
        header('Content-Type: application/json; charset=UTF-8');
        $empId = (int)($_GET['id'] ?? 0);
        if (!$empId) {
            echo json_encode(['error' => $langText['error_employee_id_missing'] ?? 'ID não fornecido']);
            exit;
        }
        $pdo = Database::connect();
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT pr.project_id) AS cnt
            FROM project_resources pr
            JOIN projects p ON pr.project_id = p.id
            WHERE pr.resource_type='employee'
              AND pr.resource_id=?
              AND p.status='in_progress'
        ");
        $stmt->execute([$empId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['count' => (int)$row['cnt']]);
        exit;
    }

    public function transactions()
    {
        header('Content-Type: application/json; charset=UTF-8');
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            echo json_encode([]);
            exit;
        }

        $pdo = Database::connect();
        $stmt = $pdo->prepare("
        SELECT 
            ft.date,
            ft.type,
            ft.amount,
            ft.category
        FROM financial_transactions ft
        WHERE 
            (ft.category = 'projetos' AND ft.project_id = ?)
            OR EXISTS(
            SELECT 1 
            FROM debts d
            WHERE d.transaction_id = ft.id
                AND d.project_id = ?
            )
        ORDER BY ft.date DESC
        ");
        $stmt->execute([$id, $id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    public function delete()
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            ProjectModel::delete($id);
        }
        header("Location: /ams-malergeschaft/public/projects");
        exit;
    }
}
