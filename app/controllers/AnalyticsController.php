<?php
require_once __DIR__ . '/../models/Analytics.php';

class AnalyticsController {
    public function index() {
        include __DIR__ . '/../views/analytics/index.php';
    }

    public function stats() {
        header('Content-Type: application/json');
        $year = $_GET['year'] ?? date('Y');
        $quarter = $_GET['quarter'] ?? '';
        $semester = $_GET['semester'] ?? '';
        echo json_encode(Analytics::getStats($year, $quarter, $semester));
        exit;
    }
}
