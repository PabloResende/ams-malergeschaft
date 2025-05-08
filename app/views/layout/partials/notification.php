<?php
// app/views/layout/partials/notification.php

// 1) Inicia sessão se ainda não estiver ativa
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// 2) Conexão com o banco
require_once __DIR__ . '/../../../../config/Database.php';
$pdo     = Database::connect();
$baseUrl = '/ams-malergeschaft/public';

// 3) Função helper: adiciona notificação se não estiver marcada como lida
function addNotif(array &$arr, string $key, string $text, string $url): void {
    // obtém sempre um array
    $readKeys = $_SESSION['read_notifications'] ?? [];
    if (! is_array($readKeys)) {
        $readKeys = [];
    }
    if (! in_array($key, $readKeys, true)) {
        $arr[] = compact('key', 'text', 'url');
    }
}

// 4) Inicializa o array de notificações
$notifications = [];

// ——— Materiais com estoque < 10 ———
$stmt = $pdo->query("
    SELECT id, name, quantity
    FROM inventory
    WHERE type = 'material' AND quantity < 10
");
while ($it = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $key  = "inventory_{$it['id']}";
    $text = $it['quantity'] == 0
        ? "Acabou: «{$it['name']}»"
        : "Tem {$it['quantity']}× «{$it['name']}»";
    $url  = "{$baseUrl}/inventory?show={$it['id']}";
    addNotif($notifications, $key, $text, $url);
}

// ——— Projetos vencendo em ≤ 5 dias ———
$today = date('Y-m-d');
$limit = date('Y-m-d', strtotime('+5 days'));
$stmt  = $pdo->prepare("
    SELECT id, name, end_date
    FROM projects
    WHERE end_date BETWEEN ? AND ?
");
$stmt->execute([$today, $limit]);
while ($pj = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $diff  = (strtotime($pj['end_date']) - strtotime($today)) / 86400;
    $dias  = (int)$diff;
    $label = $dias === 0
        ? "vence hoje"
        : ($dias === 1 ? "vence em 1 dia" : "vence em {$dias} dias");
    $key   = "project_{$pj['id']}";
    $text  = "Projeto «{$pj['name']}» {$label}!";
    $url   = "{$baseUrl}/projects?show={$pj['id']}";
    addNotif($notifications, $key, $text, $url);
}

// ——— Clientes com ≥ 5 projetos ———
$stmt = $pdo->query("
    SELECT c.id, c.name, COUNT(p.id) AS cnt
    FROM client c
    JOIN projects p ON p.client_id = c.id
    GROUP BY c.id
    HAVING cnt >= 5
");
while ($c = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $key  = "client_{$c['id']}";
    $text = "Cliente «{$c['name']}» fez {$c['cnt']} projetos";
    $url  = "{$baseUrl}/clients?show={$c['id']}";
    addNotif($notifications, $key, $text, $url);
}

// 5) Retorna apenas notificações não-lidas
return $notifications;
