<?php
// app/models/InventoryModel.php
require_once __DIR__ . '/../../config/Database.php';

class InventoryModel {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::connect();
    }

    public function getAll($filter = 'all') {
        if ($filter !== 'all') {
            $stmt = $this->pdo->prepare("SELECT * FROM inventory WHERE type = ? ORDER BY created_at DESC");
            $stmt->execute([$filter]);
        } else {
            $stmt = $this->pdo->query("SELECT * FROM inventory ORDER BY created_at DESC");
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM inventory WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function insert($type, $name, $quantity) {
        $stmt = $this->pdo->prepare("INSERT INTO inventory (type, name, quantity) VALUES (?, ?, ?)");
        return $stmt->execute([$type, $name, $quantity]);
    }

    public function update($id, $type, $name, $quantity) {
        $stmt = $this->pdo->prepare("UPDATE inventory SET type = ?, name = ?, quantity = ? WHERE id = ?");
        return $stmt->execute([$type, $name, $quantity, $id]);
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM inventory WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /** Acrescenta quantidade ao item */
    public function addQuantity(int $id, int $qty): bool {
        $stmt = $this->pdo->prepare("UPDATE inventory SET quantity = quantity + ? WHERE id = ?");
        return $stmt->execute([$qty, $id]);
    }

    /** Subtrai quantidade do item */
    public function subtractQuantity(int $id, int $qty): bool {
        $stmt = $this->pdo->prepare("UPDATE inventory SET quantity = quantity - ? WHERE id = ?");
        return $stmt->execute([$qty, $id]);
    }
}
