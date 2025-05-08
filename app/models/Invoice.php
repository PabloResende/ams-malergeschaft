<?php
require_once __DIR__ . '/../../config/Database.php';

class Invoice {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::connect();
    }

    public function create(array $data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO invoices (number, client_name, client_email, amount, issue_date, due_date, status)
            VALUES (:number, :client_name, :client_email, :amount, :issue_date, :due_date, :status)
        ");
        $stmt->execute($data);
        return $this->pdo->lastInsertId();
    }

    public function find($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM invoices WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
