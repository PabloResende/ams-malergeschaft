<?php
// app/controllers/FinancialController.php

require_once __DIR__ . '/../models/TransactionModel.php';
require_once __DIR__ . '/../models/Project.php';
require_once __DIR__ . '/../models/Employees.php';
require_once __DIR__ . '/../models/Clients.php';

class FinancialController
{
    public function index()
    {
        $start    = $_GET['start']    ?? date('Y-m-01');
        $end      = $_GET['end']      ?? date('Y-m-d');
        $type     = $_GET['type']     ?? '';
        $category = $_GET['category'] ?? '';

        $transactions = TransactionModel::getAll([
            'start'    => $start,
            'end'      => $end,
            'type'     => $type,
            'category' => $category,
        ]);
        $summary   = TransactionModel::getSummary($start, $end);
        $projects  = ProjectModel::getAll();
        $employees = Employee::all();
        $clients   = Client::all();

        // Categorias fixas
        $categories = [
            ['value'=>'funcionarios',      'name'=>'Funcionários',        'assoc'=>'employee'],
            ['value'=>'clientes',          'name'=>'Clientes',            'assoc'=>'client'],
            ['value'=>'projetos',          'name'=>'Projetos',            'assoc'=>'project'],
            ['value'=>'compras_materiais', 'name'=>'Compras de Materiais', 'assoc'=>null],
            ['value'=>'emprestimos',       'name'=>'Empréstimos',         'assoc'=>null],
            ['value'=>'gastos_gerais',     'name'=>'Gastos Gerais',       'assoc'=>null],
        ];

        $baseUrl = '/ams-malergeschaft/public';
        require __DIR__ . '/../views/finance/index.php';
    }

    public function edit()
    {
        $id = (int)($_GET['id'] ?? 0);
        $tx = TransactionModel::find($id);
        if (!$tx) {
            http_response_code(404);
            echo json_encode(['error'=>'ID não encontrado']);
            exit;
        }
        $tx['attachments'] = TransactionModel::getAttachments($id);
        $debt = TransactionModel::getDebt($id);
        $tx['due_date']               = $debt['due_date']           ?? null;
        $tx['installments_count']     = $debt['installments_count'] ?? null;
        $tx['initial_payment']        = $debt['initial_payment']    ?? 0;
        $tx['initial_payment_amount'] = $debt['initial_payment_amount'] ?? null;

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($tx);
        exit;
    }

    public function store()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $userId = $_SESSION['user']['id'] ?? null;

        $data = [
            'user_id'     => $userId,
            'category'    => $_POST['category']     ?? null,
            'type'        => $_POST['type']         ?? null,
            'client_id'   => $_POST['client_id']    ?? null,
            'project_id'  => $_POST['project_id']   ?? null,
            'employee_id' => $_POST['employee_id']  ?? null,
            'amount'      => $_POST['amount']       ?? null,
            'date'        => $_POST['date']         ?? null,
            'description' => $_POST['description']  ?? '',
        ];

        // validação simples
        foreach (['category','type','amount','date'] as $f) {
            if (empty($data[$f])) {
                die("Campo obrigatório faltando: $f");
            }
        }

        $attachments = $this->processAttachments($_FILES['attachments'] ?? null);

        $debtData = null;
        if ($data['type']==='debt') {
            $debtData = [
                'client_id'              => $_POST['client_id']             ?? null,
                'amount'                 => $data['amount'],
                'due_date'               => $_POST['due_date']              ?? null,
                'status'                 => 'open',
                'project_id'             => $_POST['project_id']            ?? null,
                'installments_count'     => $_POST['installments_count']    ?? null,
                'initial_payment'        => isset($_POST['initial_payment'])?1:0,
                'initial_payment_amount' => $_POST['initial_payment_amount'] ?? null,
            ];
        }

        TransactionModel::store($data, $attachments, $debtData);
        header('Location: '.$_SERVER['HTTP_REFERER']);
    }

    public function update()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $id = (int)($_POST['id'] ?? 0);

        $data = [
            'category'    => $_POST['category']     ?? null,
            'type'        => $_POST['type']         ?? null,
            'client_id'   => $_POST['client_id']    ?? null,
            'project_id'  => $_POST['project_id']   ?? null,
            'employee_id' => $_POST['employee_id']  ?? null,
            'amount'      => $_POST['amount']       ?? null,
            'date'        => $_POST['date']         ?? null,
            'description' => $_POST['description']  ?? '',
        ];

        foreach (['category','type','amount','date'] as $f) {
            if (empty($data[$f])) {
                die("Campo obrigatório faltando: $f");
            }
        }

        $attachments = $this->processAttachments($_FILES['attachments'] ?? null);

        $debtData = null;
        if ($data['type']==='debt') {
            $debtData = [
                'client_id'              => $_POST['client_id']             ?? null,
                'amount'                 => $data['amount'],
                'due_date'               => $_POST['due_date']              ?? null,
                'status'                 => $_POST['status']                ?? 'open',
                'project_id'             => $_POST['project_id']            ?? null,
                'installments_count'     => $_POST['installments_count']    ?? null,
                'initial_payment'        => isset($_POST['initial_payment'])?1:0,
                'initial_payment_amount' => $_POST['initial_payment_amount'] ?? null,
            ];
        }

        TransactionModel::update($id, $data, $attachments, $debtData);
        header('Location: '.$_SERVER['HTTP_REFERER']);
    }

    public function delete()
    {
        if (isset($_GET['id'])) {
            TransactionModel::delete((int)$_GET['id']);
        }
        header('Location: '.$_SERVER['HTTP_REFERER']);
    }

    private function processAttachments($files): array
    {
        $res = [];
        if (!$files || !isset($files['tmp_name'])) return $res;
        $dir = __DIR__ . '/../../public/uploads/finance/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        foreach ($files['tmp_name'] as $i => $tmp) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
            $ext  = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
            $name = time() . '_' . uniqid() . '.' . $ext;
            move_uploaded_file($tmp, $dir . $name);
            $res[] = ['file_path' => 'uploads/finance/' . $name];
        }
        return $res;
    }
}
