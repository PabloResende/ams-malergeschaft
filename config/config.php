<?php
// system/config/config.php

// Exibe todos os erros
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Fuso horário
date_default_timezone_set('Europe/Zurich');

// URL base da aplicação (incluindo /system)
define('BASE_URL', 'https://ams.swiss/system');

// Diretórios de upload
define('UPLOAD_DIR',          __DIR__ . '/../uploads/');
define('EMPLOYEE_UPLOAD_DIR', UPLOAD_DIR . 'employees/');
define('FINANCE_UPLOAD_DIR',  UPLOAD_DIR . 'finance/');

foreach ([UPLOAD_DIR, EMPLOYEE_UPLOAD_DIR, FINANCE_UPLOAD_DIR] as $dir) {
    if (! file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Credenciais de conexão (usar as constantes abaixo em database.php)
define('DB_HOST', 'auth-db1525.hstgr.io');
define('DB_PORT', '3306');
define('DB_NAME', 'u161269623_saas');
define('DB_USER', 'u161269623_saas');
define('DB_PASS', '$xOOtHax24çÇ@@YU');
