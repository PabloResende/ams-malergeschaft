<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../../../config/database.php';

global $pdo;

$baseUrl = '<?= BASE_URL ?>';

if (!function_exists('addNotif')) {
    function addNotif(array &$arr, string $key, string $text, string $url): void {
        $arr[] = compact('key', 'text', 'url');
    }
}

$notifications = [];

// ——— Materiais com estoque abaixo de faixas ———
$faixas = [100, 75, 50, 25, 10, 5, 1, 0];
$stmt = $pdo->query("
    SELECT id, name, quantity
    FROM inventory
    WHERE type = 'material' AND quantity < 100
");
while ($it = $stmt->fetch(PDO::FETCH_ASSOC)) {
    foreach ($faixas as $limite) {
        if ($it['quantity'] <= $limite) {
            $key  = "inventory_{$it['id']}_q{$limite}";
            $text = $it['quantity'] == 0
                ? "Acabou: «{$it['name']}»"
                : "Tem {$it['quantity']}× «{$it['name']}» (≤ {$limite})";
            $url  = "{$baseUrl}/inventory?show={$it['id']}";
            addNotif($notifications, $key, $text, $url);
            break; // pega só a menor faixa atingida
        }
    }
}

// ——— Projetos vencendo em até 7 dias ———
$today = date('Y-m-d');
$limit = date('Y-m-d', strtotime('+7 days'));
$stmt = $pdo->prepare("
    SELECT id, name, end_date
    FROM projects
    WHERE end_date BETWEEN ? AND ?
");
$stmt->execute([$today, $limit]);
while ($pj = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $diff = (strtotime($pj['end_date']) - strtotime($today)) / 86400;
    $dias = (int)$diff;

    if ($dias < 0) continue;

    $label = match($dias) {
        0 => "vence hoje",
        1 => "vence em 1 dia",
        default => "vence em {$dias} dias"
    };
    $key  = "project_{$pj['id']}_d{$dias}";
    $text = "Projeto «{$pj['name']}» {$label}!";
    $url  = "{$baseUrl}/projects?show={$pj['id']}";
    addNotif($notifications, $key, $text, $url);
}

// ——— Clientes com múltiplos de 5 projetos ———
$stmt = $pdo->query("
    SELECT c.id, c.name, COUNT(p.id) AS cnt
    FROM client c
    JOIN projects p ON p.client_id = c.id
    GROUP BY c.id
    HAVING cnt >= 5
");
while ($c = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $qtd = (int) $c['cnt'];
    $faixa = floor($qtd / 5) * 5;

    if ($faixa >= 5) {
        $key  = "client_{$c['id']}_p{$faixa}";
        $text = "Cliente «{$c['name']}» fez {$qtd} projetos";
        $url  = "{$baseUrl}/clients?show={$c['id']}";
        addNotif($notifications, $key, $text, $url);
    }
}

return $notifications;
