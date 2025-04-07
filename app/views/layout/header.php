<?php

include __DIR__ . "/partials/notification.php";

// Verifica se a sessão já está ativa para evitar múltiplos session_start()
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$baseUrl = '/ams-malergeschaft/public';

// Carrega o arquivo de tradução
require __DIR__ . '/../../lang/lang.php';

// Define o idioma
$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'pt';

$flags = [
    'pt' => ['name' => 'Português', 'flag' => $baseUrl . '/assets/flags/pt.png'],
    'en' => ['name' => 'English', 'flag' => $baseUrl . '/assets/flags/us.png'],
    'de' => ['name' => 'Deutsch', 'flag' => $baseUrl . '/assets/flags/de.png'],
    'fr' => ['name' => 'Français', 'flag' => $baseUrl . '/assets/flags/fr.png'],
];

$currentFlag = isset($flags[$lang])
    ? '<img src="' . $flags[$lang]['flag'] . '" class="w-5 h-5" alt="Bandeira">'
    : '🌎';

$isLoggedIn = isset($_SESSION['user']);
$isLoginOrRegisterPage = strpos($_SERVER['REQUEST_URI'], 'login') !== false ||
                         strpos($_SERVER['REQUEST_URI'], 'register') !== false;
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ams Malergeschäft</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="icon" href="<?= $baseUrl ?>/assets/logo/ams-malergeschaft_icon.png" type="image/png">
</head>
<body class="bg-gray-100 text-gray-900">
<?php if (!$isLoginOrRegisterPage && $isLoggedIn): ?>
  <!-- Sidebar fixa -->
  <aside class="w-56 bg-gray-900 text-white h-screen fixed left-0 top-0 p-4 flex flex-col justify-between">
      <div>
          <h1 class="text-xl font-bold mb-6"><a href="<?= $baseUrl ?>/dashboard">Ams Malergeschäft</a></h1>
          <nav>
              <ul>
                  <li class="mb-4">
                      <a href="<?= $baseUrl ?>/dashboard" class="flex items-center space-x-2 hover:text-gray-300">
                          <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                              <path d="M3 10h11M9 21V6M21 16H9M15 3h6v6"></path>
                          </svg>
                          <span><?= $langText['dashboard'] ?? 'Painel de Controle' ?></span>
                      </a>
                  </li>
                  <li class="mb-4">
                      <a href="<?= $baseUrl ?>/projects" class="flex items-center space-x-2 hover:text-gray-300">
                          <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                              <path d="M9 12h6M12 9v6M4 21h16a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"></path>
                          </svg>
                          <span><?= $langText['projects'] ?? 'Projetos' ?></span>
                      </a>
                  </li>
                  <li class="mb-4">
                      <a href="<?= $baseUrl ?>/employees" class="flex items-center space-x-2 hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M8 7a4 4 0 1 1 8 0 4 4 0 0 1-8 0zM4 21v-2a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4v2"></path>
                        </svg>
                          <span><?= $langText['employees'] ?? 'Funcionários' ?></span>
                      </a>
                  </li>
                  <li class="mb-4">
                      <a href="<?= $baseUrl ?>/clients" class="flex items-center space-x-2 hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M8 7a4 4 0 1 1 8 0 4 4 0 0 1-8 0zM4 21v-2a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4v2"></path>
                        </svg>
                          <span><?= $langText['clients'] ?? 'Clientes' ?></span>
                      </a>
                  </li>
                  <li class="mb-4">
                        <a href="<?= $baseUrl ?>/inventory" class="flex items-center space-x-2 hover:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="3" y1="9" x2="21" y2="9"></line>
                                <line x1="3" y1="15" x2="21" y2="15"></line>
                            </svg>
                            <span><?= $langText['inventory'] ?? 'Inventory' ?></span>
                        </a>
                  </li>
                  <li class="mb-4">
                    <a href="<?= $baseUrl ?>/calendar" class="flex items-center space-x-2 hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M8 7a4 4 0 1 1 8 0 4 4 0 0 1-8 0zM4 21v-2a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4v2"></path>
                        </svg>
                        <span><?= $langText['calendar'] ?? 'Calendar' ?></span>
                    </a>
                  </li>
                  <li class="mb-4">
                    <a href="<?= $baseUrl ?>/analytics" class="flex items-center space-x-2 hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span><?= $langText['analytics'] ?? 'Análises' ?></span>
                    </a>
                  </li>
                  <li class="mb-4">
                      <a href="<?= $baseUrl ?>/logout" class="flex items-center space-x-2 hover:text-red-400">
                          <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                              <path d="M15 12H3M21 16l-4-4 4-4M21 12h-8"></path>
                          </svg>
                          <span><?= $langText['logout_button'] ?? 'Sair' ?></span>
                      </a>
                  </li>
              </ul>
          </nav>
      </div>
      <div class="text-center text-xs text-gray-400">
          © UbiLabs 2025
      </div>
  </aside>

  <!-- Navbar fixa -->
  <nav class="bg-white shadow p-4 fixed top-0 left-56 right-0 z-10 flex items-center justify-between">
    <div class="flex items-center">
        <span class="text-4xl font-bold text-blue-600"><a href="<?= $baseUrl ?>/dashboard">Ams </a></span>
        <span class="ml-2 text-xl text-gray-600">Malergeschäft</span>
    </div>
      <div class="flex items-center space-x-6">
            <div class="flex items-center space-x-6">
                <a href="<?= $baseUrl ?>/projects?openModal=true" class="bg-green-500 text-white px-3 py-2 rounded hover:bg-green-600">
                    <?= $langText['new_project'] ?? 'Novo Projeto +' ?>
                </a>
            </div>
            <div class="relative">
        <!-- notificações -->
        <button id="notificationBtn" class="relative bg-transparent">
            <svg class="w-6 h-6 text-gray-600" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C8.69 2 6 4.69 6 8v5H5a1 1 0 0 0 0 2h14a1 1 0 0 0 0-2h-1V8c0-3.31-2.69-6-6-6zm-4 15a4 4 0 0 0 8 0h-8z"/>
            </svg>
            <span id="notificationDot" class="absolute top-0 right-0 bg-red-600 w-3 h-3 rounded-full animate-ping hidden"></span>
        </button>
        <div id="notificationList" class="absolute right-0 mt-2 w-80 max-h-96 overflow-y-auto bg-white text-black p-4 rounded-lg shadow-lg hidden">
            <h3 class="text-lg font-bold text-gray-800 border-b pb-2"><?= $langText['notifications'] ?? 'Notificações' ?></h3>
            <ul class="mt-2 space-y-2">
                <?php if (!empty($notifications) && is_array($notifications)): ?>
                    <?php foreach ($notifications as $notification): ?>
                        <li class="p-2 bg-gray-100 rounded-lg shadow-md flex items-center space-x-2">
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M12 22s8-4 8-10a8 8 0 10-16 0c0 6 8 10 8 10z"></path>
                            </svg>
                            <span><?= htmlspecialchars($notification) ?></span>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="text-gray-500 text-center p-2"><?= $langText['no_new_notifications'] ?? 'Sem novas notificações' ?></li>
                <?php endif; ?>
            </ul>
        </div>
        </div>
          <div class="relative group">
              <button id="language-button" class="flex items-center gap-2 bg-white text-gray-900">
                  <?= $currentFlag ?>
              </button>
              <div id="language-menu" class="absolute right-0 mt-2 w-40 bg-white rounded-md shadow-lg border border-gray-300 hidden">
                  <?php foreach ($flags as $code => $flag): ?>
                      <a href="?lang=<?= htmlspecialchars($code) ?>" class="flex px-2 py-1 text-gray-800 hover:bg-gray-100">
                          <img src="<?= htmlspecialchars($flag['flag']) ?>" class="w-5 h-5 mr-2" alt="<?= htmlspecialchars($flag['name']) ?>">
                          <span class="font-medium"><?= htmlspecialchars($flag['name']) ?></span>
                      </a>
                  <?php endforeach; ?>
              </div>
          </div>
  </nav>
<?php endif; ?>

<script src="<?= $baseUrl ?>/js/header.js"></script>