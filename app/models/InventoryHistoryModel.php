<?php
// system/app/models/InventoryHistoryModel.php

require_once __DIR__ . '/../../config/database.php';

class InventoryHistoryModel
{
    /** @var \PDO */
    private \PDO $pdo;

    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * Insere 1 movimento mestre + vários detalhes.
     *
     * @param string      $user
     * @param string      $datetime
     * @param string      $reason
     * @param int|null    $projectId
     * @param string|null $custom
     * @param array       $items      [ itemId => qty, … ] ou ['new_item'=>[…]]
     * @return int ID do movimento inserido
     */
    public function insertMovement(
        string $user,
        string $datetime,
        string $reason,
        ?int $projectId,
        ?string $custom,
        array $items
    ): int {
        $this->pdo->beginTransaction();

        // Mestre
        $stmt = $this->pdo->prepare("
            INSERT INTO inventory_movements
              (user_name, datetime, reason, project_id, custom_reason)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user, $datetime, $reason, $projectId, $custom]);
        $movementId = (int)$this->pdo->lastInsertId();

        // Detalhes
        $detail = $this->pdo->prepare("
            INSERT INTO inventory_movement_details
              (movement_id, item_id, quantity)
            VALUES (?, ?, ?)
        ");

        if (isset($items['new_item'])) {
            $ni = $items['new_item'];
            $detail->execute([
                $movementId,
                (int)$ni['id'],
                (int)$ni['quantity']
            ]);
        } else {
            foreach ($items as $id => $qty) {
                $detail->execute([
                    $movementId,
                    (int)$id,
                    (int)$qty
                ]);
            }
        }

        $this->pdo->commit();
        return $movementId;
    }

    /**
     * Retorna todas as movimentações (sem detalhes).
     *
     * @return array
     */
    public function getAllMovements(): array
    {
        $stmt = $this->pdo->query("
            SELECT id, user_name, datetime, reason, project_id, custom_reason
              FROM inventory_movements
          ORDER BY datetime DESC
        ");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Retorna mestre + detalhes de 1 movimentação.
     *
     * @param int $movementId
     * @return array ['master'=>[…], 'details'=>[…]]
     */
    public function getMovementWithDetails(int $movementId): array
    {
        // Mestre + projeto
        $stmt = $this->pdo->prepare("
            SELECT
              m.id,
              m.user_name,
              m.datetime,
              m.reason,
              m.custom_reason,
              p.id   AS project_id,
              p.name AS project_name
            FROM inventory_movements m
            LEFT JOIN projects p ON p.id = m.project_id
            WHERE m.id = ?
        ");
        $stmt->execute([$movementId]);
        $master = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];

        // Detalhes
        $stmt2 = $this->pdo->prepare("
            SELECT
              d.item_id,
              i.name     AS item_name,
              d.quantity
            FROM inventory_movement_details d
            JOIN inventory i ON i.id = d.item_id
            WHERE d.movement_id = ?
        ");
        $stmt2->execute([$movementId]);
        $details = $stmt2->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'master'  => $master,
            'details' => $details,
        ];
    }
}
