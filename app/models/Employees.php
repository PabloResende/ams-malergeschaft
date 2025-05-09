<?php
// app/models/Employee.php

require_once __DIR__ . '/../../config/Database.php';

class Employee
{
    public static function connect()
    {
        return Database::connect();
    }

    public static function all()
    {
        $pdo = self::connect();
        $stmt = $pdo->query("SELECT * FROM employees ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find($id)
    {
        $pdo = self::connect();
        $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
        $stmt->execute([$id]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($employee) {
            // apenas sinaliza existência no JSON; a URL final é montada no JS
            foreach ([
                'profile_picture', 'passport', 'permission_photo_front',
                'permission_photo_back', 'health_card_front', 'health_card_back',
                'bank_card_front', 'bank_card_back', 'marriage_certificate'
            ] as $field) {
                $employee[$field] = !empty($employee[$field]);
            }
        }

        return $employee;
    }

    public static function create($data, $files)
    {
        $pdo = self::connect();
        $uploadDir = __DIR__ . '/../../public/uploads/employees/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $upload = function($inputName) use ($uploadDir) {
            if (isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] === UPLOAD_ERR_OK) {
                $original = basename($_FILES[$inputName]['name']);
                $safeName = uniqid() . "_" . preg_replace('/[^A-Za-z0-9\.\-_]/', '_', $original);
                $dest     = $uploadDir . $safeName;
                if (move_uploaded_file($_FILES[$inputName]['tmp_name'], $dest)) {
                    return $safeName;
                }
            }
            return null;
        };

        $stmt = $pdo->prepare("
            INSERT INTO employees (
                name, last_name, address, sex, birth_date, nationality, permission_type,
                email, ahv_number, phone, religion, marital_status, role, start_date,
                about, profile_picture, passport, permission_photo_front, permission_photo_back,
                health_card_front, health_card_back, bank_card_front, bank_card_back, marriage_certificate
            ) VALUES (
                :name, :last_name, :address, :sex, :birth_date, :nationality, :permission_type,
                :email, :ahv_number, :phone, :religion, :marital_status, :role, :start_date,
                :about, :profile_picture, :passport, :permission_photo_front, :permission_photo_back,
                :health_card_front, :health_card_back, :bank_card_front, :bank_card_back, :marriage_certificate
            )
        ");

        $stmt->execute([
            'name'                   => $data['name'] ?? null,
            'last_name'              => $data['last_name'] ?? null,
            'address'                => $data['address'] ?? null,
            'sex'                    => $data['sex'] ?? null,
            'birth_date'             => $data['birth_date'] ?? null,
            'nationality'            => $data['nationality'] ?? null,
            'permission_type'        => $data['permission_type'] ?? null,
            'email'                  => $data['email'] ?? null,
            'ahv_number'             => $data['ahv_number'] ?? null,
            'phone'                  => $data['phone'] ?? null,
            'religion'               => $data['religion'] ?? null,
            'marital_status'         => $data['marital_status'] ?? null,
            'role'                   => $data['role'] ?? null,
            'start_date'             => $data['start_date'] ?? null,
            'about'                  => $data['about'] ?? null,
            'profile_picture'        => $upload('profile_picture'),
            'passport'               => $upload('passport'),
            'permission_photo_front' => $upload('permission_photo_front'),
            'permission_photo_back'  => $upload('permission_photo_back'),
            'health_card_front'      => $upload('health_card_front'),
            'health_card_back'       => $upload('health_card_back'),
            'bank_card_front'        => $upload('bank_card_front'),
            'bank_card_back'         => $upload('bank_card_back'),
            'marriage_certificate'   => $upload('marriage_certificate'),
        ]);
    }

    public static function update($id, $data, $files)
    {
        $pdo = self::connect();
        $uploadDir = __DIR__ . '/../../public/uploads/employees/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $current = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
        $current->execute([$id]);
        $current = $current->fetch(PDO::FETCH_ASSOC);

        $uploadUpdate = function($inputName, $existing) use ($uploadDir) {
            if (isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] === UPLOAD_ERR_OK) {
                $original = basename($_FILES[$inputName]['name']);
                $safeName = uniqid() . "_" . preg_replace('/[^A-Za-z0-9\.\-_]/', '_', $original);
                $dest     = $uploadDir . $safeName;
                if (move_uploaded_file($_FILES[$inputName]['tmp_name'], $dest)) {
                    return $safeName;
                }
            }
            return $existing;
        };

        $stmt = $pdo->prepare("
            UPDATE employees SET
                name                  = :name,
                last_name             = :last_name,
                address               = :address,
                sex                   = :sex,
                birth_date            = :birth_date,
                nationality           = :nationality,
                permission_type       = :permission_type,
                email                 = :email,
                ahv_number            = :ahv_number,
                phone                 = :phone,
                religion              = :religion,
                marital_status        = :marital_status,
                role                  = :role,
                start_date            = :start_date,
                about                 = :about,
                profile_picture       = :profile_picture,
                passport              = :passport,
                permission_photo_front= :permission_photo_front,
                permission_photo_back = :permission_photo_back,
                health_card_front     = :health_card_front,
                health_card_back      = :health_card_back,
                bank_card_front       = :bank_card_front,
                bank_card_back        = :bank_card_back,
                marriage_certificate  = :marriage_certificate
            WHERE id = :id
        ");

        $stmt->execute([
            'id'                     => $id,
            'name'                   => $data['name'] ?? null,
            'last_name'              => $data['last_name'] ?? null,
            'address'                => $data['address'] ?? null,
            'sex'                    => $data['sex'] ?? null,
            'birth_date'             => $data['birth_date'] ?? null,
            'nationality'            => $data['nationality'] ?? null,
            'permission_type'        => $data['permission_type'] ?? null,
            'email'                  => $data['email'] ?? null,
            'ahv_number'             => $data['ahv_number'] ?? null,
            'phone'                  => $data['phone'] ?? null,
            'religion'               => $data['religion'] ?? null,
            'marital_status'         => $data['marital_status'] ?? null,
            'role'                   => $data['role'] ?? null,
            'start_date'             => $data['start_date'] ?? null,
            'about'                  => $data['about'] ?? null,
            'profile_picture'        => $uploadUpdate('profile_picture', $current['profile_picture']),
            'passport'               => $uploadUpdate('passport', $current['passport']),
            'permission_photo_front' => $uploadUpdate('permission_photo_front', $current['permission_photo_front']),
            'permission_photo_back'  => $uploadUpdate('permission_photo_back', $current['permission_photo_back']),
            'health_card_front'      => $uploadUpdate('health_card_front', $current['health_card_front']),
            'health_card_back'       => $uploadUpdate('health_card_back', $current['health_card_back']),
            'bank_card_front'        => $uploadUpdate('bank_card_front', $current['bank_card_front']),
            'bank_card_back'         => $uploadUpdate('bank_card_back', $current['bank_card_back']),
            'marriage_certificate'   => $uploadUpdate('marriage_certificate', $current['marriage_certificate']),
        ]);
    }

    public static function delete($id)
    {
        $pdo = self::connect();
        $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
        $stmt->execute([$id]);
    }

    public static function serveDocument($id, $type)
    {
        $pdo = self::connect();
        $stmt = $pdo->prepare("SELECT $type FROM employees WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && $row[$type]) {
            $path = __DIR__ . '/../../public/uploads/employees/' . $row[$type];
            if (file_exists($path)) {
                header('Content-Type: ' . mime_content_type($path));
                readfile($path);
                exit;
            }
        }

        http_response_code(404);
        echo "Arquivo não encontrado.";
    }
}
