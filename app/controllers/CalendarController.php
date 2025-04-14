<?php
require_once __DIR__ . '/../models/Calendar.php';
require_once __DIR__ . '/../../config/Database.php';

class CalendarController {

    public function index() {
        $pdo = Database::connect();
        $reminders = Reminder::getAll($pdo);
        include __DIR__ . '/../views/calendar/index.php';
    }
public function store() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $pdo = Database::connect();
            
            // Validação básica
            if (empty($_POST['title'])) {
                throw new Exception('Título é obrigatório');
            }
            
            if (empty($_POST['reminder_date'])) {
                throw new Exception('Data é obrigatória');
            }

            $data = [
                'title'         => $_POST['title'],
                'reminder_date' => $_POST['reminder_date'],
                'color'         => $_POST['color'] ?? '#3b82f6'
            ];

            $created = Reminder::create($pdo, $data);
            
            echo json_encode([
                'success' => $created,
                'message' => $created ? 'Lembrete salvo!' : 'Erro ao salvar'
            ]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
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
