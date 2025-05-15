<?php
// app/models/Employees.php

require_once __DIR__ . '/../../config/database.php';

class Employee
{
    // Pasta onde os uploads serão salvos (dentro de public/)
    private static string $uploadDir = __DIR__ . '/../../public/uploads/employees/';

    public static function connect(): PDO
    {
        return Database::connect();
    }

    public static function all(): array
    {
        $stmt = self::connect()
            ->query("SELECT * FROM employees ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $id): ?array
    {
        $stmt = self::connect()
            ->prepare("SELECT * FROM employees WHERE id = ?");
        $stmt->execute([$id]);
        $emp = $stmt->fetch(PDO::FETCH_ASSOC);
        return $emp ?: null;
    }

    public static function create(array $data, array $files): void
    {
        $fieldsData = [
            'name','last_name','address','sex','birth_date',
            'nationality','permission_type','email','ahv_number',
            'phone','religion','marital_status','role','start_date','about'
        ];
        $fieldsFiles = [
            'passport','permission_photo_front','permission_photo_back',
            'health_card_front','health_card_back','bank_card_front','bank_card_back',
            'marriage_certificate'
        ];

        if (!is_dir(self::$uploadDir)) {
            mkdir(self::$uploadDir, 0755, true);
        }

        $filePaths = [];
        foreach ($fieldsFiles as $f) {
            if (isset($files[$f]) && $files[$f]['error'] === UPLOAD_ERR_OK) {
                $tmp  = $files[$f]['tmp_name'];
                $ext  = pathinfo($files[$f]['name'], PATHINFO_EXTENSION);
                $name = time() . '_' . uniqid() . '.' . $ext;
                move_uploaded_file($tmp, self::$uploadDir . $name);
                $filePaths[$f] = 'uploads/employees/' . $name;
            } else {
                $filePaths[$f] = null;
            }
        }

        $allCols     = array_merge($fieldsData, $fieldsFiles);
        $placeholders = array_fill(0, count($allCols), '?');
        $sql         = "INSERT INTO employees (" . implode(',', $allCols) . ")
                        VALUES (" . implode(',', $placeholders) . ")";
        $values      = [];
        foreach ($fieldsData as $col)  { $values[] = $data[$col]            ?? null; }
        foreach ($fieldsFiles as $col) { $values[] = $filePaths[$col]; }

        $stmt = self::connect()->prepare($sql);
        $stmt->execute($values);
    }

    public static function update(int $id, array $data, array $files): void
    {
        $emp = self::find($id);
        if (!$emp) return;

        $fieldsData = [
            'name','last_name','address','sex','birth_date',
            'nationality','permission_type','email','ahv_number',
            'phone','religion','marital_status','role','start_date','about'
        ];
        $fieldsFiles = [
            'passport','permission_photo_front','permission_photo_back',
            'health_card_front','health_card_back','bank_card_front','bank_card_back',
            'marriage_certificate'
        ];

        if (!is_dir(self::$uploadDir)) {
            mkdir(self::$uploadDir, 0755, true);
        }

        $filePaths = [];
        foreach ($fieldsFiles as $f) {
            if (isset($files[$f]) && $files[$f]['error'] === UPLOAD_ERR_OK) {
                $tmp  = $files[$f]['tmp_name'];
                $ext  = pathinfo($files[$f]['name'], PATHINFO_EXTENSION);
                $name = time() . '_' . uniqid() . '.' . $ext;
                move_uploaded_file($tmp, self::$uploadDir . $name);
                $filePaths[$f] = 'uploads/employees/' . $name;
            } else {
                $filePaths[$f] = $emp[$f] ?? null;
            }
        }

        $allCols    = array_merge($fieldsData, $fieldsFiles);
        $setClauses = array_map(fn($c) => "$c = ?", $allCols);
        $sql        = "UPDATE employees SET " . implode(', ', $setClauses) . " WHERE id = ?";
        $values     = [];
        foreach ($fieldsData as $col)  { $values[] = $data[$col]            ?? null; }
        foreach ($fieldsFiles as $col) { $values[] = $filePaths[$col]; }
        $values[] = $id;

        $stmt = self::connect()->prepare($sql);
        $stmt->execute($values);
    }

    public static function delete(int $id): void
    {
        self::connect()
            ->prepare("DELETE FROM employees WHERE id = ?")
            ->execute([$id]);
        // opcional: remover arquivos do disco
    }
}
