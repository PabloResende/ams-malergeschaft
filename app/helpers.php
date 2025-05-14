<?php
// app/helpers.php - Funções auxiliares para o sistema

/**
 * Gera URL completa para os recursos do sistema
 * 
 * @param string $path Caminho relativo (ex: '/login', '/projects')
 * @return string URL completa
 */
function url($path = '') {
    $baseUrl = 'https://system.ams.swiss';
    $path = ltrim($path, '/');
    return $baseUrl . '/' . $path;
}

/**
 * Retorna caminho para os assets (JS, CSS, imagens)
 * 
 * @param string $path Caminho relativo do asset
 * @return string URL completa para o asset
 */
function asset($path = '') {
    $path = ltrim($path, '/');
    return url('public/' . $path);
}

/**
 * Redireciona para a URL especificada
 * 
 * @param string $path Caminho para redirecionar
 * @return void
 */
function redirect($path = '') {
    header('Location: ' . url($path));
    exit;
}

/**
 * Formata um valor como moeda
 * 
 * @param float $value Valor a ser formatado
 * @param string $currency Código da moeda (default: BRL)
 * @return string Valor formatado como moeda
 */
function formatCurrency($value, $currency = 'BRL') {
    if ($currency === 'BRL') {
        return 'R$ ' . number_format($value, 2, ',', '.');
    } else if ($currency === 'USD') {
        return '$ ' . number_format($value, 2, '.', ',');
    } else if ($currency === 'EUR') {
        return '€ ' . number_format($value, 2, ',', '.');
    } else if ($currency === 'CHF') {
        return 'CHF ' . number_format($value, 2, '.', ',');
    }
    
    return number_format($value, 2, '.', ',');
}

/**
 * Sanitiza uma string de entrada
 * 
 * @param string $string String a ser sanitizada
 * @return string String sanitizada
 */
function sanitize($string) {
    return htmlspecialchars(trim($string), ENT_QUOTES, 'UTF-8');
}

/**
 * Gera um número de fatura
 * 
 * @return string Número da fatura no formato INV-AAAAMMDD-XXXX
 */
function generateInvoiceNumber() {
    return 'INV-' . date('Ymd') . '-' . rand(1000, 9999);
}

/**
 * Verifica se o usuário está logado
 * 
 * @return bool Verdadeiro se o usuário estiver logado
 */
function isLoggedIn() {
    return isset($_SESSION['user']);
}

/**
 * Obtém o idioma atual
 * 
 * @return string Código do idioma atual
 */
function getCurrentLanguage() {
    return $_SESSION['lang'] ?? 'pt';
}

/**
 * Verifica se um arquivo existe e é legível
 * 
 * @param string $path Caminho do arquivo
 * @return bool Verdadeiro se o arquivo existir e for legível
 */
function fileExists($path) {
    return file_exists($path) && is_readable($path);
}

/**
 * Cria um diretório se não existir
 * 
 * @param string $path Caminho do diretório
 * @param int $permissions Permissões do diretório (default: 0755)
 * @return bool Verdadeiro se o diretório existir ou for criado com sucesso
 */
function createDirIfNotExists($path, $permissions = 0755) {
    if (!file_exists($path)) {
        return mkdir($path, $permissions, true);
    }
    return true;
}

/**
 * Remove caracteres especiais de uma string
 * 
 * @param string $string String a ser limpa
 * @return string String sem caracteres especiais
 */
function removeSpecialChars($string) {
    $string = preg_replace('/[áàãâä]/ui', 'a', $string);
    $string = preg_replace('/[éèêë]/ui', 'e', $string);
    $string = preg_replace('/[íìîï]/ui', 'i', $string);
    $string = preg_replace('/[óòõôö]/ui', 'o', $string);
    $string = preg_replace('/[úùûü]/ui', 'u', $string);
    $string = preg_replace('/[ç]/ui', 'c', $string);
    $string = preg_replace('/[^a-z0-9]/i', '_', $string);
    $string = preg_replace('/_+/', '_', $string);
    return strtolower(trim($string, '_'));
}

/**
 * Obtém a extensão de um arquivo
 * 
 * @param string $filename Nome do arquivo
 * @return string Extensão do arquivo
 */
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}