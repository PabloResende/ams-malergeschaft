<?php
// system/app/controllers/FinancialController.php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/TransactionModel.php';
require_once __DIR__ . '/../models/Project.php';
require_once __DIR__ . '/../models/Employees.php';
require_once __DIR__ . '/../models/Clients.php';

class FinancialController
{
    private $langText;
    private $baseUrl;

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Escolhe idioma
        $lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'pt';
        $_SESSION['lang'] = $lang;
        $langFile = __DIR__ . "/../lang/{$lang}.php";
        if (! file_exists($langFile)) {
            $langFile = __DIR__ . '/../lang/pt.php';
        }
        $this->langText = require $langFile;
        $this->baseUrl  = BASE_URL;
    }

    /**
     * Exibe a tela principal de Financeiro, com calendário.
     */
    public function index(): void
    {
        $langText = $this->langText;

        // parâmetros de filtro
        $start    = $_GET['start']    ?? date('Y-m-01');
        $end      = $_GET['end']      ?? date('Y-m-d');
        $type     = $_GET['type']     ?? '';
        $category = $_GET['category'] ?? '';

        // modelos
        $txModel      = new TransactionModel();
        $projectModel = new ProjectModel();
        $empModel     = new Employee();
        $clientModel  = new Client();

        // busca transações
        if ($start && $end) {
            $transactions = $txModel->getFiltered($type, $start, $end, $category);
        } else {
            $transactions = $txModel->getAll();
        }

        // resumo e listas auxiliares
        $summary   = $txModel->getSummary($start, $end);
        $projects  = $projectModel->getAll();
        $employees = $empModel->all();
        $clients   = $clientModel->all();

        // categorias com fallback de rótulos
        $categories = [
            ['value'=>'funcionarios',      'name'=>$langText['category_funcionarios']      ?? 'Funcionários',      'assoc'=>'funcionarios'],
            ['value'=>'clientes',          'name'=>$langText['category_clientes']          ?? 'Clientes',          'assoc'=>'clientes'],
            ['value'=>'projetos',          'name'=>$langText['category_projetos']          ?? 'Projetos',          'assoc'=>'projetos'],
            ['value'=>'compras_materiais', 'name'=>$langText['category_compras_materiais'] ?? 'Compras de Materiais','assoc'=>'compras_materiais'],
            ['value'=>'emprestimos',       'name'=>$langText['category_emprestimos']       ?? 'Empréstimos',       'assoc'=>'emprestimos'],
            ['value'=>'gastos_gerais',     'name'=>$langText['category_gastos_gerais']     ?? 'Gastos Gerais',     'assoc'=>'gastos_gerais'],
            ['value'=>'parcelamento',      'name'=>$langText['category_parcelamento']      ?? 'Parcelamento',      'assoc'=>'parcelamento'],
        ];

        // monta array de eventos para o FullCalendar
        $events = [];
        foreach ($transactions as $tx) {
            $catName = $langText['category_' . $tx['category']] ?? ucfirst($tx['category']);
            $events[] = [
                'id'    => $tx['id'],
                'title' => $catName . ' – R$ ' . number_format($tx['amount'], 2, ',', '.'),
                'start' => $tx['date'],
            ];
        }

        // renderiza a view
        require __DIR__ . '/../../app/views/finance/index.php';
    }

    /**
     * Retorna JSON para editar transação.
     */
    public function edit(): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        $id = (int)($_GET['id'] ?? 0);
        $tx = TransactionModel::find($id);

        if (! $tx) {
            http_response_code(404);
            echo json_encode(['error' => 'Transação não encontrada']);
            exit;
        }

        // inclui anexos e dados de dívida
        $tx['attachments'] = TransactionModel::getAttachments($id);
        $debt = TransactionModel::getDebt($id);
        if ($debt) {
            $tx['due_date']               = $debt['due_date'];
            $tx['initial_payment']        = (bool)$debt['initial_payment'];
            $tx['initial_payment_amount'] = $debt['initial_payment_amount'];
            $tx['installments_count']     = $debt['installments_count'];
        }

        echo json_encode($tx);
        exit;
    }

    /**
     * Armazena nova transação.
     */
    public function store(): void
    {
        $userId = $_SESSION['user']['id'] ?? null;
        $data = [
            'user_id'     => $userId,
            'category'    => $_POST['category']  ?? null,
            'type'        => $_POST['type']      ?? null,
            'client_id'   => $_POST['client_id'] ?? null,
            'project_id'  => $_POST['project_id']?? null,
            'employee_id' => $_POST['employee_id']?? null,
            'amount'      => $_POST['amount']    ?? null,
            'date'        => $_POST['date']      ?? null,
            'description' => $_POST['description']?? '',
        ];

        // validações básicas
        foreach (['category','type','amount','date'] as $f) {
            if (empty($data[$f])) {
                die(htmlspecialchars($this->langText['required_field_missing'] . $f, ENT_QUOTES));
            }
        }

        $attachments = $this->processAttachments($_FILES['attachments'] ?? []);
        $debtData    = null;
        if ($data['type']==='debt' || $data['category']==='parcelamento') {
            $debtData = [
                'client_id'              => $_POST['client_id']              ?? null,
                'amount'                 => $data['amount'],
                'due_date'               => $_POST['due_date']               ?? null,
                'status'                 => 'open',
                'project_id'             => $_POST['project_id']             ?? null,
                'installments_count'     => $_POST['installments_count']     ?? null,
                'initial_payment'        => isset($_POST['initial_payment'])  ? 1 : 0,
                'initial_payment_amount' => $_POST['initial_payment_amount'] ?? null,
            ];
        }

        TransactionModel::store($data, $attachments, $debtData);
        header('Location: ' . ($this->baseUrl . '/finance'));
        exit;
    }

    /**
     * Atualiza transação existente.
     */
    public function update(): void
    {
        $id   = (int)($_POST['id'] ?? 0);
        $data = [
            'category'    => $_POST['category']   ?? null,
            'type'        => $_POST['type']       ?? null,
            'client_id'   => $_POST['client_id']  ?? null,
            'project_id'  => $_POST['project_id'] ?? null,
            'employee_id' => $_POST['employee_id']?? null,
            'amount'      => $_POST['amount']     ?? null,
            'date'        => $_POST['date']       ?? null,
            'description' => $_POST['description']?? '',
        ];

        foreach (['category','type','amount','date'] as $f) {
            if (empty($data[$f])) {
                die(htmlspecialchars($this->langText['required_field_missing'] . $f, ENT_QUOTES));
            }
        }

        $attachments = $this->processAttachments($_FILES['attachments'] ?? []);
        $debtData    = null;
        if ($data['type']==='debt' || $data['category']==='parcelamento') {
            $debtData = [
                'client_id'              => $_POST['client_id']              ?? null,
                'amount'                 => $data['amount'],
                'due_date'               => $_POST['due_date']               ?? null,
                'status'                 => $_POST['status']                 ?? 'open',
                'project_id'             => $_POST['project_id']             ?? null,
                'installments_count'     => $_POST['installments_count']     ?? null,
                'initial_payment'        => isset($_POST['initial_payment'])  ? 1 : 0,
                'initial_payment_amount' => $_POST['initial_payment_amount'] ?? null,
            ];
        }

        TransactionModel::update($id, $data, $attachments, $debtData);
        header('Location: ' . ($this->baseUrl . '/finance'));
        exit;
    }

    /**
     * Exclui transação.
     */
    public function delete(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            TransactionModel::delete($id);
        }
        header('Location: ' . ($this->baseUrl . '/finance'));
        exit;
    }

    /**
     * Processa uploads de anexos.
     *
     * @param array $files
     * @return array
     */
    private function processAttachments(array $files): array
    {
        $res = [];
        if (empty($files['tmp_name'])) {
            return $res;
        }

        $dir = __DIR__ . '/../../public/uploads/finance/';
        if (! is_dir($dir)) {
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
