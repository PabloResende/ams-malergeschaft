<?php
// Configurações do banco de dados para produção
$host = 'auth-db1525.hstgr.io'; // Host específico para sua hospedagem 
$db   = 'u161269623_saas';
$user = 'u161269623_saas';
$pass = '$xOOtHax24çÇ@@YU';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    error_log("Erro na conexão com o banco de dados: " . $e->getMessage());
    die("Não foi possível conectar ao banco de dados.");
}

// Configurações para o ambiente de produção
// Desative a exibição de erros em produção depois que o sistema estiver funcionando
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Defina o fuso horário para o projeto
date_default_timezone_set('Europe/Zurich');

// Definir caminho base para o sistema
$basePath = '';  // String vazia para raiz do domínio, ou '/system' se estiver em subdiretório

// Defina constantes para o ambiente
define('BASE_URL', 'https://system.ams.swiss');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('EMPLOYEE_UPLOAD_DIR', UPLOAD_DIR . 'employees/');
define('FINANCE_UPLOAD_DIR', UPLOAD_DIR . 'finance/');

// Crie os diretórios de upload se não existirem
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}
if (!file_exists(EMPLOYEE_UPLOAD_DIR)) {
    mkdir(EMPLOYEE_UPLOAD_DIR, 0755, true);
}
if (!file_exists(FINANCE_UPLOAD_DIR)) {
    mkdir(FINANCE_UPLOAD_DIR, 0755, true);
}