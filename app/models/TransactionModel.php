<?php
// app/models/TransactionModel.php
require_once __DIR__ . '/../../config/Database.php';

class TransactionModel
{
    // Mapear enum → rótulo legível
    private static array $categoryMap = [
        'funcionarios'      => 'Funcionários',
        'clientes'          => 'Clientes',
        'projetos'          => 'Projetos',
        'compras_materiais' => 'Compras de Materiais',
        'emprestimos'       => 'Empréstimos',
        'gastos_gerais'     => 'Gastos Gerais',
    ];

    public static function connect(): PDO
    {
        return Database::connect();
    }

    public static function getAll(array $f = []): array
    {
        $sql = "
            SELECT 
              ft.*, d.due_date, d.installments_count, d.initial_payment
            FROM financial_transactions ft
            LEFT JOIN debts d ON d.transaction_id = ft.id
            WHERE ft.date BETWEEN ? AND ?
        ";
        $params = [$f['start'], $f['end']];
        if (!empty($f['type'])) {
            $sql      .= " AND ft.type = ?";
            $params[]  = $f['type'];
        }
        if (!empty($f['category'])) {
            $sql      .= " AND ft.category = ?";
            $params[]  = $f['category'];
        }
        $sql .= " ORDER BY ft.date DESC";

        $stmt = self::connect()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Nome legível e retornos
        foreach ($rows as &$row) {
            $row['category_name'] = self::$categoryMap[$row['category']] ?? '';
        }
        return $rows;
    }

    public static function getSummary(string $start, string $end): array
    {
        $pdo = self::connect();
        $in  = $pdo->prepare(
          "SELECT COALESCE(SUM(amount),0) FROM financial_transactions
           WHERE type='income'  AND date BETWEEN ? AND ?"
        );
        $ex  = $pdo->prepare(
          "SELECT COALESCE(SUM(amount),0) FROM financial_transactions
           WHERE type='expense' AND date BETWEEN ? AND ?"
        );
        $in->execute([$start, $end]);
        $ex->execute([$start, $end]);
        $totIn = (float)$in->fetchColumn();
        $totEx = (float)$ex->fetchColumn();
        return [
          'income'  => $totIn,
          'expense' => $totEx,
          'net'     => $totIn - $totEx
        ];
    }

    public static function find(int $id)
    {
        $stmt = self::connect()
            ->prepare("SELECT * FROM financial_transactions WHERE id = ?");
        $stmt->execute([$id]);
        $tx = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($tx) {
            $tx['category_name'] = self::$categoryMap[$tx['category']] ?? '';
        }
        return $tx;
    }

    public static function store(
        array $data,
        array $attachments = [],
        array $debtData = null
    ): int {
        $pdo = self::connect();
        $stmt = $pdo->prepare("
            INSERT INTO financial_transactions
              (user_id,category,type,client_id,project_id,employee_id,amount,date,description)
            VALUES (?,?,?,?,?,?,?,?,?)
        ");
        $stmt->execute([
            $data['user_id'],
            $data['category'],
            $data['type'],
            $data['client_id']    ?: null,
            $data['project_id']   ?: null,
            $data['employee_id']  ?: null,
            $data['amount'],
            $data['date'],
            $data['description']
        ]);
        $txId = (int)$pdo->lastInsertId();

        // anexos
        foreach ($attachments as $a) {
            $pdo->prepare("
                INSERT INTO transaction_attachments
                  (transaction_id,file_path) VALUES (?,?)
            ")->execute([$txId, $a['file_path']]);
        }

        // dívida (continua na tabela debts)
        if ($debtData && $data['type']==='debt') {
            $pdo->prepare("
                INSERT INTO debts
                  (client_id,transaction_id,project_id,amount,due_date,status,installments_count,initial_payment)
                VALUES (?,?,?,?,?,?,?,?)
            ")->execute([
                $debtData['client_id'],
                $txId,
                $debtData['project_id']        ?: null,
                $debtData['amount'],
                $debtData['due_date'],
                $debtData['status'],
                $debtData['installments_count'],
                $debtData['initial_payment']
            ]);
        }

        return $txId;
    }

    public static function update(
        int $id,
        array $data,
        array $attachments = [],
        array $debtData = null
    ): void {
        $pdo = self::connect();
        $pdo->prepare("
            UPDATE financial_transactions
            SET category=?,type=?,client_id=?,project_id=?,employee_id=?,amount=?,date=?,description=?
            WHERE id=?
        ")->execute([
            $data['category'],
            $data['type'],
            $data['client_id']    ?: null,
            $data['project_id']   ?: null,
            $data['employee_id']  ?: null,
            $data['amount'],
            $data['date'],
            $data['description'],
            $id
        ]);

        // anexos novos
        foreach ($attachments as $a) {
            $pdo->prepare("
                INSERT INTO transaction_attachments
                  (transaction_id,file_path) VALUES (?,?)
            ")->execute([$id, $a['file_path']]);
        }

        // dívida
        $existDebt = self::connect()
            ->prepare("SELECT 1 FROM debts WHERE transaction_id = ?")
            ->execute([$id]);
        if ($data['type']==='debt' && $debtData) {
            // (mesma lógica de insert/update de debts)
        } elseif ($existDebt && $data['type']!=='debt') {
            $pdo->prepare("DELETE FROM debts WHERE transaction_id = ?")
                ->execute([$id]);
        }
    }

    public static function delete(int $id): void
    {
        $pdo = self::connect();
        $pdo->prepare("DELETE FROM transaction_attachments WHERE transaction_id = ?")
            ->execute([$id]);
        $pdo->prepare("DELETE FROM debts WHERE transaction_id = ?")
            ->execute([$id]);
        $pdo->prepare("DELETE FROM financial_transactions WHERE id = ?")
            ->execute([$id]);
    }

    public static function getAttachments(int $txId): array { /* ... */ }
    public static function getDebt(int $txId)        { /* ... */ }
}
