<?php
ini_set('display_errors', true);
ini_set('display_startup_errors', true);
error_reporting(E_ALL);

date_default_timezone_set('Europe/Zurich');

define('BASE_URL', 'https://ams.swiss/system');

define('UPLOAD_DIR',          __DIR__ . '/../uploads/');
define('EMPLOYEE_UPLOAD_DIR', UPLOAD_DIR . 'employees/');
define('FINANCE_UPLOAD_DIR',  UPLOAD_DIR . 'finance/');

foreach ([UPLOAD_DIR, EMPLOYEE_UPLOAD_DIR, FINANCE_UPLOAD_DIR] as $dir) {
    if (! file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

define('DB_HOST', 'localhost');
define('DB_NAME', 'u161269623_saas');
define('DB_USER', 'u161269623_saas');
define('DB_PASS', 'Ubilabs2478!@');
define('DB_PORT', '3306');
