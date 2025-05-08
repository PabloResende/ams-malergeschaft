<?php
// app/controllers/PaymentController.php

require_once __DIR__ . '/../../lib/StripeClient.php';
require_once __DIR__ . '/../models/Payment.php';

class PaymentController {
    private $config;
    private $stripeClient;
    private $model;

    public function __construct() {
        $this->config       = require __DIR__ . '/../../config/env.php';
        $this->stripeClient = new StripeClient();
        $this->model        = new Payment();
    }

    public function form() {
        // variáveis para o view
        $currency       = strtoupper($this->config['currency']);
        $publishableKey = $this->config['stripe_publishable'];

        // renderiza dentro do seu layout (cabeçalho + sidebar + footer)
        require __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/payments/form.php';
    }

    public function checkout() {
        $amount = intval($_POST['amount'] * 100);
        $pi     = $this->stripeClient->createPaymentIntent($amount);
        $this->model->create([
            'intent_id'  => $pi['id'],
            'amount'     => $amount,
            'currency'   => $pi['currency'],
            'status'     => $pi['status'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        header('Content-Type: application/json');
        echo json_encode(['clientSecret' => $pi['client_secret']]);
    }

    public function webhook() {
        $payload   = @file_get_contents('php://input');
        $data      = json_decode($payload, true);
        $eventType = $data['type'] ?? '';
        if ($eventType === 'payment_intent.succeeded') {
            $pi = $data['data']['object'];
            $this->model->updateStatus($pi['id'], $pi['status']);
        }
        http_response_code(200);
    }
}
