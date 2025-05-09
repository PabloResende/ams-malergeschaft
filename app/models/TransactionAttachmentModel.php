<?php
class TransactionAttachmentModel
{
    public static function connect() {
        return Database::connect();
    }

    public static function store($d) {
        self::connect()->prepare("
            INSERT INTO transaction_attachments
            (transaction_id,file_path) VALUES (?,?)
        ")->execute([$d['transaction_id'],$d['file_path']]);
    }

    public static function findByTransaction($tx_id) {
        $stmt = self::connect()
            ->prepare("SELECT * FROM transaction_attachments WHERE transaction_id=?");
        $stmt->execute([$tx_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find($id) {
        $stmt = self::connect()
            ->prepare("SELECT * FROM transaction_attachments WHERE id=?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
