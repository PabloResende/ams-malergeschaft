<?php
// app/models/Clients.php

require_once __DIR__ . '/../../config/Database.php';

class Client
{
    public static function all()
    {
        $pdo  = Database::connect();
        $stmt = $pdo->query("SELECT * FROM client ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find($id)
    {
        $pdo   = Database::connect();
        $stmt  = $pdo->prepare("SELECT * FROM client WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function create($data)
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("
            INSERT INTO client
              (name, address, phone, active, loyalty_points)
            VALUES
              (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['name'],
            $data['address'],
            $data['phone'],
            $data['active'] ?? 1,
            $data['loyalty_points'] ?? 0
        ]);
    }

    public static function update($id, $data)
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("
            UPDATE client SET
              name           = ?,
              address        = ?,
              phone          = ?,
              active         = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['name'],
            $data['address'],
            $data['phone'],
            $data['active'] ?? 1,
            $id
        ]);
    }

    public static function delete($id)
    {
        $pdo  = Database::connect();
        $stmt = $pdo->prepare("DELETE FROM client WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function countProjects($id)
    {
        $pdo  = Database::connect();
        $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM projects WHERE client_id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['cnt'] ?? 0);
    }
}
