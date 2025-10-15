<?php
require_once __DIR__ . '/../../config/database.php';
class ProjectModel
{
    private \PDO $pdo;

    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
    }

    public function getAll(): array
    {
        $stmt = $this->pdo->query("SELECT p.*, c.name AS client_name
                                   FROM projects p
                                   LEFT JOIN client c ON p.client_id = c.id
                                   ORDER BY p.created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
 public static function find(int $id): ?array
    {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT p.*, c.name AS client_name, p.client_id
            FROM projects p
            LEFT JOIN client c ON p.client_id = c.id
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Tarefas associadas a um projeto.
     *
     * @return array [ ['id'=>int,'description'=>string,'completed'=>0|1], ... ]
     */
    public static function getTasks(int $projectId): array
    {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT id, description, completed
            FROM tasks
            WHERE project_id = ?
            ORDER BY created_at ASC
        ");
        $stmt->execute([$projectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Funcionários alocados ao projeto.
     *
     * @return array [ ['id'=>int,'name'=>string,'last_name'=>string], ... ]
     */
    public static function getEmployees(int $projectId): array
    {
        global $pdo;
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

    /**
     * Itens de inventário alocados ao projeto.
     *
     * @return array [ ['name'=>string,'quantity'=>int], ... ]
     */
    public static function getInventory(int $projectId): array
    {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT i.name, pr.quantity
            FROM project_resources pr
            JOIN inventory i ON pr.resource_id = i.id
            WHERE pr.project_id = ?
              AND pr.resource_type = 'inventory'
        ");
        $stmt->execute([$projectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Transações financeiras relacionadas ao projeto.
     *
     * @return array [ ['date'=>string,'type'=>string,'amount'=>numeric,'category'=>string], ... ]
     */
    public static function getTransactions(int $projectId): array
    {
        global $pdo;
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
    /**
     * Cria um novo projeto, tarefas e alocações, e atualiza pontos de fidelidade do cliente.
     *
     * @param array $data       (name, client_id, location, description, start_date, end_date,
     *                            total_hours, budget, employee_count, status, progress)
     * @param array $tasks      Lista de tarefas [ ['description'=>string, 'completed'=>bool], ... ]
     * @param array $employees  Lista de IDs de funcionários [ id1, id2, ... ]
     * @return bool             true em sucesso, false caso contrário
     */
    public static function create(array $data, array $tasks = [], array $employees = []): bool
    {
        global $pdo;
        try {
            $pdo->beginTransaction();

            // 1) Insere o projeto
            $stmt = $pdo->prepare("
                INSERT INTO projects
                (name, client_id, location, description,
                start_date, end_date, total_hours, budget,
                employee_count, status, progress, created_at)
                VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
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
            ]);
            $projectId = (int)$pdo->lastInsertId();

            // 2) Insere as tarefas
            if (!empty($tasks)) {
                $tstmt = $pdo->prepare("
                    INSERT INTO tasks (project_id, description, completed, created_at)
                    VALUES (?, ?, ?, NOW())
                ");
                foreach ($tasks as $t) {
                    $desc = trim($t['description'] ?? '');
                    if ($desc === '') {
                        continue;
                    }
                    $tstmt->execute([
                        $projectId,
                        $desc,
                        !empty($t['completed']) ? 1 : 0,
                    ]);
                }
            }

            // 3) Insere alocações de funcionários
            if (!empty($employees)) {
                $rstmt = $pdo->prepare("
                    INSERT INTO project_resources
                    (project_id, resource_type, resource_id, quantity, created_at)
                    VALUES
                    (?, 'employee', ?, 1, NOW())
                ");
                foreach ($employees as $eid) {
                    $rstmt->execute([
                        $projectId,
                        $eid,
                    ]);
                }
            }

            // 4) Incrementa pontos de fidelidade do cliente
            if (!empty($data['client_id'])) {
                $updt = $pdo->prepare("
                    UPDATE client
                    SET loyalty_points = loyalty_points + 1
                    WHERE id = ?
                ");
                $updt->execute([$data['client_id']]);
            }

            $pdo->commit();
            return true;

        } catch (\Exception $e) {
            $pdo->rollBack();
            return false;
        }
    }

    public static function update(int $id, array $data, array $tasks = [], array $employees = []): bool
    {
        global $pdo;
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE projects SET
            name = ?, client_id = ?, location = ?, description = ?,
            start_date = ?, end_date = ?, total_hours = ?, budget = ?,
            employee_count = ?, status = ?, progress = ?
            WHERE id = ?");
        $ok = $stmt->execute([
            $data['name'], $data['client_id'], $data['location'], $data['description'],
            $data['start_date'], $data['end_date'], $data['total_hours'], $data['budget'],
            $data['employee_count'], $data['status'], $data['progress'], $id
        ]);
        // Remove antigos e insere novos
        $pdo->prepare("DELETE FROM tasks WHERE project_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM project_resources WHERE project_id = ?")->execute([$id]);
        if ($tasks) {
            $ts = $pdo->prepare("INSERT INTO tasks (project_id, description, completed, created_at)
                                 VALUES (?, ?, ?, NOW())");
            foreach ($tasks as $t) {
                if (trim($t['description']) === '') continue;
                $ts->execute([$id, $t['description'], !empty($t['completed'])]);
            }
        }
        if ($employees) {
            $rs = $pdo->prepare("INSERT INTO project_resources
                (project_id, resource_type, resource_id, quantity, created_at)
                VALUES (?, 'employee', ?, 1, NOW())");
            foreach ($employees as $eid) {
                $rs->execute([$id, $eid]);
            }
        }
        if ($ok) {
            $pdo->commit();
            return true;
        }
        $pdo->rollBack();
        return false;
    }

    public static function delete(int $id): bool
    {
        global $pdo;
        return (bool)$pdo->prepare("DELETE FROM projects WHERE id = ?")->execute([$id]);
    }


    public static function restoreInventory(int $projectId): void
    {
        global $pdo;
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
        global $pdo;
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

    /**
 * Retorna só os projetos em que o funcionário $empId está alocado.
 */
    public function getByEmployee(int $empId): array
    {
        $sql = "
        SELECT p.*, c.name AS client_name
            FROM projects p
    LEFT JOIN client   c  ON p.client_id = c.id
    INNER JOIN project_resources pr
            ON pr.project_id   = p.id
            AND pr.resource_type = 'employee'
            AND pr.resource_id   = ?
        ORDER BY p.created_at DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$empId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
