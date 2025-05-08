<?php
// app/views/layout/header.php

// 1) Inicia sessão se ainda não estiver ativa
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// 2) Marcar notificação como lida e redirecionar
if (isset($_GET['mark_read'], $_GET['redirect'])) {
    if (empty($_SESSION['read_notifications']) || ! is_array($_SESSION['read_notifications'])) {
        $_SESSION['read_notifications'] = [];
    }
    $_SESSION['read_notifications'] = array_unique(array_merge(
        $_SESSION['read_notifications'],
        [ $_GET['mark_read'] ]
    ));
    header('Location: ' . $_GET['redirect']);
    exit;
}

// 3) Carrega notificações não-lidas
$notifications = include __DIR__ . '/partials/notification.php';

// 4) Configurações gerais
$baseUrl = '/ams-malergeschaft/public';
require __DIR__ . '/../../lang/lang.php';

// 5) Idioma
$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'pt';
$_SESSION['lang'] = $lang;

// 6) Bandeiras de idioma
$flags = [
    'pt'=>['name'=>'Português','flag'=>"$baseUrl/assets/flags/pt.png"],
    'en'=>['name'=>'English',  'flag'=>"$baseUrl/assets/flags/us.png"],
    'de'=>['name'=>'Deutsch',  'flag'=>"$baseUrl/assets/flags/de.png"],
    'fr'=>['name'=>'Français', 'flag'=>"$baseUrl/assets/flags/fr.png"],
];
$currentFlag = isset($flags[$lang])
    ? '<img src="'.htmlspecialchars($flags[$lang]['flag']).'" class="w-5 h-5" alt="'.htmlspecialchars($flags[$lang]['name']).'">'
    : '🌎';

// 7) Controle de exibição
$isLoginPage = str_contains($_SERVER['REQUEST_URI'], 'login')
            || str_contains($_SERVER['REQUEST_URI'], 'register');
$isLoggedIn = isset($_SESSION['user']);
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
  <title>Ams Malergeschäft</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="icon" href="<?= $baseUrl ?>/assets/logo/ams-malergeschaft_icon.png" type="image/png">
  <style>
    body { margin:0; padding:0; }
    #sidebar { transform: translateX(-100%); transition: transform .2s; }
    @media(min-width:768px) { #sidebar { transform: translateX(0); } }
    .sidebar-open { transform: translateX(0) !important; }
    .dropdown-menu {
      position:absolute; right:0; margin-top:.5rem; width:200px;
      background:#fff; border:1px solid #d1d5db; border-radius:.375rem;
      box-shadow:0 2px 8px rgba(0,0,0,0.1); z-index:50;
    }
    .bottom-nav { box-shadow:0 -1px 5px rgba(0,0,0,0.1); border-top:1px solid #e5e7eb; }
  </style>
</head>
<body class="bg-gray-100 text-gray-900 relative">

<?php if (! $isLoginPage && $isLoggedIn): ?>

  <!-- Overlay para Mobile Menu -->
  <div id="contentOverlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-40"></div>

  <!-- ========== DESKTOP & TABLET ========== -->
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
              <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M3 10h11M9 21V6M21 16H9M15 3h6v6"/>
              </svg>
              <span><?= $langText['dashboard'] ?? 'Painel de Controle' ?></span>
            </a>
          </li>
          <li class="mb-4">
            <a href="<?= $baseUrl ?>/projects" class="flex items-center space-x-2 hover:text-gray-300">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M9 12h6M12 9v6M4 21h16a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H4v12Z"/>
              </svg>
              <span><?= $langText['projects'] ?? 'Projetos' ?></span>
            </a>
          </li>
          <li class="mb-4">
            <a href="<?= $baseUrl ?>/employees" class="flex items-center space-x-2 hover:text-gray-300">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M8 7a4 4 0 1 1 8 0M4 21v-2a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4v2"/>
              </svg>
              <span><?= $langText['employees'] ?? 'Funcionários' ?></span>
            </a>
          </li>
          <li class="mb-4">
            <a href="<?= $baseUrl ?>/inventory" class="flex items-center space-x-2 hover:text-gray-300">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/>
              </svg>
              <span><?= $langText['inventory'] ?? 'Estoque' ?></span>
            </a>
          </li>
          <li class="mb-4">
            <a href="<?= $baseUrl ?>/calendar" class="flex items-center space-x-2 hover:text-gray-300">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
              </svg>
              <span><?= $langText['calendar'] ?? 'Calendário' ?></span>
            </a>
          </li>
          <li class="mb-4">
            <a href="<?= $baseUrl ?>/clients" class="flex items-center space-x-2 hover:text-gray-300">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M8 7a4 4 0 1 1 8 0M4 21v-2a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4v2"/>
              </svg>
              <span><?= $langText['clients'] ?? 'Clientes' ?></span>
            </a>
          </li>
          <li class="mb-4">
            <a href="<?= $baseUrl ?>/analytics" class="flex items-center space-x-2 hover:text-gray-300">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M9 19v-6a2 2 0 0 0-2-2H5v6a2 2 0 0 0 2 2h2m2 0
                         v-10h2v10m2 0v-4h2v4"/>
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

        <!-- Notificações -->
        <div class="relative">
          <button id="notificationBtn" class="relative bg-transparent focus:outline-none">
            <svg class="w-8 h-8 text-gray-600" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 2a6 6 0 0 0-6 6v5H5a1 1 0 0 0 0 2h14a1 1 0 0 0 0-2h-1V8a6 6 0 0 0-6-6zM8 20a4 4 0 0 0 8 0H8z"/>
            </svg>
            <?php if ($notifications): ?>
              <span id="notificationDot" class="absolute top-0 right-0 bg-red-600 text-white w-4 h-4 rounded-full flex items-center justify-center text-[10px]">
                <?= count($notifications) ?>
              </span>
            <?php endif; ?>
          </button>
          <div id="notificationList" class="dropdown-menu hidden">
            <h3 class="text-lg font-bold text-gray-800 border-b px-2 py-1"><?= $langText['notifications'] ?? 'Notificações' ?></h3>
            <ul class="mt-1">
              <?php foreach ($notifications as $n): ?>
                <li class="px-2 py-1 border-b last:border-b-0 text-sm text-gray-700">
                  <a href="?mark_read=<?= $n['key'] ?>&redirect=<?= urlencode($n['url']) ?>" class="block hover:bg-gray-100">
                    <?= htmlspecialchars($n['text']) ?>
                  </a>
                </li>
              <?php endforeach; ?>
              <?php if (empty($notifications)): ?>
                <li class="px-2 py-1 text-center text-sm text-gray-500"><?= $langText['no_new_notifications'] ?? 'Sem novas notificações' ?></li>
              <?php endif; ?>
            </ul>
          </div>
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

  <!-- ========== MOBILE ========== -->
  <div class="md:hidden">

    <!-- Top Navbar Mobile -->
    <nav class="fixed top-0 left-0 right-0 bg-white h-14 flex items-center justify-between px-4 shadow z-50">
      <button id="mobileMenuButton" class="focus:outline-none">
        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
      <div class="flex items-center space-x-2">
        <span class="text-lg font-bold text-blue-600">Ams</span>
        <span class="text-base text-gray-600">Malergeschäft</span>
      </div>
      <div class="flex items-center space-x-4">
        <!-- Notificações Mobile (igual ao desktop) -->
        <div class="relative">
          <button class="relative bg-transparent focus:outline-none">
            <svg class="w-8 h-8 text-gray-600" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 2a6 6 0 0 0-6 6v5H5a1 1 0 0 0 0 2h14a1 1 0 0 0 0-2h-1V8a6 6 0 0 0-6-6zM8 20a4 4 0 0 0 8 0H8z"/>
            </svg>
            <?php if ($notifications): ?>
              <span class="absolute top-0 right-0 bg-red-600 text-white w-4 h-4 rounded-full flex items-center justify-center text-[10px]">
                <?= count($notifications) ?>
              </span>
            <?php endif; ?>
          </button>
          <div class="dropdown-menu hidden">
            <h3 class="text-sm font-bold text-gray-800 border-b px-2 py-1"><?= $langText['notifications'] ?? 'Notificações' ?></h3>
            <ul class="mt-1">
              <?php foreach ($notifications as $n): ?>
                <li class="px-2 py-1 border-b last:border-b-0 text-xs text-gray-700">
                  <a href="?mark_read=<?= $n['key'] ?>&redirect=<?= urlencode($n['url']) ?>" class="block hover:bg-gray-100">
                    <?= htmlspecialchars($n['text']) ?>
                  </a>
                </li>
              <?php endforeach; ?>
              <?php if (empty($notifications)): ?>
                <li class="px-2 py-1 text-center text-xs text-gray-500"><?= $langText['no_new_notifications'] ?? 'Sem novas notificações' ?></li>
              <?php endif; ?>
            </ul>
          </div>
        </div>
        <!-- Idioma Mobile -->
        <div class="relative">
          <button id="language-button" class="flex items-center gap-1 focus:outline-none"><?= $currentFlag ?></button>
          <div id="language-menu" class="dropdown-menu hidden">
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
        <li><a href="<?= $baseUrl ?>/dashboard" class="flex flex-col items-center py-2 text-gray-700">
          <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M3 10h11M9 21V6M21 16H9M15 3h6v6"/></svg>
          <span class="text-xs"><?= $langText['dashboard'] ?? 'Painel' ?></span>
        </a></li>
        <li><a href="<?= $baseUrl ?>/projects" class="flex flex-col items-center py-2 text-gray-700">
          <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M9 12h6M12 9v6M4 21h16a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H4v12Z"/></svg>
          <span class="text-xs"><?= $langText['projects'] ?? 'Projetos' ?></span>
        </a></li>
        <li><a href="<?= $baseUrl ?>/employees" class="flex flex-col items-center py-2 text-gray-700">
          <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M8 7a4 4 0 1 1 8 0M4 21v-2a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4v2"/></svg>
          <span class="text-xs"><?= $langText['employees'] ?? 'Funcionários' ?></span>
        </a></li>
        <li><a href="<?= $baseUrl ?>/inventory" class="flex flex-col items-center py-2 text-gray-700">
          <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/></svg>
          <span class="text-xs"><?= $langText['inventory'] ?? 'Estoque' ?></span>
        </a></li>
        <li><a href="<?= $baseUrl ?>/calendar" class="flex flex-col items-center py-2 text-gray-700">
          <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          <span class="text-xs"><?= $langText['calendar'] ?? 'Calendário' ?></span>
        </a></li>
        <li><a href="<?= $baseUrl ?>/clients" class="flex flex-col items-center py-2 text-gray-700">
          <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 7a4 4 0 1 1 8 0M4 21v-2a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4v2"/></svg>
          <span class="text-xs"><?= $langText['clients'] ?? 'Clientes' ?></span>
        </a></li>
        <li><a href="<?= $baseUrl ?>/analytics" class="flex flex-col items-center py-2 text-gray-700">
          <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 0 0-2-2H5v6a2 2 0 0 0 2 2h2m2 0v-10h2v10m2 0v-4h2v4"/></svg>
          <span class="text-xs"><?= $langText['analytics'] ?? 'Análises' ?></span>
        </a></li>
      </ul>
    </nav>
  </div>

<?php endif; ?>

<script src="<?= $baseUrl ?>/js/header.js"></script>
</body>
</html>
