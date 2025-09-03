<?php
// app/models/WorkLogModel.php - MODIFICAÇÕES PARA COMPATIBILIDADE

require_once __DIR__ . '/../../config/database.php';

class WorkLogModel
{
    private \PDO $pdo;

    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * MANTIDO: Retorna todos os logs de um funcionário em um projeto
     */ 
    public function getByEmployeeAndProject(int $employeeId, int $projectId): array
    {
        // PRIMEIRO: Tenta buscar na nova tabela time_entries
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    id,
                    date,
                    total_hours as hours,
                    project_id,
                    employee_id
                FROM time_entries
                WHERE employee_id = ? AND project_id = ?
                ORDER BY date DESC
            ");
            $stmt->execute([$employeeId, $projectId]);
            $newResults = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            if (!empty($newResults)) {
                return $newResults;
            }
        } catch (Exception $e) {
            // Se tabela time_entries não existe, continua com tabela antiga
        }

        // FALLBACK: Busca na tabela antiga project_work_logs
        $stmt = $this->pdo->prepare("
            SELECT id, project_id, employee_id, hours, date
            FROM project_work_logs
            WHERE employee_id = ? AND project_id = ?
            ORDER BY date DESC
        ");
        $stmt->execute([$employeeId, $projectId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * MODIFICADO: Total de horas considerando ambas as tabelas
     */
    public function getTotalHoursByEmployee(int $employeeId): float
    {
        $total = 0.0;

        // Soma da nova tabela time_entries
        try {
            $stmt = $this->pdo->prepare("
                SELECT COALESCE(SUM(total_hours),0) AS total_hours
                FROM time_entries
                WHERE employee_id = ?
            ");
            $stmt->execute([$employeeId]);
            $total += (float)$stmt->fetchColumn();
        } catch (Exception $e) {
            // Tabela não existe ainda
        }

        // Soma da tabela antiga project_work_logs
        try {
            $stmt = $this->pdo->prepare("
                SELECT COALESCE(SUM(hours),0) AS total_hours
                FROM project_work_logs
                WHERE employee_id = ?
            ");
            $stmt->execute([$employeeId]);
            $total += (float)$stmt->fetchColumn();
        } catch (Exception $e) {
            // Ignora se tabela não existe
        }

        return $total;
    }
    
    /**
     * MANTIDO: Totais de horas por funcionário para um projeto (para admin).
     */
    public function getProjectTotals(int $projectId): array
    {
        // Busca primeiro na nova tabela
        try {
            $stmt = $this->pdo->prepare("
                SELECT e.name, e.last_name, SUM(te.total_hours) AS total_hours
                FROM time_entries te
                JOIN employees e ON e.id = te.employee_id
                WHERE te.project_id = ?
                GROUP BY te.employee_id, e.name, e.last_name
            ");
            $stmt->execute([$projectId]);
            $newResults = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (!empty($newResults)) {
                $out = [];
                foreach ($newResults as $r) {
                    $out[] = [
                        'employee_name' => trim($r['name'].' '.$r['last_name']),
                        'total_hours'   => (float)$r['total_hours'],
                    ];
                }
                return $out;
            }
        } catch (Exception $e) {
            // Continua com tabela antiga
        }

        // Fallback para tabela antiga
        $stmt = $this->pdo->prepare("
            SELECT e.name, e.last_name, SUM(w.hours) AS total_hours
            FROM project_work_logs w
            JOIN employees e ON e.id = w.employee_id
            WHERE w.project_id = ?
            GROUP BY w.employee_id, e.name, e.last_name
        ");
        $stmt->execute([$projectId]);

        $out = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $r) {
            $out[] = [
                'employee_name' => trim($r['name'].' '.$r['last_name']),
                'total_hours'   => (float)$r['total_hours'],
            ];
        }
        return $out;
    }

    /**
     * MANTIDO: Soma total de horas de todos os funcionários em todos os projetos
     */
    public function getTotalHoursAll(): float
    {
        $total = 0.0;

        // Soma da nova tabela
        try {
            $stmt = $this->pdo->query("
                SELECT COALESCE(SUM(total_hours),0) AS total
                FROM time_entries
            ");
            $total += (float)$stmt->fetchColumn();
        } catch (Exception $e) {
            // Tabela não existe
        }

        // Soma da tabela antiga
        try {
            $stmt = $this->pdo->query("
                SELECT COALESCE(SUM(hours),0) AS total
                FROM project_work_logs
            ");
            $total += (float)$stmt->fetchColumn();
        } catch (Exception $e) {
            // Tabela não existe
        }

        return $total;
    }

    /**
     * MANTIDO: Soma de horas por funcionário em um projeto (para admin)
     */
    public function getTotalsByProject(int $projId): array
    {
        // Tenta nova tabela primeiro
        try {
            $stmt = $this->pdo->prepare("
                SELECT CONCAT(e.name,' ',e.last_name) AS employee_name,
                       COALESCE(SUM(te.total_hours),0) AS total_hours
                FROM time_entries te
                JOIN employees e ON e.id = te.employee_id
                WHERE te.project_id = ?
                GROUP BY te.employee_id
            ");
            $stmt->execute([$projId]);
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            if (!empty($results)) {
                return $results;
            }
        } catch (Exception $e) {
            // Continua com tabela antiga
        }

        // Fallback
        $stmt = $this->pdo->prepare("
            SELECT CONCAT(e.name,' ',e.last_name) AS employee_name,
                   COALESCE(SUM(w.hours),0) AS total_hours
            FROM project_work_logs w
            JOIN employees e ON e.id = w.employee_id
            WHERE w.project_id = ?
            GROUP BY w.employee_id
        ");
        $stmt->execute([$projId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * MANTIDO: Insere novo log de horas (tabela antiga)
     */
    public function create(array $data): bool
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO project_work_logs (employee_id, project_id, hours, date)
            VALUES (?, ?, ?, ?)
        ");

        $ok = $stmt->execute([
            $data['employee_id'],
            $data['project_id'],
            $data['hours'],
            $data['date']
        ]);

        if ($ok) {
            $updateStmt = $this->pdo->prepare("
                UPDATE projects SET total_hours = total_hours + ?
                WHERE id = ?
            ");
            $updateStmt->execute([$data['hours'], $data['project_id']]);
        }

        return $ok;
    }
}