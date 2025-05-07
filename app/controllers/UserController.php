<?php

require_once __DIR__ . '/../models/User.php';

class UserController
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function login()
    {
        if (isset($_SESSION['user'])) {
            $this->redirect('/dashboard');
        }

        require __DIR__ . '/../views/auth/login.php';
    }

    public function register()
    {
        if (isset($_SESSION['user'])) {
            $this->redirect('/dashboard');
        }

        require __DIR__ . '/../views/auth/register.php';
    }

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

    public function logout()
    {
        unset($_SESSION['user']);
        session_destroy();
        $this->redirect('/login');
    }

    public function dashboard()
    {
        require_once __DIR__ . '/../models/Project.php';
        $projectModel = new ProjectModel();
        $projects = $projectModel->getAll();

        require __DIR__ . '/../views/dashboard/index.php';
    }

    public function profile()
    {
        require __DIR__ . '/../views/profile/profile.php';
    }

    public function store()
    {
        $name = trim($_POST['name'] ?? '');
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
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
    
        $created = $this->userModel->create($name, $email, $hashedPassword);
    
        if ($created) {
            // Sucesso: redireciona para login com parâmetro na URL
            $this->redirect('/login?success=1');
        } else {
            $_SESSION['error'] = "Erro ao cadastrar. Tente novamente.";
            $this->redirect('/register');
        }
    }

    private function redirect(string $path)
    {
        $basePath = '/ams-malergeschaft/public';
        header("Location: {$basePath}{$path}");
        exit;
    }
}
