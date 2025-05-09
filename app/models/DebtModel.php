<?php
class DebtModel
{
    public static function connect() {
        return Database::connect();
    }

    public static function store($d) {
        self::connect()->prepare("
            INSERT INTO debts
            (client_id,transaction_id,amount,due_date)
            VALUES (?,?,?,?)
        ")->execute([
            $d['client_id'],$d['transaction_id'],
            $d['amount'],$d['due_date']
        ]);
    }

    // opcional: métodos para listar, atualizar status etc.
}
