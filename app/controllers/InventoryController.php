<?php
// app/controllers/InventoryController.php

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../models/Inventory.php';
require_once __DIR__ . '/../models/InventoryHistoryModel.php';
require_once __DIR__ . '/../models/Project.php';

class InventoryController {
    private InventoryModel        $inventoryModel;
    private InventoryHistoryModel $historyModel;

    public function __construct() {
        $this->inventoryModel = new InventoryModel();
        $this->historyModel   = new InventoryHistoryModel();
    }

    /**
     * GET /inventory
     */
    public function index(): void {
        $filter         = $_GET['filter'] ?? 'all';
        $inventoryItems = $this->inventoryModel->getAll($filter);
        $allItems       = $this->inventoryModel->getAll('all');
        $activeProjects = ProjectModel::getActiveProjects();
        $movements      = $this->historyModel->getAllMovements();
        require_once __DIR__ . '/../views/inventory/index.php';
    }

    /**
     * GET /inventory/create
     */
    public function create(): void {
        require_once __DIR__ . '/../views/inventory/create.php';
    }

    /**
     * POST /inventory/store
     */
    public function store(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /ams-malergeschaft/public/inventory");
            exit;
        }
        $type     = $_POST['type']     ?? '';
        $name     = trim($_POST['name']     ?? '');
        $quantity = (int)($_POST['quantity'] ?? 0);

        if ($name === '') {
            echo "Nome do item obrigatório.";
            return;
        }

        $this->inventoryModel->insert($type, $name, $quantity);
        header("Location: /ams-malergeschaft/public/inventory");
        exit;
    }

    /**
     * GET /inventory/edit?id=...
     */
    public function edit(): void {
        if (!isset($_GET['id'])) {
            echo "Inventory item ID não fornecido.";
            exit;
        }
        $item = $this->inventoryModel->getById((int)$_GET['id']);
        if (!$item) {
            echo "Item não encontrado.";
            exit;
        }
        require_once __DIR__ . '/../views/inventory/edit.php';
    }

    /**
     * POST /inventory/update
     */
    public function update(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /ams-malergeschaft/public/inventory");
            exit;
        }
        $id       = (int)($_POST['id']       ?? 0);
        $type     = $_POST['type']         ?? '';
        $name     = trim($_POST['name']     ?? '');
        $quantity = (int)($_POST['quantity'] ?? 0);

        if ($id < 1 || $name === '') {
            echo "Dados obrigatórios faltando.";
            return;
        }

        $this->inventoryModel->update($id, $type, $name, $quantity);
        header("Location: /ams-malergeschaft/public/inventory");
        exit;
    }

    /**
     * GET /inventory/delete?id=...
     */
    public function delete(): void {
        if (!isset($_GET['id'])) {
            echo "Inventory item ID não fornecido.";
            exit;
        }
        $this->inventoryModel->delete((int)$_GET['id']);
        header("Location: /ams-malergeschaft/public/inventory");
        exit;
    }

    /**
     * POST /inventory/control/store
     */
    public function storeControl(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /ams-malergeschaft/public/inventory");
            exit;
        }

        $user       = trim($_POST['user_name']     ?? '');
        $rawDate    = trim($_POST['datetime']      ?? '');
        $reason     = $_POST['reason']             ?? '';
        $project_id = $_POST['project_id'] ?: null;
        $custom     = trim($_POST['custom_reason'] ?? '');
        $itemsJson  = $_POST['items']              ?? '[]';
        $data       = json_decode($itemsJson, true);

        if ($user === '') {
            echo "O nome do usuário é obrigatório.";
            return;
        }
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "Formato de dados inválido.";
            return;
        }

        // Converte data/hora para MySQL e timezone Europe/Zurich
        try {
            $dt = \DateTime::createFromFormat(
                'd/m/Y, H:i:s',
                $rawDate,
                new \DateTimeZone('Europe/Zurich')
            );
            if (!$dt) {
                $dt = \DateTime::createFromFormat(
                    'd/m/Y H:i:s',
                    $rawDate,
                    new \DateTimeZone('Europe/Zurich')
                );
            }
            if (!$dt) {
                $dt = new \DateTime('now', new \DateTimeZone('Europe/Zurich'));
            }
        } catch (\Exception $e) {
            $dt = new \DateTime('now', new \DateTimeZone('Europe/Zurich'));
        }
        $datetime = $dt->format('Y-m-d H:i:s');

        $pdo       = Database::connect();
        $toHistory = [];

        // Criar novo item
        if ($reason === 'criar' && isset($data['new_item'])) {
            $ni   = $data['new_item'];
            $name = trim($ni['name']     ?? '');
            $type = trim($ni['type']     ?? '');
            $qty  = (int)($ni['quantity'] ?? 0);

            if ($name === '' || $type === '' || $qty < 1) {
                echo "Preencha nome, tipo e quantidade do novo item.";
                return;
            }

            $ins = $pdo->prepare("
                INSERT INTO inventory (type, name, quantity, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $ins->execute([$type, $name, $qty]);
            $newId     = (int)$pdo->lastInsertId();
            $toHistory = [ $newId => $qty ];

        } else {
            // Movimentação existente
            foreach ($data as $id => $qty) {
                $i = (int)$id;
                $q = (int)$qty;
                if ($i > 0 && $q > 0) {
                    $toHistory[$i] = $q;
                }
            }
            if (empty($toHistory)) {
                echo "Selecione ao menos um item.";
                return;
            }

            foreach ($toHistory as $id => $q) {
                $sql = $reason === 'adição'
                    ? "UPDATE inventory SET quantity = quantity + ? WHERE id = ?"
                    : "UPDATE inventory SET quantity = quantity - ? WHERE id = ?";
                $upd = $pdo->prepare($sql);
                $upd->execute([$q, $id]);
            }

            if ($reason === 'projeto' && $project_id) {
                foreach ($toHistory as $id => $q) {
                    $prCheck = $pdo->prepare("
                        SELECT id, quantity
                        FROM project_resources
                        WHERE project_id = ? AND resource_type = 'inventory' AND resource_id = ?
                    ");
                    $prCheck->execute([$project_id, $id]);
                    $prRow = $prCheck->fetch(\PDO::FETCH_ASSOC);
                    if ($prRow) {
                        $newQty = (int)$prRow['quantity'] + $q;
                        $prUpd = $pdo->prepare("
                            UPDATE project_resources
                            SET quantity = ?
                            WHERE id = ?
                        ");
                        $prUpd->execute([$newQty, $prRow['id']]);
                    } else {
                        $prIns = $pdo->prepare("
                            INSERT INTO project_resources
                              (project_id, resource_type, resource_id, quantity, created_at)
                            VALUES (?, 'inventory', ?, ?, NOW())
                        ");
                        $prIns->execute([$project_id, $id, $q]);
                    }
                }
            }
        }

        // Registrar histórico
        try {
            $this->historyModel->insertMovement(
                $user,
                $datetime,
                $reason,
                $project_id ? (int)$project_id : null,
                $custom !== '' ? $custom : null,
                $toHistory
            );
        } catch (\Exception $e) {
            echo "Erro ao registrar movimentação: " . $e->getMessage();
            return;
        }

        header("Location: /ams-malergeschaft/public/inventory");
        exit;
    }

    /**
     * GET /inventory/history/details?id=...
     * Retorna JSON com itens (qty>0) desse movimento.
     */
    public function historyDetails(): void {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        header('Content-Type: application/json; charset=utf-8');

        if (!$id) {
            http_response_code(400);
            echo json_encode(['items' => []]);
            exit;
        }

        // Busca detalhes na tabela inventory_movement_details
        $pdo = Database::connect();
        $stmt = $pdo->prepare("
            SELECT i.name,
                   d.quantity AS qty
            FROM inventory_movement_details d
            INNER JOIN inventory i ON d.item_id = i.id
            WHERE d.movement_id = ?
        ");
        $stmt->execute([$id]);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Filtra apenas qty > 0
        $items = array_filter($items, fn($i) => (int)$i['qty'] > 0);

        echo json_encode(['items' => array_values($items)]);
        exit;
    }
}
