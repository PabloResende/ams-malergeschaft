<?php
require_once __DIR__ . '/../../config/Database.php';
class Payment {
    private $pdo;
    public function __construct() {
        $this->pdo = Database::connect();
    }
    public function create(array $data) {
        $stmt = $this->pdo->prepare("INSERT INTO payments (intent_id, amount, currency, status, created_at) VALUES (:intent_id, :amount, :currency, :status, :created_at)");
        $stmt->execute($data);
    }
    public function updateStatus(string $id, string $status) {
        $stmt = $this->pdo->prepare("UPDATE payments SET status = :status WHERE intent_id = :id");
        $stmt->execute(['id'=>$id,'status'=>$status]);
    }
}

