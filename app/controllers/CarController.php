<?php
// system/app/controllers/CarController.php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/CarModel.php';
require_once __DIR__ . '/../models/CarUsageModel.php';
require_once __DIR__ . '/../models/CarHistoryModel.php';

class CarController
{
    private CarModel        $carModel;
    private CarUsageModel   $usageModel;
    private CarHistoryModel $historyModel;
    private array           $langText;
    private string          $baseUrl;
    private int             $currentUserId;
    private string          $currentUserName;

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Idioma e traduções
        $lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'pt';
        $_SESSION['lang'] = $lang;
        $lf = __DIR__ . '/../lang/' . $lang . '.php';
        $this->langText = file_exists($lf) ? include $lf : [];

        // URL base
        $this->baseUrl = BASE_URL;

        // Usuário logado
        $this->currentUserId   = $_SESSION['user']['id']   ?? 0;
        $this->currentUserName = $_SESSION['user']['name'] ?? '';

        $this->carModel     = new CarModel();
        $this->usageModel   = new CarUsageModel();
        $this->historyModel = new CarHistoryModel();
    }

    public function index(): void
    {
        $langText = $this->langText;
        $baseUrl  = $this->baseUrl;
        $cars     = $this->carModel->getAll();
        $history  = $this->usageModel->getAllUsages();
        require __DIR__ . '/../views/cars/index.php';
    }

    public function get(): void
    {
        header('Content-Type: application/json;charset=utf-8');
        $id = (int)($_GET['id'] ?? 0);
        if ($id < 1) {
            http_response_code(400);
            echo json_encode(['error' => 'ID inválido']);
            exit;
        }
        $car = $this->carModel->getById($id);
        if (!$car) {
            http_response_code(404);
            echo json_encode(['error' => 'Veículo não encontrado']);
            exit;
        }
        echo json_encode($car);
        exit;
    }

    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$this->baseUrl}/cars");
            exit;
        }
        $m   = trim($_POST['manufacturer'] ?? '');
        $mod = trim($_POST['model']        ?? '');
        $y   = (int)($_POST['year']        ?? 0);
        $p   = trim($_POST['plate']        ?? '');
        $km  = (int)($_POST['mileage']     ?? 0);
        $c   = trim($_POST['color']        ?? '');

        if ($m === '' || $mod === '' || $y < 1886 || $p === '') {
            echo "Fabricante, modelo, ano (>=1886) e placa obrigatórios.";
            return;
        }

        $this->carModel->insert($m, $mod, $y, $p, $km, $c);
        global $pdo;
        $newId = (int)$pdo->lastInsertId();
        $now   = (new DateTime())->format('Y-m-d H:i:s');

        // log de criação
        $this->historyModel->insertMovement(
            $this->currentUserName,
            $now,
            'create',
            $newId,
            null
        );

        // registro inicial de uso
        $this->usageModel->insertUsage(
            $newId,
            $this->currentUserId,
            $now,
            0,
            'km',
            []
        );

        header("Location: {$this->baseUrl}/cars");
        exit;
    }

    public function storeUsage(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$this->baseUrl}/cars");
            exit;
        }

        $carId = (int)($_POST['car_id'] ?? 0);
        $dtRaw = $_POST['usage_datetime'] ?? '';
        $dist  = (int)($_POST['distance']      ?? 0);
        $unit  = $_POST['unit'] ?? 'km';

        // garante que a pasta public/uploads/cars exista
        $uploadDir = __DIR__ . '/../../public/uploads/cars/';
        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // coleta paradas, custos e comprovantes
        $stopsDetails = [];
        foreach ($_POST['stops'] ?? [] as $i => $lit) {
            $lit   = (float)$lit;
            $cost  = (float)($_POST['costs'][$i] ?? 0);
            $receiptPath = null;

            if (!empty($_FILES['receipts']['tmp_name'][$i])
                && $_FILES['receipts']['error'][$i] === UPLOAD_ERR_OK
            ) {
                $ext  = pathinfo($_FILES['receipts']['name'][$i], PATHINFO_EXTENSION);
                $fn   = "car_{$carId}_stop_{$i}_" . time() . ".{$ext}";
                $dest = $uploadDir . $fn;

                if (move_uploaded_file($_FILES['receipts']['tmp_name'][$i], $dest)) {
                    // caminho público para o link
                    $receiptPath = "/uploads/cars/{$fn}";
                }
            }

            $stopsDetails[] = [
                'stop'    => $i,
                'liters'  => $lit,
                'cost'    => $cost,
                'receipt' => $receiptPath,
            ];
        }

        // formata data/hora
        try {
            $dt = new DateTime($dtRaw);
        } catch (Exception $e) {
            $dt = new DateTime();
        }
        $datetime = $dt->format('Y-m-d H:i:s');

        // valida existência do carro
        $car = $this->carModel->getById($carId);
        if (!$car) {
            http_response_code(400);
            echo json_encode(['error' => 'Carro inválido']);
            exit;
        }

        // insere uso
        $this->usageModel->insertUsage(
            $carId,
            $this->currentUserId,
            $datetime,
            $dist,
            $unit,
            $stopsDetails
        );

        // atualiza mileage
        $newKm = $car['mileage'] + $dist;
        $this->carModel->update(
            $carId,
            $car['manufacturer'],
            $car['model'],
            $car['year'],
            $car['plate'],
            $newKm,
            $car['color']
        );

        // grava log de histórico
        $this->historyModel->insertMovement(
            $this->currentUserName,
            $datetime,
            'usage',
            $carId,
            json_encode($stopsDetails, JSON_UNESCAPED_UNICODE)
        );

        // retorna apenas o novo KM para o JS
        header('Content-Type: application/json');
        echo json_encode(['newMileage' => $newKm]);
        exit;
    }

    public function delete(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id < 1) {
            exit("ID inválido.");
        }

        $this->usageModel->deleteByCarId($id);
        $this->carModel->delete($id);
        $this->historyModel->insertMovement(
            $this->currentUserName,
            (new DateTime())->format('Y-m-d H:i:s'),
            'delete',
            $id,
            null
        );

        header("Location: " . BASE_URL . "/cars");
        exit;
    }
}
