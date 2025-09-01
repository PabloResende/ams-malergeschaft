<?php
require_once(__DIR__ . '/../../config/database.php');

/** @var \PDO */
private $pdo;

public function __construct()
{
    global $pdo; 
    $this->pdo = $pdo;
}


class TaskModel {
   public static function updateStatus($taskId, $completed) {
       $pdo = Database::connect();
       $stmt = $pdo->prepare("UPDATE tasks SET completed = ? WHERE id = ?");
       return $stmt->execute([$completed, $taskId]);
   }
}
