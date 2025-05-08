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
        $err  = curl_error($ch);
        curl_close($ch);
        if ($err) throw new \Exception("Stripe cURL error: $err");
        return json_decode($resp, true);
    }

    public function createPaymentIntent(int $amountCentavos) {
        $cfg = require __DIR__ . '/../config/env.php';
        return $this->request('POST', '/payment_intents', [
            'amount'               => $amountCentavos,
            'currency'             => $cfg['currency'],
            'payment_method_types[]' => 'card',
        ]);
    }

    public function retrievePaymentIntent(string $id) {
        return $this->request('GET', "/payment_intents/$id");
    }
}
