<?php
// app/controllers/ClientsController {.php

require_once __DIR__ . '/../../config/Database.php';

class ClientsController {

    // Dentro da classe ClientsController
    public function list() {
        $pdo = Database::connect();
        $stmt = $pdo->query("SELECT * FROM client ORDER BY name ASC");
        $client = $stmt->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../views/clients/index.php';
    }

    public function create() {
        require_once __DIR__ . '/../views/clients/create.php';
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';
            $address = $_POST['address'] ?? '';
            $about = $_POST['about'] ?? '';
            $phone = $_POST['phone'] ?? '';
            
            $profilePicture = null;
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $profilePicture = $_FILES['profile_picture']['name'];
                move_uploaded_file($_FILES['profile_picture']['tmp_name'], __DIR__ . '/../../uploads/' . $profilePicture);
            }
    
            try {
                $pdo = Database::connect();
                $stmt = $pdo->prepare("INSERT INTO client (name, address, about, phone, profile_picture) 
                                       VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $address, $about, $phone, $profilePicture]);
    
                header('Location: /ams-malergeschaft/public/clients');
                exit;
            } catch (Exception $e) {
                echo "Erro ao salvar o cliente: " . $e->getMessage();
                exit;
            }
        }
    }    

    public function edit() {
        if (!isset($_GET['id'])) {
            echo "client ID not provided.";
            exit;
        }
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT * FROM client WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$client) {
            echo "client not found.";
            exit;
        }
        require_once __DIR__ . '/../views/clients/edit.php';
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? '';
            $name = $_POST['name'] ?? '';
            $address = $_POST['address'] ?? '';
            $about = $_POST['about'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $active = isset($_POST['active']) ? 1 : 0;

            if (empty($id) || empty($name) || empty($role)) {
                echo "Required fields missing.";
                exit;
            }

            require_once __DIR__ . '/../../config/Database.php';
            $pdo = Database::connect();
            $stmt = $pdo->prepare("UPDATE client SET name = ?, address = ?, about = ?, phone = ?, active = ? WHERE id = ?");
            if ($stmt->execute([$name, $address, $about, $phone, $active, $id])) {
                header("Location: /ams-malergeschaft/public/clients");
                exit;
            } else {
                echo "Error updating client.";
            }
        }
    }

    public function delete() {
        if (!isset($_GET['id'])) {
            echo "client ID not provided.";
            exit;
        }
        $pdo = Database::connect();
        $stmt = $pdo->prepare("DELETE FROM client WHERE id = ?");
        if ($stmt->execute([$_GET['id']])) {
            header("Location: /ams-malergeschaft/public/clients");
            exit;
        } else {
            echo "Error deleting client.";
        }
    }
}
