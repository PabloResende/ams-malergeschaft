<?php
// app/helpers.php - ARQUIVO COMPLETO CORRIGIDO

// Função para redirecionar
function redirect($path) {
    $url = BASE_URL . $path;
    error_log("Redirecting to: $url");
    header("Location: $url");
    exit;
}

// Função para gerar URL
function url($path) {
    return BASE_URL . '/' . ltrim($path, '/');
}

// Função para gerar URL de assets
function asset($path) {
    return BASE_URL . '/public/' . ltrim($path, '/');
}

// Verificar se usuário está logado
function isLoggedIn() {
    $loggedIn = isset($_SESSION['user']['id']) && !empty($_SESSION['user']['id']);
    error_log("isLoggedIn: " . ($loggedIn ? 'true' : 'false'));
    return $loggedIn;
}

// Verificar se é funcionário
function isEmployee() {
    $role = $_SESSION['user']['role'] ?? '';
    $isEmp = $role === 'employee';
    error_log("isEmployee: " . ($isEmp ? 'true' : 'false') . " (role: $role)");
    return $isEmp;
}

// Verificar se é financeiro
function isFinance() {
    $role = $_SESSION['user']['role'] ?? '';
    $isFin = $role === 'finance';
    error_log("isFinance: " . ($isFin ? 'true' : 'false') . " (role: $role)");
    return $isFin;
}

// Verificar se é admin
function isAdmin() {
    $role = $_SESSION['user']['role'] ?? '';
    $isAdm = $role === 'admin';
    error_log("isAdmin: " . ($isAdm ? 'true' : 'false') . " (role: $role)");
    return $isAdm;
}

// Verificar se é admin ou financeiro
function isAdminOrFinance() {
    $role = $_SESSION['user']['role'] ?? '';
    $isAdminOrFin = $role === 'admin' || $role === 'finance';
    error_log("isAdminOrFinance: " . ($isAdminOrFin ? 'true' : 'false') . " (role: $role)");
    return $isAdminOrFin;
}

// Função para escapar HTML
function e($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// Função para formatar moeda
function formatCurrency($value, $currency = 'CHF') {
    return number_format((float)$value, 2, ',', '.') . ' ' . $currency;
}

// Função para formatar data
function formatDate($date, $format = 'd/m/Y') {
    if (empty($date)) return '';
    try {
        return date($format, strtotime($date));
    } catch (Exception $e) {
        return $date;
    }
}

// Função para formatar data e hora
function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    if (empty($datetime)) return '';
    try {
        return date($format, strtotime($datetime));
    } catch (Exception $e) {
        return $datetime;
    }
}

// Função para gerar token CSRF
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Função para verificar token CSRF
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Função para limpar dados de entrada
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Função para validar email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Função para gerar senha aleatória
function generateRandomPassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    return substr(str_shuffle($chars), 0, $length);
}

// Função para converter bytes em formato legível
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

// Função para debug (só em desenvolvimento)
function dd($data) {
    if (defined('DEBUG') && DEBUG) {
        echo '<pre>';
        var_dump($data);
        echo '</pre>';
        die();
    }
}

// Função para logging
function logError($message, $context = []) {
    $logMessage = date('Y-m-d H:i:s') . ' - ' . $message;
    if (!empty($context)) {
        $logMessage .= ' - Context: ' . json_encode($context);
    }
    error_log($logMessage);
}

// Função para calcular tempo de serviço
function calculateServiceTime($startDate) {
    if (empty($startDate)) return '0 meses';
    
    try {
        $start = new DateTime($startDate);
        $now = new DateTime();
        $diff = $start->diff($now);
        
        $years = $diff->y;
        $months = $diff->m;
        
        if ($years > 0) {
            return $years . ' ano' . ($years > 1 ? 's' : '') . 
                   ($months > 0 ? ' e ' . $months . ' mês' . ($months > 1 ? 'es' : '') : '');
        } elseif ($months > 0) {
            return $months . ' mês' . ($months > 1 ? 'es' : '');
        } else {
            return $diff->d . ' dia' . ($diff->d > 1 ? 's' : '');
        }
    } catch (Exception $e) {
        return '0 meses';
    }
}

// Função para validar CPF (opcional)
function isValidCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    
    if (strlen($cpf) != 11 || preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }
    
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) {
            return false;
        }
    }
    
    return true;
}

// Função para validar CNPJ (opcional)
function isValidCNPJ($cnpj) {
    $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
    
    if (strlen($cnpj) != 14) {
        return false;
    }
    
    // Validação do CNPJ
    for ($i = 0, $j = 5, $sum = 0; $i < 12; $i++) {
        $sum += $cnpj[$i] * $j;
        $j = ($j == 2) ? 9 : $j - 1;
    }
    
    $remainder = $sum % 11;
    
    if ($cnpj[12] != ($remainder < 2 ? 0 : 11 - $remainder)) {
        return false;
    }
    
    for ($i = 0, $j = 6, $sum = 0; $i < 13; $i++) {
        $sum += $cnpj[$i] * $j;
        $j = ($j == 2) ? 9 : $j - 1;
    }
    
    $remainder = $sum % 11;
    
    return $cnpj[13] == ($remainder < 2 ? 0 : 11 - $remainder);
}

// Função para validar telefone brasileiro
function isValidPhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return preg_match('/^(?:\+55|55)?(?:\d{2})(?:9?\d{8})$/', $phone);
}

// Função para mascarar dados sensíveis
function maskSensitiveData($data, $type = 'email') {
    switch ($type) {
        case 'email':
            $parts = explode('@', $data);
            if (count($parts) == 2) {
                $name = $parts[0];
                $domain = $parts[1];
                $maskedName = substr($name, 0, 2) . str_repeat('*', strlen($name) - 2);
                return $maskedName . '@' . $domain;
            }
            break;
        case 'phone':
            $clean = preg_replace('/[^0-9]/', '', $data);
            if (strlen($clean) >= 8) {
                return substr($clean, 0, 2) . str_repeat('*', strlen($clean) - 4) . substr($clean, -2);
            }
            break;
        case 'cpf':
            $clean = preg_replace('/[^0-9]/', '', $data);
            if (strlen($clean) == 11) {
                return substr($clean, 0, 3) . '.***.***-' . substr($clean, -2);
            }
            break;
    }
    return $data;
}
?>