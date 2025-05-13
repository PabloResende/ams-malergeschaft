<?php
// app/views/finance/index.php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

$baseUrl   = '/ams-malergeschaft/public';
$type      = $_GET['type']     ?? '';
$start     = $_GET['start']    ?? date('Y-m-01');
$end       = $_GET['end']      ?? date('Y-m-d');
$category  = $_GET['category'] ?? '';

require __DIR__ . '/../layout/header.php';
?>
<script>
  window.BASE_URL       = '<?= $baseUrl ?>';
  window.FINANCE_PREFIX = window.BASE_URL + '/finance';
  window.FINANCE_STR    = {
    newTransaction:  <?= json_encode($langText['new_transaction']    ?? 'Nova Transação') ?>,
    editTransaction: <?= json_encode($langText['edit_transaction']   ?? 'Editar Transação') ?>,
    save:            <?= json_encode($langText['save']               ?? 'Salvar') ?>,
    saveChanges:     <?= json_encode($langText['save_changes']       ?? 'Salvar Alterações') ?>,
    confirmDelete:   <?= json_encode($langText['confirm_delete']     ?? 'Confirmar Exclusão') ?>
  };
</script>

<main class="md:pl-80 pt-20 p-6">
  <h1 class="text-3xl font-bold mb-6"><?= htmlspecialchars($langText['finance'] ?? 'Financeiro') ?></h1>

  <!-- Resumo -->
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
    <div class="p-4 bg-green-100 rounded">
      <p class="text-sm"><?= htmlspecialchars($langText['total_income'] ?? 'Total Receitas') ?></p>
      <p class="text-2xl font-bold"><?= number_format((float)$summary['income'],2,',','.') ?></p>
    </div>
    <div class="p-4 bg-red-100 rounded">
      <p class="text-sm"><?= htmlspecialchars($langText['total_expense'] ?? 'Total Despesas') ?></p>
      <p class="text-2xl font-bold"><?= number_format((float)$summary['expense'],2,',','.') ?></p>
    </div>
    <div class="p-4 bg-blue-100 rounded">
      <p class="text-sm"><?= htmlspecialchars($langText['net_balance'] ?? 'Saldo Líquido') ?></p>
      <p class="text-2xl font-bold"><?= number_format((float)$summary['net'],2,',','.') ?></p>
    </div>
  </div>

  <!-- Filtros -->
  <form method="GET" action="<?= $baseUrl ?>/finance" class="flex flex-wrap gap-2 mb-6">
    <input type="date" name="start" value="<?= htmlspecialchars($start) ?>" class="border rounded p-1"/>
    <input type="date" name="end"   value="<?= htmlspecialchars($end)   ?>" class="border rounded p-1"/>
    <select name="type" class="border rounded p-1">
      <option value=""><?= htmlspecialchars($langText['all_types'] ?? 'Todos Tipos') ?></option>
      <option value="income"  <?= $type==='income'  ? 'selected':'' ?>><?= htmlspecialchars($langText['income']  ?? 'Receita') ?></option>
      <option value="expense" <?= $type==='expense' ? 'selected':'' ?>><?= htmlspecialchars($langText['expense'] ?? 'Despesa') ?></option>
      <option value="debt"    <?= $type==='debt'    ? 'selected':'' ?>><?= htmlspecialchars($langText['debt']    ?? 'Dívida') ?></option>
    </select>
    <select name="category" id="filterCategory" class="border rounded p-1">
      <option value=""><?= htmlspecialchars($langText['all_categories'] ?? 'Todas Categorias') ?></option>
      <?php foreach($categories as $c): ?>
        <option value="<?= $c['value'] ?>" <?= $category===$c['value']?'selected':'' ?>
                data-assoc="<?= $c['assoc'] ?>">
          <?= htmlspecialchars($c['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">
      <?= htmlspecialchars($langText['filter'] ?? 'Filtrar') ?>
    </button>
  </form>

  <!-- Tabela -->
  <div class="overflow-x-auto mb-6">
    <table class="w-full bg-white rounded shadow">
      <thead class="bg-gray-100">
        <tr>
          <th class="p-2 text-left"><?= htmlspecialchars($langText['date']   ?? 'Data') ?></th>
          <th class="p-2 text-left"><?= htmlspecialchars($langText['type']   ?? 'Tipo') ?></th>
          <th class="p-2 text-left"><?= htmlspecialchars($langText['category']   ?? 'Categoria') ?></th>
          <th class="p-2 text-left"><?= htmlspecialchars($langText['associate']   ?? 'Associado') ?></th>
          <th class="p-2 text-right"><?= htmlspecialchars($langText['amount'] ?? 'Valor') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($transactions)): ?>
          <tr><td colspan="5" class="p-4 text-center"><?= htmlspecialchars($langText['no_transactions'] ?? 'Nenhuma transação') ?></td></tr>
        <?php else: foreach($transactions as $t): ?>
          <tr class="border-t tx-row cursor-pointer" data-tx-id="<?= htmlspecialchars($t['id'],ENT_QUOTES) ?>">
            <td class="p-2"><?= date('d/m/Y',strtotime($t['date'])) ?></td>
            <td class="p-2"><?= htmlspecialchars($langText[$t['type']] ?? ucfirst($t['type'])) ?></td>
            <td class="p-2"><?= htmlspecialchars($t['category_name']) ?></td>
            <td class="p-2">
              <?php
                if ($t['client_id'])     echo htmlspecialchars($clients[array_search($t['client_id'], array_column($clients,'id'))]['name']    ?? '');
                elseif ($t['project_id']) echo htmlspecialchars($projects[array_search($t['project_id'], array_column($projects,'id'))]['name'] ?? '');
                elseif ($t['employee_id'])echo htmlspecialchars($employees[array_search($t['employee_id'],array_column($employees,'id'))]['name'] ?? '');
              ?>
            </td>
            <td class="p-2 text-right"><?= number_format((float)$t['amount'],2,',','.') ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Botão + Modal -->
  <button id="openTxModalBtn" class="fixed bottom-8 right-8 bg-green-500 text-white rounded-full p-4 shadow-lg hover:bg-green-600">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
    </svg>
  </button>

  <!-- Modal -->
  <div id="transactionModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-lg mx-4 overflow-hidden">
      <div class="flex justify-between items-center px-6 py-4 border-b">
        <h3 id="txModalTitle" class="text-xl font-semibold"></h3>
        <button id="closeTxModalBtn" class="text-gray-600 hover:text-gray-800 text-2xl">&times;</button>
      </div>
      <div class="flex border-b">
        <button id="tabGeneralBtn" class="flex-1 text-center px-4 py-2 border-b-2 border-blue-600 font-medium">Geral</button>
        <button id="tabDebtBtn"    class="flex-1 text-center px-4 py-2 text-gray-500 hover:text-gray-700 hidden">Parcelamento</button>
      </div>
      <form id="transactionForm" method="POST" enctype="multipart/form-data" class="px-6 py-4 space-y-6">
        <input type="hidden" name="id" id="txId"/>

        <!-- Categoria fixa -->
        <div>
          <label class="block mb-1 font-medium"><?= htmlspecialchars($langText['category'] ?? 'Categoria') ?></label>
          <select name="category" id="txCategorySelect" required class="w-full border rounded p-2">
            <option value="" disabled selected><?= htmlspecialchars($langText['select_category'] ?? 'Selecione categoria') ?></option>
            <?php foreach($categories as $c): ?>
              <option value="<?= $c['value'] ?>" data-assoc="<?= $c['assoc'] ?>"><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Containers de associação -->
        <div id="clientContainer" class="hidden">
          <label class="block mb-1 font-medium">Cliente</label>
          <select name="client_id" id="txClientSelect" class="w-full border rounded p-2">
            <option value="" disabled selected>Selecione cliente</option>
            <?php foreach($clients as $cl): ?>
              <option value="<?= $cl['id'] ?>"><?= htmlspecialchars($cl['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div id="projectContainer" class="hidden">
          <label class="block mb-1 font-medium">Projeto</label>
          <select name="project_id" id="txProjectSelect" class="w-full border rounded p-2">
            <option value="" disabled selected>Selecione projeto</option>
            <?php foreach($projects as $pr): ?>
              <option value="<?= $pr['id'] ?>"><?= htmlspecialchars($pr['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div id="employeeContainer" class="hidden">
          <label class="block mb-1 font-medium">Funcionário</label>
          <select name="employee_id" id="txEmployeeSelect" class="w-full border rounded p-2">
            <option value="" disabled selected>Selecione funcionário</option>
            <?php foreach($employees as $e): ?>
              <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['name'].' '.$e['last_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Aba Geral -->
        <div id="tabGeneral" class="space-y-4">
          <div>
            <label class="block mb-1 font-medium"><?= htmlspecialchars($langText['type'] ?? 'Tipo') ?></label>
            <select name="type" id="txTypeSelect" required class="w-full border rounded p-2">
              <option value="" disabled selected><?= htmlspecialchars($langText['select'] ?? 'Selecione') ?></option>
              <option value="income"><?= htmlspecialchars($langText['income'] ?? 'Receita') ?></option>
              <option value="expense"><?= htmlspecialchars($langText['expense'] ?? 'Despesa') ?></option>
              <option value="debt"><?= htmlspecialchars($langText['debt'] ?? 'Dívida') ?></option>
            </select>
          </div>
          <div id="dateContainer">
            <label class="block mb-1 font-medium"><?= htmlspecialchars($langText['transaction_date'] ?? 'Data') ?></label>
            <input type="date" name="date" id="txDateInput" class="w-full border rounded p-2"/>
          </div>
          <div id="dueDateContainer" class="hidden">
            <label class="block mb-1 font-medium"><?= htmlspecialchars($langText['due_date'] ?? 'Data de Vencimento') ?></label>
            <input type="date" name="due_date" id="txDueDateInput" class="w-full border rounded p-2"/>
          </div>
          <div>
            <label class="block mb-1 font-medium"><?= htmlspecialchars($langText['amount'] ?? 'Valor') ?></label>
            <input type="number" step="0.01" name="amount" id="txAmountInput" required class="w-full border rounded p-2"/>
          </div>
          <div>
            <label class="block mb-1 font-medium"><?= htmlspecialchars($langText['description'] ?? 'Descrição') ?></label>
            <textarea name="description" id="txDescInput" rows="3" class="w-full border rounded p-2"></textarea>
          </div>
          <div>
            <label class="block mb-1 font-medium"><?= htmlspecialchars($langText['attachments'] ?? 'Anexos') ?></label>
            <ul id="txAttachments" class="list-disc ml-5 text-sm text-blue-600"></ul>
            <input type="file" name="attachments[]" multiple class="w-full border rounded p-2"/>
          </div>
        </div>

        <!-- Aba Parcelamento -->
        <div id="tabDebt" class="hidden space-y-4">
          <div class="flex items-center gap-2">
            <input type="checkbox" id="initialPaymentChk" name="initial_payment" class="h-4 w-4"/>
            <label for="initialPaymentChk" class="font-medium"><?= htmlspecialchars($langText['initial_payment'] ?? 'Entrada inicial') ?></label>
          </div>
          <div id="initialPaymentContainer" class="hidden">
            <label class="block mb-1 font-medium"><?= htmlspecialchars($langText['initial_payment_amount'] ?? 'Valor da Entrada') ?></label>
            <input type="number" step="0.01" id="initialPaymentAmt" name="initial_payment_amount" class="w-full border rounded p-2"/>
          </div>
          <div>
            <label class="block mb-1 font-medium"><?= htmlspecialchars($langText['installments_count'] ?? 'Parcelas') ?></label>
            <select id="installmentsSelect" name="installments_count" class="w-full border rounded p-2">
              <option value="" disabled selected><?= htmlspecialchars($langText['select'] ?? 'Selecione') ?></option>
              <?php for($i=1;$i<=12;$i++): ?>
                <option value="<?= $i ?>"><?= $i ?>×</option>
              <?php endfor; ?>
            </select>
          </div>
          <div id="installmentInfo" class="text-sm text-gray-600"></div>
        </div>

        <div class="flex justify-end gap-2 pt-4 border-t">
          <button type="button" id="txCancelBtn" class="px-4 py-2 border rounded hover:bg-gray-100"><?= htmlspecialchars($langText['cancel'] ?? 'Cancelar') ?></button>
          <button type="submit" id="txSaveBtn" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"><?= htmlspecialchars($langText['save'] ?? 'Salvar') ?></button>
        </div>
      </form>
      <a id="txDeleteLink" href="#" class="absolute bottom-4 left-6 text-red-600 hover:underline hidden"><?= htmlspecialchars($langText['confirm_delete'] ?? 'Excluir') ?></a>
    </div>
  </div>
</main>
<script defer src="<?= $baseUrl ?>/js/finance.js"></script>
