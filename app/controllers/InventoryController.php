<?php
// app/controllers/InventoryController.php
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
        if ($stmt->execute([$type, $name, $quantity])) {
            header("Location: /ams-malergeschaft/public/inventory");
            exit;
        } else {
            echo "Error storing inventory item.";
        }
    }
    
    public function edit() {
        if (!isset($_GET['id'])) {
            echo "Inventory item ID not provided.";
            exit;
        }
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT * FROM inventory WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$item) {
            echo "Inventory item not found.";
            exit;
        }
        require_once __DIR__ . '/../views/inventory/edit.php';
    }
    
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? '';
            $type = $_POST['type'] ?? '';
            $name = $_POST['name'] ?? '';
            $quantity = $_POST['quantity'] ?? 0;
            
            if (empty($id) || empty($name)) {
                echo "Required fields missing.";
                exit;
            }
            $pdo = Database::connect();
            $stmt = $pdo->prepare("UPDATE inventory SET type = ?, name = ?, quantity = ? WHERE id = ?");
            if ($stmt->execute([$type, $name, $quantity, $id])) {
                header("Location: /ams-malergeschaft/public/inventory");
                exit;
            } else {
                echo "Error updating inventory item.";
            }
        }
    }
    
    public function delete() {
        if (!isset($_GET['id'])) {
            echo "Inventory item ID not provided.";
            exit;
        }
        $pdo = Database::connect();
        $stmt = $pdo->prepare("DELETE FROM inventory WHERE id = ?");
        if ($stmt->execute([$_GET['id']])) {
            header("Location: /ams-malergeschaft/public/inventory");
            exit;
        } else {
            echo "Error deleting inventory item.";
        }
    }
}
