<?php
require_once __DIR__ . '/../../../../config/Database.php';
$pdo = Database::connect();

$notifications = [];

// 1) Lembretes de calendário: hoje, amanhã e depois de amanhã
$today          = date('Y-m-d');
$tomorrow       = date('Y-m-d', strtotime('+1 day'));
$afterTomorrow  = date('Y-m-d', strtotime('+2 days'));

$stmt = $pdo->prepare("
    SELECT title, reminder_date
    FROM reminders
    WHERE reminder_date IN (?, ?, ?)
");
$stmt->execute([$today, $tomorrow, $afterTomorrow]);
$reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($reminders as $r) {
    $diff = (strtotime($r['reminder_date']) - strtotime($today)) / 86400;
    if ($diff === 0) {
        $notifications[] = "Lembrete de hoje: «{$r['title']}»";
    } elseif ($diff === 1) {
        $notifications[] = "Lembrete amanhã: «{$r['title']}»";
    } elseif ($diff === 2) {
        $notifications[] = "Lembrete daqui a 2 dias: «{$r['title']}»";
    }
}

// 2) Estoque baixo: materiais com quantidade abaixo do limite
$lowStockLimit = 100;
$stmt = $pdo->prepare("
    SELECT name, quantity
    FROM inventory
    WHERE quantity < ? AND type = 'material'
");
$stmt->execute([$lowStockLimit]);
$lowStockItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($lowStockItems as $item) {
    $notifications[] = "Estoque baixo: {$item['quantity']}× «{$item['name']}»";
}

// 3) Projetos vencendo em 3 dias
$dueDate = date('Y-m-d', strtotime('+3 days'));
$stmt = $pdo->prepare("
    SELECT name
    FROM projects
    WHERE end_date = ?
");
$stmt->execute([$dueDate]);
$dueProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($dueProjects as $pj) {
    $notifications[] = "Projeto «{$pj['name']}» vence em 3 dias!";
}

// 4) Fidelidade de clientes: contar quantos projetos cada um já fez
$stmt = $pdo->query("
    SELECT c.name, COUNT(p.id) AS project_count
    FROM client c
    JOIN projects p ON p.client_id = c.id
    GROUP BY c.id
    HAVING project_count >= 5
");
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($clients as $c) {
    if ($c['project_count'] >= 10) {
        $notifications[] = "Cliente «{$c['name']}» já fez {$c['project_count']} projetos (nível VIP)";
    } else {
        $notifications[] = "Cliente «{$c['name']}» já fez {$c['project_count']} projetos";
    }
}

return $notifications;
