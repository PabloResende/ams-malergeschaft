<?php
ini_set('display_errors', true);
ini_set('display_startup_errors', true);
error_reporting(E_ALL);

// Fuso horário
date_default_timezone_set('Europe/Zurich');

// URL base do sistema (sub-pasta /system)
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

// Credenciais de conexão com o banco e porta
define('DB_HOST', 'localhost');
define('DB_NAME', 'mvp_db');
define('DB_USER', 'root');
define('DB_PASS', '');
    