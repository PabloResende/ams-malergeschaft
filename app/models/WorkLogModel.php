<?php
// app/models/WorkLogModel.php - VERSÃO COMPLETA E CORRIGIDA

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
     * NOVO: Busca todos os registros de um funcionário (com filtros opcionais)
     * Usado pela API time_entries para admin
     */
    public function getByEmployee(int $employeeId, int $projectId = null): array
    {
        $results = [];
        
        // Sistema novo (time_entries)
        try {
            $sql = "
                SELECT 
                    te.*,
                    p.name as project_name
                FROM time_entries te
                LEFT JOIN projects p ON te.project_id = p.id
                WHERE te.employee_id = ?
            ";
            
            $params = [$employeeId];
            
            if ($projectId) {
                $sql .= " AND te.project_id = ?";
                $params[] = $projectId;
            }
            
            $sql .= " ORDER BY te.date DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $newEntries = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            foreach ($newEntries as $entry) {
                $results[] = [
                    'id' => $entry['id'],
                    'date' => $entry['date'],
                    'hours' => (float)$entry['total_hours'],
                    'description' => $this->formatTimeDisplay($entry['time_records'], $entry['date']),
                    'project_name' => $entry['project_name'],
                    'type' => 'new_system'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Erro ao buscar time_entries: " . $e->getMessage());
        }
        
        // Sistema antigo (project_work_logs)
        try {
            $sql = "
                SELECT 
                    pwl.*,
                    p.name as project_name
                FROM project_work_logs pwl
                LEFT JOIN projects p ON pwl.project_id = p.id
                WHERE pwl.employee_id = ?
            ";
            
            $params = [$employeeId];
            
            if ($projectId) {
                $sql .= " AND pwl.project_id = ?";
                $params[] = $projectId;
            }
            
            $sql .= " ORDER BY pwl.date DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $oldEntries = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            foreach ($oldEntries as $entry) {
                $results[] = [
                    'id' => 'old_' . $entry['id'],
                    'date' => $entry['date'],
                    'hours' => (float)$entry['hours'],
                    'description' => $entry['description'] ?? 'Sistema Antigo (Horas Diretas)',
                    'project_name' => $entry['project_name'],
                    'type' => 'old_system'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Erro ao buscar project_work_logs: " . $e->getMessage());
        }
        
        // Ordena por data (mais recente primeiro)
        usort($results, function($a, $b) {
            return strcmp($b['date'], $a['date']);
        });
        
        return $results;
    }

    /**
     * Formata registros do novo sistema SIMPLIFICADO - SEM interrogações
     */
    private function formatTimeDisplay(string $timeRecords, string $date): string
    {
        $records = json_decode($timeRecords, true);
        if (!$records || !isset($records['entries'])) {
            return 'Registro inválido do dia ' . date('d/m/Y', strtotime($date));
        }
        
        $entries = $records['entries'];
        if (empty($entries)) {
            return 'Sem registros do dia ' . date('d/m/Y', strtotime($date));
        }
        
        // Ordena por horário
        usort($entries, function($a, $b) {
            return strcmp($a['time'] ?? '', $b['time'] ?? '');
        });
        
        // Separa entradas e saídas
        $entradas = [];
        $saidas = [];
        
        foreach ($entries as $entry) {
            $type = $entry['type'] ?? '';
            $time = $entry['time'] ?? '';
            
            if (empty($time)) continue;
            
            if ($type === 'entry') {
                $entradas[] = $time;
            } elseif ($type === 'exit') {
                $saidas[] = $time;
            }
        }
        
        // Forma pares simples entrada-saída
        $pairs = [];
        $maxPairs = max(count($entradas), count($saidas));
        
        for ($i = 0; $i < $maxPairs; $i++) {
            $entrada = isset($entradas[$i]) ? $entradas[$i] : '';
            $saida = isset($saidas[$i]) ? $saidas[$i] : '';
            
            // Só adiciona se tem pelo menos uma entrada ou saída
            if ($entrada || $saida) {
                $pair = '';
                if ($entrada) $pair .= "entrada {$entrada}";
                if ($entrada && $saida) $pair .= " ";
                if ($saida) $pair .= "saída {$saida}";
                
                $pairs[] = $pair;
            }
        }
        
        if (empty($pairs)) {
            return 'Sem horários válidos do dia ' . date('d/m/Y', strtotime($date));
        }
        
        // Formato: "entrada 8:00 saída 12:00 - entrada 14:00 saída 18:00 do dia 04/09/2025"
        $dateFormatted = date('d/m/Y', strtotime($date));
        return implode(' - ', $pairs) . " do dia {$dateFormatted}";
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
     * MANTIDO: Totais de horas por funcionário para um projeto (para admin)
     */
    public function getProjectTotals(int $projectId): array
    {
        // Tenta nova tabela primeiro
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    CONCAT(e.name,' ',e.last_name) AS employee_name,
                    COALESCE(SUM(te.total_hours),0) AS total_hours
                FROM time_entries te
                JOIN employees e ON e.id = te.employee_id
                WHERE te.project_id = ?
                GROUP BY te.employee_id
            ");
            $stmt->execute([$projectId]);
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            if (!empty($results)) {
                return $results;
            }
        } catch (Exception $e) {
            // Continua com tabela antiga
        }

        // Fallback para tabela antiga
        $stmt = $this->pdo->prepare("
            SELECT 
                CONCAT(e.name,' ',e.last_name) AS employee_name,
                COALESCE(SUM(w.hours),0) AS total_hours
            FROM project_work_logs w
            JOIN employees e ON e.id = w.employee_id
            WHERE w.project_id = ?
            GROUP BY w.employee_id
        ");
        $stmt->execute([$projectId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
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
     * MANTIDO: Ranking completo de funcionários por horas trabalhadas
     */
    public function getEmployeeHoursRanking(): array
    {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    e.id,
                    e.name, 
                    e.last_name,
                    (
                        COALESCE((SELECT SUM(te.total_hours) FROM time_entries te WHERE te.employee_id = e.id), 0) +
                        COALESCE((SELECT SUM(pwl.hours) FROM project_work_logs pwl WHERE pwl.employee_id = e.id), 0)
                    ) AS total_hours
                FROM employees e
                WHERE e.active = 1
                ORDER BY total_hours DESC
            ");
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erro no ranking: " . $e->getMessage());
            return [];
        }
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