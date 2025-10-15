<?php
require_once __DIR__ . '/../models/Employees.php';
require_once __DIR__ . '/../models/Role.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/TransactionModel.php';

class EmployeeController
{
    private array $langText;
    private string $baseUrl;
    private \PDO $pdo;

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        global $pdo;
        $this->pdo = $pdo;

        $lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'pt';
        $_SESSION['lang'] = $lang;
        $lf = __DIR__ . "/../lang/$lang.php";
        $this->langText = file_exists($lf) ? require $lf : require __DIR__ . '/../lang/pt.php';

        $this->baseUrl = BASE_URL;
    }

    public function list()
    {
        $employees = (new Employee())->all();
        $roles = (new Role())->all();
        require __DIR__ . '/../views/employees/index.php';
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$this->baseUrl}/employees");
            exit;
        }

        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $userModel = new UserModel();
        if ($email !== '' && $userModel->findByEmail($email)) {
            $_SESSION['error'] = $this->langText['email_exists'] ?? 'Email já cadastrado.';
            header("Location: {$this->baseUrl}/employees");
            exit;
        }

        $roleId   = (int)($_POST['role_id'] ?? 1);
        $role     = (new Role())->find($roleId);
        $roleName = $role['name'] ?? 'employee';

        $fullName = trim(($_POST['name'] ?? '') . ' ' . ($_POST['last_name'] ?? '')); 
        $hash     = password_hash($password, PASSWORD_DEFAULT);
        $userModel->create($fullName, $email, $hash, $roleName);
        $userId   = (int)$this->pdo->lastInsertId();

        $empModel = new Employee();
        $empModel->create(array_merge($_POST, [
            'user_id' => $userId,
            'role_id' => $roleId
        ]), $_FILES);

        header("Location: {$this->baseUrl}/employees");
        exit;
    }

    public function update()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: {$this->baseUrl}/employees");
        exit;
    }

    // 1) coleta dados do POST
    $empId   = (int) ($_POST['id']      ?? 0);
    $userId  = (int) ($_POST['user_id'] ?? 0);
    $email   = trim($_POST['email']    ?? '');
    $newPwd  = trim($_POST['password'] ?? '');
    $roleId  = (int) ($_POST['role_id'] ?? 1);
    $fullName = trim(($_POST['name'] ?? '') . ' ' . ($_POST['last_name'] ?? ''));

    // 2) determina o nome da role
    $roleModel = new Role();
    $role      = $roleModel->find($roleId);
    $roleName  = $role['name'] ?? 'employee';

    // 3) atualiza credenciais em users
    $userModel = new UserModel();
    if ($newPwd !== '') {
        // se veio senha nova, hash + atualiza senha junto
        $hash = password_hash($newPwd, PASSWORD_DEFAULT);
        $userModel->update(
          $userId,
          $fullName,
          $email,
          $hash,
          $roleName
        );
    } else {
        // sem senha, só nome/email/role
        $userModel->update(
          $userId,
          $fullName,
          $email,
          null,
          $roleName
        );
    }

    // 4) atualiza dados em employees (sem tocar em senha/email)
    $empModel = new Employee();
    // mescla role_id ao POST original para o model gravar a role correta
    $empModel->update(
      $empId,
      array_merge($_POST, ['role_id' => $roleId]),
      $_FILES
    );

    // 5) redireciona com mensagem de sucesso
    $_SESSION['success'] = $this->langText['employee_updated'] 
                        ?? 'Funcionário atualizado com sucesso.';
    header("Location: {$this->baseUrl}/employees");
    exit;
}

    public function get()
    {
        header('Content-Type: application/json; charset=UTF-8');
        $id  = (int)($_GET['id'] ?? 0);
        $emp = (new Employee())->find($id);
        if (! $emp) {
            http_response_code(404);
            echo json_encode(['error' => $this->langText['error_employee_not_found']]);
            exit;
        }

        $txModel             = new TransactionModel();
        $allTx               = $txModel->getAll();
        $emp['transactions'] = array_values(array_filter(
            $allTx,
            fn($t) => isset($t['employee_id']) && $t['employee_id'] === $id
        ));

        $userModel           = new UserModel();
        $user                = $userModel->find((int)$emp['user_id']);
        $emp['login_email']  = $user['email'] ?? '';
        $emp['user_id']      = $user['id']    ?? null;

        echo json_encode($emp);
        exit;
    }

    public function profile()
    {
        if (! isLoggedIn() || (! isEmployee() && ! isFinance())) {
            header("Location: {$this->baseUrl}/dashboard");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            (new Employee())->update($id, $_POST, $_FILES);
            $_SESSION['success'] = $this->langText['profile_updated'] ?? 'Perfil atualizado com sucesso.';
            header("Location: {$this->baseUrl}/employees/profile");
            exit;
        }

        $email     = $_SESSION['user']['email'] ?? '';
        $userModel = new UserModel();
        $user      = $userModel->findByEmail($email);
        if (! $user) {
            $_SESSION['error'] = $this->langText['error_employee_not_found'] ?? 'Funcionário não encontrado.';
            header("Location: {$this->baseUrl}/dashboard");
            exit;
        }

        $emp = (new Employee())->findByUserId((int)$user['id']);
        require __DIR__ . '/../views/employees/profile_employee.php';
    }

    public function delete()
    {
        $id = (int)($_GET['id'] ?? 0);
        (new Employee())->delete($id);
        header("Location: {$this->baseUrl}/employees");
        exit;
    }
}