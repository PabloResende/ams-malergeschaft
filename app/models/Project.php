<?php

require_once(__DIR__ . '/../../config/Database.php');

class Project {
    public function getAll() {
        $pdo = Database::connect();
        $stmt = $pdo->query("SELECT * FROM projects ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create($name, $description, $start_date, $end_date, $total_hours) {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("INSERT INTO projects (name, description, start_date, end_date, total_hours) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $start_date, $end_date, $total_hours]);
    }
}
