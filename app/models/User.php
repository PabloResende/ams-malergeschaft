<?php
// app/models/User.php

require_once __DIR__ . '/../../config/database.php';

class UserModel
{
    /** @var \PDO */
    private \PDO $pdo;

    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * Cria um usuário
     *
     * @return bool
     */
    public function create(string $name, string $email, string $password, string $role = 'employee'): bool
    {
        $sql = "
            INSERT INTO users (name, email, password, role)
            VALUES (:name, :email, :password, :role)
        ";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'name'     => $name,
            'email'    => $email,
            'password' => $password,
            'role'     => $role,
        ]);
    }

    /**
     * Busca usuário pelo ID
     */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT * 
              FROM users 
             WHERE id = :id 
             LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    /**
     * Busca usuário pelo e-mail
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT * 
              FROM users 
             WHERE email = :email 
             LIMIT 1
        ");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    /**
     * Atualiza usuário (nome, email, senha opcional e role)
     */
   public function update(
    int $id,
    string $name,
    string $email,
    ?string $password = null,
    string $role = 'employee'
): bool {
    if ($password !== null) {
        $sql = "
          UPDATE users
             SET name     = :name,
                 email    = :email,
                 password = :password,
                 role     = :role
           WHERE id = :id
        ";
        $params = [
          'name'     => $name,
          'email'    => $email,
          'password' => $password,
          'role'     => $role,
          'id'       => $id,
        ];
    } else {
        $sql = "
          UPDATE users
             SET name  = :name,
                 email = :email,
                 role  = :role
           WHERE id = :id
        ";
        $params = [
          'name'  => $name,
          'email' => $email,
          'role'  => $role,
          'id'    => $id,
        ];
    }

    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute($params);
}

}
