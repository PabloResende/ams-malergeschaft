<?php
// app/models/Clients.php

require_once __DIR__ . '/../../config/Database.php';

class Client
{
    /**
     * Retorna todos os clients ordenados por nome.
     *
     * @return array
     */
    public static function all()
    {
        $pdo = Database::connect();
        $stmt = $pdo->query("SELECT * FROM client ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca um client pelo ID.
     *
     * @param int $id
     * @return array|false
     */
    public static function find($id)
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT * FROM client WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Cria um novo client.
     * Atenção: a tabela client deve ter colunas
     * name, address, about, phone, profile_picture, active, loyalty_points.
     *
     * @param array $data
     * @return bool
     */
    public static function create($data)
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("
            INSERT INTO client
              (name, address, about, phone, profile_picture, active, loyalty_points)
            VALUES
              (?, ?, ?, ?, ?, ?, 0)
        ");
        return $stmt->execute([
            $data['name'],
            $data['address'],
            $data['about'],
            $data['phone'],
            $data['profile_picture'] ?? null,
            $data['active'] ?? 1
        ]);
    }

    /**
     * Atualiza os dados de um client existente.
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
              name            = ?,
              address         = ?,
              about           = ?,
              phone           = ?,
              profile_picture = ?,
              active          = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['name'],
            $data['address'],
            $data['about'],
            $data['phone'],
            $data['profile_picture'] ?? null,
            $data['active'] ?? 1,
            $id
        ]);
    }

    /**
     * Remove um client.
     *
     * @param int $id
     * @return bool
     */
    public static function delete($id)
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("DELETE FROM client WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Incrementa pontos de fidelidade.
     *
     * @param int $id
     * @param int $points
     * @return bool
     */
    public static function updatePoints($id, $points)
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("
            UPDATE client
            SET loyalty_points = loyalty_points + ?
            WHERE id = ?
        ");
        return $stmt->execute([$points, $id]);
    }

    /**
     * Seta exatamente a quantidade de pontos de fidelidade.
     *
     * @param int $id
     * @param int $points
     * @return bool
     */
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

    /**
     * Conta quantos projetos este client já realizou.
     *
     * @param int $id
     * @return int
     */
    public static function countProjects($id)
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS cnt
            FROM projects
            WHERE client_id = ?
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['cnt'] ?? 0);
    }
}
