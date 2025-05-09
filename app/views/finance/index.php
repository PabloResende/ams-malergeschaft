<?php
// app/views/finance/index.php

// 1) inclui header (define $langText)  
require __DIR__ . '/../layout/header.php';

// 2) Conexão com o banco  
$pdo     = Database::connect();  
$baseUrl = '/ams-malergeschaft/public';

// 3) Carrega categorias para filtro e para o <select> do modal  
$categories = $pdo  
    ->query("SELECT id, name, type FROM finance_categories ORDER BY name")  
    ->fetchAll(PDO::FETCH_ASSOC);

// 4) Lê filtros de GET  
$start = $_GET['start']       ?? date('Y-m-01');  
$end   = $_GET['end']         ?? date('Y-m-d');  
$type  = $_GET['type']        ?? '';  
$cat   = $_GET['category_id'] ?? '';

// 5) Busca transações conforme filtros  
$sql    = "
    SELECT ft.*, fc.name AS category_name
    FROM financial_transactions ft
    JOIN finance_categories fc ON ft.category_id = fc.id
    WHERE ft.date BETWEEN ? AND ?
";
$params = [ $start, $end ];
if ($type) {
    $sql      .= " AND ft.type = ?";
    $params[] = $type;
}
if ($cat) {
    $sql      .= " AND ft.category_id = ?";
    $params[] = $cat;
}
$sql .= " ORDER BY ft.date DESC";

$stmt         = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 6) Calcula resumo de entradas/saídas  
$inStmt  = $pdo->prepare("
    SELECT COALESCE(SUM(amount),0)
    FROM financial_transactions
    WHERE type = 'income' AND date BETWEEN ? AND ?
");
$exStmt  = $pdo->prepare("
    SELECT COALESCE(SUM(amount),0)
    FROM financial_transactions
    WHERE type = 'expense' AND date BETWEEN ? AND ?
");
$inStmt->execute([ $start, $end ]);
$exStmt->execute([ $start, $end ]);
$income  = $inStmt->fetchColumn();
$expense = $exStmt->fetchColumn();
$net     = $income - $expense;
?>

<div class="ml-56 pt-20 p-6 relative">
  <h1 class="text-3xl font-bold mb-6"><?= $langText['finance'] ?? 'Financeiro' ?></h1>

  <!-- Filtros + Botões -->
  <div class="flex flex-wrap items-center gap-3 mb-6">
    <form method="GET" action="<?= $baseUrl ?>/finance" class="flex flex-wrap gap-2">
      <input type="date" name="start" value="<?= htmlspecialchars($start) ?>" class="border rounded p-1"/>
      <input type="date" name="end"   value="<?= htmlspecialchars($end)   ?>" class="border rounded p-1"/>
      <select name="type" class="border rounded p-1">
        <option value=""><?= $langText['all_types'] ?? 'Todos Tipos' ?></option>
        <option value="income"  <?= $type==='income'  ? 'selected':'' ?>><?= $langText['income']  ?? 'Entrada' ?></option>
        <option value="expense" <?= $type==='expense' ? 'selected':'' ?>><?= $langText['expense'] ?? 'Saída' ?></option>
        <option value="debt"    <?= $type==='debt'    ? 'selected':'' ?>><?= $langText['debt']    ?? 'Dívida' ?></option>
      </select>
      <select name="category_id" class="border rounded p-1">
        <option value=""><?= $langText['all_categories'] ?? 'Todas Categorias' ?></option>
        <?php foreach ($categories as $c): ?>
          <?php if ($type === '' || $c['type'] === $type): ?>
            <option value="<?= $c['id'] ?>" data-type="<?= $c['type'] ?>"
              <?= $cat == $c['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['name']) ?>
            </option>
          <?php endif; ?>
        <?php endforeach; ?>
      </select>
      <button type="submit"
              class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">
        <?= $langText['filter'] ?? 'Filtrar' ?>
      </button>
    </form>

    <a href="<?= $baseUrl ?>/finance/report?start=<?= $start ?>&end=<?= $end ?>"
       class="px-3 py-1 bg-gray-700 text-white rounded hover:bg-gray-800">
      <?= $langText['report'] ?? 'Relatório' ?>
    </a>

    <a href="<?= $baseUrl ?>/calendar"
       class="px-3 py-1 bg-indigo-500 text-white rounded hover:bg-indigo-600 flex items-center gap-1">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
           viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M8 7V3m8 4V3m-9 8h10m-2 8H7a2 2 0 01-2-2V9a2 2 0 
                 012-2h10a2 2 0 012 2v10a2 2 0 01-2 2z"/>
      </svg>
      <?= $langText['calendar'] ?? 'Calendário' ?>
    </a>
  </div>

  <!-- Resumo -->
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
    <div class="p-4 bg-green-100 rounded">
      <p class="text-sm"><?= $langText['total_income']   ?? 'Total Entradas' ?></p>
      <p class="text-2xl font-bold"><?= number_format($income,  2, ',', '.') ?></p>
    </div>
    <div class="p-4 bg-red-100 rounded">
      <p class="text-sm"><?= $langText['total_expense']  ?? 'Total Saídas' ?></p>
      <p class="text-2xl font-bold"><?= number_format($expense, 2, ',', '.') ?></p>
    </div>
    <div class="p-4 bg-gray-100 rounded">
      <p class="text-sm"><?= $langText['net_balance']    ?? 'Saldo Líquido' ?></p>
      <p class="text-2xl font-bold"><?= number_format($net,     2, ',', '.') ?></p>
    </div>
  </div>

  <!-- Tabela de Transações -->
  <div class="overflow-x-auto mb-6">
    <table class="w-full bg-white rounded shadow">
      <thead class="bg-gray-100">
        <tr>
          <th class="p-2 text-left"><?= $langText['date']    ?? 'Data' ?></th>
          <th class="p-2 text-left"><?= $langText['type']    ?? 'Tipo' ?></th>
          <th class="p-2 text-left"><?= $langText['category']?? 'Categoria' ?></th>
          <th class="p-2 text-right"><?= $langText['amount']  ?? 'Valor' ?></th>
          <th class="p-2 text-left"><?= $langText['actions'] ?? 'Ações' ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($transactions)): ?>
          <tr>
            <td colspan="5" class="p-4 text-center">
              <?= $langText['no_transactions'] ?? 'Nenhuma transação.' ?>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($transactions as $t): ?>
            <tr class="border-t">
              <td class="p-2"><?= date('d/m/Y', strtotime($t['date'])) ?></td>
              <td class="p-2"><?= htmlspecialchars($langText[$t['type']] ?? ucfirst($t['type'])) ?></td>
              <td class="p-2"><?= htmlspecialchars($t['category_name']) ?></td>
              <td class="p-2 text-right"><?= number_format($t['amount'], 2, ',', '.') ?></td>
              <td class="p-2 space-x-2">
                <a href="<?= $baseUrl ?>/finance/edit?id=<?= $t['id'] ?>"
                   class="text-blue-500 hover:underline">
                  <?= $langText['edit'] ?? 'Editar' ?>
                </a>
                <a href="<?= $baseUrl ?>/finance/delete?id=<?= $t['id'] ?>"
                   onclick="return confirm('<?= $langText['confirm_delete'] ?? 'Excluir esta transação?' ?>')"
                   class="text-red-500 hover:underline">
                  <?= $langText['delete'] ?? 'Excluir' ?>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Botão flutuante de nova transação -->
  <button id="addTransactionBtn"
          class="fixed bottom-8 right-8 bg-green-500 text-white rounded-full p-4 shadow-lg hover:bg-green-600">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
    </svg>
  </button>

  <!-- Modal Nova Transação -->
  <div id="transactionModal"
       class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-md p-6 w-full max-w-md mx-4 relative">
      <button id="closeTransactionModal"
              class="absolute top-2 right-2 text-gray-700 text-2xl">&times;</button>
      <h3 class="text-xl font-bold mb-4"><?= $langText['new_transaction'] ?? 'Nova Transação' ?></h3>
      <form id="transactionForm" action="<?= $baseUrl ?>/finance/store" method="POST"
            enctype="multipart/form-data" class="space-y-4">
        <div>
          <label class="block mb-1"><?= $langText['type'] ?? 'Tipo' ?></label>
          <select name="type" id="txTypeSelect" required
                  class="w-full border rounded p-2">
            <option value="income"><?= $langText['income'] ?? 'Entrada' ?></option>
            <option value="expense"><?= $langText['expense'] ?? 'Saída' ?></option>
            <option value="debt"><?= $langText['debt'] ?? 'Dívida' ?></option>
          </select>
        </div>
        <div>
          <label class="block mb-1"><?= $langText['date'] ?? 'Data' ?></label>
          <input type="date" name="date" required value="<?= date('Y-m-d') ?>"
                 class="w-full border rounded p-2"/>
        </div>
        <div>
          <label class="block mb-1"><?= $langText['category'] ?? 'Categoria' ?></label>
          <select name="category_id" required class="w-full border rounded p-2">
            <?php foreach ($categories as $c): ?>
              <option value="<?= $c['id'] ?>" data-type="<?= $c['type'] ?>">
                <?= htmlspecialchars($c['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block mb-1"><?= $langText['amount'] ?? 'Valor' ?></label>
          <input type="number" step="0.01" name="amount" required
                 class="w-full border rounded p-2"/>
        </div>
        <div>
          <label class="block mb-1"><?= $langText['description'] ?? 'Descrição' ?></label>
          <textarea name="description" class="w-full border rounded p-2"></textarea>
        </div>
        <div>
          <label class="block mb-1"><?= $langText['attachments'] ?? 'Anexar Comprovantes' ?></label>
          <input type="file" name="attachments[]" multiple class="w-full"/>
        </div>
        <div id="dueDateContainer" class="hidden">
          <label class="block mb-1"><?= $langText['due_date'] ?? 'Vencimento' ?></label>
          <input type="date" name="due_date" class="w-full border rounded p-2"/>
        </div>
        <div class="flex justify-end gap-2">
          <button type="button" id="cancelTransaction"
                  class="px-4 py-2 border rounded">
            <?= $langText['cancel'] ?? 'Cancelar' ?>
          </button>
          <button type="submit"
                  class="bg-green-500 text-white px-4 py-2 rounded">
            <?= $langText['save'] ?? 'Salvar' ?>
          </button>
        </div>
      </form>
    </div>
  </div>

</div>

<script defer src="<?= $baseUrl ?>/js/finance.js"></script>
<script src="<?= $baseUrl ?>/js/header.js"></script>
</main>
