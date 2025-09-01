<?php

global $pdo, $langText;

// --- helpers ---
if (! function_exists('addNotif')) {
    function addNotif(array &$arr, string $key, string $text, string $url): void {
        $arr[] = compact('key','text','url');
    }
}

$notifications = [];

// --- Materiais com estoque baixo ---
$faixas = [100,75,50,25,10,5,1,0];
$stmt = $pdo->query("
    SELECT id,name,quantity
      FROM inventory
     WHERE type='material' AND quantity<100
");
while ($it = $stmt->fetch(PDO::FETCH_ASSOC)) {
    foreach ($faixas as $lim) {
        if ($it['quantity'] <= $lim) {
            $key  = "inventory_{$it['id']}_q{$lim}";
            if ($it['quantity'] === 0) {
                $text = sprintf($langText['notif_inventory_empty'], $it['name']);
            } else {
                $text = sprintf(
                    $langText['notif_inventory_low'],
                    $it['quantity'],
                    $it['name'],
                    $lim
                );
            }
            $url = BASE_URL."/inventory?show={$it['id']}";
            addNotif($notifications, $key, $text, $url);
            break;
        }
    }
}

// --- Projetos vencendo em até 7 dias ---
$hoje  = date('Y-m-d');
$lim   = date('Y-m-d', strtotime('+7 days'));
$stmt  = $pdo->prepare("
    SELECT id,name,end_date
      FROM projects
     WHERE end_date BETWEEN ? AND ?
");
$stmt->execute([$hoje,$lim]);
while ($pj = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $dias = (int)((strtotime($pj['end_date']) - strtotime($hoje)) / 86400);
    if ($dias < 0) continue;

    switch ($dias) {
      case 0:
        $msg = sprintf($langText['notif_project_due_0'], $pj['name']);
        break;
      case 1:
        $msg = sprintf($langText['notif_project_due_1'], $pj['name']);
        break;
      default:
        $msg = sprintf($langText['notif_project_due_x'], $pj['name'], $dias);
    }

    $key = "project_{$pj['id']}_d{$dias}";
    $url = BASE_URL."/projects?show={$pj['id']}";
    addNotif($notifications, $key, $msg, $url);
}

// --- Clientes com múltiplos de 5 projetos ---
$stmt = $pdo->query("
     SELECT c.id,c.name,COUNT(p.id) AS cnt
       FROM client c
  LEFT JOIN projects p ON p.client_id=c.id
   GROUP BY c.id
  HAVING cnt>=5
");
while ($c = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $qtd = (int)$c['cnt'];
    $faixa = floor($qtd/5)*5;
    $key  = "client_{$c['id']}_p{$faixa}";
    $msg  = sprintf($langText['notif_client_projects'], $c['name'], $qtd);
    $url  = BASE_URL."/clients?show={$c['id']}";
    addNotif($notifications, $key, $msg, $url);
}

return $notifications;
