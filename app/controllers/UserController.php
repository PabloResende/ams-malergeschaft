<?php

require_once __DIR__ . '/../models/User.php';

class UserController
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    // Página de login
    public function login()
    {
        if (isset($_SESSION['user'])) {
            $this->redirect('/dashboard');
        }

        require __DIR__ . '/../views/auth/login.php';
    }

    // Página de registro
    public function register()
    {
        if (isset($_SESSION['user'])) {
            $this->redirect('/dashboard');
        }

        require __DIR__ . '/../views/auth/register.php';
    }

    // Lógica de autenticação
    public function authenticate()
    {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $user = $this->userModel->findByEmail($email);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user;
            $this->redirect('/dashboard');
        }

        $_SESSION['error'] = "Email ou senha inválidos.";
        $this->redirect('/login');
    }

    // Lógica de logout
    public function logout()
    {
        unset($_SESSION['user']);
        session_destroy();
        $this->redirect('/login');
    }

    // Página de dashboard (após login)
    public function dashboard()
    {
        require_once __DIR__ . '/../models/Project.php';
        $projectModel = new ProjectModel();
        $projects = $projectModel->getAll();

        require __DIR__ . '/../views/dashboard/index.php';
    }

    // Página de perfil
    public function profile()
    {
        require __DIR__ . '/../views/profile/profile.php';
    }

    // Lógica de criação de novo usuário
    public function store()
    {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm'] ?? '';

        if ($password !== $confirm) {
            $_SESSION['error'] = "Senhas não coincidem.";
            $this->redirect('/register');
        }

        if ($this->userModel->findByEmail($email)) {
            $_SESSION['error'] = "Email já cadastrado.";
            $this->redirect('/register');
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $this->userModel->create($name, $email, $hashedPassword);

        $_SESSION['success'] = "Cadastro realizado com sucesso!";
        $this->redirect('/login');
    }

    // Redirecionamento com base no caminho
    private function redirect(string $path)
    {
        $basePath = '/ams-malergeschaft/public';
        header("Location: {$basePath}{$path}");
        exit;
    }
}
