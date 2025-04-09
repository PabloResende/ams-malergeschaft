<?php
require_once __DIR__ . '/../models/Reminder.php';
require_once __DIR__ . '/../../config/Database.php';

class CalendarController {

    public function index() {
        $pdo = Database::connect();
        $reminders = Reminder::getAll($pdo);
        include __DIR__ . '/../views/calendar/index.php';
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $pdo = Database::connect();
            $data = [
                'title'         => $_POST['title'] ?? '',
                'description'   => $_POST['description'] ?? '',
                'reminder_date' => $_POST['reminder_date'] ?? '',
                'color'         => $_POST['color'] ?? '#00ff00'
            ];
            $created = Reminder::create($pdo, $data);
            header('Content-Type: application/json');
            echo json_encode(['success' => $created]);
            exit;
        }
    }

    public function fetch() {
        $pdo = Database::connect();
        $reminders = Reminder::getAll($pdo);
        header('Content-Type: application/json');
        echo json_encode($reminders);
        exit;
    }
}
