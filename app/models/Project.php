<?php
require_once __DIR__ . '/../../config/Database.php';

class ProjectModel {

    public static function getAll() {
        $pdo = Database::connect();
        $stmt = $pdo->query("SELECT * FROM projects ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find($id) {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function getTasks($projectId) {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT id, description, completed FROM tasks WHERE project_id = ? ORDER BY created_at ASC");
        $stmt->execute([$projectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getEmployees($projectId) {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("
            SELECT e.id, e.name, e.last_name
            FROM employees e
            JOIN project_resources pr ON e.id = pr.resource_id
            WHERE pr.project_id = ? AND pr.resource_type = 'employee'
        ");
        $stmt->execute([$projectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getInventory($projectId) {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("
            SELECT i.id, i.name, pr.quantity
            FROM inventory i
            JOIN project_resources pr ON i.id = pr.resource_id
            WHERE pr.project_id = ? AND pr.resource_type = 'inventory'
        ");
        $stmt->execute([$projectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create($data, $tasks = [], $employees = [], $inventoryResources = []) {
        $pdo = Database::connect();
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO projects
            (name, location, description, start_date, end_date, total_hours, budget, employee_count, status, progress, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $ok = $stmt->execute([
            $data['name'],
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

        if (!$ok) { $pdo->rollBack(); return false; }
        $projectId = $pdo->lastInsertId();

        // Tarefas
        if (!empty($tasks)) {
            $ts = $pdo->prepare("INSERT INTO tasks (project_id, description, completed, created_at) VALUES (?, ?, ?, NOW())");
            foreach ($tasks as $t) {
                if (empty($t['description'])) continue;
                $c = !empty($t['completed']) ? 1 : 0;
                $ts->execute([$projectId, $t['description'], $c]);
            }
        }

        // Funcionários
        if (!empty($employees)) {
            $rs = $pdo->prepare("
              INSERT INTO project_resources (project_id, resource_type, resource_id, quantity, created_at)
              VALUES (?, 'employee', ?, 1, NOW())
            ");
            foreach ($employees as $eid) {
                $rs->execute([$projectId, $eid]);
            }
        }

        // Inventário
        if (!empty($inventoryResources)) {
            $sel = $pdo->prepare("SELECT quantity FROM inventory WHERE id = ?");
            $up  = $pdo->prepare("UPDATE inventory SET quantity = quantity - ? WHERE id = ?");
            $rs  = $pdo->prepare("
              INSERT INTO project_resources (project_id, resource_type, resource_id, quantity, created_at)
              VALUES (?, 'inventory', ?, ?, NOW())
            ");
            foreach ($inventoryResources as $it) {
                if (empty($it['id']) || empty($it['quantity'])) continue;
                $sel->execute([$it['id']]);
                $row = $sel->fetch(PDO::FETCH_ASSOC);
                if ($row && $row['quantity'] >= $it['quantity']) {
                    $rs->execute([$projectId, $it['id'], $it['quantity']]);
                    $up->execute([$it['quantity'], $it['id']]);
                }
            }
        }

        $pdo->commit();
        return true;
    }
    public static function update($id, $data, $tasks = [], $employees = [], $inventoryResources = []) {
        $pdo = Database::connect();
        $pdo->beginTransaction();
        try {
            // 1) Atualiza campos principais
            $stmt = $pdo->prepare("
                UPDATE projects SET
                  name           = ?,
                  location       = ?,
                  description    = ?,
                  start_date     = ?,
                  end_date       = ?,
                  total_hours    = ?,
                  budget         = ?,
                  employee_count = ?,
                  status         = ?,
                  progress       = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $data['name'],
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
    
            // 2) Remove registros antigos
            $pdo->prepare("DELETE FROM tasks WHERE project_id = ?")
                ->execute([$id]);
            $pdo->prepare("DELETE FROM project_resources WHERE project_id = ?")
                ->execute([$id]);
    
            // 3) Reinsere tarefas
            if (!empty($tasks)) {
                $ts = $pdo->prepare("
                  INSERT INTO tasks (project_id, description, completed, created_at)
                  VALUES (?, ?, ?, NOW())
                ");
                foreach ($tasks as $t) {
                    if (empty($t['description'])) continue;
                    $ts->execute([$id, $t['description'], !empty($t['completed']) ? 1 : 0]);
                }
            }
    
            // 4) Reinsere funcionários
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
    
            // 5) Reinsere inventário
            if (!empty($inventoryResources)) {
                $sel = $pdo->prepare("SELECT quantity FROM inventory WHERE id = ?");
                $up  = $pdo->prepare("UPDATE inventory SET quantity = quantity - ? WHERE id = ?");
                $rs  = $pdo->prepare("
                  INSERT INTO project_resources
                    (project_id, resource_type, resource_id, quantity, created_at)
                  VALUES (?, 'inventory', ?, ?, NOW())
                ");
                foreach ($inventoryResources as $it) {
                    if (empty($it['id']) || empty($it['quantity'])) continue;
                    $sel->execute([$it['id']]);
                    $row = $sel->fetch(PDO::FETCH_ASSOC);
                    if ($row && $row['quantity'] >= $it['quantity']) {
                        $rs->execute([$id, $it['id'], $it['quantity']]);
                        $up->execute([$it['quantity'], $it['id']]);
                    }
                }
            }
    
            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }    

    public static function delete($id) {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
