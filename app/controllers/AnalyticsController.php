<?php

class AnalyticsController
{
    public function index()
    {
        require_once __DIR__ . '/../../config/Database.php';
        $pdo = Database::connect();

        // Filtros de período
        $year = $_GET['year'] ?? date('Y');
        $month = $_GET['month'] ?? null;
        $quarter = $_GET['quarter'] ?? null;

        // Inicializa estatísticas
        $stats = [
            'total' => 0,
            'in_progress' => 0,
            'pending' => 0,
            'completed' => 0,
            'active' => 0,
            'total_hours' => 0,
        ];

        // Monta a query de estatísticas
        $query = "SELECT status, SUM(total_hours) AS total_hours, COUNT(*) AS count FROM projects WHERE YEAR(created_at) = ?";
        $params = [$year];

        if ($month) {
            $query .= " AND MONTH(created_at) = ?";
            $params[] = $month;
        }

        if ($quarter) {
            $query .= " AND QUARTER(created_at) = ?";
            $params[] = $quarter;
        }

        $query .= " GROUP BY status";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($data as $row) {
            $stats[$row['status']] = $row['count'];
            $stats['total_hours'] += $row['total_hours'] ?? 0;
            $stats['total'] += $row['count'];
        }

        // Projetos ativos são aqueles em progresso ou pendentes
        $stats['active'] = $stats['in_progress'] + $stats['pending'];

        // Calcula crescimento percentual
        $stats['growth'] = $this->calculateGrowth($pdo, $year, $month, $quarter);

        // Renderiza a view
        require_once __DIR__ . '/../views/analytics/index.php';
    }

    private function calculateGrowth($pdo, $year, $month, $quarter)
    {
        $prevYear = $year - 1;
        $prevMonth = $month ? $month - 1 : null;
        $prevQuarter = $quarter ? $quarter - 1 : null;

        $current = $this->getCompletedProjects($pdo, $year, $month, $quarter);
        $previous = $this->getCompletedProjects($pdo, $prevYear, $prevMonth, $prevQuarter);

        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return (($current - $previous) / $previous) * 100;
    }

    private function getCompletedProjects($pdo, $year, $month, $quarter)
    {
        $query = "SELECT COUNT(*) FROM projects WHERE status = 'completed' AND YEAR(created_at) = ?";
        $params = [$year];

        if ($month) {
            $query .= " AND MONTH(created_at) = ?";
            $params[] = $month;
        }

        if ($quarter) {
            $query .= " AND QUARTER(created_at) = ?";
            $params[] = $quarter;
        }

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }
}
