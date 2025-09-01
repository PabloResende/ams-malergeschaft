<?php
// system/app/models/CarHistoryModel.php

require_once __DIR__ . '/../../config/database.php';

class CarHistoryModel
{
    private \PDO $pdo;

    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
    }

    public function insertMovement(string $user, string $datetime, string $action, int $carId, ?string $details): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO car_movements
              (user_name, datetime, action, car_id, details)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user, $datetime, $action, $carId, $details]);
        return (int)$this->pdo->lastInsertId();
    }

    public function getAllMovements(): array
    {
        $stmt = $this->pdo->query("
            SELECT * FROM car_movements ORDER BY datetime DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
