<?php
// system/app/models/TransactionModel.php

require_once __DIR__ . '/../../config/database.php';

class TransactionModel
{
    /** @var \PDO */
    private $pdo;

    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * Retorna todas as transações.
     *
     * @return array
     */
    public function getAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM financial_transactions ORDER BY date DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna transações filtradas por tipo, data e categoria.
     *
     * @param string $type
     * @param string $start  YYYY-MM-DD
     * @param string $end    YYYY-MM-DD
     * @param string $category
     * @return array
     */
    public function getFiltered(string $type, string $start, string $end, string $category): array
    {
        $sql    = "SELECT * FROM financial_transactions WHERE date BETWEEN :start AND :end";
        $params = ['start' => $start, 'end' => $end];

        if ($type !== '') {
            $sql           .= " AND type = :type";
            $params['type'] = $type;
        }
        if ($category !== '') {
            $sql               .= " AND category = :category";
            $params['category'] = $category;
        }
        $sql .= " ORDER BY date DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna resumo (income, expense, net) no período.
     *
     * @param string $start
     * @param string $end
     * @return array
     */
    public function getSummary(string $start, string $end): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
              SUM(CASE WHEN type='income' THEN amount ELSE 0 END)  AS income,
              SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) AS expense
            FROM financial_transactions
            WHERE date BETWEEN :start AND :end
        ");
        $stmt->execute(['start' => $start, 'end' => $end]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $income  = (float)($row['income']  ?? 0);
        $expense = (float)($row['expense'] ?? 0);
        return [
            'income'  => $income,
            'expense' => $expense,
            'net'     => $income - $expense,
        ];
    }

    /**
     * Encontra uma transação pelo ID.
     *
     * @param int $id
     * @return array|null
     */
    public static function find(int $id): ?array
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM financial_transactions WHERE id = ?");
        $stmt->execute([$id]);
        $tx = $stmt->fetch(PDO::FETCH_ASSOC);
        return $tx ?: null;
    }

    /**
     * Busca anexos de uma transação.
     *
     * @param int $txId
     * @return array
     */
    public static function getAttachments(int $txId): array
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM transaction_attachments WHERE transaction_id = ?");
        $stmt->execute([$txId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca dados de dívida de uma transação.
     *
     * @param int $txId
     * @return array
     */
    public static function getDebt(int $txId): array
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM debts WHERE transaction_id = ?");
        $stmt->execute([$txId]);
        $debt = $stmt->fetch(PDO::FETCH_ASSOC);
        return $debt ?: [];
    }

    /**
     * Insere nova transação (e anexos/debt se houver).
     *
     * @param array      $data
     * @param array      $attachments
     * @param array|null $debtData
     * @return int ID da nova transação
     */
    public static function store(array $data, array $attachments = [], array $debtData = null): int
    {
        global $pdo;
        $stmt = $pdo->prepare("
            INSERT INTO financial_transactions
              (user_id, category, type, client_id, project_id, employee_id, amount, date, description)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
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

        // Anexos
        foreach ($attachments as $file) {
            $pdo->prepare("
                INSERT INTO transaction_attachments (transaction_id, file_path)
                VALUES (?, ?)
            ")->execute([$txId, $file['file_path']]);
        }

        // Dívida
        if ($debtData) {
            $pdo->prepare("
                INSERT INTO debts
                  (client_id, transaction_id, project_id, amount, due_date, status, installments_count, initial_payment, initial_payment_amount)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
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

    /**
     * Atualiza transação (e anexos/debt).
     *
     * @param int        $id
     * @param array      $data
     * @param array      $attachments
     * @param array|null $debtData
     */
    public static function update(int $id, array $data, array $attachments = [], array $debtData = null): void
    {
        global $pdo;
        // Atualiza registro principal
        $pdo->prepare("
            UPDATE financial_transactions
            SET category=?, type=?, client_id=?, project_id=?, employee_id=?, amount=?, date=?, description=?
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

        // Insere novos anexos
        foreach ($attachments as $file) {
            $pdo->prepare("
                INSERT INTO transaction_attachments (transaction_id, file_path)
                VALUES (?, ?)
            ")->execute([$id, $file['file_path']]);
        }

        // Dívida existente?
        $existDebt = self::getDebt($id);
        if ($debtData) {
            if ($existDebt) {
                // Atualiza dívida
                $pdo->prepare("
                    UPDATE debts
                    SET client_id=?, project_id=?, amount=?, due_date=?, status=?, installments_count=?, initial_payment=?, initial_payment_amount=?
                    WHERE transaction_id=?
                ")->execute([
                    $debtData['client_id'],
                    $debtData['project_id']        ?: null,
                    $debtData['amount'],
                    $debtData['due_date'],
                    $debtData['status'],
                    $debtData['installments_count'],
                    $debtData['initial_payment'],
                    $debtData['initial_payment_amount'],
                    $id,
                ]);
            } else {
                // Insere nova dívida
                $pdo->prepare("
                    INSERT INTO debts
                      (client_id, transaction_id, project_id, amount, due_date, status, installments_count, initial_payment, initial_payment_amount)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ")->execute([
                    $debtData['client_id'],
                    $id,
                    $debtData['project_id']        ?: null,
                    $debtData['amount'],
                    $debtData['due_date'],
                    $debtData['status'],
                    $debtData['installments_count'],
                    $debtData['initial_payment'],
                    $debtData['initial_payment_amount'],
                ]);
            }
        } elseif ($existDebt) {
            // Remove dívida se não aplicável
            $pdo->prepare("DELETE FROM debts WHERE transaction_id = ?")->execute([$id]);
        }
    }

    /**
     * Deleta transação e registros relacionados.
     *
     * @param int $id
     */
    public static function delete(int $id): void
    {
        global $pdo;
        $pdo->prepare("DELETE FROM transaction_attachments WHERE transaction_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM debts WHERE transaction_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM financial_transactions WHERE id = ?")->execute([$id]);
    }
}
