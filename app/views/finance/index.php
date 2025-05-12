<?php
// app/views/finance/index.php

$baseUrl        = '/ams-malergeschaft/public';
$type           = $_GET['type']        ?? '';
$start          = $_GET['start']       ?? date('Y-m-01');
$end            = $_GET['end']         ?? date('Y-m-d');
$cat            = $_GET['category_id'] ?? '';
$projectCatName = $langText['project_expense'] ?? 'Gastos com Projeto';

// Do controller: $transactions, $allCategories, $summary, $projects

require __DIR__ . '/../layout/header.php';
?>
<script>
  window.BASE_URL       = '<?= $baseUrl ?>';
  window.FINANCE_PREFIX = window.BASE_URL + '/finance';
  window.PROJECT_CAT    = <?= json_encode($projectCatName) ?>;
  window.FINANCE_STR = {
    newTransaction:  <?= json_encode($langText['new_transaction']) ?>,
    save:            <?= json_encode($langText['save']) ?>,
    editTransaction: <?= json_encode($langText['edit_transaction']  ?? 'Editar Transação') ?>,
    saveChanges:     <?= json_encode($langText['save_changes']      ?? 'Salvar') ?>,
    confirmDelete:   <?= json_encode($langText['confirm_delete']     ?? 'Excluir esta transação?') ?>
  };
</script>

<main class="md:pl-80 pt-20 p-6">
  <h1 class="text-3xl font-bold mb-6"><?= htmlspecialchars($langText['finance']) ?></h1>

  <!-- Resumo -->
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
    <div class="p-4 bg-green-100 rounded">
      <p class="text-sm"><?= htmlspecialchars($langText['total_income']) ?></p>
      <p class="text-2xl font-bold"><?= number_format((float)$summary['income'],2,',','.') ?></p>
    </div>
    <div class="p-4 bg-red-100 rounded">
      <p class="text-sm"><?= htmlspecialchars($langText['total_expense']) ?></p>
      <p class="text-2xl font-bold"><?= number_format((float)$summary['expense'],2,',','.') ?></p>
    </div>
    <div class="p-4 bg-blue-100 rounded">
      <p class="text-sm"><?= htmlspecialchars($langText['net_balance']) ?></p>
      <p class="text-2xl font-bold"><?= number_format((float)$summary['net'],2,',','.') ?></p>
    </div>
  </div>

  <!-- Filtros + Ações -->
  <div class="flex flex-wrap items-center gap-3 mb-6">
    <form method="GET" action="<?= $baseUrl ?>/finance" class="flex flex-wrap gap-2">
      <input type="date"   name="start" value="<?= htmlspecialchars($start) ?>" class="border rounded p-1"/>
      <input type="date"   name="end"   value="<?= htmlspecialchars($end)   ?>" class="border rounded p-1"/>
      <select name="type" class="border rounded p-1">
        <option value=""><?= htmlspecialchars($langText['all_types']) ?></option>
        <option value="income"  <?= $type==='income'  ? 'selected':'' ?>><?= htmlspecialchars($langText['income']) ?></option>
        <option value="expense" <?= $type==='expense' ? 'selected':'' ?>><?= htmlspecialchars($langText['expense']) ?></option>
        <option value="debt"    <?= $type==='debt'    ? 'selected':'' ?>><?= htmlspecialchars($langText['debt']) ?></option>
      </select>
      <select name="category_id" class="border rounded p-1">
        <option value=""><?= htmlspecialchars($langText['all_categories']) ?></option>
        <?php foreach($allCategories as $c): if ($type === '' || $c['type'] === $type): ?>
          <option 
            value="<?= $c['id'] ?>" 
            data-type="<?= $c['type'] ?>" 
            data-project="<?= $c['name']=== $projectCatName ? '1':'0' ?>"
            <?= $cat == $c['id'] ? 'selected':''?>>
            <?= htmlspecialchars($c['name']) ?>
          </option>
        <?php endif; endforeach; ?>
      </select>
      <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">
        <?= htmlspecialchars($langText['filter']) ?>
      </button>
    </form>

    <a href="<?= $baseUrl ?>/finance/calendar"
       class="px-3 py-1 bg-indigo-500 text-white rounded hover:bg-indigo-600 flex items-center gap-1">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
           viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M8 7V3m8 4V3m-9 8h10m-2 8H7a2 2 0 
                 01-2-2V9a2 2 0 012-2h10a2 2 0 
                 012 2v10a2 2 0 01-2 2z"/>
      </svg>
      <?= htmlspecialchars($langText['calendar']) ?>
    </a>
  </div>

  <!-- Tabela de Transações -->
  <div class="overflow-x-auto mb-6">
    <table class="w-full bg-white rounded shadow">
      <thead class="bg-gray-100">
        <tr>
          <th class="p-2 text-left"><?= htmlspecialchars($langText['date']) ?></th>
          <th class="p-2 text-left"><?= htmlspecialchars($langText['type']) ?></th>
          <th class="p-2 text-left"><?= htmlspecialchars($langText['category']) ?></th>
          <th class="p-2 text-right"><?= htmlspecialchars($langText['amount']) ?></th>
          <th class="p-2 text-center">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($transactions)): ?>
          <tr>
            <td colspan="5" class="p-4 text-center"><?= htmlspecialchars($langText['no_transactions']) ?></td>
          </tr>
        <?php else: foreach ($transactions as $t): ?>
          <tr class="border-t">
            <td class="p-2"><?= date('d/m/Y', strtotime($t['date'])) ?></td>
            <td class="p-2"><?= htmlspecialchars($langText[$t['type']]) ?></td>
            <td class="p-2"><?= htmlspecialchars($t['category_name']) ?></td>
            <td class="p-2 text-right"><?= number_format((float)$t['amount'],2,',','.') ?></td>
            <td class="p-2 text-center">
              <button class="editBtn px-2 py-1 bg-yellow-400 text-white rounded text-sm"
                      data-id="<?= $t['id'] ?>">
                Editar
              </button>
              <a href="<?= $baseUrl ?>/finance/delete?id=<?= $t['id'] ?>"
                 class="px-2 py-1 bg-red-500 text-white rounded text-sm"
                 onclick="return confirm('<?= htmlspecialchars($langText['confirm_delete']) ?>')">
                Excluir
              </a>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Botão Nova Transação -->
  <button id="openTxModalBtn"
          class="fixed bottom-8 right-8 bg-green-500 text-white rounded-full p-4 shadow-lg hover:bg-green-600">
    <svg class="w-6 h-6" fill="none" stroke="currentColor"
         stroke-width="2" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round"
            d="M12 4v16m8-8H4"/>
    </svg>
  </button>

  <!-- Modal -->
  <div id="transactionModal"
       class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
    <div class="bg-white rounded-md p-6 w-full max-w-md mx-4 relative">
      <button id="closeTxModalBtn" class="absolute top-2 right-2 text-gray-700 text-2xl">&times;</button>
      <h3 id="txModalTitle" class="text-xl font-bold mb-4"></h3>

      <!-- Abas dentro do modal -->
      <div class="flex border-b mb-4">
        <button id="modalTabGenBtn"
                class="px-4 py-2 border-b-2 border-blue-600 text-blue-600">
          Geral
        </button>
        <button id="modalTabDebtBtn"
                class="px-4 py-2 text-gray-600 hidden">
          Parcelamento
        </button>
      </div>

      <form id="transactionForm" method="POST" enctype="multipart/form-data" class="space-y-4">
        <input type="hidden" name="id" id="txId">

        <!-- Aba Geral -->
        <div id="tabGeneral">
          <div>
            <label class="block mb-1"><?= htmlspecialchars($langText['type']) ?></label>
            <select name="type" id="txTypeSelect" required class="w-full border rounded p-2">
              <option value="income"><?= htmlspecialchars($langText['income']) ?></option>
              <option value="expense"><?= htmlspecialchars($langText['expense']) ?></option>
              <option value="debt"><?= htmlspecialchars($langText['debt']) ?></option>
            </select>
          </div>

          <div class="flex gap-4">
            <div class="flex-1">
              <label class="block mb-1"><?= htmlspecialchars($langText['date']) ?></label>
              <input type="date" name="date" id="txDateInput" required class="w-full border rounded p-2"/>
            </div>
            <div class="flex-1">
              <label class="block mb-1"><?= htmlspecialchars($langText['amount']) ?></label>
              <input type="number" step="0.01" name="amount" id="txAmountInput" required class="w-full border rounded p-2"/>
            </div>
          </div>

          <div>
            <label class="block mb-1"><?= htmlspecialchars($langText['category']) ?></label>
            <select name="category_id" id="txCategorySelect" required class="w-full border rounded p-2">
              <?php foreach($allCategories as $c): ?>
                <option
                  value="<?= $c['id'] ?>"
                  data-type="<?= $c['type'] ?>"
                  data-project="<?= $c['name']=== $projectCatName ? '1':'0' ?>">
                  <?= htmlspecialchars($c['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div id="projectContainer" class="hidden">
            <label class="block mb-1"><?= htmlspecialchars($langText['project']) ?></label>
            <select name="project_id" id="txProjectSelect" class="w-full border rounded p-2">
              <option value=""><?= htmlspecialchars($langText['select_project']) ?></option>
              <?php foreach($projects as $proj): ?>
                <option value="<?= $proj['id'] ?>"><?= htmlspecialchars($proj['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label class="block mb-1"><?= htmlspecialchars($langText['description']) ?></label>
            <textarea name="description" id="txDescInput" class="w-full border rounded p-2"></textarea>
          </div>

          <div>
            <label class="block mb-1"><?= htmlspecialchars($langText['attachments']) ?></label>
            <ul id="txAttachments" class="list-disc ml-5 text-sm text-blue-600"></ul>
            <input type="file" name="attachments[]" multiple class="w-full mt-1"/>
          </div>
        </div>

        <!-- Aba Parcelamento -->
        <div id="tabDebt" class="hidden space-y-2">
          <div class="flex items-center gap-2">
            <input type="checkbox" name="initial_payment" id="initialPaymentChk" class="h-4 w-4"/>
            <label for="initialPaymentChk"><?= htmlspecialchars($langText['initial_payment'] ?? 'Entrada') ?></label>
          </div>
          <p class="text-xs text-gray-600">
            <?= htmlspecialchars($langText['initial_payment_help']
                  ?? 'Marcar como entrada inicial; não afeta o cálculo das parcelas.') ?>
          </p>

          <div>
            <label class="block mb-1"><?= htmlspecialchars($langText['installments'] ?? 'Parcelas') ?></label>
            <select name="installments_count" id="installmentsSelect" class="w-full border rounded p-2">
              <option value="">
                <?= htmlspecialchars($langText['select_installments'] ?? 'Selecione nº de parcelas') ?>
              </option>
              <?php for($i=1;$i<=12;$i++): ?>
                <option value="<?= $i ?>"><?= $i ?>×</option>
              <?php endfor; ?>
            </select>
          </div>
          <p id="installmentInfo" class="text-sm text-gray-600"></p>

          <div>
            <label class="block mb-1"><?= htmlspecialchars($langText['due_date']) ?></label>
            <input type="date" name="due_date" id="txDueDateInput" class="w-full border rounded p-2"/>
          </div>
        </div>

        <!-- Botões Ações -->
        <div class="flex justify-end gap-2 mt-4">
          <button type="button" id="txCancelBtn" class="px-4 py-2 border rounded">
            <?= htmlspecialchars($langText['cancel']) ?>
          </button>
          <button type="submit" id="txSaveBtn" class="bg-green-500 text-white px-4 py-2 rounded">
            <?= htmlspecialchars($langText['save']) ?>
          </button>
        </div>
      </form>

      <a href="#" id="txDeleteLink" class="absolute bottom-6 left-6 text-red-600 hover:underline hidden">
        <?= htmlspecialchars($langText['delete']) ?>
      </a>
    </div>
  </div>
</main>

<script defer src="<?= $baseUrl ?>/js/finance.js"></script>
