<?php
// app/controllers/EmployeeController.php

require_once __DIR__ . '/../../config/Database.php';

class EmployeeController {

    // Lista todos os funcionários e exibe a view com modal para criação
    public function list() {
        $pdo = Database::connect();
        $stmt = $pdo->query("SELECT * FROM employees ORDER BY name ASC");
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../views/employees/index.php';
    }

    // Processa o cadastro de funcionário
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name       = $_POST['name'] ?? '';
            $role       = $_POST['role'] ?? '';
            $birth_date = $_POST['birth_date'] ?? '';
            $start_date = $_POST['start_date'] ?? '';
            $address    = $_POST['address'] ?? '';
            $about      = $_POST['about'] ?? '';
            $phone      = $_POST['phone'] ?? '';

            if (empty($name) || empty($role) || empty($birth_date) || empty($start_date)) {
                echo "Please fill in all required fields.";
                exit;
            }

            // Processa o upload da foto de perfil, se houver
            $profile_picture = '';
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath   = $_FILES['profile_picture']['tmp_name'];
                $fileName      = $_FILES['profile_picture']['name'];
                $fileNameCmps  = explode(".", $fileName);
                $fileExtension = strtolower(end($fileNameCmps));
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $uploadFileDir = __DIR__ . '/../../public/uploads/';
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0777, true);
                }
                $dest_path = $uploadFileDir . $newFileName;
                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $profile_picture = $newFileName;
                } else {
                    echo "Error moving the uploaded file.";
                    exit;
                }
            }

            require_once __DIR__ . '/../../config/Database.php';
            $pdo = Database::connect();
            $stmt = $pdo->prepare("INSERT INTO employees (name, role, birth_date, start_date, address, about, phone, profile_picture, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            if ($stmt->execute([$name, $role, $birth_date, $start_date, $address, $about, $phone, $profile_picture])) {
                header("Location: /ams-malergeschaft/public/employees");
                exit;
            } else {
                echo "Error registering employee.";
                exit;
            }
        } else {
            header("Location: /ams-malergeschaft/public/employees");
            exit;
        }
    }
}
