<?php
// app/controllers/AnalyticsController.php

require_once __DIR__ . '/../models/Analytics.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../libs/fpdf.php';

class AnalyticsController
{
    // Garante usuário e email válidos
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

    // Calcula o período anterior ao mesmo filtro
    private function getPrevPeriod(int $y, string $q, string $s): array {
        $py = $y; $pq = $q; $ps = $s;
        if ($s) {
            if ($s === '1') { $py = $y - 1; $ps = '2'; }
            else            { $ps = '1';        }
        } elseif ($q) {
            if ($q === '1') { $py = $y - 1; $pq = '4'; }
            else            { $pq = (string)((int)$q - 1); }
        } else {
            $py = $y - 1;
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
        echo json_encode(Analytics::getStats($y,$q,$s));
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

        $y = (int)($_GET['year'] ?? date('Y'));
        $q = $_GET['quarter']  ?? '';
        $s = $_GET['semester'] ?? '';
        $curr = Analytics::getStats($y,$q,$s);

        // pega stats do período anterior
        list($py,$pq,$ps) = $this->getPrevPeriod($y,$q,$s);
        $prev = Analytics::getStats($py,$pq,$ps);

        // calcula variações
        $pct = function($now,$bef){
            if ($bef==0) return '—';
            return sprintf("%+.1f%%", ($now-$bef)/$bef*100);
        };
        $deltaMat  = $pct($curr['materials'],    $prev['materials']);
        $deltaCre  = $pct(array_sum($curr['created']),  array_sum($prev['created']));
        $deltaCom  = $pct(array_sum($curr['completed']),array_sum($prev['completed']));
        $utilBudget= $curr['budget_total']>0
            ? sprintf("%.1f%%", $curr['budget_used'] / $curr['budget_total']*100)
            : '—';

        // PDF
        $pdf = new FPDF('L','mm','A4');
        $pdf->AddPage();
        $pdf->SetFont('Arial','B',16);
        $pdf->Cell(0,10,"Analytics Report — Period Filtered",0,1,'C');

        // detalhes do filtro
        $pdf->SetFont('Arial','',12);
        $pdf->Cell(60,6,"Year: $y",0,0);
        if ($q) $pdf->Cell(60,6,"Quarter: Q$q",0,0);
        if ($s) $pdf->Cell(60,6,"Semester: S$s",0,0);
        $pdf->Ln(10);

        // Resumo numérico
        $pdf->SetFont('Arial','B',14);
        $pdf->Cell(0,6,"Summary",0,1);
        $pdf->SetFont('Arial','',12);
        $pdf->Cell(80,6,"Budget Planned:",0,0);
        $pdf->Cell(40,6,number_format($curr['budget_total'],2),0,1);
        $pdf->Cell(80,6,"Budget Used:",0,0);
        $pdf->Cell(40,6,number_format($curr['budget_used'],2)." ($utilBudget)",0,1);
        $pdf->Cell(80,6,"Materials Used:",0,0);
        $pdf->Cell(40,6,$curr['materials']." ($deltaMat vs prev)",0,1);
        $pdf->Cell(80,6,"Projects Created:",0,0);
        $pdf->Cell(40,6,array_sum($curr['created'])." ($deltaCre)",0,1);
        $pdf->Cell(80,6,"Projects Completed:",0,0);
        $pdf->Cell(40,6,array_sum($curr['completed'])." ($deltaCom)",0,1);
        $pdf->Ln(8);

        // Análise textual
        $pdf->SetFont('Arial','B',14);
        $pdf->Cell(0,6,"Analysis",0,1);
        $pdf->SetFont('Arial','',12);
        $pdf->MultiCell(0,6,
          "Compared to the previous period ($py"
          .($q?" Q$pq":"")
          .($s?" S$ps":"")
          ."), materials usage changed by $deltaMat, projects created by $deltaCre, "
          ."projects completed by $deltaCom. Budget utilization stands at $utilBudget."
        );
        $pdf->Ln(6);

        // Tabela mensal
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(30,8,'Month',1);
        $pdf->Cell(30,8,'Created',1);
        $pdf->Cell(30,8,'Completed',1);
        $pdf->Cell(40,8,'Materials',1);
        $pdf->Cell(40,8,'Budget Used',1);
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
        $curr = Analytics::getStats($y,$q,$s);
        list($py,$pq,$ps) = $this->getPrevPeriod($y,$q,$s);
        $prev = Analytics::getStats($py,$pq,$ps);

        // percent helper
        $percent = fn($n,$o)=> $o? round(($n-$o)/$o*100,1).'%' : '—';

        // prepara CSV
        header('Content-Type: text/csv; charset=UTF-8');
        header("Content-Disposition: attachment; filename=\"analytics_report_{$y}.csv\"");
        $out = fopen('php://output','w');
        // BOM
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

        // Análise no topo
        fputcsv($out, ['Metric','Value','Change vs Prev']);
        fputcsv($out, ['Budget Planned',$curr['budget_total'],'']);
        fputcsv($out, ['Budget Used',$curr['budget_used'],$percent($curr['budget_used'],$prev['budget_used'])]);
        fputcsv($out, ['Materials Used',$curr['materials'],$percent($curr['materials'],$prev['materials'])]);
        fputcsv($out, ['Projects Created',array_sum($curr['created']),$percent(array_sum($curr['created']),array_sum($prev['created']))]);
        fputcsv($out, ['Projects Completed',array_sum($curr['completed']),$percent(array_sum($curr['completed']),array_sum($prev['completed']))]);
        fputcsv($out, []);

        // Mensais
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
        $body = json_decode(file_get_contents('php://input'),true)['summary'] ?? [];
        // caso queira complementar, pode gerar análise aqui
        $msg = implode("\n", $body);
        $sub = 'Your Detailed Analytics Report';
        $hdr = "From: no-reply@yourdomain.com\r\nContent-Type: text/plain; charset=UTF-8\r\n";
        $ok  = mail($email,$sub,$msg,$hdr);
        echo json_encode(['success'=>$ok,'message'=>$msg]);
        exit;
    }
}
