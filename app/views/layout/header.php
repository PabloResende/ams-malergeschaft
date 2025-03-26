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

// Verifica se a linguagem existe no array antes de definir a bandeira  
$currentFlag = isset($flags[$lang]) ? '<img src="' . $flags[$lang]['flag'] . '" class="w-5 h-5 object-fit">' : '🌎';

// Verifica se o usuário está logado e se a página atual é login ou register  
$isLoggedIn = isset($_SESSION['user']);
$isLoginOrRegisterPage = strpos($_SERVER['REQUEST_URI'], 'login') !== false || strpos($_SERVER['REQUEST_URI'], 'register') !== false;
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
    <?php if (!$isLoginOrRegisterPage && $isLoggedIn): ?>
        <div class="flex">
            <!-- Sidebar (Navbar Lateral) -->
            <nav class="bg-white w-64 p-4 text-gray-800 fixed top-0 bottom-0 left-0 flex flex-col shadow-lg">
                <a href="/ams-malergeschaft/public/" class="font-bold flex items-center space-x-2 mb-8">
                    <img src="/ams-malergeschaft/public/assets/logo/ams-malergeschaft_icon.png" alt="Ams Malergeschäft Logo" class="h-8 w-10 object-contain">
                    <span>Ams Malergeschäft</span>
                </a>
                <div class="flex flex-col space-y-4 mb-auto text-lg font-semibold">
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

                <!-- Language Selector -->
                <div class="relative group mt-auto">
                    <button id="language-button" class="flex items-center gap-2 bg-white text-gray-900">
                        <?= $currentFlag ?>
                    </button>
                    <div id="language-menu" class="absolute right-0 mt-2 w-40 bg-white rounded-md shadow-lg border border-gray-300 hidden">
                        <?php foreach ($flags as $code => $flag): ?>
                            <a href="?lang=<?= htmlspecialchars($code) ?>" class="flex px-2 py-1 text-gray-800 hover:bg-gray-100">
                                <img src="<?= htmlspecialchars($flag['flag']) ?>" class="w-5 h-5 mr-2">
                                <span class="font-medium"><?= htmlspecialchars($flag['name']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Copyright at the bottom of the sidebar -->
                <div class="mt-auto text-center text-sm">
                    &copy; <?php echo date('Y'); ?> MVP tailwind
                </div>
            </nav>

            <!-- Área principal para páginas autenticadas -->
            <div class="flex-1 ml-64 p-8">
                <!-- Conteúdo da página -->
            </div>
        </div>
    <?php endif; ?>

    <script>
        const langButton = document.getElementById("language-button");
        const langMenu = document.getElementById("language-menu");

        langButton.addEventListener("click", (event) => {
            event.stopPropagation();
            langMenu.classList.toggle("hidden");
        });

        document.addEventListener("click", (event) => {
            if (!langButton.contains(event.target) && !langMenu.contains(event.target)) {
                langMenu.classList.add("hidden");
            }
        });
    </script>
</body>
</html>
