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
            $email = $this->ensureEmail();
        } catch (Exception $e) {
            http_response_code(400);
            echo $e->getMessage();
            exit;
        }
        $y = (int)($_GET['year'] ?? date('Y'));
        $q = $_GET['quarter']  ?? '';
        $s = $_GET['semester'] ?? '';
        $d = Analytics::getStats($y,$q,$s);

        // PDF
        $pdf = new FPDF('L','mm','A4');
        $pdf->AddPage();
        $pdf->SetFont('Arial','B',16);
        $pdf->Cell(0,10,"Analytics Report - {$y}",0,1,'C');
        $pdf->SetFont('Arial','',12);
        $pdf->Ln(4);
        // resumo
        $pdf->Cell(60,8,'Budget Planned:',0,0);
        $pdf->Cell(40,8,number_format($d['budget_total'],2),0,1);
        $pdf->Cell(60,8,'Budget Used:',0,0);
        $pdf->Cell(40,8,number_format($d['budget_used'],2),0,1);
        $pdf->Cell(60,8,'Total Materials:',0,0);
        $pdf->Cell(40,8,$d['materials'],0,1);
        $pdf->Cell(60,8,'Total Hours:',0,0);
        $pdf->Cell(40,8,$d['hours'],0,1);
        // tabela mensal
        $pdf->Ln(6);
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(30,8,'Month',1);
        $pdf->Cell(30,8,'Created',1);
        $pdf->Cell(30,8,'Completed',1);
        $pdf->Cell(40,8,'Materials Use',1);
        $pdf->Ln();
        $pdf->SetFont('Arial','',12);
        foreach ($d['labels'] as $i => $lbl) {
            $pdf->Cell(30,8,$lbl,1);
            $pdf->Cell(30,8,$d['created'][$i],1);
            $pdf->Cell(30,8,$d['completed'][$i],1);
            $pdf->Cell(40,8,$d['materialsUsage'][$i],1);
            $pdf->Ln();
        }
        header('Content-Type: application/pdf');
        header("Content-Disposition: attachment; filename=\"analytics_{$y}.pdf\"");
        $pdf->Output('D');
        exit;
    }

    public function exportExcel() {
        try {
            $email = $this->ensureEmail();
        } catch (Exception $e) {
            http_response_code(400);
            echo $e->getMessage();
            exit;
        }
        $y = (int)($_GET['year'] ?? date('Y'));
        $q = $_GET['quarter']  ?? '';
        $s = $_GET['semester'] ?? '';
        $d = Analytics::getStats($y,$q,$s);

        header('Content-Type: text/csv; charset=UTF-8');
        header("Content-Disposition: attachment; filename=\"analytics_{$y}.csv\"");
        $out = fopen('php://output','w');
        // BOM UTF-8
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
        // cabeçalhos
        fputcsv($out, ['Metric','Value']);
        fputcsv($out, ['Budget Planned',$d['budget_total']]);
        fputcsv($out, ['Budget Used',$d['budget_used']]);
        fputcsv($out, ['Total Materials',$d['materials']]);
        fputcsv($out, ['Total Hours',$d['hours']]);
        fputcsv($out, []);
        fputcsv($out, array_merge(['Month'], $d['labels']));
        fputcsv($out, array_merge(['Created'], $d['created']));
        fputcsv($out, array_merge(['Completed'], $d['completed']));
        fputcsv($out, array_merge(['Materials Usage'], $d['materialsUsage']));
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
        $msg  = implode("\n",$body);
        $sub  = 'Your Analytics Report';
        $hdr  = "From: no-reply@yourdomain.com\r\nContent-Type: text/plain; charset=UTF-8\r\n";
        $ok   = mail($email,$sub,$msg,$hdr);
        echo json_encode(['success'=>$ok,'message'=>$msg]);
        exit;
    }
}
