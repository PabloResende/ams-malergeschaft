<?php
// app/views/layout/header.php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$notifications       = include __DIR__ . '/partials/notification.php';
$notificationCount   = count($notifications);


$baseUrl = '/ams-malergeschaft/public';
require __DIR__ . '/../../lang/lang.php';

$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'pt';
$_SESSION['lang'] = $lang;

$flags = [
    'pt'=>['name'=>'Português','flag'=>"$baseUrl/assets/flags/pt.png"],
    'en'=>['name'=>'English',  'flag'=>"$baseUrl/assets/flags/us.png"],
    'de'=>['name'=>'Deutsch',  'flag'=>"$baseUrl/assets/flags/de.png"],
    'fr'=>['name'=>'Français', 'flag'=>"$baseUrl/assets/flags/fr.png"],
];
$currentFlag = isset($flags[$lang])
    ? '<img src="'.htmlspecialchars($flags[$lang]['flag']).'" class="w-5 h-5" alt="'.htmlspecialchars($flags[$lang]['name']).'">'
    : '🌎';

$isLoginPage = str_contains($_SERVER['REQUEST_URI'],'login')
            || str_contains($_SERVER['REQUEST_URI'],'register');
$isLoggedIn = isset($_SESSION['user']);
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
  <title>Ams Malergeschäft</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="icon" href="<?= $baseUrl ?>/assets/logo/ams-malergeschaft_icon.png">
  <style>
    body { margin:0; padding:0; }
    #sidebar { transform: translateX(-100%); transition: transform .2s; }
    @media(min-width:768px){ #sidebar{transform:translateX(0);} }
    .sidebar-open{transform: translateX(0)!important;}
    .dropdown-menu{
      position:absolute; right:0; margin-top:.5rem; width:200px;
      background:#fff; border:1px solid #d1d5db; border-radius:.375rem;
      box-shadow:0 2px 8px rgba(0,0,0,0.1); z-index:50;
    }
    .bottom-nav{
      box-shadow:0 -1px 5px rgba(0,0,0,0.1);
      border-top:1px solid #e5e7eb;
    }
  </style>
</head>
<body class="bg-gray-100 text-gray-900 relative">

<?php if (! $isLoginPage && $isLoggedIn): ?>

  <!-- Overlay Mobile -->
  <div id="contentOverlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-40"></div>

  <!-- Desktop & Tablet -->
  <div class="hidden md:block">

    <!-- Sidebar -->
    <aside id="sidebar" class="w-56 bg-gray-900 text-white h-screen fixed left-0 top-0 p-4 z-50">
      <h1 class="text-2xl font-bold mb-6">
        <a href="<?= $baseUrl ?>/dashboard">Ams Malergeschäft</a>
      </h1>
      <nav>
        <ul>
        <li class="mb-4">
          <a href="<?= $baseUrl ?>/dashboard" class="flex items-center space-x-2 hover:text-gray-300">
            <!-- Heroicon: Home simples -->
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
              <path d="M10.707 1.707a1 1 0 00-1.414 0l-7 7A1 1 0 003 10h1v7a1 1 0 001 1h4v-5h2v5h4a1 1 0 001-1v-7h1a1 1 0 00.707-1.707l-7-7z"/>
            </svg>
            <span><?= $langText['dashboard'] ?? 'Painel de Controle' ?></span>
          </a>
        </li>
        <li class="mb-4">
          <a href="<?= $baseUrl ?>/finance" class="flex items-center space-x-2 hover:text-gray-300">
            <!-- Heroicon: Home simples -->
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 1v2m0 18v2m4-11H8m3-4c-1.657 0-3 1.343-3 3s1.343 3 3 3 3 1.343 3 3-1.343 3-3 3"/>
            </svg>
            <span><?= $langText['finance'] ?? 'Financeiro' ?></span>
          </a>
        </li>
          <li class="mb-4">
            <a href="<?= $baseUrl ?>/projects" class="flex items-center space-x-2 hover:text-gray-300">
              <!-- Heroicon: Briefcase -->
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V5a2 2 0 012-2h4a2 2 0 012 2v2m-8 0h8m-9 4h10m-10 4h10m-9 4h8a2 2 0 002-2v-2H5v2a2 2 0 002 2z" />
              </svg>
              <span><?= $langText['projects'] ?? 'Projetos' ?></span>
            </a>
          </li>
          <li class="mb-4">
            <a href="<?= $baseUrl ?>/employees" class="flex items-center space-x-2 hover:text-gray-300">
              <!-- Heroicon: Users -->
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87M16 7a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
              <span><?= $langText['employees'] ?? 'Funcionários' ?></span>
            </a>
          </li>
          <li class="mb-4">
            <a href="<?= $baseUrl ?>/inventory" class="flex items-center space-x-2 hover:text-gray-300">
              <!-- Heroicon: Cube -->
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0v6l-8 4-8-4V7m16 6l-8 4m0 0l-8-4" />
              </svg>
              <span><?= $langText['inventory'] ?? 'Estoque' ?></span>
            </a>
          </li>
          <li class="mb-4">
            <a href="<?= $baseUrl ?>/calendar" class="flex items-center space-x-2 hover:text-gray-300">
              <!-- Heroicon: Calendar -->
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10m-2 8H7a2 2 0 01-2-2V9a2 2 0 012-2h10a2 2 0 012 2v10a2 2 0 01-2 2z" />
              </svg>
              <span><?= $langText['calendar'] ?? 'Calendário' ?></span>
            </a>
          </li>
          <li class="mb-4">
          <a href="<?= $baseUrl ?>/clients" class="flex items-center space-x-2 hover:text-gray-300">
            <!-- Heroicon: User Group -->
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 20h5v-2a4 4 0 00-3-3.87M2 18v-2a4 4 0 013-3.87M12 7a4 4 0 110 8 4 4 0 010-8z" />
            </svg>
            <span><?= $langText['clients'] ?? 'Clientes' ?></span>
          </a>
        </li>
          <li class="mb-4">
            <a href="<?= $baseUrl ?>/analytics" class="flex items-center space-x-2 hover:text-gray-300">
              <!-- Heroicon: Chart Bar -->
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v18h18M12 13v6m-4-4v4m8-8v8m-4-12v12" />
              </svg>
              <span><?= $langText['analytics'] ?? 'Análises' ?></span>
            </a>
          </li>
        </ul>
      </nav>
    </aside>

    <!-- Topbar -->
    <nav class="fixed top-0 left-56 right-0 bg-white shadow p-4 flex items-center justify-between z-30">
      <div class="flex items-center space-x-2">
        <a href="<?= $baseUrl ?>/dashboard" class="text-3xl font-bold text-blue-600">Ams</a>
        <span class="text-xl text-gray-600">Malergeschäft</span>
      </div>
      <div class="flex items-center space-x-4">
        <!-- Novo Projeto -->
        <a href="<?= $baseUrl ?>/projects?openModal=true" class="bg-green-500 text-white px-3 py-2 rounded hover:bg-green-600">
          <?= $langText['new_project'] ?? 'Novo Projeto +' ?>
        </a>

        <div class="relative">
        <button id="notificationBtn" class="relative p-2 focus:outline-none">
          <!-- Bell icon -->
          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0a3 3 0 11-6 0h6z" />
          </svg>
          <span id="notificationCount"
                class="hidden absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
            0
          </span>
        </button>
        <ul id="notificationList"
            class="dropdown-menu hidden absolute right-0 mt-2 w-64 bg-white shadow-lg max-h-80 overflow-auto z-50">
          <?php foreach ($notifications as $n): ?>
            <li class="notification-item px-4 py-2 hover:bg-gray-100 cursor-pointer"
                data-key="<?= htmlspecialchars($n['key'], ENT_QUOTES) ?>">
              <a href="<?= $n['url'] ?>" class="block">
                <?= htmlspecialchars($n['text'], ENT_QUOTES) ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

        <!-- Idioma -->
        <div class="relative">
          <button id="language-button" class="flex items-center gap-1 bg-transparent focus:outline-none"><?= $currentFlag ?></button>
          <div id="language-menu" class="dropdown-menu hidden">
            <?php foreach ($flags as $code => $f): ?>
              <a href="?lang=<?= $code ?>" class="flex items-center px-2 py-1 hover:bg-gray-100">
                <img src="<?= htmlspecialchars($f['flag']) ?>" class="w-5 h-5 mr-2" alt="<?= htmlspecialchars($f['name']) ?>">
                <span class="text-sm"><?= htmlspecialchars($f['name']) ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        </div>

      </div>
    </nav>
  </div>

  <!-- Mobile -->
  <div class="md:hidden">
    <!-- Top Navbar Mobile -->
    <nav class="fixed top-0 left-0 right-0 bg-white h-14 flex items-center justify-between px-4 shadow z-50">
      <button id="mobileMenuButton" class="focus:outline-none">
        <!-- Heroicon: Bars 3 -->
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>
      <div class="flex items-center space-x-2">
        <span class="text-lg font-bold text-blue-600">Ams</span>
        <span class="text-base text-gray-600">Malergeschäft</span>
      </div>
      <div class="flex items-center space-x-4">
        <!-- Notificações Mobile -->
        <div class="relative md:hidden">
          <button id="notificationBtnMobile" class="relative p-2 focus:outline-none">
            <!-- Bell icon -->
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0a3 3 0 11-6 0h6z" />
            </svg>
            <span id="notificationCountMobile"
                  class="hidden absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
              0
            </span>
          </button>
          <ul id="notificationListMobile"
              class="dropdown-menu hidden absolute right-0 mt-2 w-64 bg-white shadow-lg max-h-80 overflow-auto z-50">
            <?php foreach ($notifications as $n): ?>
              <li class="notification-item px-4 py-2 hover:bg-gray-100 cursor-pointer"
                  data-key="<?= htmlspecialchars($n['key'], ENT_QUOTES) ?>">
                <a href="<?= $n['url'] ?>" class="block">
                  <?= htmlspecialchars($n['text'], ENT_QUOTES) ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
        <!-- Idioma Mobile -->
        <div class="relative">
          <button id="language-button-mobile" class="flex items-center gap-1 focus:outline-none"><?= $currentFlag ?></button>
          <div id="language-menu-mobile" class="dropdown-menu hidden">
            <?php foreach ($flags as $code => $f): ?>
              <a href="?lang=<?= $code ?>" class="flex items-center px-2 py-1 hover:bg-gray-100">
                <img src="<?= htmlspecialchars($f['flag']) ?>" class="w-5 h-5 mr-2" alt="<?= htmlspecialchars($f['name']) ?>">
                <span class="text-xs"><?= htmlspecialchars($f['name']) ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </nav>
    <!-- Bottom Navbar Mobile -->
    <nav class="fixed bottom-0 left-0 right-0 bg-white bottom-nav z-50">
      <ul class="flex justify-around">
        <li>
          <a href="<?= $baseUrl ?>/dashboard" class="flex flex-col items-center py-2 text-gray-700">
            <!-- Heroicon: Home -->
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7m-9 2v8m4-8v8m5-2h-2a4 4 0 01-4-4V9" />
            </svg>
            <span class="text-xs"><?= $langText['dashboard'] ?? 'Painel' ?></span>
          </a>
        </li>
        <li>
          <a href="<?= $baseUrl ?>/projects" class="flex flex-col items-center py-2 text-gray-700">
            <!-- Heroicon: Briefcase -->
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V5a2 2 0 012-2h4a2 2 0 012 2v2m-8 0h8m-9 4h10m-10 4h10m-9 4h8a2 2 0 002-2v-2H5v2a2 2 0 002 2z" />
            </svg>
            <span class="text-xs"><?= $langText['projects'] ?? 'Projetos' ?></span>
          </a>
        </li>
        <li>
          <a href="<?= $baseUrl ?>/employees" class="flex flex-col items-center py-2 text-gray-700">
            <!-- Heroicon: Users -->
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87M16 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <span class="text-xs"><?= $langText['employees'] ?? 'Funcionários' ?></span>
          </a>
        </li>
        <li>
          <a href="<?= $baseUrl ?>/inventory" class="flex flex-col items-center py-2 text-gray-700">
            <!-- Heroicon: Cube -->
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0v6l-8 4-8-4V7m16 6l-8 4m0 0l-8-4" />
            </svg>
            <span class="text-xs"><?= $langText['inventory'] ?? 'Estoque' ?></span>
          </a>
        </li>
        <li>
          <a href="<?= $baseUrl ?>/calendar" class="flex flex-col items-center py-2 text-gray-700">
            <!-- Heroicon: Calendar -->
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10m-2 8H7a2 2 0 01-2-2V9a2 2 0 012-2h10a2 2 0 012 2v10a2 2 0 01-2 2z" />
            </svg>
            <span class="text-xs"><?= $langText['calendar'] ?? 'Calendário' ?></span>
          </a>
        </li>
        <li>
          <a href="<?= $baseUrl ?>/clients" class="flex flex-col items-center py-2 text-gray-700">
            <!-- Heroicon: User Group -->
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87M12 7a4 4 0 110 8 4 4 0 010-8z" />
            </svg>
            <span class="text-xs"><?= $langText['clients'] ?? 'Clientes' ?></span>
          </a>
        </li>
        <li>
          <a href="<?= $baseUrl ?>/analytics" class="flex flex-col items-center py-2 text-gray-700">
            <!-- Heroicon: Chart Bar -->
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v18h18M12 13v6m-4-4v4m8-8v8m-4-12v12" />
            </svg>
            <span class="text-xs"><?= $langText['analytics'] ?? 'Análises' ?></span>
          </a>
        </li>
      </ul>
    </nav>
  </div>

<?php endif; ?>

<script src="<?= $baseUrl ?>/js/header.js"></script>
</body>
</html>
