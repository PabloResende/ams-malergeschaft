<?php

$pdo = Database::connect();

$lowStockLimit = 100;

// Lembretes de calendário (avisa no dia e nos 2 dias anteriores)
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$afterTomorrow = date('Y-m-d', strtotime('+2 days'));

$stmt = $pdo->prepare("SELECT title, reminder_date FROM reminders WHERE reminder_date IN (?, ?, ?)");
$stmt->execute([$today, $tomorrow, $afterTomorrow]);
$reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($reminders as $r) {
    $dateDiff = (strtotime($r['reminder_date']) - strtotime($today)) / 86400;

    if ($dateDiff == 0) {
        $notifications[] = "Lembrete de hoje: '{$r['title']}'";
    } elseif ($dateDiff == 1) {
        $notifications[] = "Lembrete amanhã: '{$r['title']}'";
    } elseif ($dateDiff == 2) {
        $notifications[] = "Lembrete em 2 dias: '{$r['title']}'";
    }
}

// Notificações de estoque baixo para materiais
$stmt = $pdo->prepare("SELECT id, name, quantity FROM inventory WHERE quantity < ? AND type = 'material'");
$stmt->execute([$lowStockLimit]);
$lowStockItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

$notifications = [];

foreach ($lowStockItems as $item) {
    $notifications[] = "{$item['quantity']}x {$item['name']}.";
}

// Notificações de projetos: avisar quando a data de entrega é daqui a 3 dias
$threeDaysFromNow = date('Y-m-d', strtotime('+3 days'));
$stmt = $pdo->prepare("SELECT id, name, end_date FROM projects WHERE end_date = ?");
$stmt->execute([$threeDaysFromNow]);
$projectNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($projectNotifications as $project) {
    $notifications[] = "Projeto '{$project['name']}' vence em 3 dias!";
}

return $notifications;
?>
