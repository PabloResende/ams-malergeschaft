<?php
// app/models/Clients.php

require_once __DIR__ . '/../../config/database.php';

class Client
{
    /**
     * Retorna todos os clientes cadastrados.
     *
     * @return array
     */
    public static function all()
    {
        $pdo  = Database::connect();
        $stmt = $pdo->query("SELECT * FROM client ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Encontra um cliente pelo ID.
     *
     * @param int $id
     * @return array|false
     */
    public static function find($id)
    {
        $pdo  = Database::connect();
        $stmt = $pdo->prepare("SELECT * FROM client WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Cria um novo cliente.
     *
     * @param array $data
     * @return bool
     */
    public static function create($data)
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("
            INSERT INTO client
              (name, address, phone, active, loyalty_points)
            VALUES
              (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['name'],
            $data['address']        ?? null,
            $data['phone']          ?? null,
            $data['active']         ?? 1,
            // inicializa sempre 0, mas fidelidade desativada em view/js
            $data['loyalty_points'] ?? 0
        ]);
    }

    /**
     * Atualiza um cliente existente.
     *
     * @param int   $id
     * @param array $data
     * @return bool
     */
    public static function update($id, $data)
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("
            UPDATE client SET
              name           = ?,
              address        = ?,
              phone          = ?,
              active         = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['name'],
            $data['address'] ?? null,
            $data['phone']   ?? null,
            $data['active']  ?? 1,
            $id
        ]);
    }

    /**
     * Remove um cliente.
     *
     * @param int $id
     * @return bool
     */
    public static function delete($id)
    {
        $pdo  = Database::connect();
        $stmt = $pdo->prepare("DELETE FROM client WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Conta quantos projetos estão associados a este cliente.
     *
     * @param int $id
     * @return int
     */
    public static function countProjects($id)
    {
        $pdo  = Database::connect();
        $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM projects WHERE client_id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * Atualiza os pontos de fidelidade do cliente.
     * ATENÇÃO: método comentado para desativar fidelidade temporariamente.
     */
    /*
    public static function setPoints($id, $points)
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("
            UPDATE client
               SET loyalty_points = ?
             WHERE id = ?
        ");
        return $stmt->execute([$points, $id]);
    }
    */
}
