<?php

class ProjectController {

    public function index() {
        require_once __DIR__ . '/../views/projects/index.php';
    }
    public function create() {
        require_once __DIR__ . '/../views/create_project/create.php';
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $name = $_POST['name'] ?? '';
            $client_name = $_POST['client_name'] ?? '';
            $description = $_POST['description'] ?? '';
            $end_date = $_POST['end_date'] ?? '';
            $start_date = $_POST['start_date'] ?? '';
            $total_hours = $_POST['total_hours'] ?? 0;
            $status = $_POST['status'] ?? 'in_progress';
            $progress = $_POST['progress'] ?? 0;

            if (empty($name)) {
                echo "O nome do projeto é obrigatório.";
                return;
            }

            require_once __DIR__ . '/../../config/Database.php';
            $pdo = Database::connect();

            $stmt = $pdo->prepare("INSERT INTO projects (name, client_name, description, end_date, start_date, total_hours, status, progress, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            if ($stmt->execute([$name, $client_name, $description, $end_date, $start_date, $total_hours, $status, $progress])) {
                header("Location: /ams-malergeschaft/public/projects");
                exit;
            } else {
                echo "Erro ao salvar o projeto.";
            }
        } else {
            header("Location: /ams-malergeschaft/public/create_project");
            exit;
        }
    }

    public function show() {
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            header("Location: /ams-malergeschaft/public/projects");
            exit;
        }

        $id = $_GET['id'];

        require_once __DIR__ . '/../../config/Database.php';
        $pdo = Database::connect();

        // Busca o projeto pelo ID
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
        $stmt->execute([$id]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$project) {
            http_response_code(404);
            echo "Projeto não encontrado.";
            exit;
        }

        require_once __DIR__ . '/../views/projects/index.php';
    }
}
