<?php
// app/models/ProjectModel.php
require_once __DIR__ . '/../../config/Database.php';

class ProjectModel {

    // Busca todos os projetos já com o nome do cliente
    public static function getAll() {
        $pdo = Database::connect();
        $stmt = $pdo->query("
            SELECT p.*, c.name AS client_name
            FROM projects p
            LEFT JOIN client c ON p.client_id = c.id
            ORDER BY p.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Encontra 1 projeto já trazendo o nome do cliente
    public static function find($id) {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("
            SELECT p.*, c.name AS client_name
            FROM projects p
            LEFT JOIN client c ON p.client_id = c.id
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Cria projeto (já incluindo client_id)
    public static function create($data, $tasks = [], $employees = []) {
        $pdo = Database::connect();
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("
            INSERT INTO projects
              (name, client_id, location, description, start_date, end_date,
               total_hours, budget, employee_count, status, progress, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $ok = $stmt->execute([
            $data['name'],
            $data['client_id'],
            $data['location'],
            $data['description'],
            $data['start_date'],
            $data['end_date'],
            $data['total_hours'],
            $data['budget'],
            $data['employee_count'],
            $data['status'],
            $data['progress']
        ]);
        $projectId = $pdo->lastInsertId();

        if (!empty($tasks)) {
            $ts = $pdo->prepare("
                INSERT INTO tasks
                  (project_id, description, completed, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            foreach ($tasks as $t) {
                if (empty($t['description'])) continue;
                $ts->execute([
                    $projectId,
                    $t['description'],
                    !empty($t['completed']) ? 1 : 0
                ]);
            }
        }

        if (!empty($employees)) {
            $rs = $pdo->prepare("
                INSERT INTO project_resources
                  (project_id, resource_type, resource_id, quantity, created_at)
                VALUES (?, 'employee', ?, 1, NOW())
            ");
            foreach ($employees as $eid) {
                $rs->execute([$projectId, $eid]);
            }
        }

        $pdo->commit();
        return true;
    }
    
    public static function update($id, $data, $tasks = [], $employees = []) {
        $pdo = Database::connect();
        $pdo->beginTransaction();
        // restaura inventário...
        self::restoreInventory($id);

        $stmt = $pdo->prepare("
            UPDATE projects SET
              name        = ?,
              client_id   = ?,
              location    = ?,
              description = ?,
              start_date  = ?,
              end_date    = ?,
              total_hours = ?,
              budget      = ?,
              employee_count = ?,
              status      = ?,
              progress    = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $data['name'],
            $data['client_id'],
            $data['location'],
            $data['description'],
            $data['start_date'],
            $data['end_date'],
            $data['total_hours'],
            $data['budget'],
            $data['employee_count'],
            $data['status'],
            $data['progress'],
            $id
        ]);
        
        $pdo->prepare("DELETE FROM tasks WHERE project_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM project_resources WHERE project_id = ?")->execute([$id]);

        if (!empty($tasks)) {
            $ts = $pdo->prepare("
                INSERT INTO tasks
                  (project_id, description, completed, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            foreach ($tasks as $t) {
                if (empty($t['description'])) continue;
                $ts->execute([
                    $id,
                    $t['description'],
                    !empty($t['completed']) ? 1 : 0
                ]);
            }
        }

        if (!empty($employees)) {
            $rs = $pdo->prepare("
                INSERT INTO project_resources
                  (project_id, resource_type, resource_id, quantity, created_at)
                  VALUES (?, 'employee', ?, 1, NOW())
            ");
            foreach ($employees as $eid) {
                $rs->execute([$id, $eid]);
            }
        }

        $pdo->commit();
        return true;
    }
    
        public static function getTasks($projectId) {
            $pdo = Database::connect();
            $stmt = $pdo->prepare("
                SELECT id, description, completed
                FROM tasks
                WHERE project_id = ?
                ORDER BY created_at ASC
            ");
            $stmt->execute([$projectId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    
        public static function getEmployees($projectId) {
            $pdo = Database::connect();
            $stmt = $pdo->prepare("
                SELECT e.id, e.name, e.last_name
                FROM employees e
                JOIN project_resources pr
                  ON e.id = pr.resource_id
                WHERE pr.project_id = ?
                  AND pr.resource_type = 'employee'
            ");
            $stmt->execute([$projectId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    
    public static function delete($id) {
        $pdo = Database::connect();
        return $pdo->prepare("DELETE FROM projects WHERE id = ?")
        ->execute([$id]);
    }

    /**
     * Retorna projetos ativos (in_progress) para uso no controle de estoque.
     *
     * @return array
     */
    public static function getActiveProjects() {
        $pdo = Database::connect();
        $stmt = $pdo->query("
            SELECT id, name
            FROM projects
            WHERE status = 'in_progress'
            ORDER BY name
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Restaura inventário antes de atualizar, se esse método ainda existir.
     */
    public static function restoreInventory(int $projectId): void {
        // se estiver usando controle de inventário em ProjectModel
        $pdo = Database::connect();
        $stmt = $pdo->prepare("
            SELECT resource_id, quantity
            FROM project_resources
            WHERE project_id = ? AND resource_type = 'inventory'
        ");
        $stmt->execute([$projectId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $upd   = $pdo->prepare("UPDATE inventory SET quantity = quantity + ? WHERE id = ?");
        foreach ($items as $it) {
            $upd->execute([(int)$it['quantity'], $it['resource_id']]);
        }
    }
}
