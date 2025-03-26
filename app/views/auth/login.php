<?php require __DIR__ . '/../layout/header.php'; ?>


<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <title><?= $langText['login'] ?></title>
</head>
<body>

    <div class="flex items-center justify-center h-screen">
        <div class="bg-white p-6 rounded shadow-md w-96">
            <h2 class="text-2xl mb-4"><?= $langText['login'] ?></h2>
            <?php if(isset($error)): ?>
                <div class="text-red-500"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form action="/ams-malergeschaft/public/auth" method="POST">
                <div class="mb-4">
                    <label>Email</label>
                    <input type="email" name="email" class="border p-2 w-full" required>
                </div>
                <div class="mb-4">
                    <label>Password</label>
                    <input type="password" name="password" class="border p-2 w-full" required>
                </div>
                <button type="submit" class="bg-blue-600 text-white p-2 w-full"><?= $langText['login'] ?></button>
            </form>
        </div>
    </div>
</body>
</html>

