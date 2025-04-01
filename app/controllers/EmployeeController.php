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
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Coletando os dados do formulário
            $name = $_POST['name'] ?? '';
            $role = $_POST['role'] ?? '';
            $birthDate = $_POST['birth_date'] ?? '';
            $startDate = $_POST['start_date'] ?? '';
            $address = $_POST['address'] ?? '';
            $about = $_POST['about'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $status = isset($_POST['active']) ? 1 : 0;
            
            // Processando o upload da imagem
            $profilePicture = null;
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $profilePicture = $_FILES['profile_picture']['name'];
                move_uploaded_file($_FILES['profile_picture']['tmp_name'], __DIR__ . '/../../uploads/' . $profilePicture);
            }
    
            try {
                $pdo = Database::connect();
                $stmt = $pdo->prepare("INSERT INTO employees (name, role, birth_date, start_date, address, about, phone, status, profile_picture) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $role, $birthDate, $startDate, $address, $about, $phone, $status, $profilePicture]);
    
                header('Location: /ams-malergeschaft/public/employees');
                exit;
            } catch (Exception $e) {
                // Caso haja erro no banco de dados
                echo "Erro ao salvar o funcionário: " . $e->getMessage();
                exit;
            }
        }
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
            $status = isset($_POST['active']) ? 1 : 0;

            if (empty($id) || empty($name) || empty($role)) {
                echo "Required fields missing.";
                exit;
            }

            require_once __DIR__ . '/../../config/Database.php';
            $pdo = Database::connect();
            $stmt = $pdo->prepare("UPDATE employees SET name = ?, role = ?, birth_date = ?, start_date = ?, address = ?, about = ?, phone = ?, status = ? WHERE id = ?");
            if ($stmt->execute([$name, $role, $birth_date, $start_date, $address, $about, $phone, $status, $id])) {
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
