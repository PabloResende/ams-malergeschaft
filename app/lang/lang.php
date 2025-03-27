<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$lang = 'pt'; 
if (isset($_GET['lang'])) {
    $lang = $_GET['lang'];
    $_SESSION['lang'] = $lang;

    if (!headers_sent()) {
        setcookie('lang', $lang, time() + (86400 * 30), "/");
    }
} elseif (isset($_SESSION['lang'])) {
    $lang = $_SESSION['lang'];
} elseif (isset($_COOKIE['lang'])) {
    $lang = $_COOKIE['lang'];
}

$langFile = __DIR__ . "/{$lang}.php";
if (!file_exists($langFile)) {
    $langFile = __DIR__ . "/pt.php"; 
}

$langText = require $langFile;
?>
