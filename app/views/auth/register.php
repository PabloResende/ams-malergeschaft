<?php require __DIR__ . '/../layout/header.php'; ?>  

<script>
    window.onload = () => {
        const toast = document.getElementById('toast-success');
        if (toast) {
            toast.classList.remove('hidden');
            setTimeout(() => toast.classList.add('hidden'), 3000);
        }
    };
</script>

<h1 class="text-center text-2xl mt-4"><?= $langText['register'] ?></h1>  

<?php if (isset($_GET['success'])): ?>
    <div id="toast-success" class="fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded shadow-lg z-50 transition-opacity duration-500 opacity-100">
        Cadastro realizado com sucesso!
    </div>
    <script>
        setTimeout(() => {
            const toast = document.getElementById('toast-success');
            if (toast) {
                toast.classList.add('opacity-0');
                setTimeout(() => {
                    toast.remove();
                    const url = new URL(window.location);
                    url.searchParams.delete('success');
                    window.history.replaceState({}, document.title, url.pathname);
                }, 500);
            }
        }, 3000);
    </script>
<?php endif; ?>

<div class="flex items-center justify-center h-screen">  
    <div class="bg-white p-6 rounded shadow-md w-96">  
        <h2 class="text-2xl mb-4"><?= $langText['register'] ?></h2>  

        <?php if (isset($_SESSION['error'])): ?>
            <div class="text-red-500 mb-4"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
        <?php endif; ?>

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
            <div class="mb-4">  
                <label>Confirm Password</label>  
                <input type="password" name="confirm" class="border p-2 w-full" required>  
            </div>  
            <button type="submit" class="bg-green-600 text-white p-2 w-full" onclick="this.disabled = true; this.form.submit();">
                <?= $langText['register'] ?>
            </button>  
        </form>

        <div class="mt-2 text-center">
            <a class="text-blue-600 text-sm" href="/ams-malergeschaft/public/login">← Voltar para o login</a>
        </div>
    </div>  
</div>
