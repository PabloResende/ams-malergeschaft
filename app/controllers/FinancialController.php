// app/controllers/FinancialController.php
<?php
require_once __DIR__ . '/../models/TransactionModel.php';
require_once __DIR__ . '/../models/FinanceCategoryModel.php';
require_once __DIR__ . '/../models/Project.php';

class FinancialController
{
    public function index()
    {
        $start       = $_GET['start']       ?? date('Y-m-01');
        $end         = $_GET['end']         ?? date('Y-m-d');
        $type        = $_GET['type']        ?? '';
        $category_id = $_GET['category_id'] ?? '';

        $transactions  = TransactionModel::getAll([
            'start'       => $start,
            'end'         => $end,
            'type'        => $type,
            'category_id' => $category_id,
        ]);
        $allCategories = FinanceCategoryModel::getAll();
        $summary       = TransactionModel::getSummary($start, $end);
        $projects      = ProjectModel::getAll();

        $baseUrl = '/ams-malergeschaft/public';
        require __DIR__ . '/../views/finance/index.php';
    }

    public function edit()
    {
        $id = (int)($_GET['id'] ?? 0);
        $tx = TransactionModel::find($id);
        if (!$tx) {
            http_response_code(404);
            echo json_encode(['error' => 'ID não encontrado']);
            exit;
        }
        $tx['attachments']        = TransactionModel::getAttachments($id);
        $debt                     = TransactionModel::getDebt($id);
        $tx['due_date']           = $debt['due_date']           ?? null;
        $tx['installments_count'] = $debt['installments_count'] ?? null;
        $tx['initial_payment']    = $debt['initial_payment']    ?? 0;
        $tx['project_id']         = $debt['project_id']         ?? null;

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($tx);
        exit;
    }

    public function store()
    {
        session_start();
        $data = [
            'user_id'     => $_SESSION['user']['id'],
            'category_id' => $_POST['category_id'],
            'type'        => $_POST['type'],
            'amount'      => $_POST['amount'],
            'date'        => $_POST['date'],
            'description' => $_POST['description'] ?? ''
        ];
        $attachments = $this->processAttachments($_FILES['attachments'] ?? null);

        $debtData = null;
        if ($data['type']==='debt') {
            $debtData = [
                'client_id'               => $_POST['client_id'] ?? null,
                'amount'                  => $data['amount'],
                'due_date'                => $_POST['due_date'],
                'status'                  => 'open',
                'project_id'              => $_POST['project_id'] ?? null,
                'installments_count'      => $_POST['installments_count'] ?? null,
                'initial_payment'         => isset($_POST['initial_payment']) ? 1 : 0,
                'initial_payment_amount'  => $_POST['initial_payment_amount'] ?? null,
            ];
        }

        TransactionModel::store($data, $attachments, $debtData);
        header('Location: /ams-malergeschaft/public/finance');
    }

    public function update()
    {
        session_start();
        $id = (int)$_POST['id'];
        $data = [
            'category_id' => $_POST['category_id'],
            'type'        => $_POST['type'],
            'amount'      => $_POST['amount'],
            'date'        => $_POST['date'],
            'description' => $_POST['description'] ?? ''
        ];
        $attachments = $this->processAttachments($_FILES['attachments'] ?? null);

        $debtData = null;
        if ($data['type']==='debt') {
            $debtData = [
                'client_id'               => $_POST['client_id'] ?? null,
                'amount'                  => $data['amount'],
                'due_date'                => $_POST['due_date'],
                'status'                  => $_POST['status'] ?? 'open',
                'project_id'              => $_POST['project_id'] ?? null,
                'installments_count'      => $_POST['installments_count'] ?? null,
                'initial_payment'         => isset($_POST['initial_payment']) ? 1 : 0,
                'initial_payment_amount'  => $_POST['initial_payment_amount'] ?? null,
            ];
        }

        TransactionModel::update($id, $data, $attachments, $debtData);
        header('Location: /ams-malergeschaft/public/finance');
    }

    public function delete()
    {
        if (!isset($_GET['id'])) {
            header('Location: /ams-malergeschaft/public/finance');
            return;
        }
        TransactionModel::delete((int)$_GET['id']);
        header('Location: /ams-malergeschaft/public/finance');
    }

    private function processAttachments($files): array
    {
        $r = [];
        if (!$files || !isset($files['tmp_name'])) return $r;
        $dir = __DIR__ . '/../../public/uploads/finance/';
        if (!is_dir($dir)) mkdir($dir,0755,true);
        foreach($files['tmp_name'] as $i=>$tmp) {
            if ($files['error'][$i]!==UPLOAD_ERR_OK) continue;
            $ext  = pathinfo($files['name'][$i],PATHINFO_EXTENSION);
            $name = time().'_'.uniqid().'.'.$ext;
            move_uploaded_file($tmp,$dir.$name);
            $r[] = ['file_path'=>'uploads/finance/'.$name];
        }
        return $r;
    }
}
