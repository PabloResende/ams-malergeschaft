<?php
class StripeClient {
    private $secret;
    private $base;

    public function __construct() {
        $cfg = require __DIR__ . '/../config/env.php';
        $this->secret = $cfg['stripe_secret'];
        $this->base   = $cfg['stripe_base'];
    }

    private function request(string $method, string $path, array $params = []) {
        $ch = curl_init($this->base . $path);
        curl_setopt($ch, CURLOPT_USERPWD, $this->secret . ':');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }
        $resp = curl_exec($ch);
        if ($err = curl_error($ch)) {
            curl_close($ch);
            throw new \Exception("Stripe cURL error: $err");
        }
        curl_close($ch);
        return json_decode($resp, true);
    }

    public function createCheckoutSession(int $amount, string $successUrl, string $cancelUrl) {
        $cfg = require __DIR__ . '/../config/env.php';
        return $this->request('POST', '/checkout/sessions', [
            'payment_method_types[]' => 'card',
            'line_items[0][price_data][currency]'      => $cfg['currency'],
            'line_items[0][price_data][unit_amount]'   => $amount,
            'line_items[0][price_data][product_data][name]' => 'Cobrança',
            'line_items[0][quantity]'                  => 1,
            'mode'                    => 'payment',
            'success_url'             => $successUrl,
            'cancel_url'              => $cancelUrl,
        ]);
    }

    public function createPaymentLink(int $amount) {
        $cfg = require __DIR__ . '/../config/env.php';
        return $this->request('POST', '/payment_links', [
            'line_items[0][price_data][currency]'      => $cfg['currency'],
            'line_items[0][price_data][unit_amount]'   => $amount,
            'line_items[0][price_data][product_data][name]' => 'Cobrança',
            'line_items[0][quantity]'                  => 1,
        ]);
    }
}
