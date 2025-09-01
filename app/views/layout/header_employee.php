<?php
// app/views/layout/header_employee.php

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
    'es'=> ['name'=>'Español',  'flag'=> asset('assets/flags/es.png')],
];
$currentFlag = isset($flags[$lang])
    ? '<img src="'.htmlspecialchars($flags[$lang]['flag'],ENT_QUOTES).'" class="w-5 h-5" alt="'.htmlspecialchars($flags[$lang]['name'],ENT_QUOTES).'">'
    : '🌎';

// Helper para rota ativa
function isActiveEmp($path) {
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
  <script>window.baseUrl=<?= json_encode(BASE_URL, JSON_UNESCAPED_SLASHES) ?>;</script>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-900 relative">

  <!-- Desktop & Tablet -->
  <div class="hidden md:flex">
    <!-- Sidebar -->
    <aside class="w-56 bg-gray-900 text-white h-screen fixed left-0 top-0 p-4 flex flex-col z-50">
      <h1 class="text-2xl font-bold mb-6">
        <a href="<?= url('employees/dashboard') ?>">Ams</a>
        <span class="text-gray-400 text-sm">
          <?= htmlspecialchars($langText['employees'] ?? 'Funcionários', ENT_QUOTES) ?>
        </span>
      </h1>
      <nav class="flex-1">
        <ul>
          <!-- Dashboard -->
          <li class="mb-4">
            <a href="<?= url('employees/dashboard') ?>"
               class="flex items-center space-x-2 <?= isActiveEmp('/employees/dashboard') ?>">
              <!-- ícone -->
              <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10.707 1.707a1 1 0 00-1.414 0l-7 7A1 1 0 003 10h1v7a1 1 0 001 1h4v-5h2v5h4a1 1 0 001-1v-7h1a1 1 0 00.707-1.707l-7-7z"/>
              </svg>
              <span><?= htmlspecialchars($langText['dashboard'] ?? 'Dashboard', ENT_QUOTES) ?></span>
            </a>
          </li>
          <!-- Meu Perfil -->
          <li class="mb-4">
            <a href="<?= url('employees/profile') ?>"
               class="flex items-center space-x-2 <?= isActiveEmp('/employees/profile') ?>">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87M16 7a4 4 0 11-8 0 4 4 0 018 0z"/>
              </svg>
              <span><?= htmlspecialchars($langText['employee_tab'] ?? 'Meu Perfil', ENT_QUOTES) ?></span>
            </a>
          </li>
          <!-- Estoque -->
          <li class="mb-4">
            <a href="<?= url('inventory') ?>"
               class="flex items-center space-x-2 <?= isActiveEmp('/inventory') ?>">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M20 7l-8-4-8 4m16 0v6l-8 4-8-4V7m16 6l-8 4m0 0l-8-4"/>
              </svg>
              <span><?= htmlspecialchars($langText['inventory'] ?? 'Estoque', ENT_QUOTES) ?></span>
            </a>
          </li>
          <!-- Carros -->
          <li class="mb-4">
            <a href="<?= url('cars') ?>"
               class="flex items-center space-x-2 <?= isActiveEmp('/cars') ?>">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 13l1-3h16l1 3M5 17h2a1 1 0 001 1h8a1 1 0 001-1h2M7 17v2m10-2v2"/>
              </svg>
              <span><?= htmlspecialchars($langText['cars'] ?? 'Carros', ENT_QUOTES) ?></span>
            </a>
          </li>
          <!-- Logout -->
          <li class="mt-auto">
            <a href="<?= url('logout') ?>"
               class="flex items-center space-x-2 text-red-500 hover:text-red-400">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M14 3H5a2 2 0 00-2 2v14a2 2 0 002 2h9"/>
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
        <a href="<?= url('employees/dashboard') ?>"
           class="text-3xl font-bold text-blue-600">Ams Malergeschäft</a>
      </div>
      <button id="language-button"
              class="flex items-center gap-1 focus:outline-none">
        <?= $currentFlag ?>
      </button>
      <div id="language-menu"
           class="hidden absolute top-16 right-4 mt-2 w-32 bg-white rounded shadow-lg divide-y divide-gray-100 overflow-auto max-h-60 z-50">
        <?php foreach ($flags as $code => $f): ?>
          <a href="?lang=<?= $code ?>"
             class="flex items-center px-3 py-2 hover:bg-gray-100">
            <img src="<?= htmlspecialchars($f['flag'], ENT_QUOTES) ?>"
                 class="w-5 h-5 mr-2"
                 alt="<?= htmlspecialchars($f['name'], ENT_QUOTES) ?>">
            <span><?= htmlspecialchars($f['name'], ENT_QUOTES) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </nav>
</div>

  <!-- MOBILE Topbar -->
  <div class="md:hidden">
    <nav class="fixed top-0 left-0 right-0 bg-white h-14 flex items-center justify-between px-4 shadow z-50">
      <span class="text-lg font-bold text-blue-600">Ams Malergeschäft</span>
      <div class="relative">
        <button id="language-button-mobile"
                class="flex items-center gap-1 focus:outline-none">
          <?= $currentFlag ?>
        </button>
        <div id="language-menu-mobile"
             class="hidden absolute top-full right-0 mt-2 w-32 bg-white rounded shadow-lg divide-y divide-gray-100 overflow-auto max-h-60 z-50">
          <?php foreach ($flags as $code => $f): ?>
            <a href="?lang=<?= $code ?>"
               class="flex items-center px-3 py-2 hover:bg-gray-100">
              <img src="<?= htmlspecialchars($f['flag'],ENT_QUOTES) ?>"
                   class="w-5 h-5 mr-2"
                   alt="<?= htmlspecialchars($f['name'],ENT_QUOTES) ?>">
              <span><?= htmlspecialchars($f['name'],ENT_QUOTES) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </nav>
  </div>

  <!-- Mobile Bottom Nav -->
  <div class="md:hidden fixed bottom-0 left-0 right-0 bg-white bottom-nav z-50">
    <ul class="flex justify-around">
      <li>
        <a href="<?= url('employees/dashboard') ?>"
           class="flex flex-col items-center py-2 <?= isActiveEmp('/employees/dashboard') ?>">
          <svg class="w-6 h-6 mb-1" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10.707 1.707a1 1 0 00-1.414 0l-7 7A1 1 0 003 10h1v7a1 1 0 001 1h4v-5h2v5h4a1 1 0 001-1v-7h1a1 1 0 00.707-1.707l-7-7z"/>
          </svg>
          <span class="text-xs"><?= htmlspecialchars($langText['dashboard'] ?? 'Painel', ENT_QUOTES) ?></span>
        </a>
      </li>
      <li>
        <a href="<?= url('employees/profile') ?>"
           class="flex flex-col items-center py-2 <?= isActiveEmp('/employees/profile') ?>">
          <svg class="w-6 h-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 
                     013-3.87M16 7a4 4 0 11-8 0 4 4 0 018 0z"/>
          </svg>
          <span class="text-xs"><?= htmlspecialchars($langText['employee_tab'] ?? 'Meu Perfil', ENT_QUOTES) ?></span>
        </a>
      </li>
      <li>
        <a href="<?= url('inventory') ?>"
           class="flex flex-col items-center py-2 <?= isActiveEmp('/inventory') ?>">
          <svg class="w-6 h-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M20 7l-8-4-8 4m16 0v6l-8 4-8-4V7m16 
                     6l-8 4m0 0l-8-4"/>
          </svg>
          <span class="text-xs"><?= htmlspecialchars($langText['inventory'] ?? 'Estoque', ENT_QUOTES) ?></span>
        </a>
      </li>
      <li>
        <a href="<?= url('cars') ?>"
           class="flex flex-col items-center py-2 <?= isActiveEmp('/cars') ?>">
          <svg class="w-6 h-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M3 13l1-3h16l1 3M5 17h2a1 1 0 
                     001 1h8a1 1 0 001-1h2M7 17v2m10-2v2"/>
          </svg>
          <span class="text-xs"><?= htmlspecialchars($langText['cars'] ?? 'Carros', ENT_QUOTES) ?></span>
        </a>
      </li>
      <li class="mt-auto">
        <a href="<?= url('logout') ?>"
           class="flex flex-col items-center py-2 text-red-500 hover:text-red-400">
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M14 3H5a2 2 0 00-2 2v14a2 2 0 002 2h9"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M7 12h14m0 0l-4-4m4 4l-4 4"/>
          </svg>
          <span class="text-xs"><?= htmlspecialchars($langText['logout_button'] ?? 'Sair', ENT_QUOTES) ?></span>
        </a>
      </li>
    </ul>
  </div>
