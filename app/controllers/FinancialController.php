<?php
// app/controllers/FinancialController.php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/TransactionModel.php';
require_once __DIR__ . '/../models/Project.php';
require_once __DIR__ . '/../models/Employees.php';
require_once __DIR__ . '/../models/Clients.php';

class FinancialController
{
    private array $langText;
    private string $baseUrl = '$basePath';

    public function __construct()
    {
        // 1) Inicia sessão e detecta idioma
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'pt';
        $_SESSION['lang'] = $lang;

        // 2) Monta o caminho para app/lang/{pt,en,de,fr}.php
        $langFile = __DIR__ . '/../lang/' . $lang . '.php';

        // 3) Fallback para pt se não existir
        if (! file_exists($langFile)) {
            $langFile = __DIR__ . '/../lang/pt.php';
        }

        // 4) Carrega o array de traduções
        $this->langText = require $langFile;
    }

    public function index()
    {
        // Extrai as traduções para a view
        $langText = $this->langText;

        // 1) Se o usuário não enviou start/end, valores ficam nulos
        $start    = $_GET['start']    ?? null;
        $end      = $_GET['end']      ?? null;
        $type     = $_GET['type']     ?? '';
        $category = $_GET['category'] ?? '';

        // 2) Busca transações: sem filtro de data se start/end forem nulos
        $transactions = TransactionModel::getAll([
            'start'    => $start,
            'end'      => $end,
            'type'     => $type,
            'category' => $category,
        ]);

        // 3) Resumo também ajustado: totaliza tudo ou por intervalo
        $summary   = TransactionModel::getSummary($start, $end);
        $projects  = ProjectModel::getAll();
        $employees = Employee::all();
        $clients   = Client::all();

        // Dropdown de categorias (mantive igual)
        $categories = [
            ['value'=>'funcionarios',      'name'=>$langText['category_funcionarios'],      'assoc'=>'employee'],
            ['value'=>'clientes',          'name'=>$langText['category_clientes'],          'assoc'=>'client'],
            ['value'=>'projetos',          'name'=>$langText['category_projetos'],          'assoc'=>'project'],
            ['value'=>'compras_materiais', 'name'=>$langText['category_compras_materiais'], 'assoc'=>null],
            ['value'=>'emprestimos',       'name'=>$langText['category_emprestimos'],       'assoc'=>null],
            ['value'=>'gastos_gerais',     'name'=>$langText['category_gastos_gerais'],     'assoc'=>null],
            ['value'=>'parcelamento',      'name'=>$langText['category_parcelamento'],      'assoc'=>null],
        ];

        require __DIR__ . '/../views/finance/index.php';
    }

    public function edit()
    {
        $langText = $this->langText;

        $id = (int)($_GET['id'] ?? 0);
        $tx = TransactionModel::find($id);
        if (!$tx) {
            http_response_code(404);
            echo json_encode(['error' => $langText['error_tx_not_found']]);
            exit;
        }
        $tx['attachments']            = TransactionModel::getAttachments($id);
        $debt                         = TransactionModel::getDebt($id);
        $tx['due_date']               = $debt['due_date']             ?? null;
        $tx['installments_count']     = $debt['installments_count']   ?? null;
        $tx['initial_payment']        = $debt['initial_payment']      ?? 0;
        $tx['initial_payment_amount'] = $debt['initial_payment_amount'] ?? null;

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($tx);
        exit;
    }

    public function store()
    {
        $langText = $this->langText;

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $userId = $_SESSION['user']['id'] ?? null;

        $data = [
            'user_id'     => $userId,
            'category'    => $_POST['category']            ?? null,
            'type'        => $_POST['type']                ?? null,
            'client_id'   => $_POST['client_id']           ?? null,
            'project_id'  => $_POST['project_id']          ?? null,
            'employee_id' => $_POST['employee_id']         ?? null,
            'amount'      => $_POST['amount']              ?? null,
            'date'        => $_POST['date']                ?? null,
            'description' => $_POST['description']         ?? '',
        ];

        // Validação básica
        foreach (['category','type','amount','date'] as $f) {
            if (empty($data[$f])) {
                die(htmlspecialchars($langText['required_field_missing'] . $f, ENT_QUOTES));
            }
        }

        $attachments = $this->processAttachments($_FILES['attachments'] ?? null);

        $debtData = null;
        if ($data['type'] === 'debt' || $data['category'] === 'parcelamento') {
            $debtData = [
                'client_id'              => $_POST['client_id']             ?? null,
                'amount'                 => $data['amount'],
                'due_date'               => $_POST['due_date']              ?? null,
                'status'                 => 'open',
                'project_id'             => $_POST['project_id']            ?? null,
                'installments_count'     => $_POST['installments_count']    ?? null,
                'initial_payment'        => isset($_POST['initial_payment']) ? 1 : 0,
                'initial_payment_amount' => $_POST['initial_payment_amount'] ?? null,
            ];
        }

        TransactionModel::store($data, $attachments, $debtData);
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? $this->baseUrl . '/finance'));
        exit;
    }

    public function update()
    {
        $langText = $this->langText;

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $id = (int)($_POST['id'] ?? 0);

        $data = [
            'category'    => $_POST['category']            ?? null,
            'type'        => $_POST['type']                ?? null,
            'client_id'   => $_POST['client_id']           ?? null,
            'project_id'  => $_POST['project_id']          ?? null,
            'employee_id' => $_POST['employee_id']         ?? null,
            'amount'      => $_POST['amount']              ?? null,
            'date'        => $_POST['date']                ?? null,
            'description' => $_POST['description']         ?? '',
        ];

        foreach (['category','type','amount','date'] as $f) {
            if (empty($data[$f])) {
                die(htmlspecialchars($langText['required_field_missing'] . $f, ENT_QUOTES));
            }
        }

        $attachments = $this->processAttachments($_FILES['attachments'] ?? null);

        $debtData = null;
        if ($data['type'] === 'debt' || $data['category'] === 'parcelamento') {
            $debtData = [
                'client_id'              => $_POST['client_id']             ?? null,
                'amount'                 => $data['amount'],
                'due_date'               => $_POST['due_date']              ?? null,
                'status'                 => $_POST['status']                ?? 'open',
                'project_id'             => $_POST['project_id']            ?? null,
                'installments_count'     => $_POST['installments_count']    ?? null,
                'initial_payment'        => isset($_POST['initial_payment']) ? 1 : 0,
                'initial_payment_amount' => $_POST['initial_payment_amount'] ?? null,
            ];
        }

        TransactionModel::update($id, $data, $attachments, $debtData);
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? $this->baseUrl . '/finance'));
        exit;
    }

    public function delete()
    {
        if (isset($_GET['id'])) {
            TransactionModel::delete((int)$_GET['id']);
        }
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? $this->baseUrl . '/finance'));
        exit;
    }

    private function processAttachments($files): array
    {
        $res = [];
        if (!$files || !isset($files['tmp_name'])) {
            return $res;
        }
        $dir = __DIR__ . '/../../public/uploads/finance/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        foreach ($files['tmp_name'] as $i => $tmp) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }
            $ext  = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
            $name = time() . '_' . uniqid() . '.' . $ext;
            move_uploaded_file($tmp, $dir . $name);
            $res[] = ['file_path' => 'uploads/finance/' . $name];
        }
        return $res;
    }
}
