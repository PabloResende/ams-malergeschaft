<?php
// system/app/models/CarModel.php

require_once __DIR__ . '/../../config/database.php';

class CarModel
{
    private \PDO $pdo;

    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
    }

    public function getAll(): array
    {
        return $this->pdo
            ->query("SELECT * FROM cars ORDER BY created_at DESC")
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM cars WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function insert(
        string $manufacturer,
        string $model,
        int    $year,
        string $plate,
        int    $mileage,
        string $color
    ): bool {
        $stmt = $this->pdo->prepare("
            INSERT INTO cars
              (manufacturer, model, year, plate, mileage, color, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([
            $manufacturer,
            $model,
            $year,
            $plate,
            $mileage,
            $color
        ]);
    }

    public function update(
        int    $id,
        string $manufacturer,
        string $model,
        int    $year,
        string $plate,
        int    $mileage,
        string $color
    ): bool {
        $stmt = $this->pdo->prepare("
            UPDATE cars SET
              manufacturer = ?,
              model        = ?,
              year         = ?,
              plate        = ?,
              mileage      = ?,
              color        = ?,
              updated_at   = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([
            $manufacturer,
            $model,
            $year,
            $plate,
            $mileage,
            $color,
            $id
        ]);
    }

    public function delete(int $id): bool
    {
        return $this->pdo
            ->prepare("DELETE FROM cars WHERE id = ?")
            ->execute([$id]);
    }
}
