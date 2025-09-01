<?php
// app/controllers/ClientsController.php

require_once __DIR__ . '/../models/Clients.php';
require_once __DIR__ . '/../models/TransactionModel.php';

class ClientsController
{
    /** @var array */
    private array $langText;
    /** @var string */
    private string $baseUrl;

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'pt';
        $_SESSION['lang'] = $lang;
        $langFile = __DIR__ . '/../lang/' . $lang . '.php';
        if (! file_exists($langFile)) {
            $langFile = __DIR__ . '/../lang/pt.php';
        }
        $this->langText = require $langFile;
        $this->baseUrl  = BASE_URL;
    }

    /**
     * Lista todos os clientes (somente active = 1, caso você mantenha esse filtro).
     */
    public function list(): void
    {
        $langText    = $this->langText;
        $baseUrl     = $this->baseUrl;
        $clientModel = new Client();
        $clients     = $clientModel->all();
        require __DIR__ . '/../views/clients/index.php';
    }

    /**
     * Form de criação.
     */
    public function create(): void
    {
        $langText = $this->langText;
        $baseUrl  = $this->baseUrl;
        require __DIR__ . '/../views/clients/create.php';
    }

    /**
     * Retorna JSON com os dados de um cliente.
     */
    public function show(): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        $id          = (int)($_GET['id'] ?? 0);
        $clientModel = new Client();
        $client      = $clientModel->find($id);

        if (! $client) {
            http_response_code(404);
            echo json_encode(['error' => 'Cliente não encontrado']);
            exit;
        }

        $txModel   = new TransactionModel();
        $allTx     = $txModel->getAll();
        $client['transactions'] = array_values(array_filter(
            $allTx,
            fn($t) => isset($t['client_id']) && (int)$t['client_id'] === $id
        ));

        echo json_encode($client);
        exit;
    }

    /**
     * Cria ou atualiza um cliente.
     */
    public function save(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        $clientModel = new Client();
        $data = [
            'contact_number'  => $_POST['contact_number']  ?? null,
            'name'            => $_POST['name']            ?? '',
            'address'         => $_POST['address']         ?? null,
            'zip_code'        => $_POST['zip_code']        ?? null,
            'city'            => $_POST['city']            ?? null,
            'country'         => $_POST['country']         ?? 'Suíça',
            'complement'      => $_POST['complement']      ?? null,
            'about'           => $_POST['about']           ?? null,
            'phone'           => $_POST['phone']           ?? null,
            'phone2'          => $_POST['phone2']          ?? null,
            'mobile'          => $_POST['mobile']          ?? null,
            'fax'             => $_POST['fax']             ?? null,
            'email'           => $_POST['email']           ?? null,
            'email2'          => $_POST['email2']          ?? null,
            'website'         => $_POST['website']         ?? null,
            'skype'           => $_POST['skype']           ?? null,
            'contact_person'  => $_POST['contact_person']  ?? null,
            'owner'           => $_POST['owner']           ?? null,
            'correspondence'  => $_POST['correspondence']  ?? 'Mail',
            'language'        => $_POST['language']        ?? 'pt',
            'category'        => $_POST['category']        ?? null,
            'branch'          => $_POST['branch']          ?? null,
            'employee_count'  => (int)($_POST['employee_count']  ?? 0),
            'registry_number' => $_POST['registry_number'] ?? null,
            'vat_number'      => $_POST['vat_number']      ?? null,
            'tax_id_number'   => $_POST['tax_id_number']   ?? null,
            'active'          => isset($_POST['active']) ? 1 : 1, // sempre ativo ao criar/atualizar
            'profile_picture' => null,
        ];

        if (!empty($_FILES['profile_picture']['name']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $tmp  = $_FILES['profile_picture']['tmp_name'];
            $ext  = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $name = uniqid('client_').'.'.$ext;
            $dest = __DIR__ . '/../../public/uploads/clients/' . $name;
            if (! is_dir(dirname($dest))) {
                mkdir(dirname($dest), 0755, true);
            }
            if (move_uploaded_file($tmp, $dest)) {
                $data['profile_picture'] = $name;
            }
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $clientModel->update($id, $data);
        } else {
            $clientModel->create($data);
        }

        // Se for AJAX/fetch, retorna 200
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        ) {
            http_response_code(200);
            exit;
        }

        header("Location: {$this->baseUrl}/clients");
        exit;
    }

    /**
     * Exclui de verdade um cliente do banco.
     */
    public function delete(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            $clientModel = new Client();
            $clientModel->delete($id);
        }

        // Se chamada via AJAX, só devolve 200
        if (
            !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        ) {
            http_response_code(200);
            exit;
        }

        // Se não for AJAX, redireciona
        header("Location: {$this->baseUrl}/clients");
        exit;
    }

}
