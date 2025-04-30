<?php
// app/models/InventoryHistoryModel.php
require_once __DIR__ . '/../../config/Database.php';

class InventoryHistoryModel {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::connect();
    }
    public function insertMovement(
        string $user,
        string $datetime,
        string $reason,
        ?int $projectId,
        ?string $custom,
        array $items
    ): int {
        $this->pdo->beginTransaction();
        // registro mestre
        $stmt = $this->pdo->prepare("
            INSERT INTO inventory_movements
              (user_name, datetime, reason, project_id, custom_reason)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user, $datetime, $reason, $projectId, $custom]);
        $movementId = (int)$this->pdo->lastInsertId();

        // detalhes
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
                $detail->execute([$movementId, (int)$id, (int)$qty]);
            }
        }

        $this->pdo->commit();
        return $movementId;
    }

    /** Retorna todas as movimentações (sem detalhes) */
    public function getAllMovements(): array {
        $stmt = $this->pdo->query("
            SELECT id, user_name, datetime, reason
            FROM inventory_movements
            ORDER BY datetime DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Retorna detalhes de uma movimentação específica */
    public function getMovementDetails(int $movementId): array {
        $stmt = $this->pdo->prepare("
            SELECT d.item_id, i.name AS item_name, d.quantity
            FROM inventory_movement_details d
            JOIN inventory i ON i.id = d.item_id
            WHERE d.movement_id = ?
        ");
        $stmt->execute([$movementId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
