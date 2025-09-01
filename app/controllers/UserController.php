<?php
// app/controllers/UserController.php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Employees.php';
require_once __DIR__ . '/../models/Project.php';

class UserController
{
    private array  $langText;
    private string $baseUrl;

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Idioma e traduções
        $lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'pt';
        $_SESSION['lang'] = $lang;
        $langFile = __DIR__ . '/../lang/' . $lang . '.php';
        $this->langText = file_exists($langFile)
            ? require $langFile
            : require __DIR__ . '/../lang/pt.php';

        // URL base
        $this->baseUrl = BASE_URL;
    }

    /**
     * Exibe o formulário de login
     */
    public function login()
    {
        // 1) Se já estiver logado, redireciona para o dashboard
        if (isLoggedIn()) {
            header("Location: {$this->baseUrl}/dashboard");
            exit;
        }

        // 2) Pega e limpa eventual mensagem de erro
        $error = $_SESSION['error'] ?? null;
        unset($_SESSION['error']);

        // 3) Exporta para a view os mesmos dados de idioma e URL base
        $langText = $this->langText;
        $baseUrl  = $this->baseUrl;

        // 4) Carrega o template de login
        require __DIR__ . '/../views/auth/login.php';
    }

    /**
     * Processa o POST de /auth
     */
    public function authenticate()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$this->baseUrl}/login");
            exit;
        }

        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $_SESSION['error'] = $this->langText['login_invalid'] ?? 'Usuário ou senha inválidos.';
            header("Location: {$this->baseUrl}/login");
            exit;
        }

        $userModel = new UserModel();
        $user      = $userModel->findByEmail($email);

        if (! $user || ! password_verify($password, $user['password'])) {
            $_SESSION['error'] = $this->langText['login_invalid'] ?? 'Usuário ou senha inválidos.';
            header("Location: {$this->baseUrl}/login");
            exit;
        }

        // Grava informações do usuário na sessão
        $_SESSION['user'] = [
            'id'    => $user['id'],
            'name'  => $user['name'],
            'email' => $user['email'],
            'role'  => $user['role'],
        ];

        // Redireciona conforme papel
        switch ($user['role']) {
            case 'admin':
                header("Location: {$this->baseUrl}/dashboard");
                break;

            case 'financeiro':
            case 'finance':
                header("Location: {$this->baseUrl}/finance");
                break;

            case 'employee':
                header("Location: {$this->baseUrl}/employees/dashboard");
                break;

            default:
                header("Location: {$this->baseUrl}/dashboard");
                break;
        }
        exit;
    }

    /**
     * Exibe o formulário de registro
     */
    public function register()
    {
        require __DIR__ . '/../views/auth/register.php';
    }

    /**
     * Processa o POST de /store (registro de novos usuários)
     */
    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$this->baseUrl}/register");
            exit;
        }

        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($name === '' || $email === '' || $password === '') {
            $_SESSION['error'] = $this->langText['register_error'] ?? 'Erro no cadastro.';
            header("Location: {$this->baseUrl}/register");
            exit;
        }

        $userModel = new UserModel();
        if ($userModel->findByEmail($email)) {
            $_SESSION['error'] = $this->langText['email_exists'] ?? 'Email já cadastrado.';
            header("Location: {$this->baseUrl}/register");
            exit;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $userModel->create($name, $email, $hash, 'employee');

        // Auto-login após registro
        $_SESSION['user'] = [
            'id'    => $userModel->lastInsertId(),
            'name'  => $name,
            'email' => $email,
            'role'  => 'employee',
        ];

        header("Location: {$this->baseUrl}/employees/dashboard");
        exit;
    }

    /**
     * Encerra a sessão e redireciona para login
     */
    public function logout()
    {
        session_destroy();
        header("Location: {$this->baseUrl}/login");
        exit;
    }

    /**
     * Painel de Admin
     */
    public function dashboard()
    {
        require __DIR__ . '/../views/dashboard/index.php';
    }

    /**
     * Painel de Funcionário
     */
    public function employeeDashboard()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (! isEmployee()) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        $userId   = (int)($_SESSION['user']['id'] ?? 0);
        $empModel = new Employee();
        $emp      = $empModel->findByUserId($userId);
        $empId    = $emp['id'] ?? 0;


        require_once __DIR__ . '/../models/Project.php';
        $projectModel = new ProjectModel();
        $projects     = $projectModel->getByEmployee($empId);

        require __DIR__ . '/../views/employees/dashboard_employee.php';
    }

}
