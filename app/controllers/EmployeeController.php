<?php
// app/controllers/EmployeeController.php

require_once __DIR__ . '/../../config/Database.php';

class EmployeeController {

    public function list() {
        $pdo = Database::connect();
        $stmt = $pdo->query("SELECT * FROM employees ORDER BY name ASC");
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../views/employees/index.php';
    }

    public function create() {
        require_once __DIR__ . '/../views/employees/create.php';
    }

    public function store() {
        // (Código já existente para cadastro de funcionário)
        // ...
    }

    public function edit() {
        if (!isset($_GET['id'])) {
            echo "Employee ID not provided.";
            exit;
        }
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$employee) {
            echo "Employee not found.";
            exit;
        }
        require_once __DIR__ . '/../views/employees/edit.php';
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? '';
            $name = $_POST['name'] ?? '';
            $role = $_POST['role'] ?? '';
            $birth_date = $_POST['birth_date'] ?? '';
            $start_date = $_POST['start_date'] ?? '';
            $address = $_POST['address'] ?? '';
            $about = $_POST['about'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $active = isset($_POST['active']) ? 1 : 0;

            if (empty($id) || empty($name) || empty($role)) {
                echo "Required fields missing.";
                exit;
            }

            require_once __DIR__ . '/../../config/Database.php';
            $pdo = Database::connect();
            $stmt = $pdo->prepare("UPDATE employees SET name = ?, role = ?, birth_date = ?, start_date = ?, address = ?, about = ?, phone = ?, active = ? WHERE id = ?");
            if ($stmt->execute([$name, $role, $birth_date, $start_date, $address, $about, $phone, $active, $id])) {
                header("Location: /ams-malergeschaft/public/employees");
                exit;
            } else {
                echo "Error updating employee.";
            }
        }
    }

    public function delete() {
        if (!isset($_GET['id'])) {
            echo "Employee ID not provided.";
            exit;
        }
        $pdo = Database::connect();
        $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
        if ($stmt->execute([$_GET['id']])) {
            header("Location: /ams-malergeschaft/public/employees");
            exit;
        } else {
            echo "Error deleting employee.";
        }
    }
}
