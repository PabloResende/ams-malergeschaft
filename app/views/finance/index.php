<?php
// app/views/finance/index.php

// 1) inclui header (define $langText, $baseUrl, e disponibiliza as variáveis:
//    $transactions, $allCategories, $summary, $start, $end, $type, $cat)
require __DIR__ . '/../layout/header.php';
?>
<script>
  // rota base e legendas para o JS externo
  window.FINANCE_PREFIX = '<?= $baseUrl ?>/finance';
  window.FINANCE_STR = {
    newTransaction: <?= json_encode($langText['new_transaction']) ?>,
    save:           <?= json_encode($langText['save']) ?>,
    editTransaction:<?= json_encode($langText['edit_transaction']  ?? 'Editar Transação') ?>,
    saveChanges:    <?= json_encode($langText['save_changes']      ?? 'Salvar') ?>
  };
</script>

<main class="md:pl-56 pt-20 p-6">
  <!-- Título -->
  <h1 class="text-3xl font-bold mb-6"><?= $langText['finance'] ?></h1>

  <!-- Resumo -->
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
    <div class="p-4 bg-green-100 rounded">
      <p class="text-sm"><?= $langText['total_income'] ?></p>
      <p class="text-2xl font-bold"><?= number_format($summary['income'],2,',','.') ?></p>
    </div>
    <div class="p-4 bg-red-100 rounded">
      <p class="text-sm"><?= $langText['total_expense'] ?></p>
      <p class="text-2xl font-bold"><?= number_format($summary['expense'],2,',','.') ?></p>
    </div>
    <div class="p-4 bg-gray-100 rounded">
      <p class="text-sm"><?= $langText['net_balance'] ?></p>
      <p class="text-2xl font-bold"><?= number_format($summary['net'],2,',','.') ?></p>
    </div>
  </div>

  <!-- Filtros + Ações -->
  <div class="flex flex-wrap items-center gap-3 mb-6">
    <form method="GET" action="<?= $baseUrl ?>/finance" class="flex flex-wrap gap-2">
      <input type="date" name="start" value="<?= htmlspecialchars($start) ?>" class="border rounded p-1"/>
      <input type="date" name="end"   value="<?= htmlspecialchars($end)   ?>" class="border rounded p-1"/>
      <select name="type" class="border rounded p-1">
        <option value=""><?= $langText['all_types'] ?></option>
        <option value="income"  <?= $type==='income'  ? 'selected':'' ?>><?= $langText['income'] ?></option>
        <option value="expense" <?= $type==='expense' ? 'selected':'' ?>><?= $langText['expense'] ?></option>
        <option value="debt"    <?= $type==='debt'    ? 'selected':'' ?>><?= $langText['debt'] ?></option>
      </select>
      <select name="category_id" class="border rounded p-1">
        <option value=""><?= $langText['all_categories'] ?></option>
        <?php foreach($allCategories as $c): ?>
          <?php if ($type === '' || $c['type'] === $type): ?>
            <option value="<?= $c['id'] ?>" data-type="<?= $c['type'] ?>" <?= $cat==$c['id']?'selected':''?>>
              <?= htmlspecialchars($c['name']) ?>
            </option>
          <?php endif; ?>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">
        <?= $langText['filter'] ?>
      </button>
    </form>

    <a href="<?= $baseUrl ?>/finance/report?start=<?= $start ?>&end=<?= $end ?>"
       class="px-3 py-1 bg-gray-700 text-white rounded hover:bg-gray-800">
      <?= $langText['report'] ?>
    </a>

    <a href="<?= $baseUrl ?>/calendar"
       class="px-3 py-1 bg-indigo-500 text-white rounded hover:bg-indigo-600 flex items-center gap-1">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
           viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M8 7V3m8 4V3m-9 8h10m-2 8H7a2 2 0 
                 01-2-2V9a2 2 0 012-2h10a2 2 0 
                 012 2v10a2 2 0 01-2 2z"/>
      </svg>
      <?= $langText['calendar'] ?>
    </a>
  </div>

  <!-- Tabela de Transações -->
  <div class="overflow-x-auto mb-6">
    <table class="w-full bg-white rounded shadow">
      <thead class="bg-gray-100">
        <tr>
          <th class="p-2 text-left"><?= $langText['date'] ?></th>
          <th class="p-2 text-left"><?= $langText['type'] ?></th>
          <th class="p-2 text-left"><?= $langText['category'] ?></th>
          <th class="p-2 text-right"><?= $langText['amount'] ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($transactions)): ?>
          <tr>
            <td colspan="4" class="p-4 text-center"><?= $langText['no_transactions'] ?></td>
          </tr>
        <?php else: ?>
          <?php foreach ($transactions as $t): ?>
            <tr class="border-t tx-row cursor-pointer" data-tx-id="<?= $t['id'] ?>">
              <td class="p-2"><?= date('d/m/Y', strtotime($t['date'])) ?></td>
              <td class="p-2"><?= htmlspecialchars($langText[$t['type']]) ?></td>
              <td class="p-2"><?= htmlspecialchars($t['category_name']) ?></td>
              <td class="p-2 text-right"><?= number_format($t['amount'],2,',','.') ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Botão Nova Transação -->
  <button id="openTxModalBtn"
          class="fixed bottom-8 right-8 bg-green-500 text-white rounded-full p-4 shadow-lg hover:bg-green-600">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
    </svg>
  </button>

  <!-- Modal de Detalhes / Criação -->
  <div id="transactionModal"
       class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
    <div class="bg-white rounded-md p-6 w-full max-w-md mx-4 relative">
      <button id="closeTxModalBtn" class="absolute top-2 right-2 text-gray-700 text-2xl">&times;</button>
      <h3 id="txModalTitle" class="text-xl font-bold mb-4"></h3>

      <form id="transactionForm" method="POST" enctype="multipart/form-data" class="space-y-4">
        <input type="hidden" name="id" id="txId">

        <div>
          <label class="block mb-1"><?= $langText['type'] ?></label>
          <select name="type" id="txTypeSelect" required class="w-full border rounded p-2">
            <option value="income"><?= $langText['income'] ?></option>
            <option value="expense"><?= $langText['expense'] ?></option>
            <option value="debt"><?= $langText['debt'] ?></option>
          </select>
        </div>

        <div>
          <label class="block mb-1"><?= $langText['date'] ?></label>
          <input type="date" name="date" id="txDateInput" required class="w-full border rounded p-2"/>
        </div>

        <div>
          <label class="block mb-1"><?= $langText['category'] ?></label>
          <select name="category_id" id="txCategorySelect" required class="w-full border rounded p-2">
            <?php foreach ($allCategories as $c): ?>
              <option value="<?= $c['id'] ?>" data-type="<?= $c['type'] ?>">
                <?= htmlspecialchars($c['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="block mb-1"><?= $langText['amount'] ?></label>
          <input type="number" step="0.01" name="amount" id="txAmountInput" required class="w-full border rounded p-2"/>
        </div>

        <div>
          <label class="block mb-1"><?= $langText['description'] ?></label>
          <textarea name="description" id="txDescInput" class="w-full border rounded p-2"></textarea>
        </div>

        <div id="dueDateContainer" class="hidden">
          <label class="block mb-1"><?= $langText['due_date'] ?></label>
          <input type="date" name="due_date" id="txDueDateInput" class="w-full border rounded p-2"/>
        </div>

        <div>
          <label class="block mb-1"><?= $langText['attachments'] ?></label>
          <ul id="txAttachments" class="list-disc ml-5 text-sm text-blue-600"></ul>
          <input type="file" name="attachments[]" multiple class="w-full mt-1"/>
        </div>

        <div class="flex justify-end gap-2">
          <button type="button" id="txCancelBtn" class="px-4 py-2 border rounded"><?= $langText['cancel'] ?></button>
          <button type="submit" id="txSaveBtn" class="bg-green-500 text-white px-4 py-2 rounded"><?= $langText['save'] ?></button>
        </div>
      </form>
    </div>
  </div>
</main>

<!-- scripts -->
<script defer src="<?= $baseUrl ?>/js/finance.js"></script>
