<?php
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../models/Project.php';
require_once __DIR__ . '/../models/Clients.php';
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

        $tasks     = json_decode($_POST['tasks']     ?? '[]', true);
        $employees = json_decode($_POST['employees'] ?? '[]', true);

        // if (ProjectModel::create($data, $tasks, $employees)) {
        //     if ($clientId) {
        //         $count = Client::countProjects($clientId);
        //         Client::setPoints($clientId, $count);
        //     }
        //     header("Location: /ams-malergeschaft/public/projects");
        //     exit;
        // }

        echo $langText['error_saving_project'] ?? 'Erro ao salvar o projeto.';
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

        $clientId = isset($_POST['client_id']) && $_POST['client_id'] !== ''
            ? max(0, (int)$_POST['client_id'])
            : $existing['client_id'];

        $data = [
            'name'           => trim($_POST['name']        ?? $existing['name']),
            'client_id'      => $clientId,
            'location'       => trim($_POST['location']    ?? $existing['location']),
            'description'    => trim($_POST['description'] ?? $existing['description']),
            'start_date'     => $_POST['start_date']       ?? $existing['start_date'],
            'end_date'       => $_POST['end_date']         ?? $existing['end_date'],
            'total_hours'    => (int)($_POST['total_hours']    ?? $existing['total_hours']),
            'budget'         => (float)($_POST['budget']       ?? $existing['budget']),
            'employee_count' => (int)($_POST['employee_count'] ?? $existing['employee_count']),
            'status'         => $_POST['status']              ?? $existing['status'],
            'progress'       => (int)($_POST['progress']       ?? $existing['progress']),
        ];

        $tasks = json_decode($_POST['tasks'] ?? '[]', true);
        if (!is_array($tasks)) {
            $tasks = [];
        }

        $employees = json_decode($_POST['employees'] ?? '[]', true);
        if (!is_array($employees)) {
            $employees = [];
        }

        // if (ProjectModel::update($id, $data, $tasks, $employees)) {
        //     if ($clientId) {
        //         $count = Client::countProjects($clientId);
        //         Client::setPoints($clientId, $count);
        //     }
        //     header("Location: /ams-malergeschaft/public/projects");
        //     exit;
        // }

        echo $langText['error_updating_project'] ?? 'Erro ao atualizar o projeto.';
    }

    public function show()
    {
        header('Content-Type: application/json; charset=UTF-8');
        $id = max(0, (int)($_GET['id'] ?? 0));
        if (!$id) {
            echo json_encode(['error' => $langText['error_project_id_missing']]);
            exit;
        }

        $project = ProjectModel::find($id);
        if (!$project) {
            echo json_encode(['error' => $langText['error_project_not_found']]);
            exit;
        }

        $project['tasks']     = ProjectModel::getTasks($id);
        $project['employees'] = ProjectModel::getEmployees($id);
        $project['inventory'] = ProjectModel::getInventory($id);

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

        // Se veio project_id (na edição), exclui-o do cálculo:
        $excludeProj = isset($_GET['project_id'])
            ? max(0, (int)$_GET['project_id'])
            : null;

        // Usa a nova assinatura que já exclui completed
        $count = ProjectModel::countProjectsByEmployee($empId, $excludeProj);

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

    public function delete()
    {
        $id = max(0, (int)($_GET['id'] ?? 0));
        if ($id) {
            ProjectModel::delete($id);
        }
        header("Location: /ams-malergeschaft/public/projects");
        exit;
    }
}
