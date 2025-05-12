<?php
// app/models/FinanceCategoryModel.php
require_once(__DIR__ . '/../../config/Database.php');

class FinanceCategoryModel
{
    public static function connect() {
        return Database::connect();
    }

    public static function getAll() {
        $pdo = self::connect();
        return $pdo
          ->query("SELECT * FROM finance_categories ORDER BY name")
          ->fetchAll(PDO::FETCH_ASSOC);
    }
}
