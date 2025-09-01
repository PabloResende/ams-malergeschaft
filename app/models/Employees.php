<?php
// app/models/Employees.php

require_once __DIR__ . '/../../config/database.php';

class Employee
{
    private static string $uploadDir = __DIR__ . '/../../public/uploads/employees/';
    private \PDO $pdo;

    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
    }

    private static function connect(): \PDO
    {
        global $pdo;
        return $pdo;
    }

    /** Lista com join em roles */
    public function all(): array
    {
        $sql = "
            SELECT e.id,
                   e.name,
                   e.last_name,
                   e.function,
                   r.id   AS role_id,
                   r.name AS role,
                   e.start_date,
                   e.zip_code,
                   e.city,
                   e.user_id
              FROM employees e
         LEFT JOIN roles r ON e.role_id = r.id
         ORDER BY e.last_name, e.name
        ";
        return $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** Busca um funcionário pelo ID (inclui role) */
    public static function find(int $id): ?array
    {
        $stmt = self::connect()->prepare("
            SELECT e.*,
                   r.id   AS role_id,
                   r.name AS role
              FROM employees e
         LEFT JOIN roles r ON e.role_id = r.id
             WHERE e.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Busca um funcionário pelo user_id (chave estrangeira em employees)
     */
    public static function findByUserId(int $userId): ?array
    {
        $stmt = self::connect()->prepare("
            SELECT e.*,
                   r.id   AS role_id,
                   r.name AS role
              FROM employees e
         LEFT JOIN roles r ON e.role_id = r.id
             WHERE e.user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Cria um funcionário
     *
     * @param array $data  Dados do formulário ($_POST) + ['user_id'=>int,'role_id'=>int]
     * @param array $files $_FILES
     */
    public static function create(array $data, array $files): void
    {
        $pdo = self::connect();

        // Sanitiza CEP
        $data['zip_code'] = isset($data['zip_code'])
            ? preg_replace('/\D+/', '', $data['zip_code'])
            : null;

        // Colunas de dados (sem email/senha)
        $fieldsData = [
            'name','last_name','function','address','zip_code','city','sex',
            'birth_date','nationality','permission_type','ahv_number',
            'phone','religion','marital_status','start_date','about',
            'user_id','role_id'
        ];

        // Colunas BLOB
        $fieldsFiles = [
            'profile_picture','passport','permission_photo_front',
            'permission_photo_back','health_card_front','health_card_back',
            'bank_card_front','bank_card_back','marriage_certificate'
        ];

        // Prepara BLOBs
        $fileBlobs = [];
        foreach ($fieldsFiles as $f) {
            if (!empty($files[$f]['tmp_name']) && $files[$f]['error'] === UPLOAD_ERR_OK) {
                $fileBlobs[$f] = file_get_contents($files[$f]['tmp_name']);
            } else {
                $fileBlobs[$f] = null;
            }
        }

        // Monta SQL
        $allCols      = array_merge($fieldsData, $fieldsFiles);
        $placeholders = implode(',', array_fill(0, count($allCols), '?'));
        $sql = 'INSERT INTO employees ('
             . implode(',', $allCols)
             . ') VALUES ('
             . $placeholders
             . ')';

        $stmt = $pdo->prepare($sql);

        // Valores na ordem
        $values = [];
        foreach ([
            'name','last_name','function','address','zip_code','city','sex',
            'birth_date','nationality','permission_type','ahv_number',
            'phone','religion','marital_status','start_date','about'
        ] as $col) {
            $values[] = $data[$col] ?? null;
        }
        $values[] = $data['user_id'] ?? null;
        $values[] = $data['role_id'] ?? null;
        foreach ($fieldsFiles as $col) {
            $values[] = $fileBlobs[$col];
        }

        $stmt->execute($values);
    }

    /**
     * Atualiza um funcionário (sem tocar em email/senha)
     *
     * @param int   $id
     * @param array $data  Dados do formulário ($_POST) + ['role_id'=>int]
     * @param array $files $_FILES
     */
    public static function update(int $id, array $data, array $files): void
    {
        $emp = self::find($id);
        if (! $emp) {
            return;
        }

        $fieldsData = [
            'name','last_name','function',
            'address','zip_code','city','sex','birth_date',
            'nationality','permission_type','ahv_number',
            'phone','religion','marital_status','start_date','about','role_id'
        ];
        $fieldsFiles = [
            'passport','permission_photo_front','permission_photo_back',
            'health_card_front','health_card_back',
            'bank_card_front','bank_card_back','marriage_certificate'
        ];

        if (! is_dir(self::$uploadDir)) {
            mkdir(self::$uploadDir, 0755, true);
        }

        // Processa e salva uploads
        $filePaths = [];
        foreach ($fieldsFiles as $f) {
            if (!empty($files[$f]['tmp_name']) && $files[$f]['error'] === UPLOAD_ERR_OK) {
                $ext  = pathinfo($files[$f]['name'], PATHINFO_EXTENSION);
                $name = time() . '_' . uniqid() . ".{$ext}";
                move_uploaded_file($files[$f]['tmp_name'], self::$uploadDir . $name);
                $filePaths[$f] = 'uploads/employees/' . $name;
            } else {
                $filePaths[$f] = $emp[$f] ?? null;
            }
        }

        // Monta SETs
        $sets = [];
        foreach ($fieldsData as $col) {
            $sets[] = "{$col} = ?";
        }
        foreach ($fieldsFiles as $f) {
            $sets[] = "{$f} = ?";
        }

        $sql  = 'UPDATE employees SET ' . implode(', ', $sets) . ' WHERE id = ?';
        $stmt = self::connect()->prepare($sql);

        // Valores na ordem
        $vals = [];
        foreach ($fieldsData as $col) {
            $vals[] = $data[$col] ?? $emp[$col] ?? null;
        }
        foreach ($fieldsFiles as $f) {
            $vals[] = $filePaths[$f];
        }
        $vals[] = $id;

        $stmt->execute($vals);
    }

    /** Remove funcionário */
    public static function delete(int $id): void
    {
        self::connect()
            ->prepare('DELETE FROM employees WHERE id = ?')
            ->execute([$id]);
    }

}
