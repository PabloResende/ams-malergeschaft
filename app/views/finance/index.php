<?php
// app/views/finance/index.php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

$baseUrl   = '$basePath';
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
    newTransaction:  <?= json_encode($langText['new_transaction'], JSON_UNESCAPED_UNICODE) ?>,
    editTransaction: <?= json_encode($langText['edit_transaction'], JSON_UNESCAPED_UNICODE) ?>,
    save:            <?= json_encode($langText['save'], JSON_UNESCAPED_UNICODE) ?>,
    saveChanges:     <?= json_encode($langText['save_changes'], JSON_UNESCAPED_UNICODE) ?>,
    confirmDelete:   <?= json_encode($langText['confirm_delete'], JSON_UNESCAPED_UNICODE) ?>
  };
</script>

<main class="md:pl-80 pt-20 p-6">
  <h1 class="text-3xl font-bold mb-6"><?= htmlspecialchars($langText['finance'], ENT_QUOTES) ?></h1>

  <!-- Resumo -->
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
    <div class="p-4 bg-green-100 rounded">
      <p class="text-sm"><?= htmlspecialchars($langText['total_income'], ENT_QUOTES) ?></p>
      <p class="text-2xl font-bold"><?= number_format((float)$summary['income'],2,',','.') ?></p>
    </div>
    <div class="p-4 bg-red-100 rounded">
      <p class="text-sm"><?= htmlspecialchars($langText['total_expense'], ENT_QUOTES) ?></p>
      <p class="text-2xl font-bold"><?= number_format((float)$summary['expense'],2,',','.') ?></p>
    </div>
    <div class="p-4 bg-blue-100 rounded">
      <p class="text-sm"><?= htmlspecialchars($langText['net_balance'], ENT_QUOTES) ?></p>
      <p class="text-2xl font-bold"><?= number_format((float)$summary['net'],2,',','.') ?></p>
    </div>
  </div>

  <!-- Filtros -->
  <form method="GET" action="<?= $baseUrl ?>/finance" class="flex flex-wrap gap-2 mb-6">
    <input type="date" name="start"    value="<?= htmlspecialchars($start, ENT_QUOTES) ?>" class="border rounded p-1"/>
    <input type="date" name="end"      value="<?= htmlspecialchars($end,   ENT_QUOTES) ?>" class="border rounded p-1"/>
    <select name="type" class="border rounded p-1">
      <option value=""><?= htmlspecialchars($langText['all_types'], ENT_QUOTES) ?></option>
      <option value="income"  <?= $type==='income'  ? 'selected':'' ?>><?= htmlspecialchars($langText['income'], ENT_QUOTES) ?></option>
      <option value="expense" <?= $type==='expense' ? 'selected':'' ?>><?= htmlspecialchars($langText['expense'], ENT_QUOTES) ?></option>
      <option value="debt"    <?= $type==='debt'    ? 'selected':'' ?>><?= htmlspecialchars($langText['debt'], ENT_QUOTES) ?></option>
    </select>
    <select name="category" class="border rounded p-1">
      <option value=""><?= htmlspecialchars($langText['all_categories'], ENT_QUOTES) ?></option>
      <?php foreach($categories as $c): ?>
        <option value="<?= htmlspecialchars($c['value'], ENT_QUOTES) ?>" <?= $category===$c['value']?'selected':'' ?>>
          <?= htmlspecialchars($c['name'], ENT_QUOTES) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">
      <?= htmlspecialchars($langText['filter'], ENT_QUOTES) ?>
    </button>
  </form>

  <!-- Tabela de transações -->
  <div class="overflow-x-auto mb-6">
    <table class="w-full bg-white rounded shadow">
      <thead class="bg-gray-100">
        <tr>
          <th class="p-2 text-left"><?= htmlspecialchars($langText['date'], ENT_QUOTES) ?></th>
          <th class="p-2 text-left"><?= htmlspecialchars($langText['type'], ENT_QUOTES) ?></th>
          <th class="p-2 text-left"><?= htmlspecialchars($langText['category'], ENT_QUOTES) ?></th>
          <th class="p-2 text-left"><?= htmlspecialchars($langText['associate'], ENT_QUOTES) ?></th>
          <th class="p-2 text-right"><?= htmlspecialchars($langText['amount'], ENT_QUOTES) ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($transactions)): ?>
          <tr>
            <td colspan="5" class="p-4 text-center"><?= htmlspecialchars($langText['no_transactions'], ENT_QUOTES) ?></td>
          </tr>
        <?php else: foreach($transactions as $t): ?>
          <tr class="border-t tx-row cursor-pointer" data-tx-id="<?= htmlspecialchars($t['id'],ENT_QUOTES) ?>">
            <td class="p-2"><?= date('d/m/Y', strtotime($t['date'])) ?></td>
            <td class="p-2"><?= htmlspecialchars($langText[$t['type']] ?? ucfirst($t['type']), ENT_QUOTES) ?></td>
            <td class="p-2"><?= htmlspecialchars($langText['category_'.$t['category']] ?? $t['category'], ENT_QUOTES) ?></td>
            <td class="p-2">
              <?php
                if ($t['client_id'])       echo htmlspecialchars($clients[array_search($t['client_id'],   array_column($clients,'id'))]['name']    ?? '', ENT_QUOTES);
                elseif ($t['project_id'])  echo htmlspecialchars($projects[array_search($t['project_id'],array_column($projects,'id'))]['name'] ?? '', ENT_QUOTES);
                elseif ($t['employee_id']) echo htmlspecialchars($employees[array_search($t['employee_id'],array_column($employees,'id'))]['name'] ?? '', ENT_QUOTES);
              ?>
            </td>
            <td class="p-2 text-right"><?= number_format((float)$t['amount'],2,',','.') ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Botão Nova Transação -->
  <button id="openTxModalBtn"
          class="fixed bottom-8 right-8 bg-green-500 text-white rounded-full p-4 shadow-lg hover:bg-green-600"
          aria-label="<?= htmlspecialchars($langText['new_transaction'], ENT_QUOTES) ?>">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
    </svg>
  </button>

  <!-- Modal de Transação -->
  <div id="transactionModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-lg mx-4 overflow-hidden">
      <div class="flex justify-between items-center px-6 py-4 border-b">
        <h3 id="txModalTitle" class="text-xl font-semibold"></h3>
        <button id="closeTxModalBtn" class="text-gray-600 hover:text-gray-800 text-2xl" aria-label="<?= htmlspecialchars($langText['cancel'], ENT_QUOTES) ?>">&times;</button>
      </div>

      <div class="flex border-b">
        <button id="tabGeneralBtn" class="flex-1 text-center px-4 py-2 border-b-2 border-blue-600 font-medium"><?= htmlspecialchars($langText['general'], ENT_QUOTES) ?></button>
        <button id="tabDebtBtn"    class="flex-1 text-center px-4 py-2 text-gray-500 hover:text-gray-700 hidden"><?= htmlspecialchars($langText['debt_tab'], ENT_QUOTES) ?></button>
      </div>

      <form id="transactionForm" method="POST" enctype="multipart/form-data" class="px-6 py-4 space-y-6">
        <input type="hidden" name="id" id="txId"/>

        <div id="tabGeneral" class="space-y-4">
          <!-- tipo -->
          <div>
            <label class="block mb-1 font-medium"><?= htmlspecialchars($langText['type'], ENT_QUOTES) ?></label>
            <select name="type" id="txTypeSelect" required class="w-full border rounded p-2">
              <option value="" disabled selected><?= htmlspecialchars($langText['select'], ENT_QUOTES) ?></option>
              <option value="income"><?= htmlspecialchars($langText['income'], ENT_QUOTES) ?></option>
              <option value="expense"><?= htmlspecialchars($langText['expense'], ENT_QUOTES) ?></option>
              <option value="debt"><?= htmlspecialchars($langText['debt'], ENT_QUOTES) ?></option>
            </select>
          </div>

          <!-- categoria -->
          <div>
            <label class="block mb-1 font-medium"><?= htmlspecialchars($langText['category'], ENT_QUOTES) ?></label>
            <select name="category" id="txCategorySelect" required class="w-full border rounded p-2">
              <option value="" disabled selected><?= htmlspecialchars($langText['select_category'], ENT_QUOTES) ?></option>
              <?php foreach($categories as $c): ?>
                <option value="<?= htmlspecialchars($c['value'],ENT_QUOTES) ?>" data-assoc="<?= htmlspecialchars($c['assoc'],ENT_QUOTES) ?>">
                  <?= htmlspecialchars($c['name'], ENT_QUOTES) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- associação -->
          <div id="clientContainer" class="hidden">
            <label class="block mb-1 font-medium"><?= htmlspecialchars($langText['select_client'], ENT_QUOTES) ?></label>
            <select name="client_id" id="txClientSelect" class="w-full border rounded p-2">
              <option value="" disabled selected><?= htmlspecialchars($langText['select_client'], ENT_QUOTES) ?></option>
              <?php foreach($clients as $cl): ?>
                <option value="<?= htmlspecialchars($cl['id'],ENT_QUOTES) ?>"><?= htmlspecialchars($cl['name'],ENT_QUOTES) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div id="projectContainer" class="hidden">
            <label class="block mb-1 font-medium"><?= htmlspecialchars($langText['select_project'], ENT_QUOTES) ?></label>
            <select name="project_id" id="txProjectSelect" class="w-full border rounded p-2">
              <option value="" disabled selected><?= htmlspecialchars($langText['select_project'], ENT_QUOTES) ?></option>
              <?php foreach($projects as $pr): ?>
                <option value="<?= htmlspecialchars($pr['id'],ENT_QUOTES) ?>"><?= htmlspecialchars($pr['name'],ENT_QUOTES) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div id="employeeContainer" class="hidden">
            <label class="block mb-1 font-medium"><?= htmlspecialchars($langText['select_employee'], ENT_QUOTES) ?></label>
            <select name="employee_id" id="txEmployeeSelect" class="w-full border rounded p-2">
              <option value="" disabled selected><?= htmlspecialchars($langText['select_employee'], ENT_QUOTES) ?></option>
              <?php foreach($employees as $e): ?>
                <option value="<?= htmlspecialchars($e['id'],ENT_QUOTES) ?>"><?= htmlspecialchars($e['name'].' '.$e['last_name'],ENT_QUOTES) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- data -->
          <div id="dateContainer">
            <label class="block mb-1 font-medium"><?= htmlspecialchars($langText['transaction_date'], ENT_QUOTES) ?></label>
            <input type="date" name="date" id="txDateInput" required class="w-full border rounded p-2"/>
          </div>

          <!-- valor -->
          <div>
            <label class="block mb-1 font-medium"><?= htmlspecialchars($langText['amount'], ENT_QUOTES) ?></label>
            <input type="number" step="0.01" name="amount" id="txAmountInput" required class="w-full border rounded p-2"/>
          </div>

          <!-- descrição -->
          <div>
            <label class="block mb-1 font-medium"><?= htmlspecialchars($langText['description'], ENT_QUOTES) ?></label>
            <textarea name="description" id="txDescInput" rows="3" class="w-full border rounded p-2"></textarea>
          </div>

          <!-- anexos -->
          <div>
            <label class="block mb-1 font-medium"><?= htmlspecialchars($langText['attachments'], ENT_QUOTES) ?></label>
            <ul id="txAttachments" class="list-disc ml-5 text-sm text-blue-600"></ul>
            <input type="file" name="attachments[]" multiple class="w-full border rounded p-2"/>
          </div>
        </div>

        <div id="tabDebt" class="hidden space-y-4">
          <div>
            <label class="block mb-1 font-medium"><?= htmlspecialchars($langText['due_date'], ENT_QUOTES) ?></label>
            <input type="date" name="due_date" id="txDueDateInput" class="w-full border rounded p-2"/>
          </div>
          <div class="flex items-center gap-2">
            <input type="checkbox" id="initialPaymentChk" name="initial_payment" class="h-4 w-4"/>
            <label for="initialPaymentChk" class="font-medium"><?= htmlspecialchars($langText['initial_payment'], ENT_QUOTES) ?></label>
          </div>
          <div id="initialPaymentContainer" class="hidden">
            <label class="block mb-1 font-medium"><?= htmlspecialchars($langText['initial_payment_amount'], ENT_QUOTES) ?></label>
            <input type="number" step="0.01" id="initialPaymentAmt" name="initial_payment_amount" class="w-full border rounded p-2"/>
          </div>
          <div>
            <label class="block mb-1 font-medium"><?= htmlspecialchars($langText['installments_count'], ENT_QUOTES) ?></label>
            <select id="installmentsSelect" name="installments_count" class="w-full border rounded p-2">
              <option value="" disabled selected><?= htmlspecialchars($langText['select'], ENT_QUOTES) ?></option>
              <?php for($i=1; $i<=12; $i++): ?>
                <option value="<?= $i ?>"><?= $i ?>×</option>
              <?php endfor; ?>
            </select>
          </div>
          <div id="installmentInfo" class="text-sm text-gray-600"></div>
        </div>

        <div class="flex justify-end gap-2 pt-4 border-t">
          <button type="button" id="txCancelBtn" class="px-4 py-2 border rounded hover:bg-gray-100"><?= htmlspecialchars($langText['cancel'], ENT_QUOTES) ?></button>
          <button type="submit" id="txSaveBtn" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"><?= htmlspecialchars($langText['save'], ENT_QUOTES) ?></button>
        </div>
      </form>

      <a id="txDeleteLink" href="#" class="absolute bottom-4 left-6 text-red-600 hover:underline hidden"><?= htmlspecialchars($langText['delete'], ENT_QUOTES) ?></a>
    </div>
  </div>
</main>

<script defer src="<?= $baseUrl ?>/js/finance.js"></script>
