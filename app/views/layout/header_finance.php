<?php
// app/views/layout/header_finance.php

// 1) Sessão e helpers
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../helpers.php';

// 2) Internacionalização
$lang       = $_GET['lang'] ?? $_SESSION['lang'] ?? 'pt';
$_SESSION['lang'] = $lang;
$langFile   = __DIR__ . '/../../lang/' . $lang . '.php';
$langText   = file_exists($langFile) ? require $langFile : [];

// 3) Bandeirinhas
$flags = [
    'pt'=> ['name'=>'Português','flag'=> asset('assets/flags/pt.png')],
    'en'=> ['name'=>'English',  'flag'=> asset('assets/flags/us.png')],
    'de'=> ['name'=>'Deutsch',  'flag'=> asset('assets/flags/de.png')],
    'fr'=> ['name'=>'Français', 'flag'=> asset('assets/flags/fr.png')],
];
$currentFlag = isset($flags[$lang])
    ? '<img src="'.htmlspecialchars($flags[$lang]['flag'],ENT_QUOTES).'" class="w-5 h-5" alt="'.htmlspecialchars($flags[$lang]['name'],ENT_QUOTES).'">'
    : '🌎';

// 4) Helper para rota ativa
function isActive($path) {
    return strpos($_SERVER['REQUEST_URI'], $path) !== false
        ? 'text-blue-400'
        : 'hover:text-gray-300';
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Ams Malergeschäft</title>
  <link rel="stylesheet" href="<?= htmlspecialchars(asset('css/tailwind.css'), ENT_QUOTES) ?>">
  <link rel="icon" href="<?= htmlspecialchars(asset('assets/logo/ams-malergeschaft_icon.png'), ENT_QUOTES) ?>">
  <script>window.baseUrl = <?= json_encode(BASE_URL, JSON_UNESCAPED_SLASHES) ?>;</script>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-900">

  <!-- DESKTOP & TABLET -->
  <div class="hidden md:flex">

    <!-- Sidebar -->
    <aside class="w-56 bg-gray-900 text-white h-screen fixed left-0 top-0 p-4 flex flex-col z-50">
      <h1 class="text-2xl font-bold mb-6">
        <a href="<?= url('finance') ?>">Ams</a>
        <span class="text-gray-400 text-sm"><?= htmlspecialchars($langText['finance'] ?? 'Financeiro', ENT_QUOTES) ?></span>
      </h1>
      <nav class="flex-1">
        <ul>
          <!-- Financeiro -->
          <li class="mb-4">
            <a href="<?= url('finance') ?>" class="flex items-center space-x-2 <?= isActive('/finance') ?>">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 1v2m0 18v2m4-11H8m3-4c-1.657 0-3 
                         1.343-3 3s1.343 3 3 3 3 1.343 3 3
                         -1.343 3-3 3"/>
              </svg>
              <span><?= htmlspecialchars($langText['finance'] ?? 'Financeiro', ENT_QUOTES) ?></span>
            </a>
          </li>
          <!-- Análises -->
          <li class="mb-4">
            <a href="<?= url('analytics') ?>" class="flex items-center space-x-2 <?= isActive('/analytics') ?>">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 3v18h18M12 13v6m-4-4v4m8-8v8m-4-12v12"/>
              </svg>
              <span><?= htmlspecialchars($langText['analytics'] ?? 'Análises', ENT_QUOTES) ?></span>
            </a>
          </li>
          <!-- Estoque -->
          <li class="mb-4">
            <a href="<?= url('inventory') ?>" class="flex items-center space-x-2 <?= isActive('/inventory') ?>">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M20 7l-8-4-8 4m16 0v6l-8 4-8-4V7m16 
                         6l-8 4m0 0l-8-4"/>
              </svg>
              <span><?= htmlspecialchars($langText['inventory'] ?? 'Estoque', ENT_QUOTES) ?></span>
            </a>
          </li>
          <!-- Carros -->
          <li class="mb-4">
            <a href="<?= url('cars') ?>" class="flex items-center space-x-2 <?= isActive('/cars') ?>">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 13l1-3h16l1 3M5 17h2a1 1 0 
                         001 1h8a1 1 0 001-1h2M7 17v2m10-2v2"/>
              </svg>
              <span><?= htmlspecialchars($langText['cars'] ?? 'Carros', ENT_QUOTES) ?></span>
            </a>
          </li>
          <!-- Perfil -->
          <li class="mt-auto mb-4">
            <a href="<?= url('employees/profile') ?>" class="flex items-center space-x-2 <?= isActive('/employees/profile') ?>">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M5.121 17.804A8.963 8.963 0 0112 15
                         c2.366 0 4.533.924 6.121 2.804M15 
                         11a3 3 0 11-6 0 3 3 0 016 0z"/>
              </svg>
              <span><?= htmlspecialchars($langText['profile'] ?? 'Perfil', ENT_QUOTES) ?></span>
            </a>
          </li>
          <!-- Logout -->
          <li>
            <a href="<?= url('logout') ?>" class="flex items-center space-x-2 text-red-500 hover:text-red-400">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M14 3H5a2 2 0 00-2 2v14a2 2 0 
                         002 2h9"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M7 12h14m0 0l-4-4m4 4l-4 4"/>
              </svg>
              <span><?= htmlspecialchars($langText['logout_button'] ?? 'Sair', ENT_QUOTES) ?></span>
            </a>
          </li>
        </ul>
      </nav>
    </aside>

    <!-- Topbar -->
    <nav class="fixed top-0 left-56 right-0 bg-white shadow p-4 flex items-center justify-between z-30">
      <div class="flex items-center space-x-2">
        <a href="<?= url('finance') ?>" class="text-3xl font-bold text-blue-600">Ams Malergeschäft</a>
      </div>
      <div class="relative">
        <button id="language-button" class="flex items-center gap-1 focus:outline-none">
          <?= $currentFlag ?>
        </button>
        <div id="language-menu" class="hidden absolute top-full right-0 mt-2 w-32 bg-white rounded shadow-lg divide-y divide-gray-100 overflow-auto max-h-60 z-50">
          <?php foreach ($flags as $code => $f): ?>
            <a href="?lang=<?= $code ?>" class="flex items-center px-3 py-2 hover:bg-gray-100">
              <img src="<?= htmlspecialchars($f['flag'],ENT_QUOTES) ?>" class="w-5 h-5 mr-2" alt="<?= htmlspecialchars($f['name'],ENT_QUOTES) ?>">
              <span><?= htmlspecialchars($f['name'],ENT_QUOTES) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </nav>
  </div>

  <!-- MOBILE Topbar -->
  <div class="md:hidden">
    <nav class="fixed top-0 left-0 right-0 bg-white h-14 flex items-center justify-between px-4 shadow z-50">
      <span class="text-lg font-bold text-blue-600">Ams Malergeschäft</span>
      <div class="relative">
        <button id="language-button-mobile" class="flex items-center gap-1 focus:outline-none">
          <?= $currentFlag ?>
        </button>
        <div id="language-menu-mobile" class="hidden absolute top-full right-0 mt-2 w-32 bg-white rounded shadow-lg divide-y divide-gray-100 overflow-auto max-h-60 z-50">
          <?php foreach ($flags as $code => $f): ?>
            <a href="?lang=<?= $code ?>" class="flex items-center px-3 py-2 hover:bg-gray-100">
              <img src="<?= htmlspecialchars($f['flag'],ENT_QUOTES) ?>" class="w-5 h-5 mr-2" alt="<?= htmlspecialchars($f['name'],ENT_QUOTES) ?>">
              <span><?= htmlspecialchars($f['name'],ENT_QUOTES) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </nav>
  </div>

  <!-- MOBILE Bottom Nav -->
  <div class="md:hidden fixed bottom-0 left-0 right-0 bg-white bottom-nav z-50">
    <ul class="flex justify-around">
      <li>
        <a href="<?= url('finance') ?>" class="flex flex-col items-center py-2 <?= isActive('/finance') ?>">
          <svg class="w-6 h-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 1v2m0 18v2m4-11H8m3-4c-1.657 0-3 1.343-3 3s1.343 3 3 3 3 1.343 3 3-1.343 3-3 3"/></svg>
          <span class="text-xs"><?= htmlspecialchars($langText['finance'] ?? 'Finanças', ENT_QUOTES) ?></span>
        </a>
      </li>
      <li>
        <a href="<?= url('analytics') ?>" class="flex flex-col items-center py-2 <?= isActive('/analytics') ?>">
          <svg class="w-6 h-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v18h18M12 13v6m-4-4v4m8-8v8m-4-12v12"/></svg>
          <span class="text-xs"><?= htmlspecialchars($langText['analytics'] ?? 'Análises', ENT_QUOTES) ?></span>
        </a>
      </li>
      <li>
        <a href="<?= url('inventory') ?>" class="flex flex-col items-center py-2 <?= isActive('/inventory') ?>">
          <svg class="w-6 h-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0v6l-8 4-8-4V7m16 6l-8 4m0 0l-8-4"/></svg>
          <span class="text-xs"><?= htmlspecialchars($langText['inventory'] ?? 'Estoque', ENT_QUOTES) ?></span>
        </a>
      </li>
      <li>
        <a href="<?= url('cars') ?>" class="flex flex-col items-center py-2 <?= isActive('/cars') ?>">
          <svg class="w-6 h-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 13l1-3h16l1 3M5 17h2a1 1 0 001 1h8a1 1 0 001-1h2M7 17v2m10-2v2"/></svg>
          <span class="text-xs"><?= htmlspecialchars($langText['cars'] ?? 'Carros', ENT_QUOTES) ?></span>
        </a>
      </li>
      <li>
        <a href="<?= url('employees/profile') ?>" class="flex flex-col items-center py-2 <?= isActive('/employees/profile') ?>">
          <svg class="w-6 h-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A8.963 8.963 0 0112 15c2.366 0 4.533.924 6.121 2.804M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          <span class="text-xs"><?= htmlspecialchars($langText['profile'] ?? 'Perfil', ENT_QUOTES) ?></span>
        </a>
      </li>
        <!-- Logout -->
          <li>
            <a href="<?= url('logout') ?>" class="flex flex-col items-center py-2 text-red-500 hover:text-red-400">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M14 3H5a2 2 0 00-2 2v14a2 2 0 
                         002 2h9"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M7 12h14m0 0l-4-4m4 4l-4 4"/>
              </svg>
              <span><?= htmlspecialchars($langText['logout_button'] ?? 'Sair', ENT_QUOTES) ?></span>
            </a>
          </li>
    </ul>
  </div>

