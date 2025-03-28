<?php
class ProjectController {

    public function index() {
        require_once __DIR__ . '/../views/projects/index.php';
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

            if (!empty($_POST['employees'])) {
                foreach ($_POST['employees'] as $employeeId) {
                    $stmt = $pdo->prepare("INSERT INTO project_resources (project_id, resource_type, resource_id) VALUES (?, 'employee', ?)");
                    $stmt->execute([$projectId, $employeeId]);
                }
            }
            
            // Processar alocação de itens do inventário
            if (!empty($_POST['inventory_items'])) {
                foreach ($_POST['inventory_items'] as $inventoryId => $quantity) {
                    $stmt = $pdo->prepare("INSERT INTO project_resources (project_id, resource_type, resource_id, quantity) VALUES (?, 'inventory', ?, ?)");
                    $stmt->execute([$projectId, $inventoryId, $quantity]);
                }
            }

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
            header("Location: /ams-malergeschaft/public/projects");
            exit;
        }
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id = $_POST['id'] ?? '';
            $name = $_POST['name'] ?? '';
            $client_name = $_POST['client_name'] ?? '';
            $description = $_POST['description'] ?? '';
            $end_date = $_POST['end_date'] ?? '';
            $start_date = $_POST['start_date'] ?? '';
            $total_hours = $_POST['total_hours'] ?? 0;
            $status = $_POST['status'] ?? 'in_progress';
            $progress = $_POST['progress'] ?? 0;

            if (empty($name) || empty($id)) {
                echo "Dados obrigatórios faltando.";
                return;
            }

            require_once __DIR__ . '/../../config/Database.php';
            $pdo = Database::connect();

            $stmt = $pdo->prepare("UPDATE projects SET name = ?, client_name = ?, description = ?, end_date = ?, start_date = ?, total_hours = ?, status = ?, progress = ? WHERE id = ?");
            if ($stmt->execute([$name, $client_name, $description, $end_date, $start_date, $total_hours, $status, $progress, $id])) {
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

    public function checkEmployee() {
        $employeeId = $_GET['employee_id'] ?? 0;
        $pdo = Database::connect();
        
        $stmt = $pdo->prepare("SELECT p.name FROM project_resources pr 
                              JOIN projects p ON pr.project_id = p.id 
                              WHERE pr.resource_type = 'employee' AND pr.resource_id = ?");
        $stmt->execute([$employeeId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'assigned' => $result !== false,
            'project_name' => $result['name'] ?? ''
        ]);
        exit;
    }
    
    public function checkInventory() {
        $inventoryId = $_GET['inventory_id'] ?? 0;
        $pdo = Database::connect();
        
        $stmt = $pdo->prepare("SELECT p.name FROM project_resources pr 
                              JOIN projects p ON pr.project_id = p.id 
                              WHERE pr.resource_type = 'inventory' AND pr.resource_id = ?");
        $stmt->execute([$inventoryId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'assigned' => $result !== false,
            'project_name' => $result['name'] ?? ''
        ]);
        exit;
    }

    public function delete() {
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            
            require_once __DIR__ . '/../../config/Database.php';
            $pdo = Database::connect();
            
            $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
            if ($stmt->execute([$id])) {
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