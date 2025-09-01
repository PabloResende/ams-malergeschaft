<?php
// app/controllers/AnalyticsController.php

require_once __DIR__ . '/../models/Analytics.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../../config/config.php';

class AnalyticsController
{
    public function index() {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (!isset($_SESSION['user'])) {
            header("Location: " . BASE_URL . "/login");
            exit;
        }
        include __DIR__ . '/../views/analytics/index.php';
    }
    
  public function stats(): void
    {
        // 1) cabeçalho JSON
        header('Content-Type: application/json; charset=utf-8');

        // 2) parâmetros antes de tudo
        $year     = (int)($_GET['year']     ?? date('Y'));
        $quarter  = $_GET['quarter']  ?? '';
        $semester = $_GET['semester'] ?? '';

        // 3) use a conexão que o database.php já definiu
        global $pdo;

        // 4) gere as estatísticas
        try {
            $stats = (new Analytics($pdo))->getStats($year, $quarter, $semester);
            echo json_encode($stats);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Falha ao gerar estatísticas',
                'detail' => $e->getMessage()
            ]);
        }
        exit;
    }
}
