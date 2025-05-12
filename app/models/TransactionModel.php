<?php
// app/models/TransactionModel.php
require_once(__DIR__ . '/../../config/Database.php');

class TransactionModel
{
    public static function connect(): PDO
    {
        return Database::connect();
    }

    public static function getAll(array $f = []): array
    {
        $sql = "
            SELECT 
              ft.id, ft.user_id, ft.category_id, ft.type,
              ft.amount, ft.date, ft.description, ft.created_at,
              fc.name AS category_name,
              d.project_id, d.due_date, d.status,
              d.installments_count, d.initial_payment
            FROM financial_transactions ft
            JOIN finance_categories fc ON ft.category_id = fc.id
            LEFT JOIN debts d ON d.transaction_id = ft.id
            WHERE ft.date BETWEEN ? AND ?
        ";
        $params = [$f['start'], $f['end']];
        if (!empty($f['type'])) {
            $sql       .= " AND ft.type = ?";
            $params[]   = $f['type'];
        }
        if (!empty($f['category_id'])) {
            $sql       .= " AND ft.category_id = ?";
            $params[]   = $f['category_id'];
        }
        $sql .= " ORDER BY ft.date DESC";

        $stmt = self::connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getSummary(string $start, string $end): array
    {
        $pdo = self::connect();
        $in  = $pdo->prepare(
          "SELECT COALESCE(SUM(amount),0) FROM financial_transactions
           WHERE type='income' AND date BETWEEN ? AND ?"
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
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function getAttachments(int $txId): array
    {
        $stmt = self::connect()
            ->prepare("SELECT * FROM transaction_attachments WHERE transaction_id = ?");
        $stmt->execute([$txId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getDebt(int $txId)
    {
        $stmt = self::connect()
            ->prepare("SELECT * FROM debts WHERE transaction_id = ?");
        $stmt->execute([$txId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function store(
        array $data,
        array $attachments = [],
        array $debtData = null
    ): int {
        $pdo = self::connect();
        $stmt = $pdo->prepare("
            INSERT INTO financial_transactions
              (user_id,category_id,type,amount,date,description)
            VALUES (?,?,?,?,?,?)
        ");
        $stmt->execute([
            $data['user_id'],
            $data['category_id'],
            $data['type'],
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

        // dívida
        if ($debtData && $data['type']==='debt') {
            $pdo->prepare("
                INSERT INTO debts
                  (client_id,transaction_id,project_id,amount,due_date,status,installments_count,initial_payment)
                VALUES (?,?,?,?,?,?,?,?)
            ")->execute([
                $debtData['client_id'],
                $txId,
                $debtData['project_id'] ?? null,
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
            SET category_id=?,type=?,amount=?,date=?,description=?
            WHERE id=?
        ")->execute([
            $data['category_id'],
            $data['type'],
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
        $existDebt = self::getDebt($id);
        if ($data['type']==='debt' && $debtData) {
            if ($existDebt) {
                $pdo->prepare("
                    UPDATE debts
                    SET client_id=?,project_id=?,amount=?,due_date=?,status=?,installments_count=?,initial_payment=?
                    WHERE transaction_id=?
                ")->execute([
                    $debtData['client_id'],
                    $debtData['project_id'],
                    $debtData['amount'],
                    $debtData['due_date'],
                    $debtData['status'],
                    $debtData['installments_count'],
                    $debtData['initial_payment'],
                    $id
                ]);
            } else {
                $pdo->prepare("
                    INSERT INTO debts
                      (client_id,transaction_id,project_id,amount,due_date,status,installments_count,initial_payment)
                    VALUES (?,?,?,?,?,?,?,?)
                ")->execute([
                    $debtData['client_id'],
                    $id,
                    $debtData['project_id'],
                    $debtData['amount'],
                    $debtData['due_date'],
                    $debtData['status'],
                    $debtData['installments_count'],
                    $debtData['initial_payment']
                ]);
            }
        } elseif ($existDebt && $data['type']!=='debt') {
            // remove dívida se não for mais debt
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
}
