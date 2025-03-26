<?php
require_once __DIR__ . '/../app/controllers/UserController.php';

$uri = $_SERVER['REQUEST_URI'];
$basePath = '/ams-malergeschaft/public';
$route = str_replace($basePath, '', $uri);

// Verifica se há um parâmetro de idioma e salva na sessão/cookie
if (isset($_GET['lang'])) {
    $lang = $_GET['lang'];
    $_SESSION['lang'] = $lang;
    setcookie('lang', $lang, time() + (86400 * 30), "/");
}

// Remove parâmetros GET da URL antes de processar as rotas
$route = strtok($route, '?'); 

$controller = new UserController();

switch ($route) {
    case '/':
    case '/login':
        $controller->login();
        break;
    
    case '/auth':
        $controller->authenticate();
        break;
    
    case '/register':
        $controller->register();
        break;
    
    case '/store':
        $controller->store();
        break;
    
    case '/dashboard':
        $controller->dashboard();
        break;
    
    case '/logout':
        $controller->logout();
        break;
    
    case '/profile':
        $controller->profile();
        break;
    
    default:
        http_response_code(404);
        echo "404 - Page not found.";
        break;
}
