<?php
require_once __DIR__ . '/../../config/Database.php';

class ProjectModel
{
    public static function getAll(): array
    {
        $pdo = Database::connect();
        $stmt = $pdo->query("
            SELECT p.*, c.name AS client_name
            FROM projects p
            LEFT JOIN client c ON p.client_id = c.id
            ORDER BY p.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $id)
    {
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

    public static function create(array $data, array $tasks = [], array $employees = []): bool
    {
        $pdo = Database::connect();
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO projects
              (name, client_id, location, description, start_date, end_date,
               total_hours, budget, employee_count, status, progress, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
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
            $data['progress']
        ]);
        $projectId = (int)$pdo->lastInsertId();

        if (!empty($tasks)) {
            $ts = $pdo->prepare("
                INSERT INTO tasks (project_id, description, completed, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            foreach ($tasks as $t) {
                if (trim($t['description']) === '') {
                    continue;
                }
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

    public static function update(int $id, array $data, array $tasks = [], array $employees = []): bool
    {
        $pdo = Database::connect();
        $pdo->beginTransaction();

        self::restoreInventory($id);

        $stmt = $pdo->prepare("
            UPDATE projects SET
              name = ?, client_id = ?, location = ?, description = ?,
              start_date = ?, end_date = ?, total_hours = ?, budget = ?,
              employee_count = ?, status = ?, progress = ?
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
                INSERT INTO tasks (project_id, description, completed, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            foreach ($tasks as $t) {
                if (trim($t['description']) === '') {
                    continue;
                }
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

    public static function delete(int $id): bool
    {
        $pdo = Database::connect();
        return (bool)$pdo->prepare("DELETE FROM projects WHERE id = ?")->execute([$id]);
    }

    public static function getTasks(int $projectId): array
    {
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

    public static function getEmployees(int $projectId): array
    {
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

    public static function getInventory(int $projectId): array
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("
            SELECT pr.quantity, i.name
            FROM project_resources pr
            JOIN inventory i ON pr.resource_id = i.id
            WHERE pr.project_id = ?
              AND pr.resource_type = 'inventory'
        ");
        $stmt->execute([$projectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getTransactions(int $projectId): array
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("
            SELECT ft.date, ft.type, ft.amount, ft.category
            FROM financial_transactions ft
            WHERE (ft.category = 'projetos' AND ft.project_id = ?)
               OR EXISTS (
                   SELECT 1 FROM debts d
                   WHERE d.transaction_id = ft.id
                     AND d.project_id = ?
               )
            ORDER BY ft.date DESC
        ");
        $stmt->execute([$projectId, $projectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function restoreInventory(int $projectId): void
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("
            SELECT resource_id, quantity
            FROM project_resources
            WHERE project_id = ? AND resource_type = 'inventory'
        ");
        $stmt->execute([$projectId]);
        $upd = $pdo->prepare("UPDATE inventory SET quantity = quantity + ? WHERE id = ?");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $it) {
            $upd->execute([(int)$it['quantity'], $it['resource_id']]);
        }
    }

    /**
     * Conta em quantos projetos (pendentes ou em andamento) este funcionário já está atribuído.
     */ public static function countProjectsByEmployee(int $empId, int $excludeProjectId = null): int
    {
        $pdo = Database::connect();
        $sql = "
            SELECT COUNT(DISTINCT pr.project_id) AS cnt
            FROM project_resources pr
            JOIN projects p
              ON pr.project_id = p.id
            WHERE pr.resource_type = 'employee'
              AND pr.resource_id = ?
              AND p.status <> 'completed'
        ";

        if ($excludeProjectId !== null) {
            $sql .= " AND pr.project_id <> ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$empId, $excludeProjectId]);
        } else {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$empId]);
        }

        return (int) $stmt->fetchColumn();
    }

}
