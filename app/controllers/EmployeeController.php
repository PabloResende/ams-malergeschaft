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

        $_SESSION['success'] = $this->langText['employee_created'] ?? 'Funcionário criado com sucesso.';
        header("Location: {$this->baseUrl}/employees");
        exit;
    }

    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$this->baseUrl}/employees");
            exit;
        }

        $empId   = (int) ($_POST['id']      ?? 0);
        $userId  = (int) ($_POST['user_id'] ?? 0);
        $email   = trim($_POST['email']    ?? '');
        $newPwd  = trim($_POST['password'] ?? '');
        $roleId  = (int) ($_POST['role_id'] ?? 1);
        $fullName = trim(($_POST['name'] ?? '') . ' ' . ($_POST['last_name'] ?? ''));

        $roleModel = new Role();
        $role      = $roleModel->find($roleId);
        $roleName  = $role['name'] ?? 'employee';

        $userModel = new UserModel();
        if ($newPwd !== '') {
            $hash = password_hash($newPwd, PASSWORD_DEFAULT);
            $userModel->update($userId, $fullName, $email, $hash, $roleName);
        } else {
            $userModel->update($userId, $fullName, $email, null, $roleName);
        }

        $empModel = new Employee();
        $empModel->update($empId, array_merge($_POST, ['role_id' => $roleId]), $_FILES);

        $_SESSION['success'] = $this->langText['employee_updated'] ?? 'Funcionário atualizado com sucesso.';
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
            echo json_encode(['error' => $this->langText['error_employee_not_found'] ?? 'Funcionário não encontrado']);
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
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            header("Location: {$this->baseUrl}/employees");
            exit;
        }

        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            $employee = (new Employee())->find($id);
            if ($employee) {
                (new Employee())->delete($id);
                
                if (!empty($employee['user_id'])) {
                    $userModel = new UserModel();
                    $userModel->delete((int)$employee['user_id']);
                }
            }
            $_SESSION['success'] = $this->langText['employee_deleted'] ?? 'Funcionário excluído com sucesso.';
        } else {
            $_SESSION['error'] = $this->langText['invalid_employee'] ?? 'Funcionário inválido.';
        }
        
        header("Location: {$this->baseUrl}/employees");
        exit;
    }

    public function getEmployeeHours($employeeId = null)
    {
        header('Content-Type: application/json; charset=UTF-8');
        
        $empId = $employeeId ?? max(0, (int)($_GET['id'] ?? 0));
        if (!$empId) {
            echo json_encode(['entries' => [], 'total_hours' => '0.00']);
            exit;
        }
        
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        $month = $_GET['month'] ?? null;
        $year = $_GET['year'] ?? null;
        
        $allEntries = [];
        $totalHours = 0;
        
        try {
            global $pdo;
            
            // Sistema novo (time_entries)
            try {
                $sql = "
                    SELECT 
                        te.*, 
                        p.name as project_name
                    FROM time_entries te
                    LEFT JOIN projects p ON p.id = te.project_id  
                    WHERE te.employee_id = ?
                ";
                
                $params = [$empId];
                
                if ($startDate && $endDate) {
                    $sql .= " AND te.date BETWEEN ? AND ?";
                    $params[] = $startDate;
                    $params[] = $endDate;
                } elseif ($month && $year) {
                    $sql .= " AND YEAR(te.date) = ? AND MONTH(te.date) = ?";
                    $params[] = $year;
                    $params[] = $month;
                } elseif ($year) {
                    $sql .= " AND YEAR(te.date) = ?";
                    $params[] = $year;
                }
                
                $sql .= " ORDER BY te.date DESC";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $newSystemEntries = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                
                foreach ($newSystemEntries as $entry) {
                    $records = json_decode($entry['time_records'], true) ?? ['entries' => []];
                    $formatted = $this->formatTimeEntryDisplay($records['entries'], $entry['date']);
                    
                    $allEntries[] = [
                        'id' => $entry['id'],
                        'date' => $entry['date'],
                        'total_hours' => (float)$entry['total_hours'],
                        'project_name' => $entry['project_name'],
                        'formatted_display' => $formatted,
                        'system_type' => 'new'
                    ];
                    
                    $totalHours += (float)$entry['total_hours'];
                }
            } catch (Exception $e) {
                error_log("Erro ao buscar time_entries: " . $e->getMessage());
            }
            
            // Sistema antigo (project_work_logs)
            try {
                $sql = "
                    SELECT 
                        pwl.*,
                        p.name as project_name
                    FROM project_work_logs pwl
                    LEFT JOIN projects p ON p.id = pwl.project_id
                    WHERE pwl.employee_id = ?
                ";
                
                $params = [$empId];
                
                if ($startDate && $endDate) {
                    $sql .= " AND pwl.date BETWEEN ? AND ?";
                    $params[] = $startDate;
                    $params[] = $endDate;
                } elseif ($month && $year) {
                    $sql .= " AND YEAR(pwl.date) = ? AND MONTH(pwl.date) = ?";
                    $params[] = $year;
                    $params[] = $month;
                } elseif ($year) {
                    $sql .= " AND YEAR(pwl.date) = ?";
                    $params[] = $year;
                }
                
                $sql .= " ORDER BY pwl.date DESC";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $oldSystemEntries = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                
                foreach ($oldSystemEntries as $entry) {
                    $dateFormatted = date('d/m/Y', strtotime($entry['date']));
                    $hours = (float)$entry['hours'];
                    
                    $allEntries[] = [
                        'id' => $entry['id'],
                        'date' => $entry['date'],
                        'total_hours' => $hours,
                        'project_name' => $entry['project_name'],
                        'formatted_display' => "Sistema antigo: {$hours}h - {$dateFormatted}",
                        'system_type' => 'old'
                    ];
                    
                    $totalHours += $hours;
                }
            } catch (Exception $e) {
                error_log("Erro ao buscar project_work_logs: " . $e->getMessage());
            }
            
        } catch (Exception $e) {
            error_log("Erro ao buscar horas do funcionário {$empId}: " . $e->getMessage());
            echo json_encode(['entries' => [], 'total_hours' => '0.00', 'error' => 'Erro interno']);
            exit;
        }
        
        usort($allEntries, function($a, $b) {
            return strcmp($b['date'], $a['date']);
        });
        
        echo json_encode([
            'entries' => $allEntries,
            'total_hours' => number_format($totalHours, 2, '.', ''),
            'count' => count($allEntries)
        ]);
        exit;
    }

    public function getEmployeeHoursSummary()
    {
        header('Content-Type: application/json; charset=UTF-8');
        
        $empId = (int)($_GET['employee_id'] ?? 0);
        $period = $_GET['period'] ?? 'month';
        $month = $_GET['month'] ?? date('m');
        $year = $_GET['year'] ?? date('Y');
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        
        if (!$empId) {
            echo json_encode(['error' => 'Employee ID required']);
            exit;
        }
        
        try {
            global $pdo;
            $totalHours = 0;
            
            // Sistema novo (time_entries)
            try {
                $sql = "SELECT SUM(total_hours) as total FROM time_entries WHERE employee_id = ?";
                $params = [$empId];
                
                if ($period === 'custom' && $startDate && $endDate) {
                    $sql .= " AND date BETWEEN ? AND ?";
                    $params[] = $startDate;
                    $params[] = $endDate;
                } elseif ($period === 'month') {
                    $sql .= " AND YEAR(date) = ? AND MONTH(date) = ?";
                    $params[] = $year;
                    $params[] = $month;
                } elseif ($period === 'year') {
                    $sql .= " AND YEAR(date) = ?";
                    $params[] = $year;
                }
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                $totalHours += (float)($result['total'] ?? 0);
            } catch (Exception $e) {
                error_log("Erro ao buscar total time_entries: " . $e->getMessage());
            }
            
            // Sistema antigo (project_work_logs)
            try {
                $sql = "SELECT SUM(hours) as total FROM project_work_logs WHERE employee_id = ?";
                $params = [$empId];
                
                if ($period === 'custom' && $startDate && $endDate) {
                    $sql .= " AND date BETWEEN ? AND ?";
                    $params[] = $startDate;
                    $params[] = $endDate;
                } elseif ($period === 'month') {
                    $sql .= " AND YEAR(date) = ? AND MONTH(date) = ?";
                    $params[] = $year;
                    $params[] = $month;
                } elseif ($period === 'year') {
                    $sql .= " AND YEAR(date) = ?";
                    $params[] = $year;
                }
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                $totalHours += (float)($result['total'] ?? 0);
            } catch (Exception $e) {
                error_log("Erro ao buscar total project_work_logs: " . $e->getMessage());
            }
            
            echo json_encode([
                'total_hours' => number_format($totalHours, 2, '.', ''),
                'employee_id' => $empId,
                'period' => $period,
                'month' => $month,
                'year' => $year
            ]);
            
        } catch (Exception $e) {
            error_log("Erro ao buscar resumo de horas: " . $e->getMessage());
            echo json_encode(['error' => 'Erro interno', 'total_hours' => '0.00']);
        }
        
        exit;
    }

    private function formatTimeEntryDisplay(array $entries, string $date): string
    {
        if (empty($entries)) {
            $dateFormatted = date('d/m/Y', strtotime($date));
            return "Sem registros - {$dateFormatted}";
        }

        usort($entries, function($a, $b) {
            return strcmp($a['time'], $b['time']);
        });
        
        $pairs = [];
        $currentEntry = null;
        
        foreach ($entries as $entry) {
            if ($entry['type'] === 'entry') {
                if ($currentEntry) {
                    $pairs[] = "entrada {$currentEntry} saída ?";
                }
                $currentEntry = $entry['time'];
            } elseif ($entry['type'] === 'exit') {
                if ($currentEntry) {
                    $pairs[] = "entrada {$currentEntry} saída {$entry['time']}";
                    $currentEntry = null;
                } else {
                    $pairs[] = "entrada ? saída {$entry['time']}";
                }
            }
        }
        
        if ($currentEntry) {
            $pairs[] = "entrada {$currentEntry} saída ?";
        }
        
        $dateFormatted = date('d/m/Y', strtotime($date));
        $pairsText = empty($pairs) ? 'Sem registros válidos' : implode(' - ', $pairs);
        
        return "{$pairsText} {$dateFormatted}";
    }
}