<?php
// system/app/models/CarUsageModel.php

require_once __DIR__ . '/../../config/database.php';

class CarUsageModel
{
    private \PDO $pdo;

    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
    }

    public function insertUsage(
        int    $carId,
        int    $userId,
        string $datetime,
        int    $distance,
        string $unit,
        array  $stopsDetails
    ): int {
        // se não há usuário válido, insere NULL
        $userToInsert = $userId > 0 ? $userId : null;

        $json = json_encode($stopsDetails, JSON_UNESCAPED_UNICODE);
        $stmt = $this->pdo->prepare("
            INSERT INTO car_usages
              (car_id, user_id, datetime, distance, unit, stops_details_json, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bindValue(1, $carId, PDO::PARAM_INT);
        $stmt->bindValue(2, $userToInsert, is_null($userToInsert) ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(3, $datetime, PDO::PARAM_STR);
        $stmt->bindValue(4, $distance, PDO::PARAM_INT);
        $stmt->bindValue(5, $unit, PDO::PARAM_STR);
        $stmt->bindValue(6, $json, PDO::PARAM_STR);
        $stmt->execute();

        return (int)$this->pdo->lastInsertId();
    }

    public function deleteByCarId(int $carId): bool
    {
        return $this->pdo
            ->prepare("DELETE FROM car_usages WHERE car_id = ?")
            ->execute([$carId]);
    }

    public function getAllUsages(): array
    {
        $stmt = $this->pdo->query("
            SELECT u.*, c.manufacturer, c.model, c.plate,
                   usr.name AS user_name
              FROM car_usages u
              JOIN cars c   ON u.car_id = c.id
              LEFT JOIN users usr ON u.user_id = usr.id
          ORDER BY u.datetime DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByCarId(int $carId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT u.*, usr.name AS user_name
              FROM car_usages u
              LEFT JOIN users usr ON u.user_id = usr.id
             WHERE u.car_id = ?
          ORDER BY u.datetime DESC
        ");
        $stmt->execute([$carId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
