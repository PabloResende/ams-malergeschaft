<?php
// system/app/models/Calendar.php

require_once __DIR__ . '/../../config/database.php';

class CalendarModel
{
    /** @var \PDO */
    private \PDO $pdo;

    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * Retorna todos os lembretes.
     *
     * @return array{id:int,title:string,start:string,color:string}
     */
    public function getAllEvents(): array
    {
        $stmt = $this->pdo->query("
            SELECT
                id,
                title,
                reminder_date AS start,
                color
            FROM reminders
            ORDER BY reminder_date ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna lembretes num intervalo de datas.
     *
     * @param string $start YYYY-MM-DD
     * @param string $end   YYYY-MM-DD
     * @return array{id:int,title:string,start:string,color:string}
     */
    public function getEventsInRange(string $start, string $end): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                id,
                title,
                reminder_date AS start,
                color
            FROM reminders
            WHERE reminder_date BETWEEN :start AND :end
            ORDER BY reminder_date ASC
        ");
        $stmt->execute([
            'start' => $start,
            'end'   => $end,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Insere um novo lembrete e retorna os dados completos.
     *
     * @param array{title:string,reminder_date:string,color:string} $data
     * @return array{id:int,title:string,start:string,color:string}
     */
    public function storeEvent(array $data): array
    {
        // Ajustado: tabela 'reminders' NÃO tem coluna user_id
        $stmt = $this->pdo->prepare("
            INSERT INTO reminders
              (title, reminder_date, color)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([
            $data['title'],
            $data['reminder_date'],
            $data['color'],
        ]);

        $id = (int)$this->pdo->lastInsertId();

        return [
            'id'    => $id,
            'title' => $data['title'],
            'start' => $data['reminder_date'],
            'color' => $data['color'],
        ];
    }
}
