<?php

require_once(__DIR__ . '/../../config/Database.php');

class Reminder {

    public static function getAll($pdo) {
        $today = date('Y-m-d');
        $stmt = $pdo->prepare("SELECT * FROM reminders WHERE reminder_date >= ? ORDER BY reminder_date ASC");
        $stmt->execute([$today]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create($pdo, $data) {
        $stmt = $pdo->prepare("INSERT INTO reminders (title, reminder_date, color) VALUES (?, ?, ?)");
        return $stmt->execute([
            $data['title'],
            $data['reminder_date'],
            $data['color']
        ]);
    }
}
