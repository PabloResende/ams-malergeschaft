<?php
// app/models/TimeEntryModel.php

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
     * Calcula total de horas baseado nos pares entrada/saída
     */
    private function calculateTotalHours(array $entries): float 
    {
        $totalMinutes = 0;
        $currentEntry = null;
        
        // Ordena por horário
        usort($entries, function($a, $b) {
            return strcmp($a['time'], $b['time']);
        });
        
        foreach ($entries as $entry) {
            if ($entry['type'] === 'entry') {
                if ($currentEntry) {
                    // Entrada sem saída correspondente anterior - ignora entrada anterior
                }
                $currentEntry = $entry['time'];
            } elseif ($entry['type'] === 'exit' && $currentEntry) {
                // Calcula diferença em minutos
                $start = strtotime($currentEntry);
                $end = strtotime($entry['time']);
                if ($end > $start) {
                    $totalMinutes += ($end - $start) / 60;
                }
                $currentEntry = null;
            }
        }
        
        return round($totalMinutes / 60, 2);
    }
    
    /**
     * Busca registros de um funcionário em um projeto
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
            $results[] = [
                'id' => $row['id'],
                'date' => $row['date'],
                'total_hours' => $row['total_hours'],
                'entries' => $records['entries'],
                'formatted_display' => $this->formatDisplay($records['entries'], $row['date'])
            ];
        }
        
        return $results;
    }
    
    /**
     * Formata para exibição: "entrada 8:00 saída 12:00 - entrada 14:00 saída 18:00 09/04/2025"
     */
    private function formatDisplay(array $entries, string $date): string
    {
        // Ordena por horário
        usort($entries, function($a, $b) {
            return strcmp($a['time'], $b['time']);
        });
        
        $pairs = [];
        $currentEntry = null;
        
        foreach ($entries as $entry) {
            if ($entry['type'] === 'entry') {
                if ($currentEntry) {
                    // Entrada sem saída correspondente
                    $pairs[] = "entrada {$currentEntry} saída ?";
                }
                $currentEntry = $entry['time'];
            } elseif ($entry['type'] === 'exit') {
                if ($currentEntry) {
                    $pairs[] = "entrada {$currentEntry} saída {$entry['time']}";
                    $currentEntry = null;
                } else {
                    // Saída sem entrada correspondente  
                    $pairs[] = "entrada ? saída {$entry['time']}";
                }
            }
        }
        
        // Entrada pendente sem saída
        if ($currentEntry) {
            $pairs[] = "entrada {$currentEntry} saída ?";
        }
        
        $dateFormatted = date('d/m/Y', strtotime($date));
        return implode(' - ', $pairs) . " {$dateFormatted}";
    }
    
    /**
     * Total de horas de um funcionário (para compatibilidade com dashboard admin)
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
     * Total de horas por projeto (para dashboard admin)
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
     * Registros individuais para API (formato compatível com código atual)
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
            
            // Converte cada entrada/saída individual para o formato esperado
            foreach ($records['entries'] as $entry) {
                $results[] = [
                    'id' => $row['id'],
                    'date' => $row['date'],
                    'time' => $entry['time'],
                    'entry_type' => $entry['type']
                ];
            }
        }
        
        return $results;
    }
}