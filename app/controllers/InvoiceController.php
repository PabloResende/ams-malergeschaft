<?php
require_once __DIR__ . '/../../lib/fpdf.php';
require_once __DIR__ . '/../models/Invoice.php';

class InvoiceController {
    private $model;
    public function __construct() {
        $this->model = new Invoice();
    }

    public function generate($id) {
        $inv = $this->model->find($id);
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial','B',16);
        $pdf->Cell(0,10, "Fatura #{$inv['number']}",0,1);
        $pdf->SetFont('Arial','',12);
        $pdf->Cell(0,8, "Cliente: {$inv['client_name']}",0,1);
        $pdf->Cell(0,8, "Email:  {$inv['client_email']}",0,1);
        $pdf->Cell(0,8, "Valor:  ".number_format($inv['amount'],2,',','.'),0,1);
        $pdf->Cell(0,8, "Emissão: {$inv['issue_date']}",0,1);
        $pdf->Cell(0,8, "Venc.:   {$inv['due_date']}",0,1);
        $pdf->Output('I', "invoice_{$inv['number']}.pdf");
    }
}
