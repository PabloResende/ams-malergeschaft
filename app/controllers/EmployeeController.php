<?php
require_once __DIR__ . '/../models/Employees.php';

class EmployeeController {

    public function list() {
        $employees = Employee::all();
        require_once __DIR__ . '/../views/employees/index.php';
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                Employee::create($_POST, $_FILES);
                header('Location: /ams-malergeschaft/public/employees');
                exit;
            } catch (Exception $e) {
                die("Erro ao salvar funcionário: " . $e->getMessage());
            }
        }
    }

    public function update() {
        if ($data['status'] === 'completed' && $data['progress'] < 100) {
            echo "Não é possível marcar como concluído até que todas as tasks estejam finalizadas.";
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                Employee::update($_POST['id'], $_POST, $_FILES);
                header('Location: /ams-malergeschaft/public/employees');
                exit;
            } catch (Exception $e) {
                die("Erro ao atualizar funcionário: " . $e->getMessage());
            }
        }
    }

    public function get() {
        header('Content-Type: application/json');
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'ID do funcionário não fornecido']);
            return;
        }

        $employee = Employee::find($_GET['id']);
        if ($employee) {
            echo json_encode($employee);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Funcionário não encontrado']);
        }
    }

    public function delete() {
        if (!isset($_GET['id'])) {
            echo "Employee ID not provided.";
            exit;
        }
        Employee::delete($_GET['id']);
        header("Location: /ams-malergeschaft/public/employees");
    }

    public function serveDocument() {
        Employee::serveDocument($_GET['id'], $_GET['type']);
    }

    public function checkAllocation() {
        $input = json_decode(file_get_contents('php://input'), true);
        $emp_id = $input['emp_id'] ?? null;
        if (!$emp_id) {
            http_response_code(400);
            echo json_encode(['error'=>'emp_id missing']);
            return;
        }
        $pdo = Database::connect();
        $stmt = $pdo->prepare("
          SELECT COUNT(*) as count
          FROM project_resources
          WHERE resource_type = 'employee'
            AND resource_id   = ?
        ");
        $stmt->execute([$emp_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $count = $row['count'] ?? 0;
        header('Content-Type: application/json');
        echo json_encode([
          'allocated' => ($count > 0),
          'count'     => (int)$count
        ]);
    }    
}
