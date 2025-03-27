<?php
// Inicia a sessão se ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Carrega o arquivo de tradução
require __DIR__ . '/../../lang/lang.php';

// Define o idioma
$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'pt';

// Definição das bandeiras e nomes dos idiomas
$flags = [
    'pt' => ['name' => 'Português', 'flag' => '/ams-malergeschaft/public/assets/flags/pt.png'],
    'en' => ['name' => 'English', 'flag' => '/ams-malergeschaft/public/assets/flags/us.png'],
    'de' => ['name' => 'Deutsch', 'flag' => '/ams-malergeschaft/public/assets/flags/de.png'],
    'fr' => ['name' => 'Français', 'flag' => '/ams-malergeschaft/public/assets/flags/fr.png'],
];

// Define a bandeira atual
$currentFlag = isset($flags[$lang])
    ? '<img src="' . $flags[$lang]['flag'] . '" class="w-5 h-5" alt="Bandeira">'
    : '🌎';

// Verifica se o usuário está logado
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
  <link rel="icon" href="/ams-malergeschaft/public/assets/logo/ams-malergeschaft_icon.png" type="image/png">
</head>
<body class="bg-gray-100 text-gray-900">
<?php if (!$isLoginOrRegisterPage && $isLoggedIn): ?>
  <!-- Sidebar fixa -->
  <aside class="w-56 bg-gray-900 text-white h-screen fixed left-0 top-0 p-4 flex flex-col justify-between">
      <div>
          <h1 class="text-xl font-bold mb-6">AMS Malergeschäft</h1>
          <nav>
              <ul>
                  <li class="mb-4">
                      <a href="/dashboard" class="flex items-center space-x-2 hover:text-gray-300">
                          <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                              <path d="M3 10h11M9 21V6M21 16H9M15 3h6v6"></path>
                          </svg>
                          <span>Dashboard</span>
                      </a>
                  </li>
                  <li class="mb-4">
                      <a href="/projects" class="flex items-center space-x-2 hover:text-gray-300">
                          <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                              <path d="M9 12h6M12 9v6M4 21h16a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"></path>
                          </svg>
                          <span>Projetos</span>
                      </a>
                  </li>
                  <li class="mb-4">
                      <a href="/employees" class="flex items-center space-x-2 hover:text-gray-300">
                          <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                              <path d="M8 7a4 4 0 1 1 8 0 4 4 0 0 1-8 0zM4 21V12h16v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"></path>
                          </svg>
                          <span>Funcionários</span>
                      </a>
                  </li>
                  <li class="mb-4">
                      <a href="/logout" class="flex items-center space-x-2 hover:text-red-400">
                          <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                              <path d="M15 12H3M21 16l-4-4 4-4M21 12h-8"></path>
                          </svg>
                          <span>Sair</span>
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
          <div class="flex items-center">
              <span class="text-4xl font-bold text-blue-600">Ams</span>
              <span class="ml-2 text-xl text-gray-600"><?= $langText['Malergeschften'] ?? 'Malergeschäft' ?></span>
          </div>
      </div>
      <div class="flex items-center space-x-6">
          <a href="/create_project" class="bg-green-500 text-white px-3 py-2 rounded hover:bg-green-600">Novo Projeto +</a>
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
      </div>
  </nav>
<?php endif; ?>