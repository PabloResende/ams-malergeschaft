<?php
// app/controllers/InventoryController.php
require_once __DIR__ . '/../models/Inventory.php';

class InventoryController {
    private $inventoryModel;

    public function __construct() {
        $this->inventoryModel = new InventoryModel();
    }

    public function index() {
        $filter = $_GET['filter'] ?? 'all';
        $inventoryItems = $this->inventoryModel->getAll($filter);
        require_once __DIR__ . '/../views/inventory/index.php';
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
