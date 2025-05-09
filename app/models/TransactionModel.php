<?php
// app/models/TransactionModel.php

require_once(__DIR__ . '/../../config/Database.php');


class TransactionModel
{
    public static function connect(): PDO
    {
        return Database::connect();
    }

    /**
     * Busca todas as transações com filtros, trazendo também nome da categoria
     * e, se existir, due_date/status da dívida.
     */
    public static function getAll(array $f = []): array
    {
        $sql = "
            SELECT 
              ft.*,
              fc.name AS category_name,
              d.due_date,
              d.status
            FROM financial_transactions ft
            JOIN finance_categories fc ON ft.category_id = fc.id
            LEFT JOIN debts d ON d.transaction_id = ft.id
            WHERE ft.date BETWEEN ? AND ?
        ";
        $params = [ $f['start'], $f['end'] ];
        if (!empty($f['type'])) {
            $sql .= " AND ft.type = ?";
            $params[] = $f['type'];
        }
        if (!empty($f['category_id'])) {
            $sql .= " AND ft.category_id = ?";
            $params[] = $f['category_id'];
        }
        $sql .= " ORDER BY ft.date DESC";

        $stmt = self::connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna soma de entradas, saídas e saldo líquido.
     */
    public static function getSummary(string $start, string $end): array
    {
        $pdo  = self::connect();
        $in   = $pdo->prepare(
            "SELECT COALESCE(SUM(amount),0) FROM financial_transactions 
             WHERE type='income' AND date BETWEEN ? AND ?"
        );
        $ex   = $pdo->prepare(
            "SELECT COALESCE(SUM(amount),0) FROM financial_transactions 
             WHERE type='expense' AND date BETWEEN ? AND ?"
        );
        $in->execute([$start,$end]);
        $ex->execute([$start,$end]);
        $totIn  = $in->fetchColumn();
        $totEx  = $ex->fetchColumn();
        return [
            'income'  => (float)$totIn,
            'expense' => (float)$totEx,
            'net'     => (float)$totIn - (float)$totEx
        ];
    }

    /**
     * Busca uma transação por id.
     */
    public static function find(int $id)
    {
        $stmt = self::connect()
            ->prepare("SELECT * FROM financial_transactions WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lista anexos de uma transação.
     */
    public static function getAttachments(int $txId): array
    {
        $stmt = self::connect()
            ->prepare("SELECT * FROM transaction_attachments WHERE transaction_id = ?");
        $stmt->execute([$txId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca dados de dívida (se houver) para uma transação.
     */
    public static function getDebt(int $txId)
    {
        $stmt = self::connect()
            ->prepare("SELECT * FROM debts WHERE transaction_id = ?");
        $stmt->execute([$txId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Cria transação + anexos + dívida opcional.
     * $data = [user_id, category_id, type, amount, date, description]
     * $attachments = array of ['file_path'=>string]
     * $debtData = ['client_id'=>int|null,'amount'=>float,'due_date'=>date]
     */
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
                INSERT INTO debts (client_id,transaction_id,amount,due_date,status)
                VALUES (?,?,?,?,?)
            ")->execute([
                $debtData['client_id'],
                $txId,
                $debtData['amount'],
                $debtData['due_date'],
                $debtData['status'] ?? 'open'
            ]);
        }

        return $txId;
    }

    /**
     * Atualiza transação, anexos novos e dívida.
     */
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
        $existingDebt = self::getDebt($id);
        if ($data['type']==='debt' && $debtData) {
            if ($existingDebt) {
                // atualiza
                $pdo->prepare("
                    UPDATE debts SET client_id=?,amount=?,due_date=?,status=?
                    WHERE transaction_id=?
                ")->execute([
                    $debtData['client_id'],
                    $debtData['amount'],
                    $debtData['due_date'],
                    $debtData['status'] ?? $existingDebt['status'],
                    $id
                ]);
            } else {
                // cria
                $pdo->prepare("
                    INSERT INTO debts (client_id,transaction_id,amount,due_date,status)
                    VALUES (?,?,?,?,?)
                ")->execute([
                    $debtData['client_id'],
                    $id,
                    $debtData['amount'],
                    $debtData['due_date'],
                    $debtData['status'] ?? 'open'
                ]);
            }
        } elseif ($existingDebt && $data['type']!=='debt') {
            // remove dívida se não for mais debt
            $pdo->prepare("DELETE FROM debts WHERE transaction_id = ?")
                ->execute([$id]);
        }
    }

    /**
     * Remove transação + anexos + dívida.
     */
    public static function delete(int $id): void
    {
        $pdo = self::connect();
        // remove anexos
        $pdo->prepare("DELETE FROM transaction_attachments WHERE transaction_id = ?")
            ->execute([$id]);
        // remove dívida
        $pdo->prepare("DELETE FROM debts WHERE transaction_id = ?")
            ->execute([$id]);
        // remove transação
        $pdo->prepare("DELETE FROM financial_transactions WHERE id = ?")
            ->execute([$id]);
    }
}
