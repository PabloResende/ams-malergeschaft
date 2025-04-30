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
     * Exibe a lista de itens + modais
     */
    public function index(): void {
        $filter = $_GET['filter'] ?? 'all';
        $inventoryItems = $this->inventoryModel->getAll($filter);
        $allItems       = $this->inventoryModel->getAll('all');
        $activeProjects = ProjectModel::getActiveProjects();
        $movements      = $this->historyModel->getAllMovements();

        require_once __DIR__ . '/../views/inventory/index.php';
    }

    /**
     * Processa o formulário de Controle de Estoque
     */
    public function storeControl(): void {
        $user       = trim($_POST['user_name'] ?? '');
        $datetime   = trim($_POST['datetime']  ?? '');
        $reason     = trim($_POST['reason']    ?? '');
        $project_id = $_POST['project_id'] ?: null;
        $custom     = trim($_POST['custom_reason'] ?? '');
        $itemsJson  = $_POST['items'] ?? '[]';
        $data       = json_decode($itemsJson, true);

        // Validações iniciais
        if ($user === '') {
            echo "O nome do usuário é obrigatório.";
            return;
        }
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "Formato de dados inválido.";
            return;
        }

        $pdo = Database::connect();

        // Caso CRIAR NOVO ITEM
        if ($reason === 'criar' && isset($data['new_item'])) {
            $ni   = $data['new_item'];
            $name = trim($ni['name'] ?? '');
            $type = trim($ni['type'] ?? '');
            $qty  = (int)($ni['quantity'] ?? 0);

            if ($name === '' || $type === '' || $qty < 1) {
                echo "Nome, tipo e quantidade do novo item são obrigatórios.";
                return;
            }

            // Insere novo item no inventário
            $ins = $pdo->prepare("
                INSERT INTO inventory
                  (type, name, quantity, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $ins->execute([$type, $name, $qty]);
            $newId = (int)$pdo->lastInsertId();

            $toHistory = [ $newId => $qty ];

        } else {
            // Movimento em itens existentes
            $toHistory = [];
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

            // Atualiza o estoque
            foreach ($toHistory as $id => $c) {
                if ($reason === 'adição') {
                    $upd = $pdo->prepare("UPDATE inventory SET quantity = quantity + ? WHERE id = ?");
                } else {
                    $upd = $pdo->prepare("UPDATE inventory SET quantity = quantity - ? WHERE id = ?");
                }
                $upd->execute([$c, $id]);
            }
        }

        // Grava no histórico
        try {
            $this->historyModel->insertMovement(
                $user,
                $datetime,
                $reason,
                $project_id,
                $custom,
                $toHistory
            );
        } catch (\Exception $e) {
            echo "Erro ao registrar movimentação: " . $e->getMessage();
            return;
        }

        // Redireciona
        header("Location: /ams-malergeschaft/public/inventory");
        exit;
    }

    /**
     * Retorna via JSON os detalhes de um movimento (usado no histórico)
     */
    public function historyDetails(): void {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(["error" => "ID do movimento não informado"]);
            return;
        }
        try {
            $items = $this->historyModel->getMovementItems((int)$id);
            header('Content-Type: application/json');
            echo json_encode($items);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => "Falha ao buscar detalhes"]);
        }
    }


    /**
     * Exibe formulário de controle de estoque.
     */
    public function control() {
        $items          = $this->inventoryModel->getAll('all');
        $activeProjects = ProjectModel::getActiveProjects();
        require_once __DIR__ . '/../views/inventory/control.php';
    }

    /**
     * Exibe histórico de movimentações.
     */
    public function history() {
        $movements = $this->historyModel->getAllMovements();
        require_once __DIR__ . '/../views/inventory/history.php';
    }

    public function store() {
        $type = $_POST['type'] ?? '';
        $name = $_POST['name'] ?? '';
        $quantity = $_POST['quantity'] ?? 0;

        if ($this->inventoryModel->insert($type, $name, $quantity)) {
            header("Location: /ams-malergeschaft/public/inventory");
            exit;
        } else {
            echo "Error storing inventory item.";
        }
    }

    public function edit() {
        if (!isset($_GET['id'])) {
            echo "Inventory item ID not provided.";
            exit;
        }

        $item = $this->inventoryModel->getById($_GET['id']);
        if (!$item) {
            echo "Inventory item not found.";
            exit;
        }

        require_once __DIR__ . '/../views/inventory/edit.php';
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? '';
            $type = $_POST['type'] ?? '';
            $name = $_POST['name'] ?? '';
            $quantity = $_POST['quantity'] ?? 0;

            if (empty($id) || empty($name)) {
                echo "Required fields missing.";
                exit;
            }

            if ($this->inventoryModel->update($id, $type, $name, $quantity)) {
                header("Location: /ams-malergeschaft/public/inventory");
                exit;
            } else {
                echo "Error updating inventory item.";
            }
        }
    }

    public function delete() {
        if (!isset($_GET['id'])) {
            echo "Inventory item ID not provided.";
            exit;
        }

        if ($this->inventoryModel->delete($_GET['id'])) {
            header("Location: /ams-malergeschaft/public/inventory");
            exit;
        } else {
            echo "Error deleting inventory item.";
        }
    }
}
