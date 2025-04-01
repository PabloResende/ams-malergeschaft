<?php

$pdo = Database::connect();

// Defina o limite para baixo estoque
$lowStockLimit = 100;

// Consulta para pegar os itens com estoque abaixo do limite
$stmt = $pdo->prepare("SELECT id, name, quantity FROM inventory WHERE quantity < ?");
$stmt->execute([$lowStockLimit]);
$lowStockItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

$notifications = [];

foreach ($lowStockItems as $item) {
    $notifications[] = "Só tem {$item['quantity']} unidades de {$item['name']} no estoque.";
}

return $notifications;
?>
