<?php
// EmployeeController.php

class EmployeeController {

    // Displays the create employee form
    public function create() {
        require_once __DIR__ . '/../views/employees/create.php';
    }

    // Processes the employee registration form
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            // Retrieve form data
            $name       = $_POST['name'] ?? '';
            $role       = $_POST['role'] ?? '';
            $birth_date = $_POST['birth_date'] ?? '';
            $start_date = $_POST['start_date'] ?? '';
            $address    = $_POST['address'] ?? '';
            $about      = $_POST['about'] ?? '';
            $phone      = $_POST['phone'] ?? '';

            // Basic validation for required fields
            if (empty($name) || empty($role) || empty($birth_date) || empty($start_date)) {
                echo "Please fill in all required fields.";
                return;
            }

            // Process profile picture upload if available
            $profile_picture = '';
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath   = $_FILES['profile_picture']['tmp_name'];
                $fileName      = $_FILES['profile_picture']['name'];
                $fileNameCmps  = explode(".", $fileName);
                $fileExtension = strtolower(end($fileNameCmps));

                // Create a unique filename
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;

                // Define upload directory (adjust path as needed)
                $uploadFileDir = __DIR__ . '/../../public/uploads/';
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0777, true);
                }
                $dest_path = $uploadFileDir . $newFileName;
                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $profile_picture = $newFileName;
                } else {
                    echo "Error moving the uploaded file.";
                    return;
                }
            }

            // Connect to the database
            require_once __DIR__ . '/../../config/Database.php';
            $pdo = Database::connect();

            // Insert employee data
            $stmt = $pdo->prepare("INSERT INTO employees (name, role, birth_date, start_date, address, about, phone, profile_picture, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            if ($stmt->execute([$name, $role, $birth_date, $start_date, $address, $about, $phone, $profile_picture])) {
                header("Location: /ams-malergeschaft/public/employees");
                exit;
            } else {
                echo "Error registering employee.";
            }
        } else {
            header("Location: /ams-malergeschaft/public/employees/create");
            exit;
        }
    }
}
