<?php
// include __DIR__ . "/partials/notification.php";

// Inicia a sessão caso não esteja ativa
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
$isLoginOrRegisterPage = strpos($_SERVER['REQUEST_URI'], 'login') !== false || strpos($_SERVER['REQUEST_URI'], 'register') !== false;
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
  <meta charset="UTF-8">
  <!-- Meta viewport para garantir experiência uniforme no mobile -->
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Ams Malergeschäft</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="icon" href="<?= $baseUrl ?>/assets/logo/ams-malergeschaft_icon.png" type="image/png">
  <style>
    /* Estilos comuns */
    body {
      margin: 0;
      padding: 0;
    }
    /* Espaçamento para conteúdo não ser encoberto pelos elementos fixos */
    .content-wrapper {
      padding-top: 60px;
      padding-bottom: 70px;
    }
    /* Estilo da barra inferior para mobile */
    .bottom-nav {
      box-shadow: 0 -1px 5px rgba(0,0,0,0.1);
      border-top: 1px solid #e5e7eb;
    }
    /* Dropdown comum para idioma e notificações */
    .dropdown-menu {
      position: absolute;
      right: 0;
      margin-top: 0.5rem;
      width: 160px;
      background: #fff;
      border: 1px solid #d1d5db;
      border-radius: 0.375rem;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      z-index: 50;
    }
  </style>
</head>
<body class="bg-gray-100 text-gray-900 relative">
<?php if (!$isLoginOrRegisterPage && $isLoggedIn): ?>

  <!-- ================= Desktop Layout ================= -->
  <div class="hidden md:block">
    <!-- Sidebar fixa -->
    <aside id="sidebar" class="w-56 bg-gray-900 text-white h-screen fixed left-0 top-0 p-4 flex flex-col justify-between z-50">
        <div>
            <h1 class="text-2xl font-bold mb-6"><a href="<?= $baseUrl ?>/dashboard">Ams Malergeschäft</a></h1>
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
                        <a href="<?= $baseUrl ?>/inventory" class="flex items-center space-x-2 hover:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="3" y1="9" x2="21" y2="9"></line>
                                <line x1="3" y1="15" x2="21" y2="15"></line>
                            </svg>
                            <span><?= $langText['inventory'] ?? 'Estoque' ?></span>
                        </a>
                    </li>
                    <li class="mb-4">
                        <a href="<?= $baseUrl ?>/calendar" class="flex items-center space-x-2 hover:text-gray-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                            <span><?= $langText['calendar'] ?? 'Calendário' ?></span>
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
                        <a href="<?= $baseUrl ?>/analytics" class="flex items-center space-x-2 hover:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <span><?= $langText['analytics'] ?? 'Análises' ?></span>
                        </a>
                    </li>
                    <li class="mb-4">
                      <a href="<?= $baseUrl ?>/finance" class="flex items-center space-x-2 hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                          <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                          <line x1="1" y1="10" x2="23" y2="10"></line>
                        </svg>
                        <span><?= $langText['finance'] ?? 'Financeiro' ?></span>
                      </a>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    <!-- Navbar Superior (Desktop) -->
    <nav class="top-navbar bg-white shadow p-4 fixed top-0 left-56 right-0 z-10 flex items-center justify-between">
      <div class="flex items-center">
          <span class="text-3xl font-bold text-blue-600"><a href="<?= $baseUrl ?>/dashboard">Ams </a></span>
          <span class="ml-1 text-xl text-gray-600">Malergeschäft</span>
      </div>
      <div class="flex items-center space-x-4">
          <a href="<?= $baseUrl ?>/projects?openModal=true" class="bg-green-500 text-white px-3 py-2 rounded hover:bg-green-600">
              <?= $langText['new_project'] ?? 'Novo Projeto +' ?>
          </a>
          <!-- Botão de Notificações -->
          <div class="relative">
              <button id="notificationBtn" class="relative bg-transparent">
                  <svg class="w-6 h-6 text-gray-600" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M12 2C8.69 2 6 4.69 6 8v5H5a1 1 0 0 0 0 2h14a1 1 0 0 0 0-2h-1V8c0-3.31-2.69-6-6-6zm-4 15a4 4 0 0 0 8 0h-8z"/>
                  </svg>
                  <span id="notificationDot" class="absolute top-0 right-0 bg-red-600 w-3 h-3 rounded-full animate-ping hidden"></span>
              </button>
              <div id="notificationList" class="dropdown-menu hidden">
                  <h3 class="text-lg font-bold text-gray-800 border-b px-2 py-1"><?= $langText['notifications'] ?? 'Notificações' ?></h3>
                  <ul class="mt-1">
                      <?php if (!empty($notifications) && is_array($notifications)): ?>
                          <?php foreach ($notifications as $notification): ?>
                              <li class="px-2 py-1 border-b last:border-b-0 text-sm text-gray-700">
                                  <?= htmlspecialchars($notification) ?>
                              </li>
                          <?php endforeach; ?>
                      <?php else: ?>
                          <li class="px-2 py-1 text-center text-sm text-gray-500"><?= $langText['no_new_notifications'] ?? 'Sem novas notificações' ?></li>
                      <?php endif; ?>
                  </ul>
              </div>
          </div>
          <!-- Dropdown de Idioma -->
          <div class="relative">
              <button id="language-button" class="flex items-center gap-1 bg-transparent">
                  <?= $currentFlag ?>
              </button>
              <div id="language-menu" class="dropdown-menu hidden">
                  <?php foreach ($flags as $code => $flag): ?>
                      <a href="?lang=<?= htmlspecialchars($code) ?>" class="flex items-center px-2 py-1 text-gray-800 hover:bg-gray-100">
                          <img src="<?= htmlspecialchars($flag['flag']) ?>" class="w-5 h-5 mr-2" alt="<?= htmlspecialchars($flag['name']) ?>">
                          <span class="text-sm"><?= htmlspecialchars($flag['name']) ?></span>
                      </a>
                  <?php endforeach; ?>
              </div>
          </div>
      </div>
    </nav>

  </div>
  <!-- ================ Fim Desktop Layout ================ -->

  <!-- ================= Mobile Layout ================= -->
  <div class="block md:hidden">
    <!-- Navbar Superior Mobile -->
    <nav class="bg-white fixed top-0 left-0 right-0 h-14 flex items-center justify-between px-4 z-50 shadow">
      <div class="flex items-center space-x-2">
        <span class="text-lg font-bold text-blue-600">Ams</span>
        <span class="text-base text-gray-600">Malergeschäft</span>
      </div>
      <div class="flex items-center space-x-4">
        <!-- Botão de Notificações Mobile -->
        <div class="relative">
          <button id="notificationBtn" class="relative bg-transparent focus:outline-none">
            <svg class="w-6 h-6 text-gray-600" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 2C8.69 2 6 4.69 6 8v5H5a1 1 0 0 0 0 2h14a1 1 0 0 0 0-2h-1V8c0-3.31-2.69-6-6-6zm-4 15a4 4 0 0 0 8 0h-8z"/>
            </svg>
            <span id="notificationDot" class="absolute top-0 right-0 bg-red-600 w-3 h-3 rounded-full animate-ping hidden"></span>
          </button>
          <div id="notificationList" class="dropdown-menu hidden">
            <h3 class="text-sm font-bold text-gray-800 border-b px-2 py-1"><?= $langText['notifications'] ?? 'Notificações' ?></h3>
            <ul class="mt-1">
              <?php if (!empty($notifications) && is_array($notifications)): ?>
                <?php foreach ($notifications as $notification): ?>
                  <li class="px-2 py-1 border-b last:border-b-0 text-xs text-gray-700">
                    <?= htmlspecialchars($notification) ?>
                  </li>
                <?php endforeach; ?>
              <?php else: ?>
                <li class="px-2 py-1 text-center text-xs text-gray-500"><?= $langText['no_new_notifications'] ?? 'Sem novas notificações' ?></li>
              <?php endif; ?>
            </ul>
          </div>
        </div>
        <!-- Dropdown de Idioma Mobile -->
        <div class="relative">
          <button id="language-button" class="flex items-center gap-1 focus:outline-none">
            <?= $currentFlag ?>
          </button>
          <div id="language-menu" class="dropdown-menu hidden">
            <?php foreach ($flags as $code => $flag): ?>
              <a href="?lang=<?= htmlspecialchars($code) ?>" class="flex items-center px-2 py-1 text-gray-800 hover:bg-gray-100">
                <img src="<?= htmlspecialchars($flag['flag']) ?>" class="w-5 h-5 mr-2" alt="<?= htmlspecialchars($flag['name']) ?>">
                <span class="text-xs"><?= htmlspecialchars($flag['name']) ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </nav>

    <!-- Barra de Navegação Inferior Mobile -->
    <nav class="fixed bottom-0 left-0 right-0 bg-white bottom-nav z-50">
      <ul class="flex justify-around overflow-x-auto">
        <!-- Cada item abaixo representa uma página; acrescente ou modifique conforme necessário -->
        <li>
          <a href="<?= $baseUrl ?>/dashboard" class="flex flex-col items-center py-2 text-gray-700">
            <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M3 10h11M9 21V6M21 16H9M15 3h6v6"></path>
            </svg>
          </a>
        </li>
        <li>
          <a href="<?= $baseUrl ?>/projects" class="flex flex-col items-center py-2 text-gray-700">
            <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M9 12h6M12 9v6M4 21h16a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"></path>
            </svg>
          </a>
        </li>
        <li class="mb-4">
          <a href="<?= $baseUrl ?>/finance" class="flex items-center space-x-2 hover:text-gray-300">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                  <line x1="1" y1="10" x2="23" y2="10"></line>
              </svg>
              <span><?= $langText['finance'] ?? 'Financeiro' ?></span>
          </a>
      </li>
        <li>
          <a href="<?= $baseUrl ?>/employees" class="flex flex-col items-center py-2 text-gray-700">
            <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M8 7a4 4 0 1 1 8 0 4 4 0 0 1-8 0zM4 21v-2a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4v2"></path>
            </svg>
          </a>
        </li>
        <li>
          <a href="<?= $baseUrl ?>/inventory" class="flex flex-col items-center py-2 text-gray-700">
            <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
              <line x1="3" y1="9" x2="21" y2="9"></line>
              <line x1="3" y1="15" x2="21" y2="15"></line>
            </svg>
          </a>
        </li>
        <li>
          <a href="<?= $baseUrl ?>/calendar" class="flex flex-col items-center py-2 text-gray-700">
            <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
              <line x1="16" y1="2" x2="16" y2="6"></line>
              <line x1="8" y1="2" x2="8" y2="6"></line>
              <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
          </a>
        </li>
        <li>
          <a href="<?= $baseUrl ?>/clients" class="flex flex-col items-center py-2 text-gray-700">
            <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M8 7a4 4 0 1 1 8 0 4 4 0 0 1-8 0zM4 21v-2a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4v2"></path>
            </svg>
          </a>
        </li>
        <li>
          <a href="<?= $baseUrl ?>/analytics" class="flex flex-col items-center py-2 text-gray-700">
            <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
          </a>
        </li>
      </ul>
    </nav>
  </div>
  <!-- ================ Fim Mobile Layout ================ -->

<?php else: ?>

<?php endif; ?>

<script src="<?= $baseUrl ?>/js/header.js"></script>
</body>
</html>
