<?php
// app/models/TransactionModel.php

require_once __DIR__ . '/../../config/database.php';

class TransactionModel
{
    public static function connect(): PDO
    {
        return Database::connect();
    }

    /**
     * Busca transações, aplicando filtro de data apenas se start/end não forem nulos
     */
    public static function getAll(array $f = []): array
    {
        $sql = "
            SELECT
              ft.*,
              d.due_date,
              d.installments_count,
              d.initial_payment,
              d.initial_payment_amount
            FROM financial_transactions ft
            LEFT JOIN debts d
              ON d.transaction_id = ft.id
        ";
        $params     = [];
        $conditions = [];

        // só filtra data se o usuário enviou ambos start e end
        if (!empty($f['start']) && !empty($f['end'])) {
            $conditions[] = "ft.date BETWEEN ? AND ?";
            $params[]     = $f['start'];
            $params[]     = $f['end'];
        }

        // filtros de tipo, categoria e associações
        foreach (['type','category','client_id','project_id','employee_id'] as $field) {
            if (!empty($f[$field])) {
                $conditions[] = "ft.{$field} = ?";
                $params[]     = $f[$field];
            }
        }

        if (count($conditions) > 0) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY ft.date DESC";

        $stmt = self::connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Gera resumo de receita, despesa e saldo.
     * Se start/end forem nulos, totaliza tudo; senão, por intervalo.
     */
    public static function getSummary(?string $start = null, ?string $end = null): array
    {
        $pdo = self::connect();

        if (!empty($start) && !empty($end)) {
            $stmtIn = $pdo->prepare("
                SELECT COALESCE(SUM(amount),0)
                FROM financial_transactions
                WHERE type='income' AND date BETWEEN ? AND ?
            ");
            $stmtIn->execute([$start, $end]);

            $stmtEx = $pdo->prepare("
                SELECT COALESCE(SUM(amount),0)
                FROM financial_transactions
                WHERE type='expense' AND date BETWEEN ? AND ?
            ");
            $stmtEx->execute([$start, $end]);
        } else {
            $stmtIn = $pdo->prepare("
                SELECT COALESCE(SUM(amount),0)
                FROM financial_transactions
                WHERE type='income'
            ");
            $stmtIn->execute([]);

            $stmtEx = $pdo->prepare("
                SELECT COALESCE(SUM(amount),0)
                FROM financial_transactions
                WHERE type='expense'
            ");
            $stmtEx->execute([]);
        }

        $totIn = (float)$stmtIn->fetchColumn();
        $totEx = (float)$stmtEx->fetchColumn();

        return [
            'income'  => $totIn,
            'expense' => $totEx,
            'net'     => $totIn - $totEx,
        ];
    }

    public static function find(int $id): ?array
    {
        $stmt = self::connect()
            ->prepare("SELECT * FROM financial_transactions WHERE id = ?");
        $stmt->execute([$id]);
        $tx = $stmt->fetch(PDO::FETCH_ASSOC);
        return $tx ?: null;
    }

    public static function getAttachments(int $txId): array
    {
        $stmt = self::connect()
            ->prepare("SELECT * FROM transaction_attachments WHERE transaction_id = ?");
        $stmt->execute([$txId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function getDebt(int $txId): array
    {
        $stmt = self::connect()
            ->prepare("SELECT * FROM debts WHERE transaction_id = ?");
        $stmt->execute([$txId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
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
            $data['description'],
        ]);
        $txId = (int)$pdo->lastInsertId();

        foreach ($attachments as $a) {
            $pdo->prepare("
                INSERT INTO transaction_attachments
                  (transaction_id,file_path) VALUES (?,?)
            ")->execute([$txId, $a['file_path']]);
        }

        if ($debtData) {
            $pdo->prepare("
                INSERT INTO debts
                  (client_id,transaction_id,project_id,amount,due_date,status,installments_count,initial_payment,initial_payment_amount)
                VALUES (?,?,?,?,?,?,?,?,?)
            ")->execute([
                $debtData['client_id'],
                $txId,
                $debtData['project_id']        ?: null,
                $debtData['amount'],
                $debtData['due_date'],
                $debtData['status'],
                $debtData['installments_count'],
                $debtData['initial_payment'],
                $debtData['initial_payment_amount'],
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
            $id,
        ]);

        foreach ($attachments as $a) {
            $pdo->prepare("
                INSERT INTO transaction_attachments
                  (transaction_id,file_path) VALUES (?,?)
            ")->execute([$id, $a['file_path']]);
        }

        $existDebt = self::getDebt($id);
        if ($debtData) {
            if ($existDebt) {
                $pdo->prepare("
                    UPDATE debts
                    SET client_id=?,project_id=?,amount=?,due_date=?,status=?,installments_count=?,initial_payment=?,initial_payment_amount=?
                    WHERE transaction_id=?
                ")->execute([
                    $debtData['client_id'],
                    $debtData['project_id'],
                    $debtData['amount'],
                    $debtData['due_date'],
                    $debtData['status'],
                    $debtData['installments_count'],
                    $debtData['initial_payment'],
                    $debtData['initial_payment_amount'],
                    $id,
                ]);
            } else {
                $pdo->prepare("
                    INSERT INTO debts
                      (client_id,transaction_id,project_id,amount,due_date,status,installments_count,initial_payment,initial_payment_amount)
                    VALUES (?,?,?,?,?,?,?,?,?)
                ")->execute([
                    $debtData['client_id'],
                    $id,
                    $debtData['project_id'],
                    $debtData['amount'],
                    $debtData['due_date'],
                    $debtData['status'],
                    $debtData['installments_count'],
                    $debtData['initial_payment'],
                    $debtData['initial_payment_amount'],
                ]);
            }
        } elseif ($existDebt) {
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
