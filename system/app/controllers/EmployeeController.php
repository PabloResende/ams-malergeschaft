<?php
// app/controllers/EmployeeController.php

require_once __DIR__ . '/../models/Employees.php';
require_once __DIR__ . '/../models/TransactionModel.php';

class EmployeeController
{
    private array $langText;
    private string $baseUrl = '$basePath';

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'pt';
        $_SESSION['lang'] = $lang;

        $langFile = __DIR__ . '/../lang/' . $lang . '.php';
        if (!file_exists($langFile)) {
            $langFile = __DIR__ . '/../lang/pt.php';
        }
        $this->langText = require $langFile;
    }

    public function list()
    {
        $langText  = $this->langText;
        $baseUrl   = $this->baseUrl;
        $employees = Employee::all();
        require __DIR__ . '/../views/employees/index.php';
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Employee::create($_POST, $_FILES);
            header("Location: {$this->baseUrl}/employees");
            exit;
        }
    }

    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Employee::update((int)$_POST['id'], $_POST, $_FILES);
            header("Location: {$this->baseUrl}/employees");
            exit;
        }
    }

    public function get()
    {
        header('Content-Type: application/json; charset=UTF-8');
        $id  = (int)($_GET['id'] ?? 0);
        $emp = Employee::find($id);
        if (!$emp) {
            http_response_code(404);
            echo json_encode(['error' => 'Funcionário não encontrado']);
            exit;
        }
        $emp['transactions'] = TransactionModel::getAll([
            'start'       => '1970-01-01',
            'end'         => date('Y-m-d'),
            'employee_id' => $id
        ]);
        echo json_encode($emp);
        exit;
    }

    public function delete()
    {
        $id = (int)($_GET['id'] ?? 0);
        Employee::delete($id);
        header("Location: {$this->baseUrl}/employees");
        exit;
    }

    public function serveDocument()
    {
        $id   = (int)($_GET['id']   ?? 0);
        $type = $_GET['type']       ?? '';
        $allowed = [
            'passport',
            'permission_photo_front','permission_photo_back',
            'health_card_front','health_card_back',
            'bank_card_front','bank_card_back',
            'marriage_certificate'
        ];
        if (!in_array($type, $allowed, true)) {
            http_response_code(400);
            exit;
        }
        $emp = Employee::find($id);
        if (!$emp || empty($emp[$type])) {
            http_response_code(404);
            exit;
        }
        $file = __DIR__ . '/../../public/' . $emp[$type];
        if (!file_exists($file)) {
            http_response_code(404);
            exit;
        }
        header('Content-Type: ' . mime_content_type($file));
        readfile($file);
        exit;
    }
}
