<?php

class Database {
    private static $pdo = null;

    public static function connect() {
        if (self::$pdo === null) {
            require __DIR__ . '/config.php';
            
            try {
                self::$pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                die("Database connection failed: " . $e->getMessage());
            }
        }
        return self::$pdo;
    }
}
