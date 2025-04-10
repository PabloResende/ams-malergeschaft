<?php

require_once(__DIR__ . '/../../config/Database.php');

class ProjectModel {
    public function getAll() {
        $pdo = Database::connect();
        $stmt = $pdo->query("SELECT * FROM projects ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create($data) {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("INSERT INTO projects (name, client_name, description, end_date, start_date, total_hours, status, progress, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        return $stmt->execute([
            $data['name'], $data['client_name'], $data['description'],
            $data['end_date'], $data['start_date'], $data['total_hours'],
            $data['status'], $data['progress']
        ]);
    }

    public static function update($id, $data) {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("UPDATE projects SET name = ?, client_name = ?, description = ?, end_date = ?, start_date = ?, total_hours = ?, status = ?, progress = ? WHERE id = ?");
        return $stmt->execute([
            $data['name'], $data['client_name'], $data['description'],
            $data['end_date'], $data['start_date'], $data['total_hours'],
            $data['status'], $data['progress'], $id
        ]);
    }

    public static function delete($id) {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
        return $stmt->execute([$id]);
    }
}

