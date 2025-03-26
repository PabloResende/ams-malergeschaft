<?php require __DIR__ . '/../layout/header.php'; ?>  

<!DOCTYPE html>  
<html lang="<?= $lang ?>">  
<head>  
    <meta charset="UTF-8">  
    <title><?= $langText['register'] ?></title>  
</head>  
<body>  
    <h1 class="text-center text-2xl mt-4"><?= $langText['register'] ?></h1>  

    <div class="flex items-center justify-center h-screen">  
        <div class="bg-white p-6 rounded shadow-md w-96">  
            <h2 class="text-2xl mb-4"><?= $langText['register'] ?></h2>  
            <form action="/ams-malergeschaft/public/store" method="POST" enctype="multipart/form-data">  
                <div class="mb-4">  
                    <label>Name</label>  
                    <input type="text" name="name" class="border p-2 w-full" required>  
                </div>  
                <div class="mb-4">  
                    <label>Email</label>  
                    <input type="email" name="email" class="border p-2 w-full" required>  
                </div>  
                <div class="mb-4">  
                    <label>Password</label>  
                    <input type="password" name="password" class="border p-2 w-full" required>  
                </div>  
                <button type="submit" class="bg-green-600 text-white p-2 w-full"><?= $langText['register'] ?></button>  
            </form>  
        </div>  
    </div>  
</body>  
</html>  
  