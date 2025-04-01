<?php

$pdo = Database::connect();

$lowStockLimit = 100;

// Se existir uma coluna 'type' que identifica o tipo do item (material, produto, etc.)
$stmt = $pdo->prepare("SELECT id, name, quantity FROM inventory WHERE quantity < ? AND type = 'material'");
$stmt->execute([$lowStockLimit]);
$lowStockItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

$notifications = [];

foreach ($lowStockItems as $item) {
    $notifications[] = "{$item['quantity']}x {$item['name']}.";
}

return $notifications;
?>
