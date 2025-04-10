<?php

require_once __DIR__ . '/../models/Project.php';

class ProjectController {

    public function index() {
        require_once __DIR__ . '/../views/projects/index.php';
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = [
                'name' => $_POST['name'] ?? '',
                'client_name' => $_POST['client_name'] ?? '',
                'description' => $_POST['description'] ?? '',
                'end_date' => $_POST['end_date'] ?? '',
                'start_date' => $_POST['start_date'] ?? '',
                'total_hours' => $_POST['total_hours'] ?? 0,
                'status' => $_POST['status'] ?? 'in_progress',
                'progress' => $_POST['progress'] ?? 0
            ];

            if (empty($data['name'])) {
                echo "O nome do projeto é obrigatório.";
                return;
            }

            if (ProjectModel::create($data)) {
                header("Location: /ams-malergeschaft/public/projects");
                exit;
            } else {
                echo "Erro ao salvar o projeto.";
            }
        } else {
            header("Location: /ams-malergeschaft/public/projects");
            exit;
        }
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id = $_POST['id'] ?? '';
            if (empty($id) || empty($_POST['name'])) {
                echo "Dados obrigatórios faltando.";
                return;
            }

            $data = [
                'name' => $_POST['name'],
                'client_name' => $_POST['client_name'] ?? '',
                'description' => $_POST['description'] ?? '',
                'end_date' => $_POST['end_date'] ?? '',
                'start_date' => $_POST['start_date'] ?? '',
                'total_hours' => $_POST['total_hours'] ?? 0,
                'status' => $_POST['status'] ?? 'in_progress',
                'progress' => $_POST['progress'] ?? 0
            ];

            if (ProjectModel::update($id, $data)) {
                header("Location: /ams-malergeschaft/public/projects");
                exit;
            } else {
                echo "Erro ao atualizar o projeto.";
            }
        } else {
            header("Location: /ams-malergeschaft/public/projects");
            exit;
        }
    }

    public function delete() {
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            if (ProjectModel::delete($id)) {
                header("Location: /ams-malergeschaft/public/projects");
                exit;
            } else {
                echo "Erro ao deletar o projeto.";
            }
        } else {
            header("Location: /ams-malergeschaft/public/projects");
            exit;
        }
    }
}
