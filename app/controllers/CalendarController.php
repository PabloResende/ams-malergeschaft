<?php
// system/app/controllers/CalendarController.php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/Calendar.php';

class CalendarController
{
    /** @var \PDO */
    private \PDO $pdo;

    /** @var CalendarModel */
    private CalendarModel $calModel;

    /** @var array */
    private array $langText;

    /** @var string */
    private string $baseUrl;

    public function __construct()
    {
        // 1) Sessão e idioma
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'pt';
        $_SESSION['lang'] = $lang;
        $langFile = __DIR__ . '/../lang/' . $lang . '.php';
        if (!file_exists($langFile)) {
            $langFile = __DIR__ . '/../lang/pt.php';
        }
        $this->langText = require $langFile;

        // 2) BASE_URL
        $this->baseUrl = BASE_URL;

        // 3) PDO global
        global $pdo;
        $this->pdo = $pdo;

        // 4) Model de lembretes
        $this->calModel = new CalendarModel();
    }

    /**
     * Exibe a página do calendário.
     */
    public function index(): void
    {
        $langText = $this->langText;
        $baseUrl  = $this->baseUrl;
        require __DIR__ . '/../views/calendar/index.php';
    }

    /**
     * Retorna JSON com os eventos (lembranças).
     * Se receberem start e end no GET, filtra pelo período.
     */
    public function fetch(): void
    {
        $start = $_GET['start'] ?? null;
        $end   = $_GET['end']   ?? null;

        if ($start !== null && $end !== null) {
            $events = $this->calModel->getEventsInRange($start, $end);
        } else {
            $events = $this->calModel->getAllEvents();
        }

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($events);
        exit;
    }

    /**
     * Armazena um novo lembrete enviado em JSON e retorna o evento criado.
     */
    public function store(): void
    {
        // Recebe JSON
        $raw  = file_get_contents('php://input');
        $data = json_decode($raw, true);

        // Validação básica
        if (
            ! is_array($data) ||
            empty($data['title']) ||
            empty($data['reminder_date']) ||
            empty($data['color'])
        ) {
            http_response_code(400);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['error' => 'Dados inválidos']);
            exit;
        }

        // Persiste via model e obtém o evento completo
        $event = $this->calModel->storeEvent($data);

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($event);
        exit;
    }
}
