<?php
// app/models/Client.php

require_once __DIR__ . '/../../config/Database.php';

class Employee {

public static function connect() {
    return Database::connect();
}

public static function all() {
    $pdo = self::connect();
    $stmt = $pdo->query("SELECT * FROM employees ORDER BY name ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public static function find($id) {
    $pdo = self::connect();
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($employee) {
        $documentFields = [
            'profile_picture', 'passport', 'permission_photo_front',
            'permission_photo_back', 'health_card_front', 'health_card_back',
            'bank_card_front', 'bank_card_back', 'marriage_certificate'
        ];

        foreach ($documentFields as $field) {
            $employee[$field] = $employee[$field]
                ? "/ams-malergeschaft/public/employees/document?id={$employee['id']}&type={$field}"
                : null;
        }
    }

    return $employee;
}

public static function create($data, $files) {
    // Aqui você coloca a lógica de validação e upload, igual estava no controller
    // Só que encapsulada no model
}

public static function update($id, $data, $files) {
    // Igual ao create, mas com lógica de UPDATE
}

public static function delete($id) {
    $pdo = self::connect();
    $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
    $stmt->execute([$id]);
}

public static function serveDocument($id, $type) {
    // Igual à função do controller, mas isolada aqui
}
}
