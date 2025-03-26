<?php
// Inicia a sessão se ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/../../lang/lang.php';

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
    ? '<img src="' . $flags[$lang]['flag'] . '" class="w-5 h-5 object-fit" alt="Bandeira">'
    : '🌎';

// Verifica se o usuário está logado e se estamos nas páginas de login ou register
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
<body class="bg-gray-100 text-gray-900 flex flex-col min-h-screen">
    <?php if (!$isLoginOrRegisterPage): ?>
        <nav class="bg-white shadow p-4 fixed top-0 w-full z-10 flex items-center justify-between">
            <div class="flex items-center">
                <!-- Logo -->
                <img src="/ams-malergeschaft/public/assets/logo/ams-malergeschaft_icon.png" alt="Logo" class="h-10 w-auto mr-4">
                <div class="flex items-center">
                    <span class="text-4xl font-bold text-blue-600 font-sans">Ams</span>
                    <span class="ml-2 text-xl text-gray-600">
                        <?= $langText['Malergeschften'] ?? 'Malergeschäft' ?>
                    </span>
                </div>
            </div>
            <!-- Links de navegação -->
            <ul class="flex space-x-6 text-lg">
                <?php if ($isLoggedIn): ?>



                <?php endif; ?>
            </ul>
        </nav>

        <?php if ($isLoggedIn): ?>
            <!-- Layout para áreas autenticadas -->
          <div class="flex flex-1 pt-16">
    <nav class="bg-white w-64 p-4 text-gray-800 fixed top-16 bottom-0 left-0 flex flex-col shadow-lg">
        <div class="flex flex-col space-y-4 mb-auto text-lg font-semibold">
            <a href="/ams-malergeschaft/public/" class="px-4 py-2 hover:bg-gray-200 rounded flex items-center space-x-2">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l6 6a1 1 0 01.293.707V16a1 1 0 01-1 1H5a1 1 0 01-1-1V10.707a1 1 0 01.293-.707l6-6A1 1 0 0110 3z" clip-rule="evenodd" />
                </svg>
                <span><?= $langText['dashboard'] ?></span>
            </a>
            <a href="/ams-malergeschaft/public/profile" class="px-4 py-2 hover:bg-gray-200 rounded flex items-center space-x-2">
                <!-- SVG Icon for Profile -->
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M10 9a3 3 0 100-6 3 3 0 000 6zM2 17a7 7 0 0114 0H2z" />
                </svg>
                <span><?= $langText['profile'] ?></span>
            </a>
            <a href="/ams-malergeschaft/public/logout" class="px-4 py-2 hover:bg-gray-200 rounded flex items-center space-x-2">
                <!-- SVG Icon for Logout -->
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.293-7.707a1 1 0 011.414 0L15 12.586l-3.293 3.293a1 1 0 01-1.414-1.414L12.586 13H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
                <span><?= $langText['logout'] ?></span>
            </a>
        </div>
    </nav>
</div>

                    <!-- Seletor de idioma -->
                    <div class="relative group mt-auto">
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
                    <!-- Copyright -->
                    <div class="mt-auto text-center text-sm">
                        &copy; <?= date('Y'); ?> MVP tailwind
                    </div>
                </nav>

                <!-- Área principal (conteúdo) -->
                <div class="flex-1 ml-64 p-8 pt-16">
                    <!-- Conteúdo da página -->
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <script>
        const langButton = document.getElementById("language-button");
        const langMenu = document.getElementById("language-menu");

        if (langButton) {
            langButton.addEventListener("click", (event) => {
                event.stopPropagation();
                langMenu.classList.toggle("hidden");
            });
        }

        document.addEventListener("click", (event) => {
            if (langButton && !langButton.contains(event.target) && !langMenu.contains(event.target)) {
                langMenu.classList.add("hidden");
            }
        });
    </script>
</body>
</html>
