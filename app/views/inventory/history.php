<?php
// app/views/inventory/history.php
require_once __DIR__ . '/../layout/header.php';
$baseUrl = '/ams-malergeschaft/public';
?>
<div class="ml-56 pt-20 p-8">
  <h2 class="text-2xl font-bold mb-4">Histórico de Movimentações</h2>
  <ul id="historyList" class="space-y-2">
    <?php foreach ($movements as $m):
      $color = $m['reason'] === 'projeto'
             ? 'bg-green-200'
             : ($m['reason'] === 'perda'
                ? 'bg-red-200'
                : 'bg-yellow-200');
    ?>
      <li class="p-4 rounded <?= $color ?> cursor-pointer history-item"
          data-id="<?= $m['id'] ?>">
        <strong><?= htmlspecialchars($m['user_name'], ENT_QUOTES, 'UTF-8') ?></strong>
        — <?= $m['datetime'] ?>
        (<?= ucfirst($m['reason']) ?>)
      </li>
    <?php endforeach; ?>
  </ul>

  <div id="historyDetails" class="mt-6 hidden p-4 bg-white border rounded shadow"></div>
</div>

<script>
document.querySelectorAll('.history-item').forEach(li => {
  li.addEventListener('click', () => {
    const id = li.dataset.id;
    fetch('<?= $baseUrl ?>/inventory/history/details?id=' + id)
      .then(res => res.json())
      .then(data => {
        let html = `<h4 class="font-semibold mb-2">Detalhes (#${id})</h4><ul class="list-disc pl-5">`;
        data.forEach(d => {
          html += `<li>${d.item_name}: ${d.quantity}</li>`;
        });
        html += '</ul>';
        const det = document.getElementById('historyDetails');
        det.innerHTML = html;
        det.classList.remove('hidden');
      });
  });
});
</script>
