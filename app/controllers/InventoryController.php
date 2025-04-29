<?php
// app/controllers/InventoryController.php

require_once __DIR__ . '/../models/Inventory.php';
require_once __DIR__ . '/../models/InventoryHistoryModel.php';
require_once __DIR__ . '/../models/Project.php';

class InventoryController {
    private $inventoryModel;
    private $historyModel;

    public function __construct() {
        $this->inventoryModel = new InventoryModel();
        $this->historyModel   = new InventoryHistoryModel();
    }

    /**
     * Lista principal de inventário.
     */
    public function index() {
        $filter = $_GET['filter'] ?? 'all';
        $items  = $this->inventoryModel->getAll($filter);
        require_once __DIR__ . '/../views/inventory/index.php';
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
     * Processa registro de movimentação e atualiza estoque.
     */
    public function storeControl() {
        $userName  = trim($_POST['user_name']    ?? '');
        $datetime  = $_POST['datetime']          ?? date('Y-m-d H:i:s');
        $reason    = $_POST['reason']            ?? 'outros';
        $custom    = trim($_POST['custom_reason'] ?? '');
        $projectId = $_POST['project_id'] ?: null;
        $items     = json_decode($_POST['items'] ?? '[]', true);

        if ($userName === '' || !is_array($items) || empty($items)) {
            echo "Preencha seu nome e selecione ao menos um item.";
            exit;
        }

        $this->historyModel->insertMovement(
            $userName,
            $datetime,
            $reason,
            $custom !== '' ? $custom : null,
            $projectId,
            $items
        );

        header("Location: /ams-malergeschaft/public/inventory");
        exit;
    }

    /**
     * Exibe histórico de movimentações.
     */
    public function history() {
        $movements = $this->historyModel->getAllMovements();
        require_once __DIR__ . '/../views/inventory/history.php';
    }

    /**
     * Retorna detalhes de um movimento (JSON).
     */
    public function historyDetails() {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            exit(json_encode(['error' => 'Missing ID']));
        }
        $details = $this->historyModel->getMovementDetails($id);
        header('Content-Type: application/json');
        echo json_encode($details);
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
