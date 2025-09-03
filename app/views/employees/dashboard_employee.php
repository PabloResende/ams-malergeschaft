<?php
// app/views/employees/dashboard_employee.php - VERSÃO CORRIGIDA

require_once __DIR__ . '/../layout/header.php';

$baseUrl = BASE_URL;
$userName = $_SESSION['user']['name'] ?? 'Funcionário';

// Buscar projetos alocados para o funcionário logado - CONSULTA CORRIGIDA
global $pdo;
$userEmail = $_SESSION['user']['email'] ?? '';

$stmt = $pdo->prepare("
    SELECT DISTINCT 
        p.*,
        c.name as client_name,
        COUNT(DISTINCT pr.resource_id) as employee_count
    FROM projects p
    LEFT JOIN client c ON p.client_id = c.id
    LEFT JOIN project_resources pr ON p.id = pr.project_id AND pr.resource_type = 'employee'
    LEFT JOIN employees e ON pr.resource_id = e.id
    LEFT JOIN users u ON e.user_id = u.id
    WHERE u.email = ? AND p.status = 'in_progress'
    GROUP BY p.id
    ORDER BY p.created_at DESC
");
$stmt->execute([$userEmail]);
$allocatedProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="min-h-screen bg-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">
                            <?= htmlspecialchars($langText['dashboard_employee'] ?? 'Dashboard do Funcionário', ENT_QUOTES); ?>
                        </h1>
                        <p class="text-gray-600">Bem-vindo, <?= htmlspecialchars($userName, ENT_QUOTES); ?>!</p>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-gray-500">
                            <?= date('d/m/Y H:i'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estatísticas Rápidas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Total de Horas -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-clock text-blue-600"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total de Horas</p>
                        <p class="text-2xl font-semibold text-gray-900" id="totalHoursCard">
                            <span class="animate-pulse">...</span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Projetos Ativos -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-project-diagram text-green-600"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Projetos Ativos</p>
                        <p class="text-2xl font-semibold text-gray-900">
                            <?= count($allocatedProjects); ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Horas Hoje -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-calendar-day text-purple-600"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Hoje</p>
                        <p class="text-2xl font-semibold text-gray-900" id="todayHoursCard">
                            <span class="animate-pulse">...</span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Esta Semana -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-calendar-week text-yellow-600"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Esta Semana</p>
                        <p class="text-2xl font-semibold text-gray-900" id="weekHoursCard">
                            <span class="animate-pulse">...</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Projetos Alocados -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">
                    <?= htmlspecialchars($langText['allocated_projects'] ?? 'Seus Projetos', ENT_QUOTES); ?>
                </h2>
                <p class="text-sm text-gray-600">Clique em um projeto para registrar ponto ou visualizar detalhes</p>
            </div>
            
            <div class="p-6">
                <?php if (empty($allocatedProjects)): ?>
                    <div class="text-center py-12">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-project-diagram text-2xl text-gray-400"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Nenhum projeto alocado</h3>
                        <p class="text-gray-500">
                            <?= htmlspecialchars($langText['no_projects_allocated'] ?? 'Você não possui projetos alocados no momento.', ENT_QUOTES); ?>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($allocatedProjects as $project): ?>
                            <div class="project-item bg-gray-50 rounded-lg border-2 border-gray-200 p-6 cursor-pointer hover:border-blue-300 hover:bg-blue-50 transition-all duration-200"
                                 data-project-id="<?= htmlspecialchars($project['id'], ENT_QUOTES); ?>">
                                
                                <!-- Status Badge -->
                                <div class="flex justify-between items-start mb-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <i class="fas fa-play mr-1"></i>
                                        Ativo
                                    </span>
                                    <div class="text-xs text-gray-500">
                                        <?= htmlspecialchars($project['employee_count'], ENT_QUOTES); ?> funcionários
                                    </div>
                                </div>

                                <!-- Nome do Projeto -->
                                <h3 class="text-lg font-semibold text-gray-900 mb-2 line-clamp-2">
                                    <?= htmlspecialchars($project['name'], ENT_QUOTES); ?>
                                </h3>

                                <!-- Cliente -->
                                <div class="flex items-center text-sm text-gray-600 mb-3">
                                    <i class="fas fa-building mr-2"></i>
                                    <span><?= htmlspecialchars($project['client_name'] ?: 'Sem cliente', ENT_QUOTES); ?></span>
                                </div>

                                <!-- Localização -->
                                <?php if (!empty($project['location'])): ?>
                                <div class="flex items-center text-sm text-gray-600 mb-3">
                                    <i class="fas fa-map-marker-alt mr-2"></i>
                                    <span><?= htmlspecialchars($project['location'], ENT_QUOTES); ?></span>
                                </div>
                                <?php endif; ?>

                                <!-- Datas -->
                                <div class="flex items-center text-xs text-gray-500 mb-4">
                                    <i class="fas fa-calendar mr-2"></i>
                                    <?php if ($project['start_date']): ?>
                                        <span><?= date('d/m/Y', strtotime($project['start_date'])); ?></span>
                                    <?php endif; ?>
                                    <?php if ($project['start_date'] && $project['end_date']): ?>
                                        <span class="mx-2">•</span>
                                    <?php endif; ?>
                                    <?php if ($project['end_date']): ?>
                                        <span><?= date('d/m/Y', strtotime($project['end_date'])); ?></span>
                                    <?php endif; ?>
                                </div>

                                <!-- Action Button -->
                                <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                                    <span class="text-sm text-blue-600 font-medium">
                                        <i class="fas fa-clock mr-1"></i>
                                        Registrar Ponto
                                    </span>
                                    <i class="fas fa-chevron-right text-gray-400"></i>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Detalhes do Projeto -->
<div id="projectDetailsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
        <!-- Header do Modal -->
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-900" id="modalProjectName">
                Detalhes do Projeto
            </h2>
            <button class="close-modal text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <!-- Tabs Navigation -->
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex px-6">
                <button data-tab="tab-geral" 
                        class="py-3 px-4 border-b-2 border-blue-500 text-blue-600 font-medium text-sm mr-8">
                    <i class="fas fa-info-circle mr-2"></i>Geral
                </button>
                <button data-tab="tab-ponto"
                        class="py-3 px-4 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium text-sm mr-8">
                    <i class="fas fa-clock mr-2"></i>Registrar Ponto
                </button>
                <button data-tab="tab-tarefas"
                        class="py-3 px-4 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium text-sm mr-8">
                    <i class="fas fa-tasks mr-2"></i>Tarefas
                </button>
                <button data-tab="tab-funcionarios"
                        class="py-3 px-4 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium text-sm mr-8">
                    <i class="fas fa-users mr-2"></i>Equipe
                </button>
                <button data-tab="tab-inventario"
                        class="py-3 px-4 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium text-sm">
                    <i class="fas fa-boxes mr-2"></i>Inventário
                </button>
            </nav>
        </div>

        <!-- Content -->
        <div class="max-h-[60vh] overflow-y-auto">
            <!-- Aba Geral -->
            <div id="tab-geral" class="tab-panel p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1">Nome do Projeto</label>
                        <p id="roName" class="text-gray-900 bg-gray-50 p-3 rounded-lg"></p>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1">Cliente</label>
                        <p id="roClient" class="text-gray-900 bg-gray-50 p-3 rounded-lg"></p>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1">Localização</label>
                        <p id="roLocation" class="text-gray-900 bg-gray-50 p-3 rounded-lg"></p>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1">Status</label>
                        <span class="inline-flex items-center px-2.5 py-1.5 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            <i class="fas fa-play mr-2"></i>Ativo
                        </span>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1">Data de Início</label>
                        <p id="roStart" class="text-gray-900 bg-gray-50 p-3 rounded-lg"></p>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1">Data de Fim</label>
                        <p id="roEnd" class="text-gray-900 bg-gray-50 p-3 rounded-lg"></p>
                    </div>
                </div>
            </div>

            <!-- Aba Registrar Ponto - NOVA E MELHORADA -->
            <div id="tab-ponto" class="tab-panel hidden p-6">
                <!-- Header da Seção -->
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Sistema de Ponto</h3>
                        <p class="text-sm text-gray-600">Registre suas entradas e saídas de forma prática</p>
                    </div>
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <p class="text-sm text-blue-600 font-medium">Total de Horas</p>
                        <p class="text-2xl font-bold text-blue-700" id="workLogTotal">0.00h</p>
                    </div>
                </div>

                <!-- Formulário de Registro de Ponto -->
                <form id="timeTrackingForm" 
                      method="POST" 
                      action="<?= BASE_URL; ?>/work_logs/store_time_entry"
                      class="mb-8 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-6">
                    
                    <input type="hidden" name="project_id" id="timeTrackingProjectId">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <!-- Data -->
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">
                                <i class="fas fa-calendar-alt mr-1"></i>
                                <?= htmlspecialchars($langText['input_date_label'] ?? 'Data', ENT_QUOTES); ?>
                            </label>
                            <input type="date" 
                                   name="date" 
                                   id="entryDate"
                                   value="<?= date('Y-m-d'); ?>"
                                   class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                                   required>
                        </div>
                        
                        <!-- Tipo -->
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">
                                <i class="fas fa-exchange-alt mr-1"></i>
                                <?= htmlspecialchars($langText['entry_type'] ?? 'Tipo', ENT_QUOTES); ?>
                            </label>
                            <select name="entry_type" 
                                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                                    required>
                                <option value="entry">
                                    <?= htmlspecialchars($langText['entry'] ?? 'Entrada', ENT_QUOTES); ?>
                                </option>
                                <option value="exit">
                                    <?= htmlspecialchars($langText['exit'] ?? 'Saída', ENT_QUOTES); ?>
                                </option>
                            </select>
                        </div>
                        
                        <!-- Horário -->
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">
                                <i class="fas fa-clock mr-1"></i>
                                <?= htmlspecialchars($langText['input_time_label'] ?? 'Horário', ENT_QUOTES); ?>
                            </label>
                            <input type="time" 
                                   name="time" 
                                   value="<?= date('H:i'); ?>"
                                   class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                                   required>
                        </div>
                        
                        <!-- Botão -->
                        <div class="flex items-end">
                            <button type="submit" 
                                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center">
                                <i class="fas fa-plus-circle mr-2"></i>
                                <?= htmlspecialchars($langText['button_register_entry'] ?? 'Registrar', ENT_QUOTES); ?>
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Lista de Registros Agrupados por Data -->
                <div class="bg-white border border-gray-200 rounded-lg">
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h4 class="text-lg font-semibold text-gray-900">
                                <i class="fas fa-history mr-2"></i>Histórico de Pontos
                            </h4>
                            <!-- Filtros Rápidos -->
                            <div class="flex gap-2">
                                <button type="button" id="filterToday" 
                                        class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm hover:bg-blue-200 transition-colors">
                                    Hoje
                                </button>
                                <button type="button" id="filterWeek" 
                                        class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm hover:bg-gray-200 transition-colors">
                                    Esta Semana
                                </button>
                                <button type="button" id="filterMonth" 
                                        class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm hover:bg-gray-200 transition-colors">
                                    Este Mês
                                </button>
                                <button type="button" id="filterAll" 
                                        class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm hover:bg-gray-200 transition-colors">
                                    Todos
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <div id="timeEntriesList" class="space-y-4">
                            <div class="text-gray-500 text-center py-8">
                                <i class="fas fa-clock text-4xl mb-3"></i>
                                <p><?= htmlspecialchars($langText['no_time_entries'] ?? 'Nenhum registro de ponto', ENT_QUOTES); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Aba Tarefas -->
            <div id="tab-tarefas" class="tab-panel hidden p-6">
                <div class="flex items-center mb-4">
                    <i class="fas fa-tasks text-blue-600 mr-3 text-xl"></i>
                    <h4 class="text-lg font-semibold text-gray-900">Tarefas do Projeto</h4>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <ul id="roTasks" class="space-y-2"></ul>
                </div>
            </div>

            <!-- Aba Funcionários -->
            <div id="tab-funcionarios" class="tab-panel hidden p-6">
                <div class="flex items-center mb-4">
                    <i class="fas fa-users text-green-600 mr-3 text-xl"></i>
                    <h4 class="text-lg font-semibold text-gray-900">Equipe do Projeto</h4>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <ul id="roEmployees" class="space-y-2"></ul>
                </div>
            </div>

            <!-- Aba Inventário -->
            <div id="tab-inventario" class="tab-panel hidden p-6">
                <div class="flex items-center mb-4">
                    <i class="fas fa-boxes text-yellow-600 mr-3 text-xl"></i>
                    <h4 class="text-lg font-semibold text-gray-900">Inventário Alocado</h4>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <div id="roInventory" class="text-gray-600">
                        <?= htmlspecialchars($langText['no_inventory_allocated'] ?? 'Nenhum item alocado', ENT_QUOTES); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer do Modal -->
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end">
            <button class="close-modal bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium py-2 px-4 rounded-lg transition-colors">
                Fechar
            </button>
        </div>
    </div>
</div>

<script>
window.baseUrl = '<?= BASE_URL; ?>';
window.translations = <?= json_encode($langText); ?>;

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