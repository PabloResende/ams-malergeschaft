<?php
// app/views/finance/index.php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

$baseUrl        = '/ams-malergeschaft/public';
$type           = $_GET['type']        ?? '';
$start          = $_GET['start']       ?? date('Y-m-01');
$end            = $_GET['end']         ?? date('Y-m-d');
$cat            = $_GET['category_id'] ?? '';
$projectCatName = $langText['project_expense'] ?? 'Gastos com Projeto';

// Recebido do controller:
 // $transactions, $allCategories, $summary, $projects

require __DIR__ . '/../layout/header.php';
?>
<script>
  window.BASE_URL       = '<?= $baseUrl ?>';
  window.FINANCE_PREFIX = window.BASE_URL + '/finance';
  window.FINANCE_STR = {
    newTransaction:  <?= json_encode($langText['new_transaction']) ?>,
    editTransaction: <?= json_encode($langText['edit_transaction']  ?? 'Editar Transação') ?>,
    save:            <?= json_encode($langText['save']) ?>,
    saveChanges:     <?= json_encode($langText['save_changes'] ?? 'Salvar') ?>,
    confirmDelete:   <?= json_encode($langText['confirm_delete']) ?>
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

  <!-- Filtros -->
  <div class="flex flex-wrap items-center gap-3 mb-6">
    <form method="GET" action="<?= $baseUrl ?>/finance" class="flex flex-wrap gap-2">
      <input type="date" name="start" value="<?= htmlspecialchars($start) ?>" class="border rounded p-1"/>
      <input type="date" name="end"   value="<?= htmlspecialchars($end)   ?>" class="border rounded p-1"/>
      <select name="type" class="border rounded p-1">
        <option value=""><?= htmlspecialchars($langText['all_types']) ?></option>
        <option value="income"  <?= $type==='income'  ? 'selected':'' ?>><?= htmlspecialchars($langText['income']) ?></option>
        <option value="expense" <?= $type==='expense' ? 'selected':'' ?>><?= htmlspecialchars($langText['expense']) ?></option>
        <option value="debt"    <?= $type==='debt'    ? 'selected':'' ?>><?= htmlspecialchars($langText['debt']) ?></option>
      </select>
      <select name="category_id" class="border rounded p-1">
        <option value=""><?= htmlspecialchars($langText['all_categories']) ?></option>
        <?php foreach($allCategories as $c): ?>
          <option value="<?= htmlspecialchars($c['id'],ENT_QUOTES) ?>"
                  <?= $cat==$c['id']?'selected':''?>>
            <?= htmlspecialchars($c['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">
        <?= htmlspecialchars($langText['filter']) ?>
      </button>
    </form>
  </div>

  <!-- Tabela -->
  <div class="overflow-x-auto mb-6">
    <table class="w-full bg-white rounded shadow">
      <thead class="bg-gray-100">
        <tr>
          <th class="p-2 text-left"><?= htmlspecialchars($langText['date']) ?></th>
          <th class="p-2 text-left"><?= htmlspecialchars($langText['type']) ?></th>
          <th class="p-2 text-left"><?= htmlspecialchars($langText['category']) ?></th>
          <th class="p-2 text-right"><?= htmlspecialchars($langText['amount']) ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($transactions)): ?>
          <tr>
            <td colspan="4" class="p-4 text-center"><?= htmlspecialchars($langText['no_transactions']) ?></td>
          </tr>
        <?php else: foreach($transactions as $t): ?>
          <tr class="border-t tx-row cursor-pointer"
              data-tx-id="<?= htmlspecialchars($t['id'],ENT_QUOTES) ?>">
            <td class="p-2"><?= date('d/m/Y',strtotime($t['date'])) ?></td>
            <td class="p-2"><?= htmlspecialchars($langText[$t['type']] ?? '') ?></td>
            <td class="p-2"><?= htmlspecialchars($t['category_name']) ?></td>
            <td class="p-2 text-right"><?= number_format((float)$t['amount'],2,',','.') ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Botão Nova Transação -->
  <button id="openTxModalBtn"
          class="fixed bottom-8 right-8 bg-green-500 text-white rounded-full p-4 shadow-lg hover:bg-green-600">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
    </svg>
  </button>

  <!-- Modal -->
  <div id="transactionModal"
       class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-lg mx-4 overflow-hidden">
      <!-- Cabeçalho -->
      <div class="flex justify-between items-center px-6 py-4 border-b">
        <h3 id="txModalTitle" class="text-xl font-semibold"></h3>
        <button id="closeTxModalBtn" class="text-gray-600 hover:text-gray-800 text-2xl">&times;</button>
      </div>
      <!-- Abas -->
      <div class="flex border-b">
        <button id="tabGeneralBtn"
                class="flex-1 text-center px-4 py-2 border-b-2 border-blue-600 font-medium">
          Geral
        </button>
        <button id="tabDebtBtn" class="flex-1 text-center px-4 py-2 text-gray-500 hover:text-gray-700">
          Parcelamento
        </button>
      </div>
      <!-- Formulário -->
      <form id="transactionForm" class="px-6 py-4 space-y-6" enctype="multipart/form-data">
        <input type="hidden" name="id" id="txId"/>

        <!-- Aba Geral -->
        <div id="tabGeneral" class="space-y-4">
          <!-- Tipo -->
          <div>
            <label class="block mb-1 font-medium">Tipo</label>
            <select name="type" id="txTypeSelect" required class="w-full border rounded p-2">
              <option value="" disabled selected>Selecione</option>
              <option value="income">Entrada</option>
              <option value="expense">Saída</option>
              <option value="debt">Dívida</option>
            </select>
          </div>
          <!-- Data Transação ou Vencimento -->
          <div id="dateContainer">
            <label class="block mb-1 font-medium">Data da Transação</label>
            <input type="date" name="date" id="txDateInput" class="w-full border rounded p-2"/>
          </div>
          <div id="dueDateContainer" class="hidden">
            <label class="block mb-1 font-medium">Data de Vencimento</label>
            <input type="date" name="due_date" id="txDueDateInput" class="w-full border rounded p-2"/>
          </div>
          <!-- Valor -->
          <div>
            <label class="block mb-1 font-medium">Valor</label>
            <input type="number" step="0.01" name="amount" id="txAmountInput" required class="w-full border rounded p-2"/>
          </div>
          <!-- Categoria & Projeto -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block mb-1 font-medium">Categoria</label>
              <select name="category_id" id="txCategorySelect" required class="w-full border rounded p-2">
                <option value="" disabled selected>Selecione categoria</option>
                <?php foreach($allCategories as $c): ?>
                  <option value="<?= htmlspecialchars($c['id'],ENT_QUOTES) ?>"
                          data-project="<?= $c['name']===$projectCatName?'1':'0' ?>">
                    <?= htmlspecialchars($c['name']) ?>
                  </option>
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
          </div>
          <!-- Descrição -->
          <div>
            <label class="block mb-1 font-medium">Descrição</label>
            <textarea name="description" id="txDescInput" rows="3" class="w-full border rounded p-2"></textarea>
          </div>
          <!-- Anexos -->
          <div>
            <label class="block mb-1 font-medium">Anexos</label>
            <ul id="txAttachments" class="list-disc ml-5 text-sm text-blue-600"></ul>
            <input type="file" name="attachments[]" multiple class="w-full border rounded p-2"/>
          </div>
        </div>

        <!-- Aba Parcelamento -->
        <div id="tabDebt" class="hidden space-y-4">
          <div class="flex items-center gap-2">
            <input type="checkbox" id="initialPaymentChk" name="initial_payment" class="h-4 w-4"/>
            <label for="initialPaymentChk" class="font-medium">Entrada inicial</label>
          </div>
          <div id="initialPaymentContainer" class="hidden">
            <label class="block mb-1 font-medium">Valor da entrada</label>
            <input type="number" step="0.01" id="initialPaymentAmt" name="initial_payment_amount" class="w-full border rounded p-2"/>
          </div>
          <div>
            <label class="block mb-1 font-medium">Número de parcelas</label>
            <select id="installmentsSelect" name="installments_count" class="w-full border rounded p-2">
              <option value="" disabled selected>Selecione</option>
              <?php for($i=1;$i<=12;$i++): ?>
                <option value="<?= $i ?>"><?= $i ?>×</option>
              <?php endfor; ?>
            </select>
          </div>
          <div id="installmentInfo" class="text-sm text-gray-600"></div>
        </div>

        <!-- Ações -->
        <div class="flex justify-end gap-2 pt-4 border-t">
          <button type="button" id="txCancelBtn" class="px-4 py-2 border rounded hover:bg-gray-100">
            Cancelar
          </button>
          <button type="submit" id="txSaveBtn" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
            Salvar
          </button>
        </div>
      </form>

      <!-- Excluir -->
      <button id="txDeleteLink" class="absolute bottom-4 left-6 text-red-600 hover:underline hidden">
        Excluir
      </button>
    </div>
  </div>
</main>

<script defer src="<?= $baseUrl ?>/js/finance.js"></script>
