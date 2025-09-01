<?php
// system/app/models/WorkLogModel.php

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
     * Retorna todos os logs de um funcionário em um projeto
     */ public function getByEmployeeAndProject(int $employeeId, int $projectId): array
    {
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
     * Total de horas de um funcionário (para o card de resumo no dashboard).
     * @return float total de horas
     */
    public function getTotalHoursByEmployee(int $employeeId): float
    {
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(hours),0) AS total_hours
            FROM project_work_logs
            WHERE employee_id = ?
        ");
        $stmt->execute([$employeeId]);
        return (float)$stmt->fetchColumn();
    }
    
    /**
     * Totais de horas por funcionário para um projeto (para admin).
     * @return array [ ['employee_name'=>string, 'total_hours'=>float], … ]
     */
    public function getProjectTotals(int $projectId): array
    {
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
     * Soma total de horas de todos os funcionários em todos os projetos
     */
    public function getTotalHoursAll(): float
    {
        $stmt = $this->pdo->query("
            SELECT COALESCE(SUM(hours),0) AS total
              FROM project_work_logs
        ");
        return (float)$stmt->fetchColumn();
    }

    /**
     * Soma de horas por funcionário em um projeto (para admin)
     * Retorna [{ employee_name, total_hours }, …]
     */
    public function getTotalsByProject(int $projId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT CONCAT(e.name,' ',e.last_name) AS employee_name,
                   COALESCE(SUM(w.hours),0)         AS total_hours
              FROM project_work_logs w
              JOIN employees e ON e.id = w.employee_id
             WHERE w.project_id = ?
             GROUP BY w.employee_id
        ");
        $stmt->execute([$projId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Insere novo log de horas e incrementa total_hours em projects
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
