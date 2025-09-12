<?php
// app/models/TimeEntryModel.php - VERSÃO COMPLETA E CORRIGIDA

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
     * Calcula total de horas SIMPLIFICADO - apenas pares entrada/saída válidos
     */
    private function calculateTotalHours(array $entries): float 
    {
        if (empty($entries)) {
            return 0.0;
        }
        
        // Ordena por horário
        usort($entries, function($a, $b) {
            $timeA = $a['time'] ?? '00:00';
            $timeB = $b['time'] ?? '00:00';
            return strcmp($timeA, $timeB);
        });
        
        // Separa entradas e saídas em ordem cronológica
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
        
        // Calcula horas apenas para pares completos (entrada + saída)
        $totalMinutes = 0;
        $pairsCount = min(count($entradas), count($saidas));
        
        for ($i = 0; $i < $pairsCount; $i++) {
            $startTime = strtotime("1970-01-01 " . $entradas[$i]);
            $endTime = strtotime("1970-01-01 " . $saidas[$i]);
            
            // Se saída for antes da entrada (passou da meia-noite), adiciona 1 dia
            if ($endTime < $startTime) {
                $endTime += 24 * 60 * 60; // +24 horas
            }
            
            if ($endTime > $startTime) {
                $totalMinutes += ($endTime - $startTime) / 60;
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
     * Formata para exibição SIMPLIFICADO - SEM interrogações
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
        
        // Forma pares simples
        $pairs = [];
        $maxPairs = max(count($entradas), count($saidas));
        
        for ($i = 0; $i < $maxPairs; $i++) {
            $entrada = isset($entradas[$i]) ? $entradas[$i] : '';
            $saida = isset($saidas[$i]) ? $saidas[$i] : '';
            
            if ($entrada || $saida) {
                $pair = '';
                if ($entrada) $pair .= "entrada {$entrada}";
                if ($entrada && $saida) $pair .= " ";
                if ($saida) $pair .= "saída {$saida}";
                
                $pairs[] = $pair;
            }
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