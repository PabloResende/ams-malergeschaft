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
            try {
                $pdo = Database::connect();
                
                // Campos de documentos
                $documentFields = [
                    'profile_picture', 'passport', 'permission_photo_front',
                    'permission_photo_back', 'health_card_front', 'health_card_back',
                    'bank_card_front', 'bank_card_back', 'marriage_certificate'
                ];
                
                $data = $_POST;
                
                // Inicializa os campos de documentos com null se não estiverem definidos
                foreach ($documentFields as $field) {
                    if (!isset($data[$field])) {
                        $data[$field] = null;
                    }
                }
                
                // Processa uploads dos documentos
                foreach ($documentFields as $field) {
                    if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                        // Verifica o tipo MIME do arquivo
                        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mimeType = finfo_file($fileInfo, $_FILES[$field]['tmp_name']);
                        finfo_close($fileInfo);
                        
                        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
                        if (!in_array($mimeType, $allowedTypes)) {
                            throw new Exception("Tipo de arquivo não permitido para $field");
                        }
                        
                        // Verifica o tamanho do arquivo (máximo 5MB)
                        if ($_FILES[$field]['size'] > 5 * 1024 * 1024) {
                            throw new Exception("Arquivo $field excede o tamanho máximo permitido");
                        }
                        
                        $filename = uniqid() . '_' . basename($_FILES[$field]['name']);
                        $destination = __DIR__ . '/../../public/uploads/employees/' . $filename;

                        if (move_uploaded_file($_FILES[$field]['tmp_name'], $destination)) {
                            $data[$field] = "/uploads/employees/$filename";
                        } else {
                            throw new Exception("Falha ao mover o arquivo de $field.");
                        }

                    }
                }
                
                // Prepara e executa a query de inserção
                $stmt = $pdo->prepare("INSERT INTO employees (
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
                )");
                
                $stmt->execute($data);
                
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
                $pdo = Database::connect();
    
                $documentFields = [
                    'profile_picture', 'passport', 'permission_photo_front',
                    'permission_photo_back', 'health_card_front', 'health_card_back',
                    'bank_card_front', 'bank_card_back', 'marriage_certificate'
                ];
    
                $fields = [
                    'name', 'last_name', 'address', 'sex', 'birth_date', 'nationality',
                    'permission_type', 'email', 'ahv_number', 'phone', 'religion',
                    'marital_status', 'role', 'start_date', 'about', 'id'
                ];
    
                $params = [];
                foreach ($fields as $field) {
                    if (isset($_FILES[$field]) && $_FILES[$field]['error'] == 0) {
                        $originalName = $_FILES[$field]['name'];
                        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                        $uniqueName = uniqid($field . "_", true) . "." . $extension;
                        $filepath = $uploadDir . $uniqueName;
                
                        if (move_uploaded_file($_FILES[$field]['tmp_name'], $filepath)) {
                            $updateFields[] = "$field = :$field";
                            $updateParams[":$field"] = $filepath;
                        }
                    }
                }
                
                // Garante que o ID foi enviado
                if (!isset($params['id'])) {
                    throw new Exception("ID do funcionário não informado.");
                }
    
                // Começa a montar a query
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
    
                // Adiciona os documentos que foram enviados
                foreach ($documentFields as $field) {
                    if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mimeType = finfo_file($fileInfo, $_FILES[$field]['tmp_name']);
                        finfo_close($fileInfo);
    
                        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
                        if (!in_array($mimeType, $allowedTypes)) {
                            throw new Exception("Tipo de arquivo não permitido para $field");
                        }
    
                        if ($_FILES[$field]['size'] > 5 * 1024 * 1024) {
                            throw new Exception("Arquivo $field excede o tamanho máximo permitido");
                        }
    
                        $filename = uniqid() . '_' . basename($_FILES[$field]['name']);
                        $destination = __DIR__ . '/../../public/uploads/employees/' . $filename;
    
                        if (move_uploaded_file($_FILES[$field]['tmp_name'], $destination)) {
                            $params[$field] = "/uploads/employees/$filename";
                            $query .= ", $field = :$field";
                        } else {
                            throw new Exception("Falha ao mover o arquivo de $field.");
                        }
                    }
                }
    
                $query .= " WHERE id = :id";
    
                $stmt = $pdo->prepare($query);
                $stmt->execute($params);
    
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
            exit;
        }
    
        try {
            $pdo = Database::connect();
            $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if (!$employee) {
                http_response_code(404);
                echo json_encode(['error' => 'Funcionário não encontrado']);
                exit;
            }
    
            // Monta links para os documentos binários
            $documentFields = [
                'profile_picture', 'passport', 'permission_photo_front',
                'permission_photo_back', 'health_card_front', 'health_card_back',
                'bank_card_front', 'bank_card_back', 'marriage_certificate'
            ];
    
            foreach ($documentFields as $field) {
                if ($employee[$field]) {
                    $employee[$field] = "/ams-malergeschaft/public/employees/document?id={$employee['id']}&type={$field}";
                } else {
                    $employee[$field] = null;
                }
            }
    
            echo json_encode($employee);
            exit;
    
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro no servidor: ' . $e->getMessage()]);
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
    
    public function serveDocument() {
        if (!isset($_GET['id']) || !isset($_GET['type'])) {
            http_response_code(400);
            echo "Parâmetros inválidos.";
            exit;
        }
    
        $id = $_GET['id'];
        $type = $_GET['type'];
    
        $allowedTypes = [
            'profile_picture', 'passport', 'permission_photo_front',
            'permission_photo_back', 'health_card_front', 'health_card_back',
            'bank_card_front', 'bank_card_back', 'marriage_certificate'
        ];
    
        if (!in_array($type, $allowedTypes)) {
            http_response_code(400);
            echo "Tipo de documento inválido.";
            exit;
        }
    
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT $type FROM employees WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$result || empty($result[$type])) {
            http_response_code(404);
            echo "Documento não encontrado.";
            exit;
        }
    
        $data = $result[$type];
    
        // Detecta o MIME type dos dados armazenados
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($data);
        if (!$mimeType) {
            $mimeType = 'application/octet-stream';
        }
    
        header("Content-Type: $mimeType");
        echo $data;
        exit;
    }
}
