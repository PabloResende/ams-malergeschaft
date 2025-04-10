<?php
// app/models/Client.php

require_once __DIR__ . '/../../config/Database.php';

class Client {
    public static function all() {
        $pdo = Database::connect();
        $stmt = $pdo->query("SELECT * FROM client ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find($id) {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT * FROM client WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function create($data) {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("INSERT INTO client (name, address, about, phone, profile_picture) 
                               VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([
            $data['name'], $data['address'], $data['about'], $data['phone'], $data['profile_picture']
        ]);
    }

    public static function update($id, $data) {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("UPDATE client SET name = ?, address = ?, about = ?, phone = ?, active = ? WHERE id = ?");
        return $stmt->execute([
            $data['name'], $data['address'], $data['about'], $data['phone'], $data['active'], $id
        ]);
    }

    public static function delete($id) {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("DELETE FROM client WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
