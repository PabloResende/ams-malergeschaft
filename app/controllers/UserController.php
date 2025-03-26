<?php
session_start();
require_once __DIR__ . '/../models/User.php';

class UserController {
    public function login() {
        if (isset($_SESSION['user'])) {
            $this->redirect('/dashboard');
        }
        require __DIR__ . '/../views/auth/login.php';
    }

    public function authenticate() {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $userModel = new User();
        $user = $userModel->findByEmail($email);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user;
            $this->redirect('/dashboard');
        } else {
            $error = "Invalid email or password.";
            require __DIR__ . '/../views/auth/login.php';
        }
    }

    public function register() {
        require __DIR__ . '/../views/auth/register.php';
    }

    public function store() {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if ($name && $email && $password) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $userModel = new User();
            $userModel->create($name, $email, $hashedPassword);
            $this->redirect('/login');
        }
    }

    public function dashboard() {
        if (!isset($_SESSION['user'])) {
            $this->redirect('/login');
        }
        require __DIR__ . '/../views/dashboard/index.php';
    }

    public function profile() {
        if (!isset($_SESSION['user'])) {
            $this->redirect('/login');
        }
        require __DIR__ . '/../views/profile/profile.php';
    }    

    public function logout() {
        session_destroy();
        $this->redirect('/login');
    }

    private function redirect($path) {
        $langQuery = isset($_SESSION['lang']) ? '?lang=' . $_SESSION['lang'] : '';
        header("Location: /ams-malergeschaft/public$path$langQuery");
        exit;
    }
}
