<?php
// system/app/models/Clients.php

require_once __DIR__ . '/../../config/database.php';

class Client
{
    /** @var \PDO */
    private \PDO $pdo;

    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * Lista todos os clientes
     */
    public function all(): array
    {
        $stmt = $this->pdo->query("
            SELECT
            c.*,
            COUNT(p.id) AS loyalty_points
            FROM client c
            LEFT JOIN projects p
            ON p.client_id = c.id
            WHERE c.active = 1
            GROUP BY c.id
            ORDER BY c.name ASC
        ");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Encontra um cliente pelo ID e injeta loyalty_points com base em countProjects().
     *
     * @param int $id
     * @return array|null
     */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM client
            WHERE id = :id
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (! $row) {
            return null;
        }
        // recalcula pontos de fidelidade
        $row['loyalty_points'] = $this->countProjects($id);
        return $row;
    }
    /**
     * Cria um novo cliente.
     * Retorna o ID inserido.
     */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO client (
            contact_number,name,address,zip_code,city,country,complement,about,
            phone,phone2,mobile,fax,email,email2,website,skype,
            contact_person,owner,correspondence,language,category,branch,
            employee_count,registry_number,vat_number,tax_id_number,
            profile_picture,active
            ) VALUES (
            :contact_number,:name,:address,:zip_code,:city,:country,
            :complement,:about,:phone,:phone2,:mobile,:fax,
            :email,:email2,:website,:skype,
            :contact_person,:owner,:correspondence,:language,
            :category,:branch,:employee_count,:registry_number,
            :vat_number,:tax_id_number,:profile_picture,:active
            )
        ");
        $stmt->execute([
            'contact_number'  => $data['contact_number'],
            'name'            => $data['name'],
            'address'         => $data['address'],
            'zip_code'        => $data['zip_code'],
            'city'            => $data['city'],
            'country'         => $data['country'],
            'complement'      => $data['complement'],
            'about'           => $data['about'],
            'phone'           => $data['phone'],
            'phone2'          => $data['phone2'],
            'mobile'          => $data['mobile'],
            'fax'             => $data['fax'],
            'email'           => $data['email'],
            'email2'          => $data['email2'],
            'website'         => $data['website'],
            'skype'           => $data['skype'],
            'contact_person'  => $data['contact_person'],
            'owner'           => $data['owner'],
            'correspondence'  => $data['correspondence'],
            'language'        => $data['language'],
            'category'        => $data['category'],
            'branch'          => $data['branch'],
            'employee_count'  => $data['employee_count'],
            'registry_number' => $data['registry_number'],
            'vat_number'      => $data['vat_number'],
            'tax_id_number'   => $data['tax_id_number'],
            'profile_picture' => $data['profile_picture'],
            'active'          => $data['active'],
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Atualiza um cliente existente.
     */
   
    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE client SET
            contact_number  = :contact_number,
            name            = :name,
            address         = :address,
            zip_code        = :zip_code,
            city            = :city,
            country         = :country,
            complement      = :complement,
            about           = :about,
            phone           = :phone,
            phone2          = :phone2,
            mobile          = :mobile,
            fax             = :fax,
            email           = :email,
            email2          = :email2,
            website         = :website,
            skype           = :skype,
            contact_person  = :contact_person,
            owner           = :owner,
            correspondence  = :correspondence,
            language        = :language,
            category        = :category,
            branch          = :branch,
            employee_count  = :employee_count,
            registry_number = :registry_number,
            vat_number      = :vat_number,
            tax_id_number   = :tax_id_number,
            profile_picture = COALESCE(:profile_picture, profile_picture),
            active          = :active
            WHERE id = :id
        ");
        return $stmt->execute([
            'contact_number'  => $data['contact_number'],
            'name'            => $data['name'],
            'address'         => $data['address'],
            'zip_code'        => $data['zip_code'],
            'city'            => $data['city'],
            'country'         => $data['country'],
            'complement'      => $data['complement'],
            'about'           => $data['about'],
            'phone'           => $data['phone'],
            'phone2'          => $data['phone2'],
            'mobile'          => $data['mobile'],
            'fax'             => $data['fax'],
            'email'           => $data['email'],
            'email2'          => $data['email2'],
            'website'         => $data['website'],
            'skype'           => $data['skype'],
            'contact_person'  => $data['contact_person'],
            'owner'           => $data['owner'],
            'correspondence'  => $data['correspondence'],
            'language'        => $data['language'],
            'category'        => $data['category'],
            'branch'          => $data['branch'],
            'employee_count'  => $data['employee_count'],
            'registry_number' => $data['registry_number'],
            'vat_number'      => $data['vat_number'],
            'tax_id_number'   => $data['tax_id_number'],
            'profile_picture' => $data['profile_picture'],
            'active'          => $data['active'],
            'id'              => $id,
        ]);
    }

    public function countProjects(int $id): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) AS cnt
              FROM projects
             WHERE client_id = :cid
        ");
        $stmt->execute(['cid' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return isset($row['cnt']) ? (int)$row['cnt'] : 0;
    }

   /**
     * Remove de verdade um cliente do banco.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("
            DELETE FROM client
             WHERE id = :id
        ");
        return $stmt->execute(['id' => $id]);
    }
}

