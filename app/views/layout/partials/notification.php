<?php
require_once __DIR__ . '/../../../../config/Database.php';
$pdo     = Database::connect();
$baseUrl = '/ams-malergeschaft/public';

$notifications = [];

// —————————————————————————————————————————————
// 1) Materiais: se 0 → “acabou”, se ≥1 e <10 → “Tem x nome”
// —————————————————————————————————————————————
$stmt = $pdo->prepare("
    SELECT id, name, quantity
    FROM inventory
    WHERE type = 'material' AND quantity < 10
");
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($items as $it) {
    if ($it['quantity'] == 0) {
        $text = "Acabou: «{$it['name']}»";
    } else {
        $text = "Tem {$it['quantity']}× «{$it['name']}»";
    }
    $notifications[] = [
        'text' => $text,
        'url'  => "{$baseUrl}/inventory/show/{$it['id']}"
    ];
}

// —————————————————————————————————————————————
// 2) Projetos com prazo curto: faltam ≤5 dias
// —————————————————————————————————————————————
$today    = date('Y-m-d');
$maxDate  = date('Y-m-d', strtotime('+5 days'));
$stmt = $pdo->prepare("
    SELECT id, name, end_date
    FROM projects
    WHERE end_date BETWEEN ? AND ?
");
$stmt->execute([$today, $maxDate]);
$pps = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($pps as $pj) {
    $diff = (strtotime($pj['end_date']) - strtotime($today)) / 86400;
    $dias = (int) $diff;
    $label = $dias === 0
        ? "vence hoje"
        : ($dias === 1 ? "vence em 1 dia" : "vence em {$dias} dias");
    $notifications[] = [
        'text' => "Projeto «{$pj['name']}» {$label}!",
        'url'  => "{$baseUrl}/projects/show/{$pj['id']}"
    ];
}

// —————————————————————————————————————————————
// 3) Clientes fiéis: ≥5 projetos
// —————————————————————————————————————————————
$stmt = $pdo->query("
    SELECT c.id, c.name, COUNT(p.id) AS cnt
    FROM client c
    JOIN projects p ON p.client_id = c.id
    GROUP BY c.id
    HAVING cnt >= 5
");
$cls = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($cls as $c) {
    $notifications[] = [
        'text' => "Cliente «{$c['name']}» fez {$c['cnt']} projetos",
        'url'  => "{$baseUrl}/clients/show/{$c['id']}"
    ];
}

return $notifications;
