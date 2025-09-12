<?php
// app/helpers.php - Funções auxiliares para o sistema

/**
 * Gera URL completa para os recursos do sistema
 *
 * @param string $path
 * @return string
 */
if (! function_exists('url')) {
    function url(string $path = ''): string {
        return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
    }
}

/**
 * Retorna caminho para os assets (JS, CSS, imagens)
 *
 * @param string $path
 * @return string
 */
if (! function_exists('asset')) {
    function asset(string $path = ''): string {
        return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
    }
}

// verifica se está logado
function isLoggedIn(): bool {
    return ! empty($_SESSION['user']);
}

// verifica employee
function isEmployee(): bool {
    return isset($_SESSION['user']['role'])
        && $_SESSION['user']['role'] === 'employee';
}

// verifica finance
function isFinance(): bool {
    return isset($_SESSION['user']['role'])
        && $_SESSION['user']['role'] === 'finance';
}

// verifica admin
function isAdmin(): bool {
    return isset($_SESSION['user']['role'])
        && $_SESSION['user']['role'] === 'admin';
}

// verifica admin ou finance
function isAdminOrFinance(): bool {
    return isAdmin() || isFinance();
}

/**
 * Redireciona para a URL especificada
 *
 * @param string $path
 * @return void
 */
if (! function_exists('redirect')) {
    function redirect(string $path = ''): void {
        header('Location: ' . url($path));
        exit;
    }
}

/**
 * Formata um valor como moeda
 *
 * @param float  $value
 * @param string $currency
 * @return string
 */
if (! function_exists('formatCurrency')) {
    function formatCurrency(float $value, string $currency = 'BRL'): string {
        switch ($currency) {
            case 'USD':
                return '$ '  . number_format($value, 2, '.', ',');
            case 'EUR':
                return '€ '  . number_format($value, 2, ',', '.');
            case 'CHF':
                return 'CHF ' . number_format($value, 2, '.', ',');
            case 'BRL':
            default:
                return 'R$ ' . number_format($value, 2, ',', '.');
        }
    }
}

/**
 * Sanitiza uma string de entrada
 *
 * @param string $string
 * @return string
 */
if (! function_exists('sanitize')) {
    function sanitize(string $string): string {
        return htmlspecialchars(trim($string), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Gera um número de fatura
 *
 * @return string
 */
if (! function_exists('generateInvoiceNumber')) {
    function generateInvoiceNumber(): string {
        return 'INV-' . date('Ymd') . '-' . rand(1000, 9999);
    }
}

/**
 * Verifica se o usuário está logado
 *
 * @return bool
 */
if (! function_exists('isLoggedIn')) {
    function isLoggedIn(): bool {
        return isset($_SESSION['user']);
    }
}

/**
 * Obtém o idioma atual
 *
 * @return string
 */
function url(string $path, array $params = []): string {
    $params['lang'] = $_SESSION['lang'] ?? 'pt';
    $qs = http_build_query($params);
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/') . '?' . $qs;
}




/**
 * Verifica se um arquivo existe e é legível
 *
 * @param string $path
 * @return bool
 */
if (! function_exists('fileExists')) {
    function fileExists(string $path): bool {
        return file_exists($path) && is_readable($path);
    }
}

/**
 * Cria um diretório se não existir
 *
 * @param string $path
 * @param int    $permissions
 * @return bool
 */
if (! function_exists('createDirIfNotExists')) {
    function createDirIfNotExists(string $path, int $permissions = 0755): bool {
        if (! file_exists($path)) {
            return mkdir($path, $permissions, true);
        }
        return true;
    }
}

/**
 * Remove caracteres especiais de uma string
 *
 * @param string $string
 * @return string
 */
if (! function_exists('removeSpecialChars')) {
    function removeSpecialChars(string $string): string {
        $replacements = [
            '/[áàãâä]/ui' => 'a', '/[éèêë]/ui' => 'e',
            '/[íìîï]/ui' => 'i', '/[óòõôö]/ui' => 'o',
            '/[úùûü]/ui' => 'u', '/[ç]/ui'      => 'c',
        ];
        $string = preg_replace(array_keys($replacements), array_values($replacements), $string);
        $string = preg_replace('/[^a-z0-9]/i', '_', $string);
        $string = preg_replace('/_+/', '_', $string);
        return strtolower(trim($string, '_'));
    }
}

/**
 * Obtém a extensão de um arquivo
 *
 * @param string $filename
 * @return string
 */
if (! function_exists('getFileExtension')) {
    function getFileExtension(string $filename): string {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }
}

/**
 * Formata uma data para exibição
 *
 * @param string $date
 * @param string $format
 * @return string
 */
if (! function_exists('formatDate')) {
    function formatDate(string $date, string $format = 'd/m/Y'): string {
        if (empty($date)) {
            return '';
        }
        return date($format, strtotime($date));
    }
}

/**
 * Caminho raiz da aplicação
 *
 * @return string
 */
if (! function_exists('getRootPath')) {
    function getRootPath(): string {
        return realpath(__DIR__ . '/..');
    }
}
