<?php
require_once __DIR__ . '/config.php';

$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    DB_HOST,
    DB_PORT,
    DB_NAME
);

try {
    // Instancia o PDO
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    error_log("Erro na conexão com o banco de dados: " . $e->getMessage());
    die("Não foi possível conectar ao banco de dados.");
}

if (! class_exists('Database')) {
    class Database {
        /** @return PDO */
        public static function connect(): PDO {
            global $pdo;
            return $pdo;
        }
    }
}
