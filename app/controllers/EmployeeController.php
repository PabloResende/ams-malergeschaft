<?php
// app/controllers/EmployeeController.php

require_once __DIR__ . '/../models/Employees.php';
require_once __DIR__ . '/../models/TransactionModel.php';

class EmployeeController
{
    public function list()
    {
        $employees = Employee::all();
        require_once __DIR__ . '/../views/employees/index.php';
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Employee::create($_POST, $_FILES);
            header('Location: /ams-malergeschaft/public/employees');
            exit;
        }
    }

    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Employee::update($_POST['id'], $_POST, $_FILES);
            header('Location: /ams-malergeschaft/public/employees');
            exit;
        }
    }

    public function get()
    {
        header('Content-Type: application/json; charset=UTF-8');
        $id = (int)($_GET['id'] ?? 0);
        $emp = Employee::find($id);
        if (!$emp) {
            http_response_code(404);
            echo json_encode(['error' => 'Funcionário não encontrado']);
            exit;
        }

        // traz só as transações deste funcionário
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
        header('Location: /ams-malergeschaft/public/employees');
        exit;
    }

    public function serveDocument()
    {
        EmployeeController::serveDocument($_GET['id'], $_GET['type']);
    }
}
