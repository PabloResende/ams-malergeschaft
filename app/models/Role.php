<?php
// app/models/Role.php
require_once __DIR__ . '/../../config/database.php';

class Role
{
    /** @var \PDO */
    private $pdo;

    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * Retorna todas as roles
     *
     * @return array [ ['id'=>1,'name'=>'admin'], ... ]
     */
    public static function all(): array
    {
        global $pdo;
        $stmt = $pdo->query("SELECT id, name FROM roles ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca uma role pelo ID
     *
     * @param int $id
     * @return array|null ['id'=>1,'name'=>'admin'] ou null se não existir
     */
    public static function find(int $id): ?array
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT id, name FROM roles WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
