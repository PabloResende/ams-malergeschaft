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
            header("Location: $basePath/login");
            exit;
        }
        include __DIR__ . '/../views/analytics/index.php';
    }

    public function stats() {
        header('Content-Type: application/json');
        $y = (int)($_GET['year'] ?? date('Y'));
        $q = $_GET['quarter']  ?? '';
        $s = $_GET['semester'] ?? '';
        echo json_encode(Analytics::getStats($y, $q, $s));
        exit;
    }
}
