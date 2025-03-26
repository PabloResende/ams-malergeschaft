<?php  
// Garante que a sessão só será iniciada se ainda não estiver ativa  
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
$currentFlag = isset($flags[$lang]) ? '<img src="' . $flags[$lang]['flag'] . '" class="w-5 h-5 object-contain">' : '🌎';
?>  

<!DOCTYPE html>  
<html lang="<?= htmlspecialchars($lang) ?>">  
<head>  
    <meta charset="UTF-8">  
    <meta name="viewport" content="width=device-width, initial-scale=1.0">  
    <title>MVP tailwind</title>  
    <script src="https://cdn.tailwindcss.com"></script>  
</head>  
<body class="bg-gray-100 text-gray-900">  
    <nav class="bg-blue-600 p-4 text-white flex justify-between items-center">  
        <a href="/ams-malergeschaft/public/" class="font-bold">MVP</a>  
        <div class="flex items-center gap-4">  
            <?php if (isset($_SESSION['user'])): ?>  
                <a href="/ams-malergeschaft/public/profile" class="px-4"><?= $langText['profile'] ?></a>  
                <a href="/ams-malergeschaft/public/logout" class="px-4"><?= $langText['logout'] ?></a>  
            <?php else: ?>  
                <a href="/ams-malergeschaft/public/login" class="px-4"><?= $langText['login'] ?></a>  
                <a href="/ams-malergeschaft/public/register" class="px-4"><?= $langText['register'] ?></a>  
            <?php endif; ?>  

            <!-- Language Selector -->  
            <div class="relative group">  
                <button id="language-button" class="flex items-center gap-2 bg-blue-600 text-gray-900">
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
        </div>  
    </nav>  

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
