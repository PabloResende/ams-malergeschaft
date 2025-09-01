<?php
// app/views/finance/index.php

// Exibe todos os erros exceto notices e deprecated
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

// Recebe constantes e parâmetros de filtro
$baseUrl   = BASE_URL;
$type      = $_GET['type']     ?? '';
$start     = $_GET['start']    ?? date('Y-m-01');
$end       = $_GET['end']      ?? date('Y-m-d');
$category  = $_GET['category'] ?? '';

require __DIR__ . '/../layout/header.php';
?>

<!-- FullCalendar CSS -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css" rel="stylesheet"/>

<script>
  // Injetando constantes e strings para o JS
  window.baseUrl        = <?= json_encode($baseUrl, JSON_UNESCAPED_SLASHES) ?>;
  window.FINANCE_PREFIX = window.baseUrl + '/finance';
  window.FINANCE_STR    = {
    newTransaction:  <?= json_encode($langText['new_transaction'] ?? 'Nova Transação', JSON_UNESCAPED_UNICODE) ?>,
    editTransaction: <?= json_encode($langText['edit_transaction']  ?? 'Editar Transação', JSON_UNESCAPED_UNICODE) ?>,
    save:            <?= json_encode($langText['save']              ?? 'Salvar', JSON_UNESCAPED_UNICODE) ?>,
    saveChanges:     <?= json_encode($langText['save_changes']      ?? 'Salvar Alterações', JSON_UNESCAPED_UNICODE) ?>,
    confirmDelete:   <?= json_encode($langText['confirm_delete']    ?? 'Confirmar Exclusão', JSON_UNESCAPED_UNICODE) ?>,
    errorFetch:      <?= json_encode($langText['error_fetch']       ?? 'Falha ao buscar dados', JSON_UNESCAPED_UNICODE) ?>
  };
  window.FINANCE_EVENTS = <?= json_encode($events, JSON_UNESCAPED_UNICODE) ?>;
</script>

<main class="md:pl-80 pt-20 p-6">
  <h1 class="text-3xl font-bold mb-6">
    <?= htmlspecialchars($langText['finance'] ?? 'Financeiro', ENT_QUOTES) ?>
  </h1>

  <!-- Resumo de receitas, despesas e saldo -->
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
    <div class="p-4 bg-green-100 rounded-lg shadow">
      <p class="text-sm text-gray-600">
        <?= htmlspecialchars($langText['total_income'] ?? 'Receita Total', ENT_QUOTES) ?>
      </p>
      <p class="text-2xl font-bold">
        <?= number_format((float)$summary['income'],  2, ',', '.') ?>
      </p>
    </div>
    <div class="p-4 bg-red-100 rounded-lg shadow">
      <p class="text-sm text-gray-600">
        <?= htmlspecialchars($langText['total_expense'] ?? 'Despesa Total', ENT_QUOTES) ?>
      </p>
      <p class="text-2xl font-bold">
        <?= number_format((float)$summary['expense'], 2, ',', '.') ?>
      </p>
    </div>
    <div class="p-4 bg-blue-100 rounded-lg shadow">
      <p class="text-sm text-gray-600">
        <?= htmlspecialchars($langText['net_balance'] ?? 'Saldo Líquido', ENT_QUOTES) ?>
      </p>
      <p class="text-2xl font-bold">
        <?= number_format((float)$summary['net'],     2, ',', '.') ?>
      </p>
    </div>
  </div>

  <!-- Formulário de filtros -->
  <form method="GET" action="<?= $baseUrl ?>/finance"
        class="flex flex-wrap gap-2 mb-6 items-end">
    <div>
      <label class="block text-sm text-gray-700">
        <?= htmlspecialchars($langText['start_date'] ?? 'Data Início', ENT_QUOTES) ?>
      </label>
      <input type="date" name="start"
             value="<?= htmlspecialchars($start, ENT_QUOTES) ?>"
             class="border rounded p-2"/>
    </div>
    <div>
      <label class="block text-sm text-gray-700">
        <?= htmlspecialchars($langText['end_date'] ?? 'Data Fim', ENT_QUOTES) ?>
      </label>
      <input type="date" name="end"
             value="<?= htmlspecialchars($end, ENT_QUOTES) ?>"
             class="border rounded p-2"/>
    </div>
    <div>
      <label class="block text-sm text-gray-700">
        <?= htmlspecialchars($langText['type'] ?? 'Tipo', ENT_QUOTES) ?>
      </label>
      <select name="type" class="border rounded p-2">
        <option value="">
          <?= htmlspecialchars($langText['all_types'] ?? 'Todos os tipos', ENT_QUOTES) ?>
        </option>
        <option value="income"  <?= $type === 'income'  ? 'selected' : '' ?>>
          <?= htmlspecialchars($langText['income'] ?? 'Receita', ENT_QUOTES) ?>
        </option>
        <option value="expense" <?= $type === 'expense' ? 'selected' : '' ?>>
          <?= htmlspecialchars($langText['expense'] ?? 'Despesa', ENT_QUOTES) ?>
        </option>
        <option value="debt"    <?= $type === 'debt'    ? 'selected' : '' ?>>
          <?= htmlspecialchars($langText['debt'] ?? 'Dívida', ENT_QUOTES) ?>
        </option>
      </select>
    </div>
    <div>
      <label class="block text-sm text-gray-700">
        <?= htmlspecialchars($langText['category'] ?? 'Categoria', ENT_QUOTES) ?>
      </label>
      <select name="category" class="border rounded p-2">
        <option value="">
          <?= htmlspecialchars($langText['all_categories'] ?? 'Todas categorias', ENT_QUOTES) ?>
        </option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= htmlspecialchars($c['value'], ENT_QUOTES) ?>"
                  <?= $category === $c['value'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['name'], ENT_QUOTES) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit"
            class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
      <?= htmlspecialchars($langText['filter'] ?? 'Filtrar', ENT_QUOTES) ?>
    </button>
  </form>

  <!-- Calendário -->
  <div id="calendar" class="bg-white rounded shadow p-4 mb-6"></div>

  <!-- Tabela de transações -->
  <div class="overflow-x-auto mb-6">
    <table class="w-full bg-white rounded-lg shadow">
      <thead class="bg-gray-100">
        <tr>
          <th class="p-3 text-left">
            <?= htmlspecialchars($langText['date']      ?? 'Data', ENT_QUOTES) ?>
          </th>
          <th class="p-3 text-left">
            <?= htmlspecialchars($langText['type']      ?? 'Tipo', ENT_QUOTES) ?>
          </th>
          <th class="p-3 text-left">
            <?= htmlspecialchars($langText['category']  ?? 'Categoria', ENT_QUOTES) ?>
          </th>
          <th class="p-3 text-left">
            <?= htmlspecialchars($langText['associate'] ?? 'Associado', ENT_QUOTES) ?>
          </th>
          <th class="p-3 text-right">
            <?= htmlspecialchars($langText['amount']    ?? 'Valor', ENT_QUOTES) ?>
          </th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($transactions)): ?>
          <tr>
            <td colspan="5" class="p-4 text-center text-gray-500">
              <?= htmlspecialchars($langText['no_transactions'] ?? 'Nenhuma Transação Encontrada', ENT_QUOTES) ?>
            </td>
          </tr>
        <?php else: foreach ($transactions as $t): ?>
          <tr class="border-t hover:bg-gray-50 cursor-pointer tx-row"
              data-tx-id="<?= (int)$t['id'] ?>">
            <td class="p-3"><?= date('d/m/Y', strtotime($t['date'])) ?></td>
            <td class="p-3">
              <?= htmlspecialchars($langText[$t['type']] ?? ucfirst($t['type']), ENT_QUOTES) ?>
            </td>
            <td class="p-3">
              <?= htmlspecialchars($langText['category_' . $t['category']] ?? ucfirst($t['category']), ENT_QUOTES) ?>
            </td>
            <td class="p-3">
              <?php
                if ($t['client_id']) {
                  foreach ($clients   as $cl) if ($cl['id'] == $t['client_id'])   echo htmlspecialchars($cl['name'], ENT_QUOTES);
                } elseif ($t['project_id']) {
                  foreach ($projects as $pr) if ($pr['id']== $t['project_id'])  echo htmlspecialchars($pr['name'], ENT_QUOTES);
                } elseif ($t['employee_id']) {
                  foreach ($employees as $e) if ($e['id']== $t['employee_id']) echo htmlspecialchars($e['name'] . ' ' . $e['last_name'], ENT_QUOTES);
                }
              ?>
            </td>
            <td class="p-3 text-right"><?= number_format((float)$t['amount'],2,',','.') ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Botão Nova Transação -->
  <button id="openTxModalBtn"
          class="fixed bottom-16 right-8 bg-green-500 text-white rounded-full p-4 shadow-xl hover:bg-green-600"
          aria-label="<?= htmlspecialchars($langText['new_transaction'] ?? 'Nova Transação', ENT_QUOTES) ?>">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
    </svg>
  </button>

  <!-- Modal de Transação -->
  <div id="transactionModal"
       class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg w-full max-w-2xl mx-4 p-6 relative">
      <!-- Botão fechar -->
      <button id="closeTxModalBtn"
              class="absolute top-4 right-4 text-gray-500 hover:text-gray-700">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none"
             viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round"
             stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>

      <!-- Link excluir (será mostrado só em edição) -->
      <a id="txDeleteLink"
         href="#"
         class="hidden mb-4 block text-red-600 hover:underline">
        <?= htmlspecialchars($langText['confirm_delete'] ?? 'Excluir Transação', ENT_QUOTES) ?>
      </a>

      <!-- Lista de anexos -->
      <div id="attachmentsContainer" class="mb-4">
        <label class="block text-sm text-gray-700">
          <?= htmlspecialchars($langText['attachments'] ?? 'Anexos', ENT_QUOTES) ?>
        </label>
        <ul id="txAttachments" class="list-disc list-inside text-sm text-gray-600"></ul>
      </div>

      <!-- Título dinâmico e formulário -->
      <h2 id="txModalTitle" class="text-xl font-semibold mb-4"></h2>
      <form id="transactionForm" class="space-y-4">
        <input type="hidden" id="txId" name="id">

        <!-- Abas Geral / Parcelamento -->
        <div class="border-b mb-4 flex space-x-4">
          <button type="button" id="tabGeneralBtn"
                  class="px-4 py-2 border-b-2 border-blue-600 text-blue-600">
            <?= htmlspecialchars($langText['general_tab'] ?? 'Geral', ENT_QUOTES) ?>
          </button>
          <button type="button" id="tabDebtBtn"
                  class="px-4 py-2 text-gray-600 hidden">
            <?= htmlspecialchars($langText['installments_tab'] ?? 'Parcelamento', ENT_QUOTES) ?>
          </button>
        </div>

        <!-- Painel Geral -->
        <div id="tabGeneral">
          <div>
            <label class="block text-sm font-medium">
              <?= htmlspecialchars($langText['type'] ?? 'Tipo', ENT_QUOTES) ?>
            </label>
            <select id="txTypeSelect" name="type" class="w-full border rounded p-2">
              <option value="income"><?= htmlspecialchars($langText['income'] ?? 'Receita', ENT_QUOTES) ?></option>
              <option value="expense"><?= htmlspecialchars($langText['expense'] ?? 'Despesa', ENT_QUOTES) ?></option>
              <option value="debt"><?= htmlspecialchars($langText['debt'] ?? 'Dívida', ENT_QUOTES) ?></option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium">
              <?= htmlspecialchars($langText['category'] ?? 'Categoria', ENT_QUOTES) ?>
            </label>
            <select id="txCategorySelect" name="category" class="w-full border rounded p-2">
              <?php foreach ($categories as $c): ?>
                <option value="<?= htmlspecialchars($c['value'], ENT_QUOTES) ?>">
                  <?= htmlspecialchars($c['name'], ENT_QUOTES) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div id="clientContainer" class="hidden">
            <label class="block text-sm font-medium">
              <?= htmlspecialchars($langText['select_client'] ?? 'Selecione um Cliente', ENT_QUOTES) ?>
            </label>
            <select id="txClientSelect" name="client_id" class="w-full border rounded p-2">
              <option value=""><?= htmlspecialchars($langText['select_client'] ?? 'Selecione um Cliente', ENT_QUOTES) ?></option>
              <?php foreach ($clients as $cl): ?>
                <option value="<?= (int)$cl['id'] ?>">
                  <?= htmlspecialchars($cl['name'], ENT_QUOTES) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div id="projectContainer" class="hidden">
            <label class="block text-sm font-medium">
              <?= htmlspecialchars($langText['select_project'] ?? 'Selecione um Projeto', ENT_QUOTES) ?>
            </label>
            <select id="txProjectSelect" name="project_id" class="w-full border rounded p-2">
              <option value=""><?= htmlspecialchars($langText['select_project'] ?? 'Selecione um Projeto', ENT_QUOTES) ?></option>
              <?php foreach ($projects as $pr): ?>
                <option value="<?= (int)$pr['id'] ?>">
                  <?= htmlspecialchars($pr['name'], ENT_QUOTES) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div id="employeeContainer" class="hidden">
            <label class="block text-sm font-medium">
              <?= htmlspecialchars($langText['select_employee'] ?? 'Selecione um Funcionário', ENT_QUOTES) ?>
            </label>
            <select id="txEmployeeSelect" name="employee_id" class="w-full border rounded p-2">
              <option value=""><?= htmlspecialchars($langText['select_employee'] ?? 'Selecione um Funcionário', ENT_QUOTES) ?></option>
              <?php foreach ($employees as $e): ?>
                <option value="<?= (int)$e['id'] ?>">
                  <?= htmlspecialchars($e['name'].' '.$e['last_name'], ENT_QUOTES) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium">
              <?= htmlspecialchars($langText['date'] ?? 'Data', ENT_QUOTES) ?>
            </label>
            <input type="date" id="txDateInput" name="date" class="w-full border rounded p-2"/>
          </div>

          <div>
            <label class="block text-sm font-medium">
              <?= htmlspecialchars($langText['amount'] ?? 'Valor', ENT_QUOTES) ?>
            </label>
            <input type="number" step="0.01" id="txAmountInput" name="amount" class="w-full border rounded p-2"/>
          </div>

          <div>
            <label class="block text-sm font-medium">
              <?= htmlspecialchars($langText['description'] ?? 'Descrição', ENT_QUOTES) ?>
            </label>
            <textarea id="txDescInput" name="description" class="w-full border rounded p-2"></textarea>
          </div>
        </div>

        <!-- Painel Parcelamento -->
        <div id="tabDebt" class="hidden space-y-4">
          <div class="flex items-center">
            <input type="checkbox" id="initialPaymentChk" name="initial_payment" class="mr-2"/>
            <label for="initialPaymentChk">
              <?= htmlspecialchars($langText['initial_payment'] ?? 'Pagamento Inicial', ENT_QUOTES) ?>
            </label>
          </div>
          <div id="initialPaymentContainer" class="hidden">
            <label class="block text-sm font-medium">
              <?= htmlspecialchars($langText['initial_payment_amount'] ?? 'Valor Inicial', ENT_QUOTES) ?>
            </label>
            <input type="number" step="0.01" id="initialPaymentAmt" name="initial_payment_amount" class="w-full border rounded p-2"/>
          </div>
          <div>
            <label class="block text-sm font-medium">
              <?= htmlspecialchars($langText['number_installments'] ?? 'Número de Parcelas', ENT_QUOTES) ?>
            </label>
            <select id="installmentsSelect" name="installments_count" class="w-full border rounded p-2">
              <option value="">--</option>
              <?php for ($i = 1; $i <= 12; $i++): ?>
                <option value="<?= $i ?>"><?= $i ?></option>
              <?php endfor; ?>
            </select>
            <p id="installmentInfo" class="mt-1 text-sm font-medium"></p>
          </div>
          <div>
            <label class="block text-sm font-medium">
              <?= htmlspecialchars($langText['due_date'] ?? 'Data de Vencimento', ENT_QUOTES) ?>
            </label>
            <input type="date" id="txDueDateInput" name="due_date" class="w-full border rounded p-2"/>
          </div>
        </div>

        <!-- Botões finais -->
        <div class="flex justify-end space-x-2 pt-4">
          <button type="button" id="txCancelBtn" class="px-4 py-2 border rounded">
            <?= htmlspecialchars($langText['cancel'] ?? 'Cancelar', ENT_QUOTES) ?>
          </button>
          <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            <?= htmlspecialchars($langText['save'] ?? 'Salvar', ENT_QUOTES) ?>
          </button>
        </div>
      </form>
    </div>
  </div>
</main>

<!-- FullCalendar JS -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.js"></script>
<script src="<?= asset('js/finance.js') ?>?v=<?= time() ?>" defer></script>
<?php require __DIR__ . '/../layout/footer.php'; ?>

