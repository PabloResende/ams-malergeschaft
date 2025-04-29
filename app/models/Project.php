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

    public static function create($data, $tasks = [], $employees = []) {
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

        if (!$ok) {
            $pdo->rollBack();
            return false;
        }
        $projectId = $pdo->lastInsertId();

        if (!empty($tasks)) {
            $ts = $pdo->prepare("INSERT INTO tasks (project_id, description, completed, created_at) VALUES (?, ?, ?, NOW())");
            foreach ($tasks as $t) {
                if (empty($t['description'])) continue;
                $ts->execute([$projectId, $t['description'], !empty($t['completed']) ? 1 : 0]);
            }
        }

        if (!empty($employees)) {
            $rs = $pdo->prepare("
                INSERT INTO project_resources (project_id, resource_type, resource_id, quantity, created_at)
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

        // limpa tarefas e recursos antigos
        $pdo->prepare("DELETE FROM tasks WHERE project_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM project_resources WHERE project_id = ?")->execute([$id]);

        if (!empty($tasks)) {
            $ts = $pdo->prepare("INSERT INTO tasks (project_id, description, completed, created_at) VALUES (?, ?, ?, NOW())");
            foreach ($tasks as $t) {
                if (empty($t['description'])) continue;
                $ts->execute([$id, $t['description'], !empty($t['completed']) ? 1 : 0]);
            }
        }

        if (!empty($employees)) {
            $rs = $pdo->prepare("
                INSERT INTO project_resources (project_id, resource_type, resource_id, quantity, created_at)
                VALUES (?, 'employee', ?, 1, NOW())
            ");
            foreach ($employees as $eid) {
                $rs->execute([$id, $eid]);
            }
        }

        $pdo->commit();
        return true;
    }

    public static function delete($id) {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
