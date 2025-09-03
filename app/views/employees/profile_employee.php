<?php
// app/views/employees/profile.php - ARQUIVO CORRIGIDO

require_once __DIR__ . '/../layout/header.php';

$baseUrl = BASE_URL;
?>

<link rel="stylesheet" href="<?= BASE_URL; ?>/public/css/layout-fixes.css">

<div class="min-h-screen bg-gray-100">
    <!-- Ajuste para header -->
    <div class="pt-20">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header do Perfil -->
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="px-6 py-6">
                    <div class="flex items-center">
                        <div class="w-20 h-20 bg-gray-300 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-2xl text-gray-600"></i>
                        </div>
                        <div class="ml-6">
                            <h1 class="text-2xl font-bold text-gray-900">
                                <?= htmlspecialchars(($employee['name'] ?? '') . ' ' . ($employee['last_name'] ?? ''), ENT_QUOTES); ?>
                            </h1>
                            <p class="text-gray-600"><?= htmlspecialchars($employee['function'] ?? 'Funcionário', ENT_QUOTES); ?></p>
                            <div class="mt-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= ($employee['active'] ?? 1) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?= ($employee['active'] ?? 1) ? 'Ativo' : 'Inativo'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Informações Pessoais -->
                <div class="lg:col-span-2 bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Informações Pessoais</h2>
                    </div>
                    <div class="p-6">
                        <form id="profileForm" method="POST" action="<?= BASE_URL; ?>/employees/update">
                            <input type="hidden" name="employee_id" value="<?= htmlspecialchars($employee['id'] ?? '', ENT_QUOTES); ?>">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                                    <input type="text" name="name" value="<?= htmlspecialchars($employee['name'] ?? '', ENT_QUOTES); ?>"
                                           class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Sobrenome</label>
                                    <input type="text" name="last_name" value="<?= htmlspecialchars($employee['last_name'] ?? '', ENT_QUOTES); ?>"
                                           class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                                <input type="email" name="email" value="<?= htmlspecialchars($employee['email'] ?? '', ENT_QUOTES); ?>"
                                       class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                                    <input type="text" name="phone" value="<?= htmlspecialchars($employee['phone'] ?? '', ENT_QUOTES); ?>"
                                           class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Cargo</label>
                                    <input type="text" name="position" value="<?= htmlspecialchars($employee['function'] ?? '', ENT_QUOTES); ?>"
                                           class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>

                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nova Senha</label>
                                <input type="password" name="password" placeholder="Deixe em branco para manter a atual"
                                       class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-6 rounded-lg transition-colors">
                                    <i class="fas fa-save mr-2"></i>Salvar Alterações
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Estatísticas -->
                <div class="space-y-6">
                    <!-- Total de Horas -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="text-center">
                            <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-clock text-2xl text-blue-600"></i>
                            </div>
                            <h3 class="text-sm font-medium text-gray-500 mb-1">Total de Horas</h3>
                            <p class="text-3xl font-bold text-blue-600" id="profileTotalHours">
                                <span class="animate-pulse">...</span>
                            </p>
                        </div>
                    </div>

                    <!-- Hoje -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="text-center">
                            <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-calendar-day text-2xl text-purple-600"></i>
                            </div>
                            <h3 class="text-sm font-medium text-gray-500 mb-1">Hoje</h3>
                            <p class="text-3xl font-bold text-purple-600" id="profileTodayHours">
                                <span class="animate-pulse">...</span>
                            </p>
                        </div>
                    </div>

                    <!-- Esta Semana -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="text-center">
                            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-calendar-week text-2xl text-green-600"></i>
                            </div>
                            <h3 class="text-sm font-medium text-gray-500 mb-1">Esta Semana</h3>
                            <p class="text-3xl font-bold text-green-600" id="profileWeekHours">
                                <span class="animate-pulse">...</span>
                            </p>
                        </div>
                    </div>

                    <!-- Link para Dashboard -->
                    <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg shadow p-6 text-center">
                        <i class="fas fa-tachometer-alt text-3xl text-white mb-4"></i>
                        <h3 class="text-lg font-semibold text-white mb-2">Painel Principal</h3>
                        <p class="text-blue-100 text-sm mb-4">Acesse seus projetos e registre ponto</p>
                        <a href="<?= BASE_URL; ?>/employees/dashboard" 
                           class="inline-block bg-white text-blue-600 font-medium py-2 px-4 rounded-lg hover:bg-blue-50 transition-colors">
                            Ir para Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.baseUrl = '<?= BASE_URL; ?>';

document.addEventListener('DOMContentLoaded', function() {
    loadProfileStats();
});

async function loadProfileStats() {
    try {
        const response = await fetch(`${window.baseUrl}/api/employees/hours-summary`);
        const data = await response.json();
        
        document.getElementById('profileTotalHours').textContent = `${data.total || '0.00'}h`;
        document.getElementById('profileTodayHours').textContent = `${data.today || '0.00'}h`;
        document.getElementById('profileWeekHours').textContent = `${data.week || '0.00'}h`;
        
    } catch (error) {
        console.error('Erro ao carregar estatísticas:', error);
        document.getElementById('profileTotalHours').textContent = '0.00h';
        document.getElementById('profileTodayHours').textContent = '0.00h';
        document.getElementById('profileWeekHours').textContent = '0.00h';
    }
}

// Formulário de perfil
document.getElementById('profileForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitButton = this.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Salvando...';
    
    try {
        const response = await fetch(this.action, {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            submitButton.innerHTML = '<i class="fas fa-check mr-2"></i>Salvo!';
            submitButton.classList.remove('bg-blue-600', 'hover:bg-blue-700');
            submitButton.classList.add('bg-green-600', 'hover:bg-green-700');
            
            setTimeout(() => {
                submitButton.innerHTML = originalText;
                submitButton.classList.remove('bg-green-600', 'hover:bg-green-700');
                submitButton.classList.add('bg-blue-600', 'hover:bg-blue-700');
                submitButton.disabled = false;
            }, 2000);
        } else {
            throw new Error(data.message || 'Erro ao salvar');
        }
    } catch (error) {
        alert(error.message || 'Erro ao salvar perfil');
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
    }
});
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>