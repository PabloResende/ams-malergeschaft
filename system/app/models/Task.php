<?php
require_once(__DIR__ . '/../../config/database.php');

class TaskModel {
   public static function updateStatus($taskId, $completed) {
       $pdo = Database::connect();
       $stmt = $pdo->prepare("UPDATE tasks SET completed = ? WHERE id = ?");
       return $stmt->execute([$completed, $taskId]);
   }
}
