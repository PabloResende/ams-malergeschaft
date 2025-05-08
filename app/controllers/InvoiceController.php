<?php
// app/controllers/InvoiceController.php
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
        $pdf->Cell(0,10,"Fatura #{$inv['number']}",0,1);
        $pdf->SetFont('Arial','',12);
        $pdf->Cell(0,8,"Cliente: {$inv['client_name']}",0,1);
        // ... restante ...
        $pdf->Output('I', "invoice_{$inv['number']}.pdf");
    }
}

