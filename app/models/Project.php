<?php

require_once(__DIR__ . '/../../config/Database.php');

class ProjectModel {

    public function getAll() {
        $pdo = Database::connect();
        $stmt = $pdo->query("SELECT * FROM projects ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Cria um projeto e insere tarefas e recursos associados.
     *
     * @param array $data Dados do projeto.
     * @param array $tasks Lista de tarefas (cada item: ['description'=>..., 'completed'=>...]).
     * @param array $employees Lista de funcionários (array de IDs).
     * @param array $inventoryResources Lista de inventário (array de arrays com keys 'id' e 'quantity').
     * @return bool
     */
    public static function create($data, $tasks = [], $employees = [], $inventoryResources = []) {
        $pdo = Database::connect();
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare(
            "INSERT INTO projects (name, client_name, description, end_date, start_date, total_hours, status, progress, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        
        $result = $stmt->execute([
            $data['name'], 
            $data['client_name'], 
            $data['description'],
            $data['end_date'], 
            $data['start_date'], 
            $data['total_hours'],
            $data['status'], 
            $data['progress']
        ]);
        
        if ($result) {
            $projectId = $pdo->lastInsertId();

            // Inserir tarefas, se houver
            if (!empty($tasks)) {
                $taskStmt = $pdo->prepare(
                    "INSERT INTO tasks (project_id, description, completed, created_at) VALUES (?, ?, ?, NOW())"
                );
                foreach ($tasks as $task) {
                    if (!isset($task['description']) || empty($task['description'])) continue;
                    $completed = isset($task['completed']) && $task['completed'] ? 1 : 0;
                    $taskStmt->execute([$projectId, $task['description'], $completed]);
                }
            }

            // Inserir recursos de funcionários
            if (!empty($employees)) {
                // Para cada ID, verifica se o funcionário existe e está ativo.
                $empStmt = $pdo->prepare("SELECT id FROM employees WHERE id = ? AND active = 1");
                $resourceStmt = $pdo->prepare(
                    "INSERT INTO project_resources (project_id, resource_type, resource_id, quantity, created_at) VALUES (?, 'employee', ?, 1, NOW())"
                );
                foreach ($employees as $empId) {
                    $empStmt->execute([$empId]);
                    if ($empStmt->fetch(PDO::FETCH_ASSOC)) {
                        // Insere o funcionário para o projeto; quantidade padrão = 1
                        $resourceStmt->execute([$projectId, $empId]);
                    }
                }
            }

            // Inserir recursos de inventário (materiais)
            if (!empty($inventoryResources)) {
                $invSelectStmt = $pdo->prepare("SELECT quantity FROM inventory WHERE id = ?");
                $invUpdateStmt = $pdo->prepare("UPDATE inventory SET quantity = quantity - ? WHERE id = ?");
                $resourceStmt = $pdo->prepare(
                    "INSERT INTO project_resources (project_id, resource_type, resource_id, quantity, created_at) VALUES (?, 'inventory', ?, ?, NOW())"
                );
                foreach ($inventoryResources as $item) {
                    if (!isset($item['id']) || !isset($item['quantity'])) continue;
                    $invSelectStmt->execute([$item['id']]);
                    $invRecord = $invSelectStmt->fetch(PDO::FETCH_ASSOC);
                    if ($invRecord && $invRecord['quantity'] >= $item['quantity']) {
                        // Insere o recurso e atualiza o inventário
                        $resourceStmt->execute([$projectId, $item['id'], $item['quantity']]);
                        $invUpdateStmt->execute([$item['quantity'], $item['id']]);
                    }
                }
            }
            
            $pdo->commit();
            return true;
        } else {
            $pdo->rollBack();
            return false;
        }
    }

    public static function update($id, $data) {
        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            "UPDATE projects SET name = ?, client_name = ?, description = ?, end_date = ?, start_date = ?, total_hours = ?, status = ?, progress = ? WHERE id = ?"
        );
        return $stmt->execute([
            $data['name'],
            $data['client_name'],
            $data['description'],
            $data['end_date'],
            $data['start_date'],
            $data['total_hours'],
            $data['status'],
            $data['progress'],
            $id
        ]);
    }

    public static function delete($id) {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
