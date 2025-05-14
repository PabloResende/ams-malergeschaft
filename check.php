<?php
// Este arquivo deve ser colocado temporariamente no diretório raiz do seu projeto no servidor
// Acesse através de: https://system.ams.swiss/check.php
// IMPORTANTE: Delete este arquivo após o uso!

// Configuração de cabeçalho
header('Content-Type: text/html; charset=utf-8');

// Desativar limite de tempo de execução
set_time_limit(60);

// Função para checar status
function checkStatus($condition, $message) {
    if ($condition) {
        return "<span style='color:green;'>✓</span> $message";
    } else {
        return "<span style='color:red;'>✗</span> $message";
    }
}

// Função para tentar conexão com o banco de dados
function testDatabase($host, $db, $user, $pass) {
    try {
        $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, $user, $pass, $options);
        
        // Testar conexão com uma query simples
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        return [
            'success' => true, 
            'message' => 'Conexão estabelecida com sucesso',
            'tables' => $tables,
            'count' => count($tables)
        ];
    } catch (PDOException $e) {
        return [
            'success' => false, 
            'message' => $e->getMessage()
        ];
    }
}

// Informações básicas do servidor
$serverInfo = [
    'PHP Version' => PHP_VERSION,
    'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Desconhecido',
    'Document Root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Desconhecido',
    'Server Name' => $_SERVER['SERVER_NAME'] ?? 'Desconhecido',
    'Request URI' => $_SERVER['REQUEST_URI'] ?? 'Desconhecido',
    'Server IP' => $_SERVER['SERVER_ADDR'] ?? 'Desconhecido',
    'Client IP' => $_SERVER['REMOTE_ADDR'] ?? 'Desconhecido',
    'Time' => date('Y-m-d H:i:s'),
    'Timezone' => date_default_timezone_get()
];

// Verificar extensões necessárias
$requiredExtensions = [
    'pdo', 'pdo_mysql', 'mbstring', 'gd', 'curl', 'json', 'session'
];

$extensionsStatus = [];
foreach ($requiredExtensions as $ext) {
    $extensionsStatus[$ext] = extension_loaded($ext);
}

// Verificar diretórios e permissões
$requiredDirs = [
    'uploads', 
    'uploads/employees', 
    'uploads/finance'
];

$dirsStatus = [];
foreach ($requiredDirs as $dir) {
    if (!file_exists($dir)) {
        $dirsStatus[$dir] = [
            'exists' => false,
            'writable' => false,
            'created' => @mkdir($dir, 0755, true)
        ];
    } else {
        $dirsStatus[$dir] = [
            'exists' => true,
            'writable' => is_writable($dir),
            'permissions' => substr(sprintf('%o', fileperms($dir)), -4)
        ];
    }
}

// Verificar arquivos importantes
$requiredFiles = [
    '.htaccess' => './',
    'public/.htaccess' => './public/',
    'index.php' => './public/',
    'config.php' => './config/'
];

$filesStatus = [];
foreach ($requiredFiles as $file => $path) {
    $fullPath = $path . $file;
    $filesStatus[$file] = [
        'exists' => file_exists($fullPath),
        'readable' => is_readable($fullPath),
        'path' => $fullPath
    ];
}

// Testar conexão com banco de dados
$hosts = ['localhost', '127.0.0.1', 'auth-db1525.hstgr.io'];
$dbConfig = [
    'db' => 'u161269623_saas',
    'user' => 'u161269623_saas',
    'pass' => '$xOOtHax24çÇ@@YU'
];

$dbResults = [];
foreach ($hosts as $host) {
    $dbResults[$host] = testDatabase($host, $dbConfig['db'], $dbConfig['user'], $dbConfig['pass']);
}

// Verificar se o arquivo .htaccess está funcionando
$htaccessWorking = !strpos($_SERVER['SCRIPT_NAME'], 'check.php');

// Testar redirecionamentos
$redirectTest = @get_headers("https://{$_SERVER['HTTP_HOST']}/non-existent-page", 1);
$redirectWorks = $redirectTest && isset($redirectTest[0]) && strpos($redirectTest[0], '404') !== false;

// Início da saída HTML
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico do Sistema AMS Malergeschäft</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
            background: #f5f5f5;
        }
        h1 {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        .section {
            background: white;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .section h2 {
            margin-top: 0;
            color: #3498db;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .success {
            color: green;
            font-weight: bold;
        }
        .warning {
            color: orange;
            font-weight: bold;
        }
        .error {
            color: red;
            font-weight: bold;
        }
        .actions {
            margin-top: 20px;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        .notice {
            padding: 10px;
            background-color: #fcf8e3;
            border: 1px solid #faebcc;
            color: #8a6d3b;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <h1>Diagnóstico do Sistema AMS Malergeschäft</h1>
    
    <div class="notice">
        <strong>AVISO DE SEGURANÇA:</strong> Este arquivo contém informações sensíveis sobre seu sistema. 
        Por favor, exclua-o assim que terminar o diagnóstico!
    </div>
    
    <div class="section">
        <h2>Informações do Servidor</h2>
        <table>
            <tr>
                <th>Item</th>
                <th>Valor</th>
            </tr>
            <?php foreach ($serverInfo as $key => $value): ?>
            <tr>
                <td><?= htmlspecialchars($key) ?></td>
                <td><?= htmlspecialchars($value) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <div class="section">
        <h2>Extensões PHP</h2>
        <table>
            <tr>
                <th>Extensão</th>
                <th>Status</th>
            </tr>
            <?php foreach ($extensionsStatus as $ext => $loaded): ?>
            <tr>
                <td><?= htmlspecialchars($ext) ?></td>
                <td class="<?= $loaded ? 'success' : 'error' ?>">
                    <?= $loaded ? '✓ Instalada' : '✗ Não encontrada' ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <div class="section">
        <h2>Diretórios e Permissões</h2>
        <table>
            <tr>
                <th>Diretório</th>
                <th>Status</th>
                <th>Permissões</th>
                <th>Gravável</th>
            </tr>
            <?php foreach ($dirsStatus as $dir => $status): ?>
            <tr>
                <td><?= htmlspecialchars($dir) ?></td>
                <td class="<?= $status['exists'] ? 'success' : ($status['created'] ? 'warning' : 'error') ?>">
                    <?php 
                    if ($status['exists']) {
                        echo '✓ Existe';
                    } else if ($status['created']) {
                        echo '⚠ Criado agora';
                    } else {
                        echo '✗ Não encontrado';
                    }
                    ?>
                </td>
                <td><?= isset($status['permissions']) ? $status['permissions'] : 'N/A' ?></td>
                <td class="<?= ($status['exists'] || $status['created']) && $status['writable'] ? 'success' : 'error' ?>">
                    <?= ($status['exists'] || $status['created']) && $status['writable'] ? '✓ Sim' : '✗ Não' ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <div class="section">
        <h2>Arquivos do Sistema</h2>
        <table>
            <tr>
                <th>Arquivo</th>
                <th>Caminho</th>
                <th>Status</th>
            </tr>
            <?php foreach ($filesStatus as $file => $status): ?>
            <tr>
                <td><?= htmlspecialchars($file) ?></td>
                <td><?= htmlspecialchars($status['path']) ?></td>
                <td class="<?= $status['exists'] ? 'success' : 'error' ?>">
                    <?= $status['exists'] ? '✓ Existe' : '✗ Não encontrado' ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <div class="section">
        <h2>Conexão com Banco de Dados</h2>
        <table>
            <tr>
                <th>Host</th>
                <th>Status</th>
                <th>Tabelas</th>
                <th>Mensagem</th>
            </tr>
            <?php foreach ($dbResults as $host => $result): ?>
            <tr>
                <td><?= htmlspecialchars($host) ?></td>
                <td class="<?= $result['success'] ? 'success' : 'error' ?>">
                    <?= $result['success'] ? '✓ Conectado' : '✗ Falha' ?>
                </td>
                <td><?= isset($result['count']) ? $result['count'] : 'N/A' ?></td>
                <td><?= htmlspecialchars($result['message']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <div class="section">
        <h2>Testes de Configuração</h2>
        <table>
            <tr>
                <th>Teste</th>
                <th>Resultado</th>
            </tr>
            <tr>
                <td>Arquivo .htaccess</td>
                <td class="<?= $htaccessWorking ? 'success' : 'warning' ?>">
                    <?= $htaccessWorking ? 
                        '✓ Funcionando corretamente' : 
                        '⚠ Parece não estar funcionando (URL não está sendo reescrita)' ?>
                </td>
            </tr>
            <tr>
                <td>Redirecionamentos</td>
                <td class="<?= $redirectWorks ? 'success' : 'warning' ?>">
                    <?= $redirectWorks ? 
                        '✓ Funcionando corretamente' : 
                        '⚠ Teste de redirecionamento falhou' ?>
                </td>
            </tr>
        </table>
    </div>
    
    <div class="actions">
        <h3>Ações Recomendadas</h3>
        <ul>
            <?php if (!empty(array_filter($extensionsStatus, function($v) { return !$v; }))): ?>
                <li class="error">Algumas extensões PHP essenciais estão faltando. Entre em contato com o suporte da hospedagem.</li>
            <?php endif; ?>
            
            <?php 
            $dirFailed = false;
            foreach ($dirsStatus as $status) {
                if (!($status['exists'] || $status['created']) || !$status['writable']) {
                    $dirFailed = true;
                    break;
                }
            }
            if ($dirFailed): 
            ?>
                <li class="error">Problemas de permissão nos diretórios. Execute os comandos: chmod -R 755 uploads</li>
            <?php endif; ?>
            
            <?php 
            $allDbsFailes = true;
            foreach ($dbResults as $result) {
                if ($result['success']) {
                    $allDbsFailes = false;
                    break;
                }
            }
            if ($allDbsFailes): 
            ?>
                <li class="error">Não foi possível conectar ao banco de dados com nenhum dos hosts. Verifique as credenciais em config/config.php</li>
            <?php endif; ?>
            
            <?php if (!$htaccessWorking): ?>
                <li class="warning">O arquivo .htaccess parece não estar funcionando corretamente. Verifique se o mod_rewrite está habilitado.</li>
            <?php endif; ?>
            
            <li class="warning">Depois de concluir o diagnóstico, <strong>APAGUE ESTE ARQUIVO</strong> do servidor imediatamente!</li>
        </ul>
    </div>
</body>
</html>