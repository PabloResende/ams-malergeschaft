<?php
// app/controllers/PaymentController.php
require_once __DIR__ . '/../../lib/StripeClient.php';
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../models/Invoice.php';
require_once __DIR__ . '/../helpers.php';
use PHPMailer\PHPMailer\PHPMailer;

class PaymentController {
    private $config, $stripe, $paymentModel, $invoiceModel;
    public function __construct() {
        $this->config       = require __DIR__ . '/../../config/env.php';
        $this->stripe       = new StripeClient();
        $this->paymentModel = new Payment();
        $this->invoiceModel = new Invoice();
    }

    // POS
    public function pos() {
        $pdo = Database::connect();
        $clients = $pdo->query("SELECT id, name, email FROM client WHERE active = 1")->fetchAll(PDO::FETCH_ASSOC);
        $vatRates = [7.7, 2.5];
        $currency = strtoupper($this->config['currency']);
        require __DIR__ . '/../views/payments/pos.php';
    }

    // AJAX: gerar link
    public function generateLink() {
        parse_str(file_get_contents('php://input'), $data);
        $amount = intval($data['amount'] * 100);
        $session = $this->stripe->createCheckoutSession($amount, \$this->config['base_url'] . '/finance/success', \$this->config['base_url'] . '/finance');
        header('Content-Type: application/json');
        echo json_encode(['url'=>$session['url']]);
    }

    // AJAX: gerar fatura
    public function generateInvoice() {
        parse_str(file_get_contents('php://input'), $data);
        $id = $this->invoiceModel->create([
            'number'       => $data['invoice_number'],
            'client_name'  => $data['client_name'],
            'client_email' => $data['client_email'],
            'amount'       => $data['amount'],
            'issue_date'   => date('Y-m-d'),
            'due_date'     => $data['due_date'],
            'status'       => 'pending',
        ]);
        header('Content-Type: application/json');
        echo json_encode(['invoice_url'=>$this->config['base_url'] . "/invoice/generate?id=$id"]);
    }

    // AJAX: enviar e-mail
    public function sendEmail() {
        parse_str(file_get_contents('php://input'), $data);
        $mail = new PHPMailer(true);
        try {
            \$cfg = \$this->config;
            \$mail->isSMTP();
            \$mail->Host       = \$cfg['smtp_host'];
            \$mail->SMTPAuth   = true;
            \$mail->Username   = \$cfg['smtp_user'];
            \$mail->Password   = \$cfg['smtp_pass'];
            \$mail->Port       = \$cfg['smtp_port'];
            \$mail->setFrom(\$cfg['smtp_from_email'], \$cfg['smtp_from_name']);
            \$mail->addAddress(\$data['client_email'], \$data['client_name']);
            \$mail->Subject = "Fatura {$data['invoice_number']}";
            \$body  = "Olá {$data['client_name']},\n\n";
            \$body .= "Link: {$data['payment_link']}\n";
            if (!empty(\$data['invoice_url'])) {
                \$body .= "Fatura PDF: {$data['invoice_url']}\n";
            }
            \$mail->Body = \$body;
            \$mail->send();
            echo json_encode(['success'=>true]);
        } catch (Exception \$e) {
            http_response_code(500);
            echo json_encode(['error'=>\$mail->ErrorInfo]);
        }
    }

    // Webhook e checkout permanecem semelhantes
}
