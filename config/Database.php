<?php
// database.php — configuração de produção na Hostinger

// Dados de conexão
$host = 'auth-db1525.hstgr.io';
$db   = 'u161269623_saas';
$user = 'u161269623_saas';
$pass = '$xOOtHax24çÇ@@YU';

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$db};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    error_log("Erro na conexão com o banco de dados: " . $e->getMessage());
    die("Não foi possível conectar ao banco de dados.");
}

// Ambiente de produção: esconda erros na tela
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Fuso horário
date_default_timezone_set('Europe/Zurich');

// Diretórios de upload
define('UPLOAD_DIR',         __DIR__ . '/../../uploads/');
define('EMPLOYEE_UPLOAD_DIR', UPLOAD_DIR . 'employees/');
define('FINANCE_UPLOAD_DIR',  UPLOAD_DIR . 'finance/');

// Cria pastas de upload caso não existam
foreach ([UPLOAD_DIR, EMPLOYEE_UPLOAD_DIR, FINANCE_UPLOAD_DIR] as $d) {
    if (! file_exists($d)) {
        mkdir($d, 0755, true);
    }
}

// URL base do sistema (subpasta /system/)
define('BASE_URL', 'https://ams.swiss/system');
