<?php
// app/views/finance/index.php
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../../config/Database.php';

$pdo = Database::connect();
$baseUrl = '/ams-malergeschaft/public';

// 1) Carrega categorias para filtro e select do modal
$categories = $pdo
  ->query("SELECT id, name, type FROM finance_categories ORDER BY name")
  ->fetchAll(PDO::FETCH_ASSOC);

// 2) Lê filtros de GET
$start = $_GET['start'] ?? date('Y-m-01');
$end   = $_GET['end']   ?? date('Y-m-d');
$type  = $_GET['type']  ?? '';
$cat   = $_GET['category_id'] ?? '';

// 3) Busca transações conforme filtros
$sql  = "
  SELECT ft.*, fc.name AS category_name
  FROM financial_transactions ft
  JOIN finance_categories fc ON ft.category_id = fc.id
  WHERE ft.date BETWEEN ? AND ?
";
$params = [$start, $end];
if ($type) {
  $sql .= " AND ft.type = ?";
  $params[] = $type;
}
if ($cat) {
  $sql .= " AND ft.category_id = ?";
  $params[] = $cat;
}
$sql .= " ORDER BY ft.date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4) Calcula resumo de entradas/saídas
$inStmt = $pdo->prepare("
  SELECT SUM(amount) FROM financial_transactions
  WHERE type = 'income' AND date BETWEEN ? AND ?
");
$exStmt = $pdo->prepare("
  SELECT SUM(amount) FROM financial_transactions
  WHERE type = 'expense' AND date BETWEEN ? AND ?
");
$inStmt->execute([$start, $end]);
$exStmt->execute([$start, $end]);
$income  = $inStmt->fetchColumn() ?: 0;
$expense = $exStmt->fetchColumn() ?: 0;
$net     = $income - $expense;
?>

<div class="ml-56 pt-20 p-6 relative">
  <h1 class="text-3xl font-bold mb-6"><?= $langText['finance'] ?? 'Financeiro' ?></h1>

  <!-- filtros -->
  <form method="GET" action="<?= $baseUrl ?>/finance" class="flex flex-wrap items-center gap-2 mb-6">
    <input type="date"   name="start" value="<?= htmlspecialchars($start) ?>"
           class="border rounded p-1"/>
    <input type="date"   name="end"   value="<?= htmlspecialchars($end) ?>"
           class="border rounded p-1"/>
    <select name="type" class="border rounded p-1">
      <option value=""><?= $langText['all_types'] ?? 'Todos Tipos' ?></option>
      <option value="income"  <?= $type==='income' ? 'selected':'' ?>><?= $langText['income']  ?? 'Entrada' ?></option>
      <option value="expense" <?= $type==='expense'? 'selected':'' ?>><?= $langText['expense'] ?? 'Saída' ?></option>
      <option value="debt"    <?= $type==='debt'   ? 'selected':'' ?>><?= $langText['debt']    ?? 'Dívida' ?></option>
    </select>
    <select name="category_id" class="border rounded p-1">
      <option value=""><?= $langText['all_categories'] ?? 'Todas Categorias' ?></option>
      <?php foreach($categories as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $cat == $c['id'] ? 'selected':'' ?>>
          <?= htmlspecialchars($c['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <button type="submit"
            class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">
      <?= $langText['filter'] ?? 'Filtrar' ?>
    </button>
  </form>

  <!-- resumo -->
  <div class="grid grid-cols-3 gap-4 mb-6">
    <div class="p-4 bg-green-100 rounded">
      <p class="text-sm"><?= $langText['total_income'] ?? 'Total Entradas' ?></p>
      <p class="text-xl font-bold"><?= number_format($income, 2, ',', '.') ?></p>
    </div>
    <div class="p-4 bg-red-100 rounded">
      <p class="text-sm"><?= $langText['total_expense'] ?? 'Total Saídas' ?></p>
      <p class="text-xl font-bold"><?= number_format($expense, 2, ',', '.') ?></p>
    </div>
    <div class="p-4 bg-gray-100 rounded">
      <p class="text-sm"><?= $langText['net_balance'] ?? 'Saldo Líquido' ?></p>
      <p class="text-xl font-bold"><?= number_format($net, 2, ',', '.') ?></p>
    </div>
  </div>

  <!-- tabela de transações -->
  <table class="w-full bg-white rounded shadow overflow-hidden">
    <thead class="bg-gray-100">
      <tr>
        <th class="p-2"><?= $langText['date'] ?? 'Data' ?></th>
        <th class="p-2"><?= $langText['type'] ?? 'Tipo' ?></th>
        <th class="p-2"><?= $langText['category'] ?? 'Categoria' ?></th>
        <th class="p-2 text-right"><?= $langText['amount'] ?? 'Valor' ?></th>
        <th class="p-2"><?= $langText['actions'] ?? 'Ações' ?></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($transactions)): ?>
        <tr><td colspan="5" class="p-4 text-center"><?= $langText['no_transactions'] ?? 'Nenhuma transação.' ?></td></tr>
      <?php else: ?>
        <?php foreach($transactions as $t): ?>
        <tr class="border-t">
          <td class="p-2"><?= date('d/m/Y', strtotime($t['date'])) ?></td>
          <td class="p-2"><?= htmlspecialchars($langText[$t['type']] ?? ucfirst($t['type'])) ?></td>
          <td class="p-2"><?= htmlspecialchars($t['category_name']) ?></td>
          <td class="p-2 text-right"><?= number_format($t['amount'], 2, ',', '.') ?></td>
          <td class="p-2 space-x-2">
            <a href="<?= $baseUrl ?>/finance/edit?id=<?= $t['id'] ?>"
               class="text-blue-500 hover:underline"><?= $langText['edit'] ?? 'Editar' ?></a>
            <a href="<?= $baseUrl ?>/finance/delete?id=<?= $t['id'] ?>"
               onclick="return confirm('<?= $langText['confirm_delete'] ?? 'Excluir esta transação?' ?>')"
               class="text-red-500 hover:underline"><?= $langText['delete'] ?? 'Excluir' ?></a>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <!-- botão flutuante nova transação -->
  <button id="addTransactionBtn"
          class="fixed bottom-8 right-8 bg-green-500 text-white rounded-full p-4 shadow-lg hover:bg-green-600">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
    </svg>
  </button>

  <!-- modal nova transação -->
  <div id="transactionModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded p-8 w-full max-w-md relative">
      <button id="closeTransactionModal" class="absolute top-4 right-4 text-gray-700 text-2xl">&times;</button>
      <h2 class="text-2xl font-bold mb-4"><?= $langText['new_transaction'] ?? 'Nova Transação' ?></h2>
      <form action="<?= $baseUrl ?>/finance/store" method="POST" enctype="multipart/form-data" class="space-y-4">
        <div>
          <label class="block"><?= $langText['type'] ?? 'Tipo' ?></label>
          <select name="type" id="txTypeSelect" required class="w-full border rounded p-2">
            <option value="income"><?= $langText['income']  ?? 'Entrada' ?></option>
            <option value="expense"><?= $langText['expense'] ?? 'Saída' ?></option>
            <option value="debt"><?= $langText['debt'] ?? 'Dívida' ?></option>
          </select>
        </div>
        <div>
          <label class="block"><?= $langText['date'] ?? 'Data' ?></label>
          <input type="date" name="date" required value="<?= date('Y-m-d') ?>"
                 class="w-full border rounded p-2"/>
        </div>
        <div>
          <label class="block"><?= $langText['category'] ?? 'Categoria' ?></label>
          <select name="category_id" required class="w-full border rounded p-2">
            <?php foreach($categories as $c): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block"><?= $langText['amount'] ?? 'Valor' ?></label>
          <input type="number" step="0.01" name="amount" required
                 class="w-full border rounded p-2"/>
        </div>
        <div id="dueDateContainer" class="hidden">
          <label class="block"><?= $langText['due_date'] ?? 'Data de Vencimento' ?></label>
          <input type="date" name="due_date"
                 class="w-full border rounded p-2"/>
        </div>
        <div>
          <label class="block"><?= $langText['description'] ?? 'Descrição' ?></label>
          <textarea name="description" class="w-full border rounded p-2"></textarea>
        </div>
        <div>
          <label class="block"><?= $langText['attachments'] ?? 'Anexar Comprovantes' ?></label>
          <input type="file" name="attachments[]" multiple class="w-full"/>
        </div>
        <div class="flex justify-end">
          <button type="button" id="cancelTransactionBtn"
                  class="mr-2 px-4 py-2 border rounded"><?= $langText['cancel'] ?? 'Cancelar' ?></button>
          <button type="submit"
                  class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
            <?= $langText['save'] ?? 'Salvar' ?>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script defer src="<?= $baseUrl ?>/js/finance.js"></script>
