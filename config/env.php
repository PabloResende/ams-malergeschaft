<?php
// -------------------------------------------
// config/env.php
// -------------------------------------------
return [
    'stripe_secret'      => 'sk_test_XXXXXXXXXXXXXXXX',
    'stripe_publishable' => 'pk_test_XXXXXXXXXXXXXXXX',
    'stripe_base'        => 'https://api.stripe.com/v1',
    'currency'           => 'chf',
    'base_url'           => 'https://seu-dominio.ch/ams-malergeschaft/public',
    'smtp_host'          => 'smtp.exemplo.ch',
    'smtp_user'          => 'noreply@empresa.ch',
    'smtp_pass'          => 'sua_senha',
    'smtp_port'          => 587,
    'smtp_from_email'    => 'noreply@empresa.ch',
    'smtp_from_name'     => 'Minha Empresa CH',
];