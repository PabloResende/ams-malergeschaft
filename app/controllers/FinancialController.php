<?php
// app/controllers/FinancialController.php

require_once __DIR__ . '/../models/TransactionModel.php';
require_once __DIR__ . '/../models/FinanceCategoryModel.php';

class FinancialController
{
    /**
     * Exibe a lista de transações e o dashboard financeiro.
     */
    public function index()
    {
        $start  = $_GET['start']       ?? date('Y-m-01');
        $end    = $_GET['end']         ?? date('Y-m-d');
        $type   = $_GET['type']        ?? '';
        $cat    = $_GET['category_id'] ?? '';

        $transactions = TransactionModel::getAll([
            'start'       => $start,
            'end'         => $end,
            'type'        => $type,
            'category_id' => $cat,
        ]);
        $categories = FinanceCategoryModel::getAll();
        $summary    = TransactionModel::getSummary($start, $end);

        require __DIR__ . '/../views/finance/index.php';
    }

    /**
     * Retorna dados de uma transação (JSON) para editar via modal.
     */
    public function get()
    {
        if (empty($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'ID é obrigatório']);
            return;
        }
        $id = (int) $_GET['id'];
        $tx = TransactionModel::find($id);
        if (! $tx) {
            http_response_code(404);
            echo json_encode(['error' => 'Transação não encontrada']);
            return;
        }
        $attachments = TransactionModel::getAttachments($id);
        $debt        = TransactionModel::getDebt($id);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'transaction' => $tx,
            'attachments' => $attachments,
            'debt'        => $debt,
        ]);
    }

    /**
     * Cria uma nova transação.
     */
    public function store()
    {
        session_start();
        $data = [
            'user_id'     => $_SESSION['user']['id'],
            'category_id' => $_POST['category_id'],
            'type'        => $_POST['type'],
            'amount'      => $_POST['amount'],
            'date'        => $_POST['date'],
            'description' => $_POST['description'] ?? '',
        ];

        $attachments = $this->processAttachments($_FILES['attachments'] ?? null);

        $debtData = null;
        if ($data['type'] === 'debt') {
            $debtData = [
                'client_id' => $_POST['client_id'] ?? null,
                'amount'    => $data['amount'],
                'due_date'  => $_POST['due_date'],
                'status'    => 'open',
            ];
        }

        TransactionModel::store($data, $attachments, $debtData);

        header('Location: /ams-malergeschaft/public/finance');
    }

    /**
     * Atualiza uma transação existente.
     */
    public function update()
    {
        session_start();
        $id = (int) $_POST['id'];
        $data = [
            'category_id' => $_POST['category_id'],
            'type'        => $_POST['type'],
            'amount'      => $_POST['amount'],
            'date'        => $_POST['date'],
            'description' => $_POST['description'] ?? '',
        ];

        $attachments = $this->processAttachments($_FILES['attachments'] ?? null);

        $debtData = null;
        if ($data['type'] === 'debt') {
            $debtData = [
                'client_id' => $_POST['client_id'] ?? null,
                'amount'    => $data['amount'],
                'due_date'  => $_POST['due_date'],
                'status'    => $_POST['debt_status'] ?? 'open',
            ];
        }

        TransactionModel::update($id, $data, $attachments, $debtData);

        header('Location: /ams-malergeschaft/public/finance');
    }

    public function edit()
{
    // pega o ID
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    // busca os dados da transação
    $tx = TransactionModel::find($id);
    if (! $tx) {
        http_response_code(404);
        echo json_encode(['error' => 'Transação não encontrada.']);
        exit;
    }

    // anexa comprovantes
    $tx['attachments'] = TransactionModel::getAttachments($id);

    // devolve JSON e termina aqui
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($tx);
    exit;
}

    /**
     * Remove transação, anexos e registro de dívida.
     */
    public function delete()
    {
        if (!isset($_GET['id'])) {
            header('Location: /ams-malergeschaft/public/finance');
            return;
        }
        $id = (int) $_GET['id'];
        TransactionModel::delete($id);
        header('Location: /ams-malergeschaft/public/finance');
    }

    /**
     * Gera relatório simples (sem botões de ação).
     */
    public function report()
    {
        $start  = $_GET['start'] ?? date('Y-m-01');
        $end    = $_GET['end']   ?? date('Y-m-d');

        $transactions = TransactionModel::getAll([
            'start' => $start,
            'end'   => $end,
        ]);
        $summary = TransactionModel::getSummary($start, $end);

        require __DIR__ . '/../views/finance/report.php';
    }

    /**
     * Download de um anexo específico.
     */
    public function downloadAttachment()
    {
        if (empty($_GET['id'])) {
            http_response_code(400);
            return;
        }
        $id = (int) $_GET['id'];
        $pdo = TransactionModel::connect();
        $stmt = $pdo->prepare("SELECT file_path FROM transaction_attachments WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (! $row) {
            http_response_code(404);
            return;
        }
        $path = __DIR__ . '/../../public/' . $row['file_path'];
        if (! file_exists($path)) {
            http_response_code(404);
            return;
        }
        header('Content-Disposition: attachment; filename="'.basename($path).'"');
        readfile($path);
    }

    /**
     * Processa uploads e retorna array de ['file_path'=>string].
     */
    private function processAttachments($files): array
    {
        $result = [];
        if (! $files || ! isset($files['tmp_name']) ) {
            return $result;
        }
        $uploadDir = __DIR__ . '/../../public/uploads/finance/';
        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        foreach ($files['tmp_name'] as $i => $tmp) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }
            $ext  = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
            $name = time() . '_' . uniqid() . '.' . $ext;
            move_uploaded_file($tmp, $uploadDir . $name);
            $result[] = ['file_path' => 'uploads/finance/' . $name];
        }
        return $result;
    }
}
