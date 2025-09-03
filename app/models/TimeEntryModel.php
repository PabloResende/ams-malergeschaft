<?php
// app/models/TimeEntryModel.php - VERSÃO CORRIGIDA COMPLETA

require_once __DIR__ . '/../../config/database.php';

class TimeEntryModel 
{
    private \PDO $pdo;
    
    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
    }
    
    /**
     * Adiciona um registro de entrada/saída
     */
    public function addTimeEntry(int $employeeId, int $projectId, string $date, string $type, string $time): bool
    {
        try {
            $this->pdo->beginTransaction();
            
            // Busca registro existente do dia
            $stmt = $this->pdo->prepare("
                SELECT id, time_records, total_hours 
                FROM time_entries 
                WHERE employee_id = ? AND project_id = ? AND date = ?
                FOR UPDATE
            ");
            $stmt->execute([$employeeId, $projectId, $date]);
            $existing = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($existing) {
                // Atualiza registro existente
                $records = json_decode($existing['time_records'], true) ?? ['entries' => []];
                
                // Garante que existe o array entries
                if (!isset($records['entries']) || !is_array($records['entries'])) {
                    $records['entries'] = [];
                }
                
                $records['entries'][] = ['type' => $type, 'time' => $time];
                
                // Recalcula total de horas
                $totalHours = $this->calculateTotalHours($records['entries']);
                
                $updateStmt = $this->pdo->prepare("
                    UPDATE time_entries 
                    SET time_records = ?, total_hours = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                
                $success = $updateStmt->execute([
                    json_encode($records, JSON_UNESCAPED_UNICODE),
                    $totalHours,
                    $existing['id']
                ]);
                
            } else {
                // Cria novo registro
                $records = ['entries' => [['type' => $type, 'time' => $time]]];
                $totalHours = $this->calculateTotalHours($records['entries']);
                
                $insertStmt = $this->pdo->prepare("
                    INSERT INTO time_entries (employee_id, project_id, date, time_records, total_hours)
                    VALUES (?, ?, ?, ?, ?)
                ");
                
                $success = $insertStmt->execute([
                    $employeeId,
                    $projectId, 
                    $date,
                    json_encode($records, JSON_UNESCAPED_UNICODE),
                    $totalHours
                ]);
            }
            
            $this->pdo->commit();
            return $success;
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            error_log("TimeEntryModel::addTimeEntry error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Calcula total de horas baseado nos pares entrada/saída - CORRIGIDO
     */
    private function calculateTotalHours(array $entries): float 
    {
        if (empty($entries)) {
            return 0.0;
        }
        
        $totalMinutes = 0;
        $currentEntry = null;
        
        // Ordena por horário para processar cronologicamente
        usort($entries, function($a, $b) {
            $timeA = $a['time'] ?? '00:00';
            $timeB = $b['time'] ?? '00:00';
            return strcmp($timeA, $timeB);
        });
        
        foreach ($entries as $entry) {
            $entryType = $entry['type'] ?? '';
            $entryTime = $entry['time'] ?? '';
            
            if (empty($entryTime)) continue;
            
            if ($entryType === 'entry') {
                // Se já há uma entrada em aberto, fecha ela com a entrada atual como saída imaginária
                if ($currentEntry !== null) {
                    // Calcula tempo até esta nova entrada (considera como fim do período anterior)
                    $start = strtotime("1970-01-01 " . $currentEntry);
                    $end = strtotime("1970-01-01 " . $entryTime);
                    if ($end > $start) {
                        $totalMinutes += ($end - $start) / 60;
                    }
                }
                $currentEntry = $entryTime;
                
            } elseif ($entryType === 'exit' && $currentEntry !== null) {
                // Calcula diferença em minutos
                $start = strtotime("1970-01-01 " . $currentEntry);
                $end = strtotime("1970-01-01 " . $entryTime);
                if ($end > $start) {
                    $totalMinutes += ($end - $start) / 60;
                }
                $currentEntry = null;
            }
        }
        
        return round($totalMinutes / 60.0, 2);
    }
    
    /**
     * Busca registros de um funcionário em um projeto com formatação corrigida
     */
    public function getByEmployeeAndProject(int $employeeId, int $projectId): array 
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM time_entries 
            WHERE employee_id = ? AND project_id = ? 
            ORDER BY date DESC
        ");
        $stmt->execute([$employeeId, $projectId]);
        
        $results = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $records = json_decode($row['time_records'], true) ?? ['entries' => []];
            $entries = $records['entries'] ?? [];
            
            $results[] = [
                'id' => $row['id'],
                'date' => $row['date'],
                'total_hours' => $row['total_hours'],
                'entries' => $entries,
                'formatted_display' => $this->formatDisplay($entries, $row['date'])
            ];
        }
        
        return $results;
    }
    
    /**
     * Formata para exibição: "entrada 8:00 saída 12:00 - entrada 14:00 saída 18:00 09/04/2025"
     * VERSÃO CORRIGIDA PARA LIDAR COM REGISTROS COMPLEXOS
     */
    private function formatDisplay(array $entries, string $date): string
    {
        if (empty($entries)) {
            return 'Sem registros para esta data ' . date('d/m/Y', strtotime($date));
        }
        
        // Ordena por horário
        usort($entries, function($a, $b) {
            $timeA = $a['time'] ?? '00:00';
            $timeB = $b['time'] ?? '00:00';
            return strcmp($timeA, $timeB);
        });
        
        $pairs = [];
        $currentEntry = null;
        
        foreach ($entries as $entry) {
            $type = $entry['type'] ?? '';
            $time = $entry['time'] ?? '';
            
            if (empty($time)) continue;
            
            if ($type === 'entry') {
                // Se já existe uma entrada sem saída, fecha com saída indefinida
                if ($currentEntry !== null) {
                    $pairs[] = "entrada {$currentEntry} saída ?";
                }
                $currentEntry = $time;
                
            } elseif ($type === 'exit') {
                if ($currentEntry !== null) {
                    $pairs[] = "entrada {$currentEntry} saída {$time}";
                    $currentEntry = null;
                } else {
                    // Saída sem entrada correspondente  
                    $pairs[] = "entrada ? saída {$time}";
                }
            }
        }
        
        // Se ainda há uma entrada em aberto
        if ($currentEntry !== null) {
            $pairs[] = "entrada {$currentEntry} saída ?";
        }
        
        if (empty($pairs)) {
            return 'Registros sem pares válidos ' . date('d/m/Y', strtotime($date));
        }
        
        $dateFormatted = date('d/m/Y', strtotime($date));
        return implode(' - ', $pairs) . " {$dateFormatted}";
    }
    
    /**
     * Total de horas de um funcionário
     */
    public function getTotalHoursByEmployee(int $employeeId): float 
    {
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(total_hours), 0) as total_hours
            FROM time_entries  
            WHERE employee_id = ?
        ");
        $stmt->execute([$employeeId]);
        return (float)$stmt->fetchColumn();
    }
    
    /**
     * Total de horas por projeto
     */
    public function getTotalHoursByProject(int $projectId): float 
    {
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(total_hours), 0) as total_hours
            FROM time_entries  
            WHERE project_id = ?
        ");
        $stmt->execute([$projectId]);
        return (float)$stmt->fetchColumn();
    }
    
    /**
     * Registros individuais para API (formato compatível)
     */
    public function getIndividualEntries(int $employeeId, int $projectId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM time_entries 
            WHERE employee_id = ? AND project_id = ? 
            ORDER BY date DESC
        ");
        $stmt->execute([$employeeId, $projectId]);
        
        $results = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $records = json_decode($row['time_records'], true) ?? ['entries' => []];
            
            // Converte cada entrada/saída individual
            foreach ($records['entries'] ?? [] as $entry) {
                $results[] = [
                    'id' => $row['id'],
                    'date' => $row['date'],
                    'time' => $entry['time'] ?? '',
                    'entry_type' => $entry['type'] ?? ''
                ];
            }
        }
        
        return $results;
    }
}