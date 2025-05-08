<?php
// app/controllers/AnalyticsController.php

require_once __DIR__ . '/../models/Analytics.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../libs/fpdf.php';

class AnalyticsController
{
    private function ensureEmail(): string {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $user = $_SESSION['user'] ?? null;
        if (empty($user['email'])) {
            throw new Exception('User email not found.');
        }
        $um = new UserModel();
        if (!$um->findByEmail($user['email'])) {
            throw new Exception('User not found.');
        }
        return $user['email'];
    }

    private function getPrevPeriod(int $y, string $q, string $s): array {
        $py = $y; $pq = $q; $ps = $s;
        if ($s) {
            if ($s === '1') { $py--; $ps = '2'; }
            else            { $ps = '1';  }
        } elseif ($q) {
            if ($q === '1') { $py--; $pq = '4'; }
            else            { $pq = (string)((int)$q - 1); }
        } else {
            $py--;
        }
        return [$py, $pq, $ps];
    }

    public function index() {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (!isset($_SESSION['user'])) {
            header("Location: /ams-malergeschaft/public/login");
            exit;
        }
        include __DIR__ . '/../views/analytics/index.php';
    }

    public function stats() {
        header('Content-Type: application/json');
        $y = (int)($_GET['year'] ?? date('Y'));
        $q = $_GET['quarter']  ?? '';
        $s = $_GET['semester'] ?? '';
        echo json_encode(Analytics::getStats($y, $q, $s));
        exit;
    }

    public function exportPdf() {
        try {
            $this->ensureEmail();
        } catch (Exception $e) {
            http_response_code(400);
            echo $e->getMessage();
            exit;
        }

        // current & previous stats
        $y = (int)($_GET['year'] ?? date('Y'));
        $q = $_GET['quarter']  ?? '';
        $s = $_GET['semester'] ?? '';
        $curr = Analytics::getStats($y, $q, $s);
        list($py, $pq, $ps) = $this->getPrevPeriod($y, $q, $s);
        $prev = Analytics::getStats($py, $pq, $ps);

        // helper: percentage change
        $pct = fn($now, $bef) => $bef == 0
            ? '—'
            : sprintf("%+.1f%%", ($now - $bef) / $bef * 100);

        // deltas for new metrics
        $deltaMat     = $pct($curr['materials'],    $prev['materials']);
        $deltaCre     = $pct($curr['totalProjects'], $prev['totalProjects']);
        $deltaCom     = $pct($curr['totalCompleted'],$prev['totalCompleted']);
        $deltaDur     = $pct($curr['avgDuration'],  $prev['avgDuration']);
        $deltaAvgBud  = $pct($curr['avgBudgetPerProject'], $prev['avgBudgetPerProject']);
        $utilBudget   = $curr['budget_total'] > 0
            ? sprintf("%.1f%%", $curr['budget_used'] / $curr['budget_total'] * 100)
            : '—';

        // period labels
        $periodLabel     = "Year $y" . ($q?" Q$q":"") . ($s?" S$s":"");
        $prevPeriodLabel = "Year $py" . ($pq?" Q$pq":"") . ($ps?" S$ps":"");

        // PDF setup
        $pdf = new FPDF('L','mm','A4');
        $pdf->AddPage();
        $pdf->SetFont('Arial','B',16);
        $pdf->Cell(0,10,"Analytics Report — $periodLabel",0,1,'C');

        // numeric summary
        $pdf->SetFont('Arial','',12);
        $pdf->Cell(80,6,"Budget Planned:",0,0);
        $pdf->Cell(40,6,number_format($curr['budget_total'],2),0,1);
        $pdf->Cell(80,6,"Budget Used:",0,0);
        $pdf->Cell(40,6,number_format($curr['budget_used'],2)." ($utilBudget)",0,1);
        $pdf->Cell(80,6,"Total Projects Created:",0,0);
        $pdf->Cell(40,6,$curr['totalProjects']." ($deltaCre)",0,1);
        $pdf->Cell(80,6,"Total Projects Completed:",0,0);
        $pdf->Cell(40,6,$curr['totalCompleted']." ($deltaCom)",0,1);
        $pdf->Cell(80,6,"Average Duration (days):",0,0);
        $pdf->Cell(40,6,number_format($curr['avgDuration'],1)." ($deltaDur)",0,1);
        $pdf->Cell(80,6,"Avg Budget per Project:",0,0);
        $pdf->Cell(40,6,number_format($curr['avgBudgetPerProject'],2)." ($deltaAvgBud)",0,1);
        $pdf->Cell(80,6,"Materials Used:",0,0);
        $pdf->Cell(40,6,$curr['materials']." ($deltaMat)",0,1);
        $pdf->Cell(80,6,"Peak Month (Created):",0,0);
        $pdf->Cell(40,6,$curr['peakCreatedLabel'],0,1);
        $pdf->Cell(80,6,"Peak Month (Completed):",0,0);
        $pdf->Cell(40,6,$curr['peakCompletedLabel'],0,1);
        $pdf->Ln(8);

        // richer textual analysis
        $pdf->SetFont('Arial','B',14);
        $pdf->Cell(0,6,"Analysis",0,1);
        $pdf->SetFont('Arial','',12);
        $analysis = "
During the period $periodLabel, planned budget was ".number_format($curr['budget_total'],2).", of which {$curr['budget_used']} was used ({$utilBudget}). 
Materials usage totaled {$curr['materials']} units ({$deltaMat} vs $prevPeriodLabel). 
Projects created reached {$curr['totalProjects']} ({$deltaCre}), with peak creations in {$curr['peakCreatedLabel']}. 
Projects completed were {$curr['totalCompleted']} ({$deltaCom}), peaking in {$curr['peakCompletedLabel']}. 
Average project duration was {$curr['avgDuration']} days ({$deltaDur}), and average budget per project was {$curr['avgBudgetPerProject']} ({$deltaAvgBud}).";
        $pdf->MultiCell(0,6,trim($analysis));
        $pdf->Ln(6);

        // table monthly
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(30,8,'Month',1);
        $pdf->Cell(30,8,'Created',1);
        $pdf->Cell(30,8,'Completed',1);
        $pdf->Cell(40,8,'Materials',1);
        $pdf->Cell(40,8,'Budget %',1);
        $pdf->Ln();
        $pdf->SetFont('Arial','',12);
        foreach ($curr['labels'] as $i => $m) {
            $pdf->Cell(30,8,$m,1);
            $pdf->Cell(30,8,$curr['created'][$i],1);
            $pdf->Cell(30,8,$curr['completed'][$i],1);
            $pdf->Cell(40,8,$curr['materialsUsage'][$i],1);
            $u = $curr['budget_total']>0
                ? round($curr['budget_used']/$curr['budget_total']*100)
                : 0;
            $pdf->Cell(40,8,"{$u}%",1);
            $pdf->Ln();
        }

        header('Content-Type: application/pdf');
        header("Content-Disposition: attachment; filename=\"analytics_report_{$y}.pdf\"");
        $pdf->Output('D');
        exit;
    }

    public function exportExcel() {
        try {
            $this->ensureEmail();
        } catch (Exception $e) {
            http_response_code(400); echo $e->getMessage(); exit;
        }
        $y = (int)($_GET['year'] ?? date('Y'));
        $q = $_GET['quarter']  ?? '';
        $s = $_GET['semester'] ?? '';
        $curr = Analytics::getStats($y, $q, $s);
        list($py, $pq, $ps) = $this->getPrevPeriod($y, $q, $s);
        $prev = Analytics::getStats($py, $pq, $ps);

        $percent = fn($n,$o)=> $o
            ? round(($n-$o)/$o*100,1).'%' 
            : '—';

        // prepare narrative
        $periodLabel     = "Year $y" . ($q?" Q$q":"") . ($s?" S$s":"");
        $prevPeriodLabel = "Year $py" . ($pq?" Q$pq":"") . ($ps?" S$ps":"");
        $deltaMat     = $percent($curr['materials'],    $prev['materials']);
        $deltaCre     = $percent($curr['totalProjects'],$prev['totalProjects']);
        $deltaCom     = $percent($curr['totalCompleted'],$prev['totalCompleted']);
        $deltaDur     = $percent($curr['avgDuration'],  $prev['avgDuration']);
        $deltaAvgBud  = $percent($curr['avgBudgetPerProject'], $prev['avgBudgetPerProject']);
        $utilBudget   = $curr['budget_total']>0 
            ? round($curr['budget_used']/$curr['budget_total']*100,1).'%'
            : '—';

        header('Content-Type: text/csv; charset=UTF-8');
        header("Content-Disposition: attachment; filename=\"analytics_report_{$y}.csv\"");
        $out = fopen('php://output','w');
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

        // narrative at top
        fputcsv($out, ['Analysis']);
        fputcsv($out, ["During the period {$periodLabel}, planned budget was {$curr['budget_total']} (used: {$curr['budget_used']} — {$utilBudget})."]);
        fputcsv($out, ["Materials usage: {$curr['materials']} units ({$deltaMat} vs {$prevPeriodLabel})."]);
        fputcsv($out, ["Projects created: {$curr['totalProjects']} ({$deltaCre}), peak in {$curr['peakCreatedLabel']}."]);
        fputcsv($out, ["Projects completed: {$curr['totalCompleted']} ({$deltaCom}), peak in {$curr['peakCompletedLabel']}."]);
        fputcsv($out, ["Avg duration: {$curr['avgDuration']} days ({$deltaDur})."]);
        fputcsv($out, ["Avg budget/project: {$curr['avgBudgetPerProject']} ({$deltaAvgBud})."]);
        fputcsv($out, []);

        // key metrics table
        fputcsv($out, ['Metric','Value','Change vs Prev']);
        fputcsv($out, ['Budget Planned',$curr['budget_total'],'']);
        fputcsv($out, ['Budget Used',$curr['budget_used'],$utilBudget]);
        fputcsv($out, ['Materials Used',$curr['materials'],$deltaMat]);
        fputcsv($out, ['Projects Created',$curr['totalProjects'],$deltaCre]);
        fputcsv($out, ['Projects Completed',$curr['totalCompleted'],$deltaCom]);
        fputcsv($out, ['Avg Duration (days)',$curr['avgDuration'],$deltaDur]);
        fputcsv($out, ['Avg Budget/Project',$curr['avgBudgetPerProject'],$deltaAvgBud]);
        fputcsv($out, []);

        // monthly breakdown
        fputcsv($out, array_merge(['Month'], $curr['labels']));
        fputcsv($out, array_merge(['Created'], $curr['created']));
        fputcsv($out, array_merge(['Completed'], $curr['completed']));
        fputcsv($out, array_merge(['Materials Usage'], $curr['materialsUsage']));
        fclose($out);
        exit;
    }

    public function sendEmail() {
        header('Content-Type: application/json');
        try {
            $email = $this->ensureEmail();
        } catch (Exception $e) {
            echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
            exit;
        }
        // You can extend here to build an even richer email body—
        // for now it will just relay what the client sent.
        $body = json_decode(file_get_contents('php://input'),true)['summary'] ?? [];
        $msg  = implode("\n", $body);
        $sub  = 'Your Detailed Analytics Report';
        $hdr  = "From: no-reply@yourdomain.com\r\nContent-Type: text/plain; charset=UTF-8\r\n";
        $ok   = mail($email, $sub, $msg, $hdr);
        echo json_encode(['success'=>$ok,'message'=>$msg]);
        exit;
    }
}
