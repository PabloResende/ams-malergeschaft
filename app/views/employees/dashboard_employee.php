<?php
// app/views/employees/dashboard_employee.php
require __DIR__.'/../layout/header.php';

?>

<div class="pt-20 px-4 py-6 sm:px-8 sm:py-8 ml-0 lg:ml-56">
    <!-- Header com boas-vindas -->
   <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">
            Bem-vindo, <?= htmlspecialchars($_SESSION['user']['name'] ?? 'Funcionário', ENT_QUOTES); ?>!
        </h1>
        <p class="text-gray-600">
            <?= htmlspecialchars($langText['dashboard_subtitle'] ?? 'Aqui você pode gerenciar seus projetos e registrar pontos.', ENT_QUOTES); ?>
        </p>
    </div>

    <!-- Cards de estatísticas -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                        <i class="fas fa-clock text-white text-sm"></i>
                    </div>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-600">
                        <?= htmlspecialchars($langText['total_hours'] ?? 'Total de Horas', ENT_QUOTES); ?>
                    </p>
                    <p id="totalHoursCard" class="text-2xl font-semibold text-gray-900">0.00h</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                        <i class="fas fa-calendar-day text-white text-sm"></i>
                    </div>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-600">Hoje</p>
                    <p id="todayHoursCard" class="text-2xl font-semibold text-gray-900">0.00h</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center">
                        <i class="fas fa-calendar-week text-white text-sm"></i>
                    </div>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-600">Esta Semana</p>
                    <p id="weekHoursCard" class="text-2xl font-semibold text-gray-900">0.00h</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Seção de Projetos -->
    <div class="bg-white rounded-lg shadow mb-8">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">
                <?= htmlspecialchars($langText['my_projects'] ?? 'Meus Projetos', ENT_QUOTES); ?>
            </h2>
        </div>

        <div class="p-6">
            <?php if (empty($projects)): ?>
                <div class="text-center py-8">
                    <i class="fas fa-folder-open text-gray-400 text-4xl mb-4"></i>
                    <p class="text-gray-500 text-lg">
                        <?= htmlspecialchars($langText['no_projects_allocated'] ?? 'Nenhum projeto alocado.', ENT_QUOTES); ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($projects as $project): ?>
                        <div class="project-item border rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer"
                             data-project-id="<?= (int)$project['id']; ?>">
                            <h3 class="font-semibold text-gray-900 mb-2">
                                <?= htmlspecialchars($project['name'], ENT_QUOTES); ?>
                            </h3>
                            <p class="text-sm text-gray-600 mb-3">
                                <?= htmlspecialchars($project['description'] ?? '', ENT_QUOTES); ?>
                            </p>
                            <div class="flex items-center text-xs text-gray-500">
                                <i class="fas fa-calendar mr-1"></i>
                                <span><?= date('d/m/Y', strtotime($project['created_at'])); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de detalhes do projeto com sistema de ponto -->
<div id="projectDetailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-hidden">
        <!-- Header do Modal -->
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">Detalhes do Projeto</h3>
                <button class="close-modal text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>

        <!-- Conteúdo do Modal -->
        <div class="flex-1 overflow-y-auto" style="max-height: calc(90vh - 140px);">
            <!-- Tabs de navegação -->
            <div class="border-b border-gray-200">
                <nav class="px-6 flex space-x-8">
                    <button data-tab="tab-geral" class="py-4 px-1 border-b-2 font-medium text-sm border-blue-500 text-blue-600">
                        Geral
                    </button>
                    <button data-tab="tab-ponto" class="py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700">
                        <?= htmlspecialchars($langText['work_hours'] ?? 'Horas de Trabalho', ENT_QUOTES); ?>
                    </button>
                </nav>
            </div>

            <!-- Painel Geral -->
            <div id="tab-geral" class="tab-panel p-6">
                <h4 class="text-lg font-semibold text-gray-900 mb-4">Informações do Projeto</h4>
                <div id="projectGeneralInfo" class="space-y-4">
                    <div class="text-center py-8 text-gray-500">
                        <?= htmlspecialchars($langText['loading'] ?? 'Carregando...', ENT_QUOTES); ?>
                    </div>
                </div>
            </div>

            <!-- Painel Horas de Trabalho -->
            <div id="tab-ponto" class="tab-panel hidden">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h4 class="text-lg font-semibold text-gray-900">
                            <?= htmlspecialchars($langText['work_hours'] ?? 'Horas de Trabalho', ENT_QUOTES); ?>
                        </h4>
                        <div class="text-2xl font-bold text-blue-600" id="modalTotalHours">44.00h</div>
                    </div>

                    <!-- Filtros -->
                    <div class="flex space-x-2 mb-6">
                        <button id="adminFilterall" class="px-4 py-2 rounded-lg text-sm font-medium bg-blue-100 text-blue-700">
                            Hoje
                        </button>
                        <button id="adminFilterweek" class="px-4 py-2 rounded-lg text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200">
                            Esta Semana
                        </button>
                        <button id="adminFiltermonth" class="px-4 py-2 rounded-lg text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200">
                            Este Mês
                        </button>
                        <button id="adminFilterperiod" class="px-4 py-2 rounded-lg text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200">
                            Todo Período
                        </button>
                    </div>

                    <!-- Formulário de registro de ponto -->
                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                        <h5 class="font-medium text-gray-900 mb-3">
                            <?= htmlspecialchars($langText['register_time_entry'] ?? 'Registrar Ponto', ENT_QUOTES); ?>
                        </h5>
                        <form id="timeTrackingForm" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <input type="hidden" id="timeTrackingProjectId" name="project_id" value="">
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    <?= htmlspecialchars($langText['input_date_label'] ?? 'Data', ENT_QUOTES); ?>
                                </label>
                                <input type="date" name="date" 
                                       value="<?= date('Y-m-d'); ?>"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       required>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    <?= htmlspecialchars($langText['input_time_label'] ?? 'Horário', ENT_QUOTES); ?>
                                </label>
                                <input type="time" name="time" 
                                       value="<?= date('H:i'); ?>"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       required>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    <?= htmlspecialchars($langText['entry_type'] ?? 'Tipo', ENT_QUOTES); ?>
                                </label>
                                <select name="type" 
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        required>
                                    <option value="entry"><?= htmlspecialchars($langText['entry'] ?? 'Entrada', ENT_QUOTES); ?></option>
                                    <option value="exit"><?= htmlspecialchars($langText['exit'] ?? 'Saída', ENT_QUOTES); ?></option>
                                </select>
                            </div>
                            
                            <div class="flex items-end">
                                <button type="submit" 
                                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                                    <?= htmlspecialchars($langText['button_register_entry'] ?? 'Registrar', ENT_QUOTES); ?>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Lista de registros -->
                    <div class="bg-white border rounded-lg">
                        <div class="p-4 border-b border-gray-200">
                            <h5 class="font-medium text-gray-900">Registro de Horas</h5>
                        </div>
                        <div id="timeEntriesList" class="divide-y divide-gray-200">
                            <div class="p-4 text-center text-gray-500">
                                <?= htmlspecialchars($langText['loading_hours'] ?? 'Carregando registros de horas...', ENT_QUOTES); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer do Modal -->
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-between">
            <button class="close-modal bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium py-2 px-4 rounded-lg transition-colors">
                Cancelar
            </button>
            <div class="space-x-2">
                <button class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                    Salvar Alterações
                </button>
                <button class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                    Excluir
                </button>
            </div>
        </div>
    </div>
</div>

<script>
window.baseUrl = '<?= BASE_URL; ?>';
window.translations = <?= json_encode($langText); ?>';

// Carrega estatísticas iniciais
document.addEventListener('DOMContentLoaded', function() {
    loadDashboardStats();
});

async function loadDashboardStats() {
    try {
        const response = await fetch(`${window.baseUrl}/api/employees/hours-summary`);
        const data = await response.json();
        
        document.getElementById('totalHoursCard').textContent = `${data.total || '0.00'}h`;
        document.getElementById('todayHoursCard').textContent = `${data.today || '0.00'}h`;
        document.getElementById('weekHoursCard').textContent = `${data.week || '0.00'}h`;
        
    } catch (error) {
        console.error('Erro ao carregar estatísticas:', error);
        document.getElementById('totalHoursCard').textContent = '0.00h';
        document.getElementById('todayHoursCard').textContent = '0.00h';
        document.getElementById('weekHoursCard').textContent = '0.00h';
    }
}
</script>

<!-- Carregar JavaScript do sistema de ponto -->
<script src="<?= BASE_URL; ?>/public/js/employee-time-tracking.js?v=<?= time(); ?>" defer></script>
<script defer src="<?= asset('js/header.js'); ?>"></script>

<?php require __DIR__ . '/../layout/footer.php'; ?>