<?php
require_once __DIR__ . '/../../config/Database.php';

class InventoryController {
    public function index() {
        $pdo = Database::connect();
        
        $filter = $_GET['filter'] ?? 'all';
        if ($filter !== 'all') {
            $stmt = $pdo->prepare("SELECT * FROM inventory WHERE type = ? ORDER BY created_at DESC");
            $stmt->execute([$filter]);
        } else {
            $stmt = $pdo->query("SELECT * FROM inventory ORDER BY created_at DESC");
        }
        $inventoryItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        require_once __DIR__ . '/../views/inventory/index.php';
    }
    
    public function store() {
        $pdo = Database::connect();
        
        $type = $_POST['type'] ?? '';
        $name = $_POST['name'] ?? '';
        $quantity = $_POST['quantity'] ?? 0;
        
        $stmt = $pdo->prepare("INSERT INTO inventory (type, name, quantity) VALUES (?, ?, ?)");
        $stmt->execute([$type, $name, $quantity]);
        
        header("Location: /ams-malergeschaft/public/inventory");
        exit;
    }
}
