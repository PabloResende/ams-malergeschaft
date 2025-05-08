<?php
// app/models/Analytics.php
require_once __DIR__ . '/../../config/Database.php';

class Analytics {
    public static function getStats(int $year, string $quarter = '', string $semester = ''): array {
        $pdo = Database::connect();

        // 1) Define the period
        $start = "$year-01-01";
        $end   = "$year-12-31";
        if ($quarter) {
            $q  = (int)$quarter;
            $m1 = ($q - 1)*3 + 1;
            $m3 = $m1 + 2;
            $start = sprintf("%04d-%02d-01", $year, $m1);
            $end   = sprintf(
                "%04d-%02d-%02d",
                $year, $m3,
                cal_days_in_month(CAL_GREGORIAN, $m3, $year)
            );
        } elseif ($semester) {
            $s = (int)$semester;
            if ($s === 1) {
                $start = "$year-01-01";
                $end   = "$year-06-30";
            } else {
                $start = "$year-07-01";
                $end   = "$year-12-31";
            }
        }

        // 2) Build months & labels
        $months = [];
        $labels = [];
        $dt     = new DateTime($start);
        $dtEnd  = new DateTime($end);
        $names  = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
        while ($dt <= $dtEnd) {
            $m = (int)$dt->format('n');
            if (!in_array($m, $months, true)) {
                $months[] = $m;
                $labels[] = $names[$m-1];
            }
            $dt->modify('+1 month');
        }
        $in = implode(',', $months);

        // 3) Projects created per month
        $sql = "
          SELECT MONTH(created_at) AS m, COUNT(*) AS cnt
            FROM projects
           WHERE created_at BETWEEN :start AND :end
             AND MONTH(created_at) IN ($in)
           GROUP BY m
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['start'=>$start,'end'=>$end]);
        $createdData = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // 4) Projects completed per month
        $sql = "
          SELECT MONTH(end_date) AS m, COUNT(*) AS cnt
            FROM projects
           WHERE status='completed'
             AND end_date BETWEEN :start AND :end
             AND MONTH(end_date) IN ($in)
           GROUP BY m
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['start'=>$start,'end'=>$end]);
        $completedData = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // 5) Fill arrays
        $created   = [];
        $completed = [];
        foreach ($months as $m) {
            $created[]   = (int)($createdData[$m]   ?? 0);
            $completed[] = (int)($completedData[$m] ?? 0);
        }

        // 6) Status breakdown
        $stmt = $pdo->prepare("
          SELECT status, COUNT(*) AS cnt
            FROM projects
           WHERE created_at BETWEEN :start AND :end
           GROUP BY status
        ");
        $stmt->execute(['start'=>$start,'end'=>$end]);
        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $statuses = [
            'pending'     => (int)($rows['pending']     ?? 0),
            'in_progress' => (int)($rows['in_progress'] ?? 0),
            'completed'   => (int)($rows['completed']   ?? 0),
        ];

        // 7) Budget totals
        $stmt = $pdo->prepare("
          SELECT 
            SUM(budget) AS total,
            SUM(CASE WHEN status='completed' THEN budget ELSE 0 END) AS used
          FROM projects
         WHERE start_date BETWEEN :start AND :end
        ");
        $stmt->execute(['start'=>$start,'end'=>$end]);
        $budg = $stmt->fetch(PDO::FETCH_ASSOC);

        // 8) Materials usage
        $stmt = $pdo->prepare("
          SELECT SUM(quantity) AS mat
            FROM project_resources
           WHERE resource_type='inventory'
             AND created_at BETWEEN :start AND :end
        ");
        $stmt->execute(['start'=>$start,'end'=>$end]);
        $materials = (int)$stmt->fetchColumn();

        // 9) Materials per month
        $stmt = $pdo->prepare("
          SELECT MONTH(created_at) AS m, SUM(quantity) AS sumq
            FROM project_resources
           WHERE resource_type='inventory'
             AND created_at BETWEEN :start AND :end
             AND MONTH(created_at) IN ($in)
           GROUP BY m
        ");
        $stmt->execute(['start'=>$start,'end'=>$end]);
        $matRows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $materialsUsage = [];
        foreach ($months as $m) {
            $materialsUsage[] = (int)($matRows[$m] ?? 0);
        }

        // 10) Total hours on completed projects
        $stmt = $pdo->prepare("
          SELECT SUM(total_hours) AS hrs
            FROM projects
           WHERE status='completed'
             AND end_date BETWEEN :start AND :end
        ");
        $stmt->execute(['start'=>$start,'end'=>$end]);
        $hours = (int)$stmt->fetchColumn();

        // --- NEW METRICS ---

        // a) Totals
        $totalProjects   = array_sum($created);
        $totalCompleted  = array_sum($completed);

        // b) Average duration (in days) of completed projects
        $stmt = $pdo->prepare("
          SELECT AVG(DATEDIFF(end_date, start_date)) AS avg_days
            FROM projects
           WHERE status='completed'
             AND end_date BETWEEN :start AND :end
        ");
        $stmt->execute(['start'=>$start,'end'=>$end]);
        $avgDays = (float)$stmt->fetchColumn();

        // c) Average budget per project created
        $avgBudgetPerProject = $totalProjects
            ? ($budg['total'] / $totalProjects)
            : 0;

        // d) Peak months
        $peakCreatedIndex    = array_search(max($created), $created);
        $peakCompletedIndex  = array_search(max($completed), $completed);
        $peakCreatedMonth    = $months[$peakCreatedIndex];
        $peakCompletedMonth  = $months[$peakCompletedIndex];
        $peakCreatedLabel    = $names[$peakCreatedMonth - 1];
        $peakCompletedLabel  = $names[$peakCompletedMonth - 1];

        return [
            'labels'               => $labels,
            'created'              => $created,
            'completed'            => $completed,
            'status'               => $statuses,
            'budget_total'         => (float)($budg['total'] ?? 0),
            'budget_used'          => (float)($budg['used']  ?? 0),
            'materials'            => $materials,
            'materialsUsage'       => $materialsUsage,
            'hours'                => $hours,

            // new
            'totalProjects'        => $totalProjects,
            'totalCompleted'       => $totalCompleted,
            'avgDuration'          => round($avgDays, 1),
            'avgBudgetPerProject'  => round($avgBudgetPerProject, 2),
            'peakCreated'          => $peakCreatedMonth,
            'peakCreatedLabel'     => $peakCreatedLabel,
            'peakCompleted'        => $peakCompletedMonth,
            'peakCompletedLabel'   => $peakCompletedLabel,
        ];
    }
}
