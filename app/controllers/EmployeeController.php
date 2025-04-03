<?php
require_once __DIR__ . '/../../config/Database.php';

class EmployeeController {

    public function list() {
        $pdo = Database::connect();
        $stmt = $pdo->query("SELECT * FROM employees ORDER BY name ASC");
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../views/employees/index.php';
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Coletando os dados do formulário
            $name = $_POST['name'] ?? '';
            $lastName = $_POST['last_name'] ?? '';
            $address = $_POST['address'] ?? '';
            $sex = $_POST['sex'] ?? '';
            $birthDate = $_POST['birth_date'] ?? '';
            $nationality = $_POST['nationality'] ?? '';
            $permissionType = $_POST['permission_type'] ?? '';
            $email = $_POST['email'] ?? '';
            $ahvNumber = $_POST['ahv_number'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $religion = $_POST['religion'] ?? '';
            $maritalStatus = $_POST['marital_status'] ?? '';
            $role = $_POST['role'] ?? '';
            $startDate = $_POST['start_date'] ?? '';
            $about = $_POST['about'] ?? '';
            
            // Processando o upload dos documentos
            $documents = [];
            $uploadFields = [
                'profile_picture', 'passport', 'permission_photo_front', 'permission_photo_back',
                'health_card_front', 'health_card_back', 'bank_card_front', 'bank_card_back',
                'marriage_certificate'
            ];
            
            foreach ($uploadFields as $field) {
                if (isset($_FILES[$field])) {
                    $documents[$field] = $this->handleFileUpload($field);
                }
            }
    
            try {
                $pdo = Database::connect();
                
                // Preparando a query SQL
                $query = "INSERT INTO employees (
                    name, last_name, address, sex, birth_date, nationality, 
                    permission_type, email, ahv_number, phone, religion, 
                    marital_status, role, start_date, about, 
                    profile_picture, passport, permission_photo_front, permission_photo_back,
                    health_card_front, health_card_back, bank_card_front, bank_card_back,
                    marriage_certificate
                ) VALUES (
                    :name, :last_name, :address, :sex, :birth_date, :nationality, 
                    :permission_type, :email, :ahv_number, :phone, :religion, 
                    :marital_status, :role, :start_date, :about, 
                    :profile_picture, :passport, :permission_photo_front, :permission_photo_back,
                    :health_card_front, :health_card_back, :bank_card_front, :bank_card_back,
                    :marriage_certificate
                )";
                
                $stmt = $pdo->prepare($query);
                
                // Executando a query com os parâmetros
                $stmt->execute([
                    ':name' => $name,
                    ':last_name' => $lastName,
                    ':address' => $address,
                    ':sex' => $sex,
                    ':birth_date' => $birthDate,
                    ':nationality' => $nationality,
                    ':permission_type' => $permissionType,
                    ':email' => $email,
                    ':ahv_number' => $ahvNumber,
                    ':phone' => $phone,
                    ':religion' => $religion,
                    ':marital_status' => $maritalStatus,
                    ':role' => $role,
                    ':start_date' => $startDate,
                    ':about' => $about,
                    ':profile_picture' => $documents['profile_picture'] ?? null,
                    ':passport' => $documents['passport'] ?? null,
                    ':permission_photo_front' => $documents['permission_photo_front'] ?? null,
                    ':permission_photo_back' => $documents['permission_photo_back'] ?? null,
                    ':health_card_front' => $documents['health_card_front'] ?? null,
                    ':health_card_back' => $documents['health_card_back'] ?? null,
                    ':bank_card_front' => $documents['bank_card_front'] ?? null,
                    ':bank_card_back' => $documents['bank_card_back'] ?? null,
                    ':marriage_certificate' => $documents['marriage_certificate'] ?? null
                ]);
    
                header('Location: /ams-malergeschaft/public/employees');
                exit;
            } catch (Exception $e) {
                // Caso haja erro no banco de dados
                echo "Erro ao salvar o funcionário: " . $e->getMessage();
                exit;
            }
        }
    }
    
    private function handleFileUpload($fieldName) {
        if (isset($_FILES[$fieldName]) && $_FILES[$fieldName]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES[$fieldName];
            $fileName = uniqid() . '_' . basename($file['name']);
            $destination = __DIR__ . '/../../uploads/' . $fileName;
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                return $fileName;
            }
        }
        return null;
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? '';
            $name = $_POST['name'] ?? '';
            $lastName = $_POST['last_name'] ?? '';
            $address = $_POST['address'] ?? '';
            $sex = $_POST['sex'] ?? '';
            $birthDate = $_POST['birth_date'] ?? '';
            $nationality = $_POST['nationality'] ?? '';
            $permissionType = $_POST['permission_type'] ?? '';
            $email = $_POST['email'] ?? '';
            $ahvNumber = $_POST['ahv_number'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $religion = $_POST['religion'] ?? '';
            $maritalStatus = $_POST['marital_status'] ?? '';
            $role = $_POST['role'] ?? '';
            $startDate = $_POST['start_date'] ?? '';
            $about = $_POST['about'] ?? '';
            
            if (empty($id) || empty($name) || empty($role)) {
                echo "Required fields missing.";
                exit;
            }
    
            $pdo = Database::connect();
            
            // Processando o upload dos documentos
            $documents = [];
            $uploadFields = [
                'profile_picture', 'passport', 'permission_photo_front', 'permission_photo_back',
                'health_card_front', 'health_card_back', 'bank_card_front', 'bank_card_back',
                'marriage_certificate'
            ];
            
            foreach ($uploadFields as $field) {
                if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                    $documents[$field] = $this->handleFileUpload($field);
                }
            }
            
            // Construindo a query dinamicamente
            $query = "UPDATE employees SET 
                name = :name,
                last_name = :last_name,
                address = :address,
                sex = :sex,
                birth_date = :birth_date,
                nationality = :nationality,
                permission_type = :permission_type,
                email = :email,
                ahv_number = :ahv_number,
                phone = :phone,
                religion = :religion,
                marital_status = :marital_status,
                role = :role,
                start_date = :start_date,
                about = :about";
            
            // Adicionando campos de documentos que foram atualizados
            foreach ($uploadFields as $field) {
                if (isset($documents[$field])) {
                    $query .= ", $field = :$field";
                }
            }
            
            $query .= " WHERE id = :id";
            
            $stmt = $pdo->prepare($query);
            
            // Parâmetros básicos
            $params = [
                ':name' => $name,
                ':last_name' => $lastName,
                ':address' => $address,
                ':sex' => $sex,
                ':birth_date' => $birthDate,
                ':nationality' => $nationality,
                ':permission_type' => $permissionType,
                ':email' => $email,
                ':ahv_number' => $ahvNumber,
                ':phone' => $phone,
                ':religion' => $religion,
                ':marital_status' => $maritalStatus,
                ':role' => $role,
                ':start_date' => $startDate,
                ':about' => $about,
                ':id' => $id
            ];
            
            // Adicionando parâmetros de documentos
            foreach ($uploadFields as $field) {
                if (isset($documents[$field])) {
                    $params[":$field"] = $documents[$field];
                }
            }
            
            // Executando a query
            $stmt->execute($params);
    
            header("Location: /ams-malergeschaft/public/employees");
            exit;
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