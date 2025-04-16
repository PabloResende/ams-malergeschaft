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
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $empId = $data['emp_id'] ?? null;
            
            if ($empId) {
              $allocations = EmployeeModel::checkAllocation($empId);
              echo json_encode(['allocated' => $allocations > 0, 'count' => $allocations]);
            }
        }
    }
}
