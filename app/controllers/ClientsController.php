<?php
// app/controllers/ClientsController.php

require_once __DIR__ . '/../models/Clients.php';
require_once __DIR__ . '/../../config/Database.php';

class ClientsController {

    public function list() {
        $clients = Client::all();
        require_once __DIR__ . '/../views/clients/index.php';
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: $basePath/clients');
            exit;
        }

        $data = [
            'name'           => trim($_POST['name'] ?? ''),
            'address'        => trim($_POST['address'] ?? ''),
            'phone'          => trim($_POST['phone'] ?? ''),
            'active'         => 1,
            // fidelidade inicial é sempre 0, mas funcionalidade comentada
            'loyalty_points' => 0
        ];

        Client::create($data);
        header('Location: $basePath/clients');
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

        // comentado: não enviar pontos ao front
        // unset($client['loyalty_points']);

        $client['project_count'] = Client::countProjects($id);

        $client['transactions'] = TransactionModel::getAll([
            'start'     => '1970-01-01',
            'end'       => date('Y-m-d'),
            'category'  => '',
            'client_id' => $id,
            'type'      => ''
        ]);

        echo json_encode($client);
        exit;
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: $basePath/clients');
            exit;
        }
        $id = (int)($_POST['id'] ?? 0);

        $data = [
            'name'    => trim($_POST['name'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'phone'   => trim($_POST['phone'] ?? ''),
            'active'  => 1
        ];

        Client::update($id, $data);

        // comentado: desativado ajuste manual de pontos
        /*
        if (isset($_POST['loyalty_points'])) {
            Client::setPoints($id, (int)$_POST['loyalty_points']);
        }
        */

        header('Location: $basePath/clients');
        exit;
    }

    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            Client::delete($id);
        }
        header('Location: $basePath/clients');
        exit;
    }
}
