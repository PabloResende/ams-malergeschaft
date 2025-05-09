<?php
// app/controllers/FinancialController.php
require_once __DIR__ . '/../models/FinancialTransactionModel.php';
require_once __DIR__ . '/../models/FinanceCategoryModel.php';
require_once __DIR__ . '/../models/TransactionAttachmentModel.php';
require_once __DIR__ . '/../models/DebtModel.php';

class FinancialController
{
    public function index()
    {
        // filtros
        $start = $_GET['start'] ?? date('Y-m-01');
        $end   = $_GET['end']   ?? date('Y-m-d');
        $type  = $_GET['type']  ?? '';
        $cat   = $_GET['category_id'] ?? '';

        $transactions = FinancialTransactionModel::getAll([
            'start'=>$start,'end'=>$end,'type'=>$type,'category_id'=>$cat
        ]);
        $categories = FinanceCategoryModel::getAll();

        $summary = FinancialTransactionModel::getSummary($start, $end);

        require __DIR__ . '/../views/finance/index.php';
    }

    public function create()
    {
        $categories = FinanceCategoryModel::getAll();
        require __DIR__ . '/../views/finance/create.php';
    }

    public function store()
    {
        // validações mínimas
        $data = [
            'user_id'     => $_SESSION['user']['id'],
            'category_id' => $_POST['category_id'],
            'type'        => $_POST['type'],
            'amount'      => $_POST['amount'],
            'date'        => $_POST['date'],
            'description' => $_POST['description'] ?? ''
        ];
        $tx_id = FinancialTransactionModel::store($data);

        // anexos
        if (!empty($_FILES['attachments']['name'][0])) {
            $this->handleAttachments($_FILES['attachments'], $tx_id);
        }

        // se for dívida, crie registro
        if ($data['type']==='debt') {
            DebtModel::store([
                'client_id'=>$_POST['client_id'] ?? null,
                'transaction_id'=>$tx_id,
                'amount'=>$data['amount'],
                'due_date'=>$_POST['due_date']
            ]);
        }

        header('Location: /ams-malergeschaft/public/finance');
    }

    public function edit()
    {
        $id = $_GET['id'];
        $tx = FinancialTransactionModel::find($id);
        $categories = FinanceCategoryModel::getAll();
        $attachments = TransactionAttachmentModel::findByTransaction($id);
        require __DIR__ . '/../views/finance/edit.php';
    }

    public function update()
    {
        $id = $_POST['id'];
        $data = [
            'category_id'=>$_POST['category_id'],
            'type'       =>$_POST['type'],
            'amount'     =>$_POST['amount'],
            'date'       =>$_POST['date'],
            'description'=>$_POST['description'] ?? ''
        ];
        FinancialTransactionModel::update($id, $data);

        // novos anexos
        if (!empty($_FILES['attachments']['name'][0])) {
            $this->handleAttachments($_FILES['attachments'], $id);
        }

        header('Location: /ams-malergeschaft/public/finance');
    }

    public function delete()
    {
        FinancialTransactionModel::delete($_GET['id']);
        header('Location: /ams-malergeschaft/public/finance');
    }

    public function report()
    {
        // gera relatório simples: totais e lista
        $start = $_GET['start'] ?? date('Y-m-01');
        $end   = $_GET['end']   ?? date('Y-m-d');
        $summary = FinancialTransactionModel::getSummary($start, $end);
        $transactions = FinancialTransactionModel::getAll(['start'=>$start,'end'=>$end]);
        require __DIR__ . '/../views/finance/report.php';
    }

    public function downloadAttachment()
    {
        $attach = TransactionAttachmentModel::find($_GET['id']);
        $path = __DIR__ . '/../../public/' . $attach['file_path'];
        if (file_exists($path)) {
            header('Content-Disposition: attachment; filename="'.basename($path).'"');
            readfile($path);
        }
    }

    private function handleAttachments($files, $transaction_id)
    {
        $uploadDir = __DIR__ . '/../../public/uploads/finance/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        foreach ($files['tmp_name'] as $i=>$tmp) {
            if ($err = $files['error'][$i]) continue;
            $ext = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
            $name = time().'_'.uniqid().'.'.$ext;
            move_uploaded_file($tmp, $uploadDir.$name);
            TransactionAttachmentModel::store([
                'transaction_id'=>$transaction_id,
                'file_path'=>'uploads/finance/'.$name
            ]);
        }
    }
}
