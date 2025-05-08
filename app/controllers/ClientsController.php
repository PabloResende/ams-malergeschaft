<?php
// app/controllers/ClientsController {.php
require_once __DIR__ . '/../models/Clients.php';

class ClientsController {

    public function list() {
        $clients = Client::all();
        require_once __DIR__ . '/../views/clients/index.php';
    }

    public function create() {
        require_once __DIR__ . '/../views/clients/create.php';
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => $_POST['name'] ?? '',
                'address' => $_POST['address'] ?? '',
                'about' => $_POST['about'] ?? '',
                'phone' => $_POST['phone'] ?? '',
                'profile_picture' => null
            ];

            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $data['profile_picture'] = $_FILES['profile_picture']['name'];
                move_uploaded_file($_FILES['profile_picture']['tmp_name'], __DIR__ . '/../../uploads/' . $data['profile_picture']);
            }

            if (Client::create($data)) {
                header('Location: /ams-malergeschaft/public/clients');
                exit;
            } else {
                echo "Erro ao salvar o cliente.";
            }
        }
    }

    public function show()
    {
        header('Content-Type: application/json; charset=UTF-8');

        if (!isset($_GET['id'])) {
            echo json_encode(['error' => 'ID não fornecido']);
            exit;
        }
        $id = (int) $_GET['id'];

        $client = Client::find($id);
        if (!$client) {
            echo json_encode(['error' => 'Cliente não encontrado']);
            exit;
        }

        // opcional: contar quantos projetos este client já fez
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM projects WHERE client_id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $client['project_count'] = (int)($row['cnt'] ?? 0);

        echo json_encode($client);
        exit;
    }

    public function edit() {
        if (!isset($_GET['id'])) {
            echo "ID não fornecido.";
            exit;
        }

        $client = Client::find($_GET['id']);
        if (!$client) {
            echo "Cliente não encontrado.";
            exit;
        }

        require_once __DIR__ . '/../views/clients/edit.php';
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? '';
            $data = [
                'name' => $_POST['name'] ?? '',
                'address' => $_POST['address'] ?? '',
                'about' => $_POST['about'] ?? '',
                'phone' => $_POST['phone'] ?? '',
                'active' => isset($_POST['active']) ? 1 : 0
            ];

            if (Client::update($id, $data)) {
                header("Location: /ams-malergeschaft/public/clients");
                exit;
            } else {
                echo "Erro ao atualizar o cliente.";
            }
        }
    }

    public function delete() {
        if (!isset($_GET['id'])) {
            echo "ID não fornecido.";
            exit;
        }

        if (Client::delete($_GET['id'])) {
            header("Location: /ams-malergeschaft/public/clients");
            exit;
        } else {
            echo "Erro ao deletar o cliente.";
        }
    }
}
