<?php
// app/views/employees/index.php - ARQUIVO COMPLETO CORRIGIDO COM NOVA ABA DE HORAS

require __DIR__.'/../layout/header.php';

// Busca funcionários
require_once __DIR__.'/../../models/Employees.php';
$employeeModel = new Employee();
$employees = $employeeModel->all();

// Busca roles para dropdown
global $pdo;
$stmt = $pdo->query('SELECT id, name FROM roles ORDER BY name ASC');
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="pt-20 px-4 py-6 sm:px-8 sm:py-8 ml-0 lg:ml-56">
  <div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl font-bold">
      <?= htmlspecialchars($langText['employees'] ?? 'Funcionários', ENT_QUOTES); ?>
    </h1>
    <button id="openEmployeeModalBtn"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
      <?= htmlspecialchars($langText['add_employee'] ?? 'Adicionar Funcionário', ENT_QUOTES); ?>
    </button>
  </div>

  <!-- Grid de funcionários -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (empty($employees)): ?>
      <div class="col-span-full text-center text-gray-500 py-8">
        <?= htmlspecialchars($langText['no_employees'] ?? 'Nenhum funcionário cadastrado', ENT_QUOTES); ?>
      </div>
    <?php else: ?>
      <?php foreach ($employees as $emp): ?>
        <div class="employee-card bg-white rounded-lg shadow p-6 cursor-pointer hover:shadow-lg transition-shadow"
             data-id="<?= (int) $emp['id']; ?>">
          <div class="flex items-center space-x-4">
            <div class="flex-shrink-0">
              <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center text-white font-semibold">
                <?= strtoupper(substr($emp['name'], 0, 1)); ?>
              </div>
            </div>
            <div class="flex-grow">
              <h3 class="text-lg font-semibold text-gray-900">
                <?= htmlspecialchars($emp['name'].' '.$emp['last_name'], ENT_QUOTES); ?>
              </h3>
              <p class="text-sm text-gray-600"><?= htmlspecialchars($emp['function'] ?? 'Função não definida', ENT_QUOTES); ?></p>
              <p class="text-xs text-gray-500"><?= htmlspecialchars($emp['phone'] ?? '', ENT_QUOTES); ?></p>
            </div>
            <div class="flex-shrink-0">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                <?= $langText['active'] ?? 'Ativo'; ?>
              </span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- Modal de Adicionar Funcionário -->
<div id="employeeModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
  <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4">
    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-medium text-gray-900" id="modalTitle">
          <?= htmlspecialchars($langText['add_employee'] ?? 'Adicionar Funcionário', ENT_QUOTES); ?>
        </h3>
        <button class="close-modal text-gray-400 hover:text-gray-600">
          <i class="fas fa-times text-xl"></i>
        </button>
      </div>
    </div>

    <form id="employeeForm" class="p-6">
      <input type="hidden" id="employeeId" name="employee_id" value="">

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div>
          <label class="block mb-2"><?= htmlspecialchars($langText['name'] ?? 'Nome', ENT_QUOTES); ?></label>
          <input type="text" id="employeeName" name="name" class="w-full border rounded-lg p-2" required>
        </div>
        <div>
          <label class="block mb-2"><?= htmlspecialchars($langText['last_name'] ?? 'Sobrenome', ENT_QUOTES); ?></label>
          <input type="text" id="employeeLastName" name="last_name" class="w-full border rounded-lg p-2" required>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div>
          <label class="block mb-2"><?= htmlspecialchars($langText['email'] ?? 'Email', ENT_QUOTES); ?></label>
          <input type="email" id="employeeEmail" name="email" class="w-full border rounded-lg p-2" required>
        </div>
        <div>
          <label class="block mb-2"><?= htmlspecialchars($langText['phone'] ?? 'Telefone', ENT_QUOTES); ?></label>
          <input type="tel" id="employeePhone" name="phone" class="w-full border rounded-lg p-2">
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div>
          <label class="block mb-2"><?= htmlspecialchars($langText['position'] ?? 'Função', ENT_QUOTES); ?></label>
          <input type="text" id="employeePosition" name="position" class="w-full border rounded-lg p-2" required>
        </div>
        <div>
          <label class="block mb-2"><?= htmlspecialchars($langText['password'] ?? 'Senha', ENT_QUOTES); ?></label>
          <input type="password" id="employeePassword" name="password" class="w-full border rounded-lg p-2" required>
        </div>
      </div>

      <div class="flex items-center mb-6">
        <input type="checkbox" id="employeeActive" name="active" class="mr-2" checked>
        <label for="employeeActive"><?= htmlspecialchars($langText['active'] ?? 'Ativo', ENT_QUOTES); ?></label>
      </div>

      <div class="flex justify-end space-x-3">
        <button type="button" class="close-modal bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium py-2 px-4 rounded-lg">
          <?= htmlspecialchars($langText['cancel'] ?? 'Cancelar', ENT_QUOTES); ?>
        </button>
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg">
          <?= htmlspecialchars($langText['save'] ?? 'Salvar', ENT_QUOTES); ?>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal de Detalhes do Funcionário -->
<div id="employeeDetailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
  <div class="bg-white rounded-lg shadow-xl max-w-6xl w-full mx-4 max-h-[90vh] overflow-hidden">
    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-medium text-gray-900">
          <?= htmlspecialchars($langText['employee_details'] ?? 'Detalhes do Funcionário', ENT_QUOTES); ?>
        </h3>
        <button class="close-modal text-gray-400 hover:text-gray-600">
          <i class="fas fa-times text-xl"></i>
        </button>
      </div>
    </div>

    <div class="flex-1 overflow-y-auto" style="max-height: calc(90vh - 140px);">
      <!-- Tabs de navegação -->
      <div class="border-b border-gray-200">
        <nav class="px-6 flex space-x-8">
          <button data-tab="panel-general-details" class="py-4 px-1 border-b-2 font-medium text-sm border-blue-500 text-blue-600">
            Geral
          </button>
          <button data-tab="panel-documents-details" class="py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700">
            Documentos
          </button>
          <button data-tab="panel-access-details" class="py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700">
            Acesso
          </button>
          <button data-tab="panel-transactions-details" class="py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700">
            Transações
          </button>
          <button data-tab="panel-hours-details" class="py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700">
            <?= htmlspecialchars($langText['work_hours'] ?? 'Horas de Trabalho', ENT_QUOTES); ?>
          </button>
        </nav>
      </div>

      <!-- Conteúdo das abas -->
      <div class="p-6">
        <!-- Painel Geral Detalhes -->
        <div id="panel-general-details" class="tab-panel">
          <h3 class="text-lg font-semibold mb-2"><?= htmlspecialchars($langText['personal_information'] ?? 'Informações Pessoais', ENT_QUOTES); ?></h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['name'] ?? 'Nome', ENT_QUOTES); ?></label>
              <input type="text" id="detailsName" name="name" class="w-full border rounded-lg p-2" required>
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['last_name'] ?? 'Sobrenome', ENT_QUOTES); ?></label>
              <input type="text" id="detailsLastName" name="last_name" class="w-full border rounded-lg p-2" required>
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['phone'] ?? 'Telefone', ENT_QUOTES); ?></label>
              <input type="tel" id="detailsPhone" name="phone" class="w-full border rounded-lg p-2">
            </div>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['position'] ?? 'Função', ENT_QUOTES); ?></label>
              <input type="text" id="detailsPosition" name="position" class="w-full border rounded-lg p-2" required>
            </div>
            <div class="flex items-center">
              <input type="checkbox" id="detailsActive" name="active" class="mr-2">
              <label for="detailsActive"><?= htmlspecialchars($langText['active'] ?? 'Ativo', ENT_QUOTES); ?></label>
            </div>
          </div>
        </div>

        <!-- Painel Documentos Detalhes -->
        <div id="panel-documents-details" class="tab-panel hidden">
          <h3 class="text-lg font-semibold mb-2"><?= htmlspecialchars($langText['documents'] ?? 'Documentos', ENT_QUOTES); ?></h3>
          <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center">
            <i class="fas fa-upload text-gray-400 text-3xl mb-4"></i>
            <p class="text-gray-500 mb-2"><?= htmlspecialchars($langText['drag_drop_files'] ?? 'Arraste arquivos aqui ou clique para enviar', ENT_QUOTES); ?></p>
            <input type="file" class="hidden" id="documentUpload" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
            <button type="button" onclick="document.getElementById('documentUpload').click()" 
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
              <?= htmlspecialchars($langText['select_files'] ?? 'Selecionar Arquivos', ENT_QUOTES); ?>
            </button>
          </div>
          <div id="documentsList" class="mt-4">
            <!-- Lista de documentos será carregada aqui -->
          </div>
        </div>

        <!-- Painel Acesso Detalhes -->
        <div id="panel-access-details" class="tab-panel hidden">
          <h3 class="text-lg font-semibold mb-2"><?= htmlspecialchars($langText['access'] ?? 'Acesso', ENT_QUOTES); ?></h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['email'] ?? 'Email', ENT_QUOTES); ?></label>
              <input type="email" id="detailsLoginEmail" name="email" class="w-full border rounded-lg p-2" required>
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['new_password'] ?? 'Nova Senha', ENT_QUOTES); ?></label>
              <input type="password" id="detailsLoginPassword" name="password" class="w-full border rounded-lg p-2" placeholder="••••••••">
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['role'] ?? 'Nível', ENT_QUOTES); ?></label>
              <select id="detailsEmployeeRoleId" name="role_id" required class="w-full border rounded-lg p-2">
                <option value=""><?= htmlspecialchars($langText['select_role'] ?? 'Selecione...', ENT_QUOTES); ?></option>
                <?php foreach ($roles as $r): ?>
                  <option value="<?= (int) $r['id']; ?>">
                    <?= htmlspecialchars(ucfirst($r['name']), ENT_QUOTES); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>

        <!-- Painel Transações Detalhes -->
        <div id="panel-transactions-details" class="tab-panel hidden">
          <h3 class="text-lg font-semibold mb-2"><?= htmlspecialchars($langText['transactions'] ?? 'Transações', ENT_QUOTES); ?></h3>
          <div class="overflow-x-auto">
            <table class="min-w-full bg-white rounded-lg shadow">
              <thead class="bg-gray-100">
                <tr>
                  <th class="p-2 text-left text-sm font-medium text-gray-700"><?= htmlspecialchars($langText['date'] ?? 'Data', ENT_QUOTES); ?></th>
                  <th class="p-2 text-left text-sm font-medium text-gray-700"><?= htmlspecialchars($langText['type'] ?? 'Tipo', ENT_QUOTES); ?></th>
                  <th class="p-2 text-right text-sm font-medium text-gray-700"><?= htmlspecialchars($langText['amount'] ?? 'Valor', ENT_QUOTES); ?></th>
                </tr>
              </thead>
              <tbody id="empTransBody">
                <tr>
                  <td colspan="3" class="p-4 text-center text-gray-500"><?= htmlspecialchars($langText['no_transactions'] ?? 'Sem transações', ENT_QUOTES); ?></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Painel Horas de Trabalho - COPIADO EXATAMENTE DO DASHBOARD FUNCIONÁRIO -->
        <div id="panel-hours-details" class="tab-panel hidden">
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-semibold text-gray-900">
              <?= htmlspecialchars($langText['work_hours'] ?? 'Horas de Trabalho', ENT_QUOTES); ?>
            </h3>
            <div class="text-2xl font-bold text-blue-600" id="employeeModalTotalHours">44.00h</div>
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

          <!-- Lista de registros -->
          <div class="bg-white border rounded-lg">
            <div class="p-4 border-b border-gray-200">
              <h5 class="font-medium text-gray-900">Registro de Horas</h5>
            </div>
            <div id="employeeHoursList" class="divide-y divide-gray-200">
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
          <?= htmlspecialchars($langText['save_changes'] ?? 'Salvar Alterações', ENT_QUOTES); ?>
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

// Sistema de modais e funcionários
document.addEventListener('DOMContentLoaded', function() {
    // Abrir modal de adicionar funcionário
    document.getElementById('openEmployeeModalBtn').addEventListener('click', function() {
        document.getElementById('employeeModal').classList.remove('hidden');
        document.getElementById('employeeModal').classList.add('flex');
        document.getElementById('modalTitle').textContent = window.translations.add_employee || 'Adicionar Funcionário';
        document.getElementById('employeeForm').reset();
        document.getElementById('employeeId').value = '';
    });

    // Abrir modal de detalhes do funcionário
    document.querySelectorAll('.employee-card').forEach(card => {
        card.addEventListener('click', function() {
            const employeeId = this.getAttribute('data-id');
            openEmployeeDetails(employeeId);
        });
    });

    // Fechar modais
    document.querySelectorAll('.close-modal').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('employeeModal').classList.add('hidden');
            document.getElementById('employeeDetailsModal').classList.add('hidden');
        });
    });

    // Tabs do modal de detalhes
    setupDetailsTabs();

    // Formulário de funcionário
    document.getElementById('employeeForm').addEventListener('submit', handleEmployeeSubmit);
});

function setupDetailsTabs() {
    const tabs = document.querySelectorAll('[data-tab]');
    const panels = document.querySelectorAll('.tab-panel');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const targetId = tab.getAttribute('data-tab');

            // Remove active de todas as tabs
            tabs.forEach(t => {
                t.classList.remove('border-blue-500', 'text-blue-600');
                t.classList.add('border-transparent', 'text-gray-500');
            });

            // Adiciona active na tab clicada
            tab.classList.remove('border-transparent', 'text-gray-500');
            tab.classList.add('border-blue-500', 'text-blue-600');

            // Esconde todos os painéis
            panels.forEach(panel => panel.classList.add('hidden'));

            // Mostra o painel ativo
            const activePanel = document.getElementById(targetId);
            if (activePanel) {
                activePanel.classList.remove('hidden');

                // Se for a aba de horas, carrega os dados
                if (targetId === 'panel-hours-details' && window.currentEmployeeId) {
                    loadEmployeeHours(window.currentEmployeeId);
                }
            }
        });
    });
}

async function openEmployeeDetails(employeeId) {
    window.currentEmployeeId = employeeId;

    const modal = document.getElementById('employeeDetailsModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');

    try {
        const response = await fetch(`${window.baseUrl}/api/employees/${employeeId}`);
        const employee = await response.json();

        if (employee.success) {
            const data = employee.data;
            
            // Preenche os campos
            document.getElementById('detailsName').value = data.name || '';
            document.getElementById('detailsLastName').value = data.last_name || '';
            document.getElementById('detailsPhone').value = data.phone || '';
            document.getElementById('detailsPosition').value = data.function || '';
            document.getElementById('detailsActive').checked = data.active == 1;
            document.getElementById('detailsLoginEmail').value = data.email || '';
            
            // Role
            const roleSelect = document.getElementById('detailsEmployeeRoleId');
            if (roleSelect && data.role_id) {
                roleSelect.value = data.role_id;
            }
        }
    } catch (error) {
        console.error('Erro ao carregar funcionário:', error);
    }
}

async function loadEmployeeHours(employeeId, filter = 'all') {
    const container = document.getElementById('employeeHoursList');
    const totalElement = document.getElementById('employeeModalTotalHours');

    if (!container) return;

    container.innerHTML = `
        <div class="flex items-center justify-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <span class="ml-2 text-gray-600">Carregando...</span>
        </div>
    `;

    try {
        let url = `${window.baseUrl}/api/employees/hours/${employeeId}`;
        if (filter !== 'all') {
            url += `?filter=${filter}`;
        }

        const response = await fetch(url);
        const data = await response.json();

        renderEmployeeHours(data, container, totalElement);
    } catch (error) {
        console.error('Erro ao carregar horas do funcionário:', error);
        container.innerHTML = '<div class="text-red-500 text-center py-4">Erro ao carregar dados</div>';
    }
}

function renderEmployeeHours(data, container, totalElement) {
    if (!data.entries || data.entries.length === 0) {
        container.innerHTML = `
            <div class="text-gray-500 text-center py-8">
                <i class="fas fa-clock text-4xl mb-2"></i>
                <p>Nenhum registro encontrado</p>
            </div>
        `;
        if (totalElement) totalElement.textContent = '0.00h';
        return;
    }

    let html = '';
    data.entries.forEach(entry => {
        html += `
            <div class="p-4 flex justify-between items-center">
                <div class="flex-1">
                    <div class="flex items-center space-x-3">
                        <div class="w-3 h-3 rounded-full ${entry.type === 'new_system' ? 'bg-blue-500' : 'bg-gray-400'}"></div>
                        <div>
                            <p class="font-medium text-gray-900">${entry.description}</p>
                            <p class="text-sm text-gray-600">${formatDate(entry.date)}</p>
                        </div>
                    </div>
                </div>
                <div class="text-right">
                    <span class="font-semibold text-gray-900">${entry.hours}h</span>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
    
    if (totalElement) {
        totalElement.textContent = `${data.total_hours || 0}h`;
    }
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('pt-BR');
}

async function handleEmployeeSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    
    try {
        submitBtn.textContent = 'Salvando...';
        submitBtn.disabled = true;
        
        const isEdit = document.getElementById('employeeId').value !== '';
        const url = isEdit 
            ? `${window.baseUrl}/api/employees/update` 
            : `${window.baseUrl}/api/employees/store`;
        
        const response = await fetch(url, {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            submitBtn.textContent = 'Salvo!';
            submitBtn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
            submitBtn.classList.add('bg-green-600', 'hover:bg-green-700');
            
            setTimeout(() => {
                window.location.reload();
            }, 1500);
            
        } else {
            throw new Error(data.message || 'Erro ao salvar');
        }
        
    } catch (error) {
        console.error('Erro:', error);
        alert(error.message || 'Erro ao salvar funcionário');
        
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    }
}

// Filtros de horas (admin)
document.querySelectorAll('[id^="adminFilter"]').forEach(btn => {
    btn.addEventListener('click', () => {
        const filter = btn.id.replace('adminFilter', '').toLowerCase();
        
        // Atualiza visual dos botões
        document.querySelectorAll('[id^="adminFilter"]').forEach(b => {
            b.classList.remove('bg-blue-100', 'text-blue-700');
            b.classList.add('bg-gray-100', 'text-gray-700');
        });
        
        btn.classList.remove('bg-gray-100', 'text-gray-700');
        btn.classList.add('bg-blue-100', 'text-blue-700');
        
        // Recarrega dados com filtro
        if (window.currentEmployeeId) {
            loadEmployeeHours(window.currentEmployeeId, filter);
        }
    });
});
</script>

<script defer src="<?= asset('js/header.js'); ?>"></script>
<?php require __DIR__ . '/../layout/footer.php'; ?>