<?php
// app/models/InventoryHistoryModel.php

require_once __DIR__ . '/../../config/Database.php';

class InventoryHistoryModel {
    /**
     * Registra no histórico uma linha por item movimentado.
     *
     * @param string $user         Nome do usuário
     * @param string $datetime     Timestamp da movimentação
     * @param string $reason       Motivo ('projeto','perda','adição','outros','criar')
     * @param int|null $project_id ID do projeto caso motivo='projeto'
     * @param string|null $custom  Texto livre caso motivo='outros'
     * @param array $items         [ item_id => quantidade, ... ]
     * @return bool
     * @throws Exception
     */
    public function insertMovement(
        string $user,
        string $datetime,
        string $reason,
        ?int $project_id,
        ?string $custom,
        array $items
    ): bool {
        $pdo = Database::connect();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("
                INSERT INTO inventory_movements
                  (user_name, datetime, reason, project_id, custom_reason, item_id, quantity)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            foreach ($items as $item_id => $qty) {
                $stmt->execute([
                    $user,
                    $datetime,
                    $reason,
                    $project_id,
                    $custom,
                    $item_id,
                    $qty
                ]);
            }

            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Retorna todos os movimentos (sem detalhes de itens).
     *
     * @return array
     */
    public function getAllMovements(): array {
        $pdo = Database::connect();
        $stmt = $pdo->query("
            SELECT DISTINCT
               id, user_name, datetime, reason, project_id, custom_reason
            FROM inventory_movements
            ORDER BY datetime DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna os itens de um movimento específico.
     *
     * @param int $movementId
     * @return array
     */
    public function getMovementItems(int $movementId): array {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("
            SELECT i.name AS item_name, m.quantity
            FROM inventory_movements m
            JOIN inventory i ON m.item_id = i.id
            WHERE m.id = ?
        ");
        $stmt->execute([$movementId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
