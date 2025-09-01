<?php
// app/controllers/InventoryController.php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/Inventory.php';
require_once __DIR__ . '/../models/InventoryHistoryModel.php';
require_once __DIR__ . '/../models/Project.php';

class InventoryController
{
    private InventoryModel $inventoryModel;
    private ProjectModel $projectModel;
    private InventoryHistoryModel $historyModel;
    private array $langText;
    private string $baseUrl;
    private string $currentUser;

    public function __construct()
    {
        // 1) Sessão e idioma
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'pt';
        $_SESSION['lang'] = $lang;
        $langFile = __DIR__ . '/../lang/' . $lang . '.php';
        if (!file_exists($langFile)) {
            $langFile = __DIR__ . '/../lang/pt.php';
        }
        $this->langText    = require $langFile;
        $this->baseUrl     = BASE_URL;
        // puxa nome de quem está logado
        $this->currentUser = $_SESSION['user']['name'] ?? '';

        // 2) Instancia models
        $this->inventoryModel = new InventoryModel();
        $this->projectModel   = new ProjectModel();
        $this->historyModel   = new InventoryHistoryModel();
    }

    /**
     * Exibe lista de itens, projetos ativos e histórico
     */
    public function index(): void
    {
        $langText       = $this->langText;
        $baseUrl        = $this->baseUrl;
        $currentUser    = $this->currentUser;
        $items          = $this->inventoryModel->getAll();
        $activeProjects = $this->projectModel->getAll();
        $movements      = $this->historyModel->getAllMovements();

        require __DIR__ . '/../views/inventory/index.php';
    }

    /**
     * Processa controle de estoque (adição, perda, projeto ou criação de item)
     */
    public function storeControl(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "/inventory");
            exit;
        }

        $user       = trim($_POST['user_name'] ?? '');
        $rawDate    = trim($_POST['datetime']  ?? '');
        $reason     = $_POST['reason'] ?? '';
        $project_id = $_POST['project_id'] ?: null;
        $custom     = trim($_POST['custom_reason'] ?? '');
        $itemsJson  = $_POST['items'] ?? '[]';
        $data       = json_decode($itemsJson, true);

        if ($user === '') {
            echo "O nome do usuário é obrigatório.";
            return;
        }
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "Formato de dados inválido.";
            return;
        }

        // Converte data/hora para Europe/Zurich
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

        global $pdo;
        $toHistory = [];

        // Criação de novo item
        if ($reason === 'criar' && isset($data['new_item'])) {
            $ni   = $data['new_item'];
            $name = trim($ni['name'] ?? '');
            $type = trim($ni['type'] ?? '');
            $qty  = (int)($ni['quantity'] ?? 0);
            $desc = trim($ni['description'] ?? '');

            if ($name === '' || $type === '' || $qty < 1) {
                echo "Preencha nome, tipo, quantidade e descrição do novo item.";
                return;
            }

            $ins = $pdo->prepare("
                INSERT INTO inventory (type, name, quantity, description, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $ins->execute([$type, $name, $qty, $desc]);
            $newId     = (int)$pdo->lastInsertId();
            $toHistory = [ $newId => $qty ];

        } else {
            // Movimentação de itens existentes
            foreach ($data as $id => $qty) {
                $i = (int)$id;
                $q = (int)$qty;
                if ($i > 0 && $q > 0) {
                    $toHistory[$i] = $q;
                }
            }
            if (empty($toHistory)) {
                echo "Selecione ao menos um item ou crie um novo.";
                return;
            }

            // Atualiza quantidade
            foreach ($toHistory as $id => $q) {
                $sql = $reason === 'adição'
                    ? "UPDATE inventory SET quantity = quantity + ? WHERE id = ?"
                    : "UPDATE inventory SET quantity = quantity - ? WHERE id = ?";
                $upd = $pdo->prepare($sql);
                $upd->execute([$q, $id]);
            }

            // Alocação em projeto
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

        // Insere histórico
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

        header("Location: " . BASE_URL . "/inventory");
        exit;
    }

    /**
     * AJAX: adiciona quantidade
     */
    public function addQuantityAjax(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $id    = $input['id']  ?? null;
        $qty   = (int)($input['qty'] ?? 0);
        if (!$id || $qty < 1) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
            exit;
        }
        $this->inventoryModel->addQuantity((int)$id, $qty);
        echo json_encode(['success' => true]);
    }

    /**
     * AJAX: deleta item
     */
    public function deleteAjax(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $id    = $input['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID inválido']);
            exit;
        }
        $this->inventoryModel->delete((int)$id);
        echo json_encode(['success' => true]);
    }

    /**
     * AJAX: atualiza descrição
     */
    public function updateDescriptionAjax(): void
    {
        global $pdo;
        $input = json_decode(file_get_contents('php://input'), true);
        $id    = $input['id'] ?? null;
        $desc  = trim($input['description'] ?? '');
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID inválido']);
            exit;
        }
        $stmt = $pdo->prepare("UPDATE inventory SET description = ? WHERE id = ?");
        $stmt->execute([$desc, (int)$id]);
        echo json_encode(['success' => true]);
    }

    /**
     * GET /inventory/edit?id=...
     */
    public function edit(): void
    {
        if (!isset($_GET['id'])) {
            echo "ID de item não fornecido.";
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
    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "/inventory");
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
        header("Location: " . BASE_URL . "/inventory");
        exit;
    }

    /**
     * GET /inventory/delete?id=...
     */
    public function delete(): void
    {
        if (!isset($_GET['id'])) {
            echo "ID de item não fornecido.";
            exit;
        }

        $this->inventoryModel->delete((int)$_GET['id']);
        header("Location: " . BASE_URL . "/inventory");
        exit;
    }

    /**
     * GET /inventory/history/details?id=...
     */
    public function historyDetails(): void
    {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        header('Content-Type: application/json; charset=utf-8');

        if (!$id) {
            http_response_code(400);
            echo json_encode(['movement' => null, 'items' => []]);
            exit;
        }

        global $pdo;
        // Mestre
        $stmt = $pdo->prepare("
            SELECT m.user_name,
                   m.datetime,
                   m.reason,
                   m.custom_reason,
                   p.name AS project_name
            FROM inventory_movements m
            LEFT JOIN projects p ON m.project_id = p.id
            WHERE m.id = ?
        ");
        $stmt->execute([$id]);
        $mv = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$mv) {
            echo json_encode(['movement' => null, 'items' => []]);
            exit;
        }

        // Ajusta fuso
        $dt = new \DateTime($mv['datetime'], new \DateTimeZone('UTC'));
        $dt->setTimezone(new \DateTimeZone('Europe/Zurich'));
        $mv['datetime'] = $dt->format('Y-m-d H:i:s');

        // Detalhes
        $stmt2 = $pdo->prepare("
            SELECT i.name,
                   d.quantity AS qty
            FROM inventory_movement_details d
            JOIN inventory i ON d.item_id = i.id
            WHERE d.movement_id = ?
        ");
        $stmt2->execute([$id]);
        $items = $stmt2->fetchAll(\PDO::FETCH_ASSOC);
        $items = array_values(array_filter($items, fn($i) => (int)$i['qty'] > 0));

        echo json_encode([
            'movement' => [
                'user_name'     => $mv['user_name'],
                'datetime'      => $mv['datetime'],
                'reason'        => $mv['reason'],
                'project_name'  => $mv['project_name'],
                'custom_reason' => $mv['custom_reason'],
            ],
            'items'    => $items
        ]);
        exit;
    }
}
