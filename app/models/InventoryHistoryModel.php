<?php
// app/models/InventoryHistoryModel.php

require_once __DIR__ . '/../../config/Database.php';

class InventoryHistoryModel {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::connect();
    }

    /**
     * Insere movimentos de inventário e atualiza estoque.
     *
     * @param string      $userName       Nome de quem fez a movimentação
     * @param string      $datetime       Data e hora, formato 'Y-m-d H:i:s'
     * @param string      $reason         'projeto', 'perda' ou 'outros'
     * @param string|null $customReason   Motivo personalizado (se 'outros')
     * @param int|null    $projectId      ID de projeto (se 'projeto')
     * @param array       $items          [ item_id => quantidade, ... ]
     */
    public function insertMovement($userName, $datetime, $reason, $customReason, $projectId, array $items) {
        $insert = $this->pdo->prepare("
            INSERT INTO inventory_movements
              (user_name, datetime, reason, custom_reason, project_id, item_id, quantity)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $update = $this->pdo->prepare("
            UPDATE inventory SET quantity = quantity - ? WHERE id = ?
        ");

        foreach ($items as $itemId => $qty) {
            $insert->execute([
                $userName,
                $datetime,
                $reason,
                $customReason,
                $projectId,
                $itemId,
                $qty
            ]);
            $update->execute([$qty, $itemId]);
        }
    }

    /**
     * Retorna todas as movimentações, agrupadas por movimento.
     * Cada linha agrupa todos os itens daquele registro.
     *
     * @return array
     */
    public function getAllMovements() {
        $sql = "
          SELECT
            m.id,
            m.user_name,
            m.datetime,
            m.reason,
            m.custom_reason,
            m.project_id,
            p.name AS project_name,
            GROUP_CONCAT(CONCAT(i.name,' (',m.quantity,')') SEPARATOR ', ') AS items
          FROM inventory_movements m
          LEFT JOIN projects p ON p.id = m.project_id
          JOIN inventory i ON i.id = m.item_id
          GROUP BY m.id
          ORDER BY m.datetime DESC
        ";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna detalhes disjuntos de um movimento (um registro por item).
     *
     * @param int $id  ID do movimento
     * @return array
     */
    public function getMovementDetails($id) {
        $sql = "
          SELECT
            m.id,
            m.user_name,
            m.datetime,
            m.reason,
            m.custom_reason,
            m.project_id,
            p.name AS project_name,
            i.id   AS item_id,
            i.name AS item_name,
            m.quantity
          FROM inventory_movements m
          LEFT JOIN projects p ON p.id = m.project_id
          JOIN inventory i ON i.id = m.item_id
          WHERE m.id = ?
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
