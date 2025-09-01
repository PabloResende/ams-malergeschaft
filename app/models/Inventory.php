<?php
// system/app/models/InventoryModel.php

require_once __DIR__ . '/../../config/database.php';

class InventoryModel
{
    /** @var \PDO */
    private \PDO $pdo;

    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
    }

    public function getAll(string $filter = 'all'): array
    {
        if ($filter === 'all') {
            $stmt = $this->pdo->query("
                SELECT *
                  FROM inventory
              ORDER BY created_at DESC
            ");
        } else {
            $stmt = $this->pdo->prepare("
                SELECT *
                  FROM inventory
                 WHERE type = :type
              ORDER BY created_at DESC
            ");
            $stmt->execute(['type' => $filter]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT *
              FROM inventory
             WHERE id = :id
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function insert(string $type, string $name, int $quantity): bool
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO inventory
              (type, name, quantity, created_at)
            VALUES
              (:type, :name, :quantity, NOW())
        ");
        return $stmt->execute([
            'type'     => $type,
            'name'     => $name,
            'quantity' => $quantity,
        ]);
    }

    public function update(int $id, string $type, string $name, int $quantity): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE inventory SET
              type     = :type,
              name     = :name,
              quantity = :quantity
            WHERE id = :id
        ");
        return $stmt->execute([
            'type'     => $type,
            'name'     => $name,
            'quantity' => $quantity,
            'id'       => $id,
        ]);
    }

    /**
     * Remove um item de inventário e limpa todas as referências
     */
    public function delete(int $id): bool
    {
        try {
            $this->pdo->beginTransaction();

            // 1) apaga detalhes de movimentação
            $stmt1 = $this->pdo->prepare("
                DELETE FROM inventory_movement_details
                 WHERE item_id = ?
            ");
            $stmt1->execute([$id]);

            // 2) apaga alocações em projetos
            $stmt2 = $this->pdo->prepare("
                DELETE FROM project_resources
                 WHERE resource_type = 'inventory'
                   AND resource_id = ?
            ");
            $stmt2->execute([$id]);

            // 3) finalmente apaga o item
            $stmt3 = $this->pdo->prepare("
                DELETE FROM inventory
                 WHERE id = ?
            ");
            $res = $stmt3->execute([$id]);

            $this->pdo->commit();
            return $res;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function addQuantity(int $id, int $qty): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE inventory
               SET quantity = quantity + :qty
             WHERE id = :id
        ");
        return $stmt->execute([
            'qty' => $qty,
            'id'  => $id,
        ]);
    }

    public function subtractQuantity(int $id, int $qty): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE inventory
               SET quantity = quantity - :qty
             WHERE id = :id
        ");
        return $stmt->execute([
            'qty' => $qty,
            'id'  => $id,
        ]);
    }
}
