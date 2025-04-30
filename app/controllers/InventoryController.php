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
     * Exibe lista de itens e carrega dados para modais.
     */
    public function index(): void {
        $filter          = $_GET['filter'] ?? 'all';
        $inventoryItems  = $this->inventoryModel->getAll($filter);
        $allItems        = $this->inventoryModel->getAll('all');
        $activeProjects  = ProjectModel::getActiveProjects();
        $movements       = $this->historyModel->getAllMovements();

        require_once __DIR__ . '/../views/inventory/index.php';
    }

    /**
     * POST /inventory/store
     * Cria um novo item no estoque.
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
     * Exibe formulário de edição.
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
     * Atualiza um item existente.
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
     * Remove um item do estoque.
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
     * Processa o modal de Controle de Estoque (remoção, adição ou criação).
     */
    public function storeControl(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /ams-malergeschaft/public/inventory");
            exit;
        }

        $user       = trim($_POST['user_name']       ?? '');
        $datetime   = $_POST['datetime']             ?? '';
        $reason     = $_POST['reason']               ?? '';
        $project_id = $_POST['project_id'] ?: null;
        $custom     = trim($_POST['custom_reason']   ?? '');
        $itemsJson  = $_POST['items']                ?? '[]';
        $data       = json_decode($itemsJson, true);

        if ($user === '') {
            echo "O nome do usuário é obrigatório.";
            return;
        }
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "Formato de dados inválido.";
            return;
        }

        $pdo = Database::connect();
        $toHistory = [];

        // Se for criar novo item
        if ($reason === 'criar' && isset($data['new_item'])) {
            $ni   = $data['new_item'];
            $name = trim($ni['name']     ?? '');
            $type = trim($ni['type']     ?? '');
            $qty  = (int)($ni['quantity'] ?? 0);

            if ($name === '' || $type === '' || $qty < 1) {
                echo "Nome, tipo e quantidade do novo item são obrigatórios.";
                return;
            }

            // Insere no inventário
            $stmt = $pdo->prepare("
                INSERT INTO inventory (type, name, quantity, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$type, $name, $qty]);
            $newId = (int)$pdo->lastInsertId();
            $toHistory = [ $newId => $qty ];

        } else {
            // Movimenta itens existentes
            foreach ($data as $id => $c) {
                $i = (int)$id;
                $q = (int)$c;
                if ($i > 0 && $q > 0) {
                    $toHistory[$i] = $q;
                }
            }
            if (empty($toHistory)) {
                echo "Selecione ao menos um item com quantidade válida.";
                return;
            }

            // Atualiza quantidades
            foreach ($toHistory as $id => $c) {
                if ($reason === 'adição') {
                    $upd = $pdo->prepare("UPDATE inventory SET quantity = quantity + ? WHERE id = ?");
                } else {
                    $upd = $pdo->prepare("UPDATE inventory SET quantity = quantity - ? WHERE id = ?");
                }
                $upd->execute([$c, $id]);
            }
        }

        // Registra no histórico (movimento mestre + detalhes)
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
     * Retorna JSON com detalhes de um movimento.
     */
    public function historyDetails(): void {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(["error" => "ID do movimento não informado"]);
            return;
        }
        $details = $this->historyModel->getMovementDetails((int)$id);
        header('Content-Type: application/json');
        echo json_encode($details);
    }

    /**
     * GET /inventory/control
     * (Se você usar view separada; caso contrário, remove.)
     */
    public function control(): void {
        $items          = $this->inventoryModel->getAll('all');
        $activeProjects = ProjectModel::getActiveProjects();
        require_once __DIR__ . '/../views/inventory/control.php';
    }

    /**
     * GET /inventory/history
     * (Se você usar view separada; caso contrário, remove.)
     */
    public function history(): void {
        $movements = $this->historyModel->getAllMovements();
        require_once __DIR__ . '/../views/inventory/history.php';
    }
}
