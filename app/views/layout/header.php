<?php
// app/views/layout/header.php

// 1) Garantir sessão e helpers
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../helpers.php';

// 2) Employee sempre usa header_employee e não carrega o resto
if (isEmployee()) {
    include __DIR__ . '/header_employee.php';
    return;
}

// 3) Finance sempre usa header_finance
if (isFinance()) {
    include __DIR__ . '/header_finance.php';
    return;
}

// 4) Chegou aqui? É Admin → renderiza o header “completo” abaixo

// Idioma
$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'pt';
$_SESSION['lang'] = $lang;
$langFile = __DIR__ . '/../../lang/' . $lang . '.php';
$langText = file_exists($langFile) ? require $langFile : [];

// Notificações e flags
$notifications     = include __DIR__ . '/partials/notification.php';
$notificationCount = count($notifications);

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

$isLoginPage = strpos($_SERVER['REQUEST_URI'], 'login')    !== false
            || strpos($_SERVER['REQUEST_URI'], 'register') !== false;
$isLoggedIn  = isLoggedIn();
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang, ENT_QUOTES) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?php echo htmlspecialchars($langText['title'] ?? 'Ams Malergeschäft', ENT_QUOTES) ?></title>

  <link rel="stylesheet"
        href="<?php echo htmlspecialchars(asset('css/tailwind.css'), ENT_QUOTES) ?>"
        media="all">

  <link rel="icon"
        href="<?php echo htmlspecialchars(asset('assets/logo/ams-malergeschaft_icon.png'), ENT_QUOTES) ?>">

  <script src="https://cdn.tailwindcss.com"></script>

  <script>
    window.baseUrl = <?php echo json_encode(BASE_URL, JSON_UNESCAPED_SLASHES) ?>;
  </script>
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
        <a href="<?php echo url('dashboard') ?>">Ams</a>
        <span class="text-gray-400 text-sm"><?= htmlspecialchars($langText['admin '] ?? 'Administrador', ENT_QUOTES) ?></span>

      </h1>
      <nav>
        <ul>
          <!-- Dashboard sempre -->
          <li class="mb-4">
            <a href="<?php echo url('dashboard') ?>" class="flex items-center space-x-2 hover:text-gray-300">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10.707 1.707a1 1 0 00-1.414 0l-7 7A1 1 0 003 10h1v7a1 1 0 001 1h4v-5h2v5h4a1 1 0 001-1v-7h1a1 1 0 00.707-1.707l-7-7z"/>
              </svg>
              <span><?php echo htmlspecialchars($langText['dashboard'] ?? 'Painel de Controle', ENT_QUOTES) ?></span>
            </a>
          </li>

          <!-- Calendário para todos os níveis -->
          <li class="mb-4">
            <a href="<?php echo url('calendar') ?>" class="flex items-center space-x-2 hover:text-gray-300">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M8 7V3m8 4V3m-9 8h10m-12 5h14a2 2 0 002-2V8a2 2 0 00-2-2H4a2 2 0 00-2 2v8a2 2 0 002 2z"/>
              </svg>
              <span><?php echo htmlspecialchars($langText['calendar'] ?? 'Calendário', ENT_QUOTES) ?></span>
            </a>
          </li>

          <!-- Finance e Analytics para Finance/Admin -->
          <?php if (isFinance() || isAdmin()): ?>
            <li class="mb-4">
              <a href="<?php echo url('finance') ?>" class="flex items-center space-x-2 hover:text-gray-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 1v2m0 18v2m4-11H8m3-4c-1.657 0-3 1.343-3 3s1.343 3 3 3 3 1.343 3 3-1.343 3-3 3"/>
                </svg>
                <span><?php echo htmlspecialchars($langText['finance']   ?? 'Financeiro', ENT_QUOTES) ?></span>
              </a>
            </li>
            <li class="mb-4">
              <a href="<?php echo url('analytics') ?>" class="flex items-center space-x-2 hover:text-gray-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 3v18h18M12 13v6m-4-4v4m8-8v8m-4-12v12"/>
                </svg>
                <span><?php echo htmlspecialchars($langText['analytics'] ?? 'Análises', ENT_QUOTES) ?></span>
              </a>
            </li>
            <li class="mb-4">
              <a href="<?php echo url('employees') ?>" class="flex items-center space-x-2 hover:text-gray-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87M16 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <span><?= $langText['employees'] ?? 'Funcionários' ?></span>
              </a>
            </li>
          <?php endif; ?>

          <!-- Projetos e Clientes para Admin -->
          <?php if (isAdmin()): ?>
            <li class="mb-4">
              <a href="<?php echo url('projects') ?>" class="flex items-center space-x-2 hover:text-gray-300">
                <svg xmlns="http://www.w3.org/2000/svg"
                     class="w-6 h-6"
                     fill="none"
                     viewBox="0 0 24 24"
                     stroke="currentColor"
                     stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 7a2 2 0 012-2h4l2 2h8
                          a2 2 0 012 2v8a2 2 0 01-2 2H5
                          a2 2 0 01-2-2V7z"/>
                </svg>
                <span><?php echo htmlspecialchars($langText['projects'] ?? 'Projetos', ENT_QUOTES) ?></span>
              </a>
            </li>
            <li class="mb-4">
              <a href="<?php echo url('clients') ?>" class="flex items-center space-x-2 hover:text-gray-300">
                <svg xmlns="http://www.w3.org/2000/svg"
                     class="w-6 h-6"
                     fill="none"
                     viewBox="0 0 24 24"
                     stroke="currentColor"
                     stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round"
                        d="M5.121 17.804A8.963 8.963 0 0112 15
                          c2.366 0 4.533.924 6.121 2.804"/>
                  <path stroke-linecap="round" stroke-linejoin="round"
                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span><?php echo htmlspecialchars($langText['clients'] ?? 'Clientes', ENT_QUOTES) ?></span>
              </a>
            </li>
          <?php endif; ?>

          <!-- Estoque e Carros para Employee, Finance e Admin -->
          <?php if (isEmployee() || isFinance() || isAdmin()): ?>
            <li class="mb-4">
              <a href="<?php echo url('inventory') ?>" class="flex items-center space-x-2 hover:text-gray-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20 7l-8-4-8 4m16 0v6l-8 4-8-4V7m16 6l-8 4m0 0l-8-4"/>
                </svg>
                <span><?php echo htmlspecialchars($langText['inventory'] ?? 'Estoque', ENT_QUOTES) ?></span>
              </a>
            </li>
            <li class="mb-4">
              <a href="<?php echo url('cars') ?>" class="flex items-center space-x-2 hover:text-gray-300">
                <svg xmlns="http://www.w3.org/2000/svg"
                     class="w-6 h-6"
                     fill="none"
                     viewBox="0 0 24 24"
                     stroke="currentColor"
                     stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 13l1-3h16l1 3M5 17h2a1 1 0 001 1h8a1 1 0 001-1h2M7 17v2m10-2v2"/>
                </svg>
                <span><?php echo htmlspecialchars($langText['car'] ?? 'Carros', ENT_QUOTES) ?></span>
              </a>
            </li>
          <?php endif; ?>

          <!-- Logout -->
          <li class="mt-4">
            <a href="<?php echo url('logout') ?>"
               class="flex items-center space-x-2 text-white hover:text-gray-300">
              <svg xmlns="http://www.w3.org/2000/svg"
                   class="w-6 h-6"
                   fill="none"
                   viewBox="0 0 24 24"
                   stroke="currentColor"
                   stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M14 3H5a2 2 0 00-2 2v14a2 2 0 002 2h9"/>
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M7 12h14m0 0l-4-4m4 4l-4 4"/>
              </svg>
              <span><?php echo htmlspecialchars($langText['logout_button'] ?? 'Sair', ENT_QUOTES) ?></span>
            </a>
          </li>
        </ul>
      </nav>
    </aside>

    <!-- Topbar -->
    <nav class="fixed top-0 left-56 right-0 bg-white shadow p-4 flex items-center justify-between z-30">
      <div class="flex items-center space-x-2">
        <a href="<?php echo url('dashboard') ?>" class="text-3xl font-bold text-blue-600">Ams</a>
        <span class="text-xl text-gray-600">Malergeschäft</span>
      </div>
      <div class="flex items-center space-x-4">
        <a href="<?php echo url('projects?openModal=true') ?>"
           class="bg-green-500 text-white px-3 py-2 rounded hover:bg-green-600">
          <?php echo htmlspecialchars($langText['new_project'] ?? 'Novo Projeto +', ENT_QUOTES) ?>
        </a>
        <div class="relative">
          <button id="notificationBtn" class="relative p-2 focus:outline-none">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159
                       c0 .538-.214 1.055-.595 1.436L4 17h5m6 0a3 3 0 11-6 0h6z"/>
            </svg>
            <?php if ($notificationCount): ?>
              <span id="notificationCount"
                    class="absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                <?= $notificationCount ?>
              </span>
            <?php endif; ?>
          </button>
          <ul id="notificationList"
              class="dropdown-menu hidden absolute right-0 mt-2 w-64 bg-white shadow-lg max-h-80 overflow-auto z-50">
            <?php foreach ($notifications as $n): ?>
              <li class="px-4 py-2 hover:bg-gray-100 notification-item" data-key="<?php echo htmlspecialchars($n['key'] ?? '', ENT_QUOTES) ?>">
                <a href="<?php echo htmlspecialchars($n['url'], ENT_QUOTES) ?>">
                  <?php echo htmlspecialchars($n['text'], ENT_QUOTES) ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>

        <!-- Language Switcher -->
        <div class="relative">
          <button id="language-button" class="flex items-center gap-1 focus:outline-none">
            <?= $currentFlag ?>
          </button>
          <div id="language-menu"
               class="hidden absolute top-full right-0 mt-2 w-32 bg-white rounded shadow-lg divide-y divide-gray-100 overflow-auto max-h-60 z-50">
            <?php foreach ($flags as $code => $f): ?>
              <a href="?lang=<?php echo $code ?>"
                 class="flex items-center px-3 py-2 hover:bg-gray-100">
                <img src="<?php echo htmlspecialchars($f['flag'], ENT_QUOTES) ?>"
                     class="w-5 h-5 mr-2"
                     alt="<?php echo htmlspecialchars($f['name'], ENT_QUOTES) ?>">
                <span class="text-sm"><?php echo htmlspecialchars($f['name'], ENT_QUOTES) ?></span>
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
      <div class="flex items-center space-x-2">
        <span class="text-lg font-bold text-blue-600">Ams</span>
        <span class="text-base text-gray-600">Malergeschäft</span>
      </div>
      <div class="flex items-center space-x-4">
        <div class="relative">
          <button id="notificationBtnMobile" class="relative p-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11
                       a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5
                       m6 0a3 3 0 11-6 0h6z"/>
            </svg>
            <?php if ($notificationCount): ?>
              <span id="notificationCountMobile"
                    class="absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                <?= $notificationCount ?>
              </span>
            <?php endif; ?>
          </button>
          <ul id="notificationListMobile"
              class="dropdown-menu hidden absolute right-0 mt-2 w-64 bg-white shadow-lg max-h-80 overflow-auto z-50">
            <?php foreach ($notifications as $n): ?>
              <li class="px-4 py-2 hover:bg-gray-100 notification-item" data-key="<?php echo htmlspecialchars($n['key'] ?? '', ENT_QUOTES) ?>">
                <a href="<?php echo htmlspecialchars($n['url'], ENT_QUOTES) ?>">
                  <?php echo htmlspecialchars($n['text'], ENT_QUOTES) ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
        <div class="relative">
          <button id="language-button-mobile" class="flex items-center gap-1 focus:outline-none">
            <?= $currentFlag ?>
          </button>
          <div id="language-menu-mobile" class="dropdown-menu hidden absolute top-full right-0 mt-2 w-32 bg-white rounded shadow-lg divide-y divide-gray-100 overflow-auto max-h-60 z-50">
            <?php foreach ($flags as $code => $f): ?>
              <a href="?lang=<?php echo $code ?>" class="flex items-center px-3 py-2 hover:bg-gray-100">
                <img src="<?php echo htmlspecialchars($f['flag'], ENT_QUOTES) ?>"
                     class="w-5 h-5 mr-2"
                     alt="<?php echo htmlspecialchars($f['name'], ENT_QUOTES) ?>">
                <span class="text-sm"><?php echo htmlspecialchars($f['name'], ENT_QUOTES) ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </nav>

    <!-- Bottom Navbar Mobile -->
    <nav class="fixed bottom-0 left-0 right-0 bg-white bottom-nav z-50 overflow-x-auto">
      <ul class="flex space-x-6 py-2 px-4 whitespace-nowrap">
        <li class="inline-flex flex-col items-center justify-center flex-shrink-0">
          <a href="<?php echo url('dashboard') ?>" class="flex flex-col items-center text-gray-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 12l2-2m0 0l7-7 7 7m-9 2v8m4-8v8m5-2h-2a4 4 0 01-4-4V9"/>
            </svg>
            <span class="text-xs"><?php echo htmlspecialchars($langText['dashboard'] ?? 'Painel', ENT_QUOTES) ?></span>
          </a>
        </li>
        <li class="inline-flex flex-col items-center justify-center flex-shrink-0">
          <a href="<?php echo url('calendar') ?>" class="flex flex-col items-center text-gray-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 7V3m8 4V3m-9 8h10m-12 5h14a2 2 0 002-2V8a2 2 0 00-2-2H4a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
            <span class="text-xs"><?php echo htmlspecialchars($langText['calendar'] ?? 'Calendário', ENT_QUOTES) ?></span>
          </a>
        </li>
        <?php if (isFinance() || isAdmin()): ?>
          <li class="inline-flex flex-col items-center justify-center flex-shrink-0">
            <a href="<?php echo url('finance') ?>" class="flex flex-col items-center text-gray-700">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 1v2m0 18v2m4-11H8m3-4c-1.657 0-3 1.343-3 3s1.343 3 3 3 3 1.343 3 3-1.343 3-3 3"/>
              </svg>
              <span class="text-xs"><?php echo htmlspecialchars($langText['finance'] ?? 'Financeiro', ENT_QUOTES) ?></span>
            </a>
          </li>
          <li class="inline-flex flex-col items-center justify-center flex-shrink-0">
            <a href="<?php echo url('analytics') ?>" class="flex flex-col items-center text-gray-700">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 3v18h18M12 13v6m-4-4v4m8-8v8m-4-12v12"/>
              </svg>
              <span class="text-xs"><?php echo htmlspecialchars($langText['analytics'] ?? 'Análises', ENT_QUOTES) ?></span>
            </a>
          </li>
             <li class="inline-flex flex-col items-center justify-center flex-shrink-0">
              <a href="<?php echo url('employees') ?>" class="flex flex-col items-center hover:text-gray-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87M16 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <span class="text-xs"><?= $langText['employees'] ?? 'Funcionários' ?></span>
              </a>
            </li>
        <?php endif; ?>
        <?php if (isEmployee() || isFinance() || isAdmin()): ?>
          <li class="inline-flex flex-col items-center justify-center flex-shrink-0">
            <a href="<?php echo url('inventory') ?>" class="flex flex-col items-center text-gray-700">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M20 7l-8-4-8 4m16 0v6l-8 4-8-4V7m16 6l-8 4m0 0l-8-4"/>
              </svg>
              <span class="text-xs"><?php echo htmlspecialchars($langText['inventory'] ?? 'Estoque', ENT_QUOTES) ?></span>
            </a>
          </li>
          <li class="inline-flex flex-col items-center justify-center flex-shrink-0">
            <a href="<?php echo url('cars') ?>" class="flex flex-col items-center text-gray-700">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 13l1-3h16l1 3M5 17h2a1 1 0 001 1h8a1 1 0 001-1h2M7 17v2m10-2v2"/>
              </svg>
              <span class="text-xs"><?php echo htmlspecialchars($langText['cars'] ?? 'Carros', ENT_QUOTES) ?></span>
            </a>
          </li>
        <?php endif; ?>
        <?php if (isAdmin()): ?>
          <li class="inline-flex flex-col items-center justify-center flex-shrink-0">
            <a href="<?php echo url('projects') ?>" class="flex flex-col items-center text-gray-700">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M8 7V5a2 2 0 012-2h4a2 2 0 012 2v2m-8 0h8m-9 4h10m-10 4h10m-9 4h8a2 2 0 002-2v-2H5v2a2 2 0 002 2z"/>
              </svg>
              <span class="text-xs"><?php echo htmlspecialchars($langText['projects'] ?? 'Projetos', ENT_QUOTES) ?></span>
            </a>
          </li>
          <li class="inline-flex flex-col items-center justify-center flex-shrink-0">
            <a href="<?php echo url('clients') ?>" class="flex flex-col items-center text-gray-700">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M5.121 17.804A8.963 8.963 0 0112 15c2.366 0 4.533.924 6.121 2.804"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
              </svg>
              <span class="text-xs"><?php echo htmlspecialchars($langText['clients'] ?? 'Clientes', ENT_QUOTES) ?></span>
            </a>
          </li>
          <li class="inline-flex flex-col items-center justify-center flex-shrink-0">
            <a href="<?php echo url('logout') ?>" class="flex flex-col items-center text-gray-700 hover:text-gray-900">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M14 3H5a2 2 0 00-2 2v14a2 2 0 002 2h9"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M7 12h14m0 0l-4-4m4 4l-4 4"/>
              </svg>
              <span class="text-xs"><?php echo htmlspecialchars($langText['logout_button'] ?? 'Sair', ENT_QUOTES) ?></span>
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </nav>
  </div>

<?php endif; ?>
