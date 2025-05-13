<?php
require_once __DIR__ . '/../models/Clients.php';
require_once __DIR__ . '/../../config/Database.php';

class ClientsController {

    public function list() {
        $clients = Client::all();
        require_once __DIR__ . '/../views/clients/index.php';
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /ams-malergeschaft/public/clients');
            exit;
        }

        $data = [
            'name'           => trim($_POST['name'] ?? ''),
            'address'        => trim($_POST['address'] ?? ''),
            'phone'          => trim($_POST['phone'] ?? ''),
            'active'         => 1,
            'loyalty_points' => 0
        ];

        Client::create($data);
        header('Location: /ams-malergeschaft/public/clients');
        exit;
    }

    public function show() {
        header('Content-Type: application/json; charset=UTF-8');
        $id = (int)($_GET['id'] ?? 0);

        $client = Client::find($id);
        if (!$client) {
            echo json_encode(['error' => 'Cliente não encontrado']);
            exit;
        }

        // total de projetos já existente
        $client['project_count'] = Client::countProjects($id);

        // traz todas transações onde client_id = $id
        $client['transactions'] = TransactionModel::getAll([
            'start'     => '1970-01-01',
            'end'       => date('Y-m-d'),
            'category'  => '',          // todas as categorias
            'client_id' => $id,
            'type'      => ''           // todos os tipos
        ]);

        echo json_encode($client);
        exit;
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /ams-malergeschaft/public/clients');
            exit;
        }
        $id = (int)($_POST['id'] ?? 0);

        $data = [
            'name'           => trim($_POST['name'] ?? ''),
            'address'        => trim($_POST['address'] ?? ''),
            'phone'          => trim($_POST['phone'] ?? ''),
            'active'         => 1
        ];

        Client::update($id, $data);
        header('Location: /ams-malergeschaft/public/clients');
        exit;
    }

    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            Client::delete($id);
        }
        header('Location: /ams-malergeschaft/public/clients');
        exit;
    }
}
