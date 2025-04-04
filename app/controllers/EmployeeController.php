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
                
                // Processar uploads
                $documentFields = [
                    'profile_picture', 'passport', 'permission_photo_front',
                    'permission_photo_back', 'health_card_front', 'health_card_back',
                    'bank_card_front', 'bank_card_back', 'marriage_certificate'
                ];
                
                $data = $_POST;
                foreach ($documentFields as $field) {
                    if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                        // Verificar tipo e tamanho do arquivo
                        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mimeType = finfo_file($fileInfo, $_FILES[$field]['tmp_name']);
                        finfo_close($fileInfo);
                        
                        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
                        if (!in_array($mimeType, $allowedTypes)) {
                            throw new Exception("Tipo de arquivo não permitido para $field");
                        }
                        
                        if ($_FILES[$field]['size'] > 5 * 1024 * 1024) { // 5MB
                            throw new Exception("Arquivo $field excede o tamanho máximo permitido");
                        }
                        
                        $data[$field] = file_get_contents($_FILES[$field]['tmp_name']);
                    }
                }
                
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

    public function serveDocument() {
        try {
            if (!isset($_GET['id']) || !isset($_GET['type'])) {
                throw new Exception('Parâmetros inválidos');
            }
            
            $pdo = Database::connect();
            $stmt = $pdo->prepare("SELECT {$_GET['type']} FROM employees WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $document = $stmt->fetchColumn();
            
            if (!$document) {
                throw new Exception('Documento não encontrado');
            }
            
            // Detecta o tipo MIME
            $finfo = new finfo(FILEINFO_MIME);
            $mime = $finfo->buffer($document);
            
            header("Content-Type: $mime");
            echo $document;
            exit;
            
        } catch (Exception $e) {
            http_response_code(404);
            readfile(__DIR__.'/../../public/assets/image-not-found.png');
            exit;
        }
    }

    public function getEmployee() {
        header('Content-Type: application/json');
        
        try {
            if (!isset($_GET['id'])) {
                throw new Exception('Employee ID not provided');
            }

            $pdo = Database::connect();
            $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$employee) {
                throw new Exception('Employee not found');
            }

            // Gera URLs para os documentos
            $documentFields = [
                'profile_picture', 'passport', 'permission_photo_front',
                'permission_photo_back', 'health_card_front', 'health_card_back',
                'bank_card_front', 'bank_card_back', 'marriage_certificate'
            ];

            foreach ($documentFields as $field) {
                $employee[$field] = !empty($employee[$field]) ? 
                    "/ams-malergeschaft/public/employees/document?id={$_GET['id']}&type=$field" : 
                    null;
            }

            echo json_encode($employee);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        
        exit;
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $pdo = Database::connect();
                
                // Processar uploads
                $documentFields = [
                    'profile_picture', 'passport', 'permission_photo_front',
                    'permission_photo_back', 'health_card_front', 'health_card_back',
                    'bank_card_front', 'bank_card_back', 'marriage_certificate'
                ];
                
                $data = $_POST;
                foreach ($documentFields as $field) {
                    if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                        // Verificar tipo e tamanho do arquivo
                        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mimeType = finfo_file($fileInfo, $_FILES[$field]['tmp_name']);
                        finfo_close($fileInfo);
                        
                        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
                        if (!in_array($mimeType, $allowedTypes)) {
                            throw new Exception("Tipo de arquivo não permitido para $field");
                        }
                        
                        if ($_FILES[$field]['size'] > 5 * 1024 * 1024) { // 5MB
                            throw new Exception("Arquivo $field excede o tamanho máximo permitido");
                        }
                        
                        $data[$field] = file_get_contents($_FILES[$field]['tmp_name']);
                    } elseif (isset($_POST["keep_$field"])) {
                        // Mantém o documento existente
                        unset($data[$field]);
                    }
                }
                
                // Montar a query de atualização
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
                
                // Adiciona campos de documentos que foram atualizados
                foreach ($documentFields as $field) {
                    if (isset($data[$field])) {
                        $query .= ", $field = :$field";
                    }
                }
                
                $query .= " WHERE id = :id";
                
                $stmt = $pdo->prepare($query);
                $stmt->execute($data);
                
                header('Location: /ams-malergeschaft/public/employees');
                exit;
                
            } catch (Exception $e) {
                die("Erro ao atualizar funcionário: " . $e->getMessage());
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