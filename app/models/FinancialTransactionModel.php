<?php
class FinancialTransactionModel
{
    public static function connect() {
        return Database::connect();
    }

    public static function getAll($f=[]) {
        $sql = "SELECT ft.*, fc.name AS category_name
                FROM financial_transactions ft
                JOIN finance_categories fc ON ft.category_id=fc.id
                WHERE date BETWEEN ? AND ?";
        $params = [$f['start'], $f['end']];
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

    public static function find($id) {
        $stmt = self::connect()->prepare("SELECT * FROM financial_transactions WHERE id=?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function store($d) {
        $stmt = self::connect()->prepare("
            INSERT INTO financial_transactions
            (user_id,category_id,type,amount,date,description)
            VALUES (?,?,?,?,?,?)
        ");
        $stmt->execute([
            $d['user_id'],$d['category_id'],$d['type'],
            $d['amount'],$d['date'],$d['description']
        ]);
        return self::connect()->lastInsertId();
    }

    public static function update($id,$d) {
        $stmt = self::connect()->prepare("
            UPDATE financial_transactions
            SET category_id=?,type=?,amount=?,date=?,description=?
            WHERE id=?
        ");
        $stmt->execute([
            $d['category_id'],$d['type'],$d['amount'],
            $d['date'],$d['description'],$id
        ]);
    }

    public static function delete($id) {
        self::connect()->prepare("DELETE FROM financial_transactions WHERE id=?")
            ->execute([$id]);
    }

    public static function getSummary($start,$end) {
        $pdo = self::connect();
        $totIn  = $pdo->prepare("SELECT SUM(amount) FROM financial_transactions WHERE type='income' AND date BETWEEN ? AND ?");
        $totEx  = $pdo->prepare("SELECT SUM(amount) FROM financial_transactions WHERE type='expense' AND date BETWEEN ? AND ?");
        $totIn->execute([$start,$end]);
        $totEx->execute([$start,$end]);
        return [
            'income'=>$totIn->fetchColumn()?:0,
            'expense'=>$totEx->fetchColumn()?:0,
            'net'=>($totIn->fetchColumn()?:0)-($totEx->fetchColumn()?:0)
        ];
    }
}
