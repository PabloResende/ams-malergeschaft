<?php

$pdo = Database::connect();

$lowStockLimit = 100;

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
