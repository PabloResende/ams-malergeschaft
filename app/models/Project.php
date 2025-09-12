<?php
// app/models/Project.php - VERSÃO CORRIGIDA COMPLETA

require_once __DIR__ . '/../../config/database.php';

class ProjectModel
{
    public function getAll(): array
    {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT p.*, c.name as client_name 
            FROM projects p 
            LEFT JOIN client c ON p.client_id = c.id 
            ORDER BY p.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByEmployee(int $employeeId): array
    {
        global $pdo;
        
        // ✅ CORRIGIDO: Usar project_resources em vez de project_employees
        $stmt = $pdo->prepare("
            SELECT DISTINCT p.*, c.name as client_name 
            FROM projects p 
            LEFT JOIN client c ON p.client_id = c.id
            INNER JOIN project_resources pr ON p.id = pr.project_id
            WHERE pr.resource_id = ? AND pr.resource_type = 'employee'
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?array
    {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT p.*, c.name as client_name 
            FROM projects p 
            LEFT JOIN client c ON p.client_id = c.id 
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function getTransactions(int $projectId): array
    {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT ft.*, c.name as client_name, e.name as employee_name
            FROM financial_transactions ft
            LEFT JOIN client c ON ft.client_id = c.id
            LEFT JOIN employees e ON ft.employee_id = e.id
            WHERE ft.project_id = ?
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
     * ✅ CORRIGIDO: Cria um novo projeto com funcionários na tabela project_resources
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

            // 3) ✅ CORRIGIDO: Insere alocações de funcionários na tabela project_resources
            if (!empty($employees)) {
                $rstmt = $pdo->prepare("
                    INSERT INTO project_resources
                    (project_id, resource_type, resource_id, quantity, created_at)
                    VALUES
                    (?, 'employee', ?, 1, NOW())
                ");
                foreach ($employees as $eid) {
                    if ((int)$eid > 0) {
                        $rstmt->execute([
                            $projectId,
                            (int)$eid,
                        ]);
                    }
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

        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Erro ao criar projeto: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ✅ CORRIGIDO: Atualiza projeto com funcionários na tabela project_resources
     */
    public static function update(int $id, array $data, array $tasks = [], array $employees = []): bool
    {
        global $pdo;
        
        try {
            $pdo->beginTransaction();
            
            // 1) Atualiza o projeto
            $stmt = $pdo->prepare("UPDATE projects SET
                name = ?, client_id = ?, location = ?, description = ?,
                start_date = ?, end_date = ?, total_hours = ?, budget = ?,
                employee_count = ?, status = ?, progress = ?
                WHERE id = ?");
            $stmt->execute([
                $data['name'], $data['client_id'], $data['location'], $data['description'],
                $data['start_date'], $data['end_date'], $data['total_hours'], $data['budget'],
                $data['employee_count'], $data['status'], $data['progress'], $id
            ]);
            
            // 2) Remove antigos e insere novos (tasks e resources)
            $pdo->prepare("DELETE FROM tasks WHERE project_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM project_resources WHERE project_id = ?")->execute([$id]);
            
            // 3) Insere novas tasks
            if (!empty($tasks)) {
                $tstmt = $pdo->prepare("
                    INSERT INTO tasks (project_id, description, completed, created_at)
                    VALUES (?, ?, ?, NOW())
                ");
                foreach ($tasks as $task) {
                    $desc = trim($task['description'] ?? '');
                    if ($desc !== '') {
                        $tstmt->execute([
                            $id,
                            $desc,
                            !empty($task['completed']) ? 1 : 0
                        ]);
                    }
                }
            }
            
            // 4) ✅ CORRIGIDO: Insere novos funcionários na tabela project_resources
            if (!empty($employees)) {
                $rstmt = $pdo->prepare("
                    INSERT INTO project_resources
                    (project_id, resource_type, resource_id, quantity, created_at)
                    VALUES (?, 'employee', ?, 1, NOW())
                ");
                foreach ($employees as $empId) {
                    if ((int)$empId > 0) {
                        $rstmt->execute([$id, (int)$empId]);
                    }
                }
            }
            
            $pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Erro ao atualizar projeto: " . $e->getMessage());
            return false;
        }
    }

    public static function delete(int $id): bool
    {
        global $pdo;
        
        try {
            $pdo->beginTransaction();
            
            // Remove dependências primeiro
            $pdo->prepare("DELETE FROM tasks WHERE project_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM project_resources WHERE project_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM project_work_logs WHERE project_id = ?")->execute([$id]);
            
            // Remove o projeto
            $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
            $stmt->execute([$id]);
            
            $pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Erro ao deletar projeto: " . $e->getMessage());
            return false;
        }
    }
}