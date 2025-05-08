<?php
require_once __DIR__ . '/../../config/Database.php';

class Payment {
    private $pdo;
    public function __construct() {
        $this->pdo = Database::connect();
    }
    public function create(array $d) {
        $stmt = $this->pdo->prepare("
            INSERT INTO payments (intent_id, amount, currency, status, created_at)
            VALUES (:intent_id, :amount, :currency, :status, :created_at)
        ");
        $stmt->execute($d);
    }
    public function updateStatus(string $intentId, string $status) {
        $stmt = $this->pdo->prepare("
            UPDATE payments SET status = :status WHERE intent_id = :intent_id
        ");
        $stmt->execute(['intent_id'=>$intentId,'status'=>$status]);
    }
}
