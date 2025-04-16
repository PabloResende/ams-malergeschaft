<?php
require_once __DIR__ . '/../models/Task.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $data = json_decode(file_get_contents('php://input'), true);
  $taskId = $data['task_id'] ?? null;
  $completed = isset($data['completed']) ? (int)$data['completed'] : 0;
  
  if ($taskId) {
    if (TaskModel::updateStatus($taskId, $completed)) {
      echo json_encode(['success' => true]);
    } else {
      echo json_encode(['success' => false]);
    }
  }
}
