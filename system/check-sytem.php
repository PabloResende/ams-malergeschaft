<?php
/*
 * Script de Verificação do Sistema AMS Malergeschäft
 * 
 * IMPORTANTE: EXCLUA ESTE ARQUIVO APÓS O USO!
 * Este script verifica a configuração do sistema e a conexão com o banco de dados.
 * Deve ser colocado no diretório raiz do sistema e acessado via navegador.
 */

// Define o tempo limite de execução para 60 segundos
set_time_limit(60);

// Inicia a saída do buffer
ob_start();

// Função para verificar o status
function checkStatus($condition, $successMessage, $errorMessage) {
    if ($condition) {
        return "<span style='color:green;'>✓</span> {$successMessage}";
    } else {
        return "<span style='color:red;'>✗</span> {$errorMessage}";
    }
}

// Função para verificar a conexão com o banco de dados
function checkDatabase($host, $dbname, $username, $password) {
    try {
        $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $pdo = new PDO($dsn, $username, $password, $options);
        
        // Verificar as tabelas
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        return [
            'success' => true,
            'message' => 'Conexão com o banco de dados bem-sucedida',
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

// Verificar permissões dos diretórios
function checkDirectories() {
    $dirsToCheck = [
        'uploads',
        'uploads/employees',
        'uploads/finance',
    ];
    
    $results = [];
    
    foreach ($dirsToCheck as $dir) {
        if (!file_exists($dir)) {
            // Tentar criar o diretório
            mkdir($dir, 0755, true);
        }
        
        $results[$dir] = [
            'exists' => file_exists($dir),
            'writable' => is_writable($dir),
            'permissions' => file_exists($dir) ? substr(sprintf('%o', fileperms($dir)), -4) : 'N/A'
        ];
    }
    
    return $results;
}

// Verificar as extensões do PHP necessárias
function checkExtensions() {
    $requiredExtensions = [
        'pdo',
        'pdo_mysql',
        'mbstring',
        'json',
        'gd',
        'curl',
        'xml',
        'fileinfo',
        'session'
    ];
    
    $results = [];
    
    foreach ($requiredExtensions as $ext) {
        $results[$ext] = extension_loaded($ext);
    }
    
    return $results;
}

// Verificar os arquivos importantes
function checkFiles() {
    $filesToCheck = [
        '.htaccess' => './',
        'public/.htaccess' => './public/',
        'public/index.php' => './public/',
        'config/config.php' => './config/',
        'config/database.php' => './config/',
        'app/helpers.php' => './app/'
    ];
    
    $results = [];
    
    foreach ($filesToCheck as $file => $path) {
        $fullPath = $path . $file;
        $results[$file] = [
            'exists' => file_exists($fullPath),
            'readable' => is_readable($fullPath),
            'path' => $fullPath
        ];
    }
    
    return $results;
}

// Testar a conexão com diferentes hosts
$hosts = ['localhost', '127.0.0.1', 'auth-db1525.hstgr.io'];
$dbConfig = [
    'db' => 'u161269623_saas',
    'user' => 'u161269623_saas',
    'pass' => '$xOOtHax24çÇ@@YU'
];

$dbResults = [];
foreach ($hosts as $host) {
    $dbResults[$host] = checkDatabase($host, $dbConfig['db'], $dbConfig['user'], $dbConfig['pass']);
}

// Obter informações do servidor
$serverInfo = [
    'PHP Version' => phpversion(),
    'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Desconhecido',
    'Document Root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Desconhecido',
    'Server Name' => $_SERVER['SERVER_NAME'] ?? 'Desconhecido',
    'Request Time' => date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME'] ?? time()),
    'Server Protocol' => $_SERVER['SERVER_PROTOCOL'] ?? 'Desconhecido',
    'Remote Address' => $_SERVER['REMOTE_ADDR'] ?? 'Desconhecido',
    'HTTP Host' => $_SERVER['HTTP_HOST'] ?? 'Desconhecido',
    'Server Admin' => $_SERVER['SERVER_ADMIN'] ?? 'Desconhecido',
    'Script Filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'Desconhecido',
    'System Temp Directory' => sys_get_temp_dir()
];

// Verificar diretórios
$dirResults = checkDirectories();

// Verificar extensões
$extResults = checkExtensions();

// Verificar arquivos
$fileResults = checkFiles();

// Verificar mod_rewrite
$modRewriteEnabled = function_exists('apache_get_modules') ? in_array('mod_rewrite', apache_get_modules()) : false;

// Verificar .htaccess
$htaccessTest = "RewriteEngine" && strpos(file_get_contents('.htaccess'), 'RewriteEngine') !== false;

// Começar a saída HTML
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação do Sistema AMS Malergeschäft</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 20px; }
        h1 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        h2 { color: #3498db; margin-top: 20px; }
        .section { background: #f9f9f9; border-radius: 5px; padding: 15px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        .success { color: green; }
        .warning { color: orange; }
        .error { color: red; }
        .notice { padding: 10px; background-color: #fcf8e3; border: 1px solid #faebcc; color: #8a6d3b; border-radius: 4px; margin-bottom: 20px; }
        .actions { background: #f5f5f5; padding: 15px; border-radius: 5px; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>Verificação do Sistema AMS Malergeschäft</h1>
    
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
        <h2>Extensões PHP</h2>
        <table>
            <tr>
                <th>Extensão</th>
                <th>Status</th>
            </tr>
            <?php foreach ($extResults as $ext => $loaded): ?>
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
            <?php foreach ($dirResults as $dir => $status): ?>
            <tr>
                <td><?= htmlspecialchars($dir) ?></td>
                <td class="<?= $status['exists'] ? 'success' : 'error' ?>">
                    <?= $status['exists'] ? '✓ Existe' : '✗ Não encontrado' ?>
                </td>
                <td><?= $status['permissions'] ?></td>
                <td class="<?= $status['writable'] ? 'success' : 'error' ?>">
                    <?= $status['writable'] ? '✓ Sim' : '✗ Não' ?>
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
            <?php foreach ($fileResults as $file => $status): ?>
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
        <h2>Testes Adicionais</h2>
        <table>
            <tr>
                <th>Teste</th>
                <th>Resultado</th>
            </tr>
            <tr>
                <td>mod_rewrite</td>
                <td class="<?= $modRewriteEnabled ? 'success' : 'warning' ?>">
                    <?= $modRewriteEnabled 
                       ? '✓ Habilitado' 
                       : '⚠ Não foi possível verificar (isso é normal em algumas hospedagens)' ?>
                </td>
            </tr>
            <tr>
                <td>Arquivo .htaccess</td>
                <td class="<?= $htaccessTest ? 'success' : 'error' ?>">
                    <?= $htaccessTest ? '✓ Configurado' : '✗ Não encontrado ou mal configurado' ?>
                </td>
            </tr>
        </table>
    </div>
    
    <div class="actions">
        <h2>Ações Recomendadas</h2>
        <ul>
            <?php 
            $hasError = false;
            $hasWarning = false;
            
            // Verificar se algum host de BD conectou com sucesso
            $dbConnected = false;
            foreach ($dbResults as $result) {
                if ($result['success']) {
                    $dbConnected = true;
                    break;
                }
            }
            
            if (!$dbConnected): 
                $hasError = true;
            ?>
                <li class="error">
                    Não foi possível conectar ao banco de dados com nenhum host. 
                    Verifique as credenciais no arquivo config/config.php.
                </li>
            <?php endif; ?>
            
            <?php
            $missingExt = [];
            foreach ($extResults as $ext => $loaded) {
                if (!$loaded) {
                    $missingExt[] = $ext;
                }
            }
            
            if (!empty($missingExt)): 
                $hasError = true;
            ?>
                <li class="error">
                    Faltam extensões PHP essenciais: <?= implode(', ', $missingExt) ?>. 
                    Entre em contato com o suporte da hospedagem.
                </li>
            <?php endif; ?>
            
            <?php
            $dirIssues = [];
            foreach ($dirResults as $dir => $status) {
                if (!$status['exists'] || !$status['writable']) {
                    $dirIssues[] = $dir;
                }
            }
            
            if (!empty($dirIssues)): 
                $hasError = true;
            ?>
                <li class="error">
                    Problemas com diretórios: <?= implode(', ', $dirIssues) ?>. 
                    Verifique se existem e têm permissões de escrita (755).
                </li>
            <?php endif; ?>
            
            <?php
            $fileIssues = [];
            foreach ($fileResults as $file => $status) {
                if (!$status['exists']) {
                    $fileIssues[] = $file;
                }
            }
            
            if (!empty($fileIssues)): 
                $hasError = true;
            ?>
                <li class="error">
                    Arquivos não encontrados: <?= implode(', ', $fileIssues) ?>. 
                    Faça upload destes arquivos.
                </li>
            <?php endif; ?>
            
            <?php if (!$modRewriteEnabled): 
                $hasWarning = true;
            ?>
                <li class="warning">
                    Não foi possível verificar se o mod_rewrite está ativado. 
                    Isso pode ser normal em algumas hospedagens. Se houver problemas de roteamento, 
                    verifique se o mod_rewrite está habilitado.
                </li>
            <?php endif; ?>
            
            <?php if (!$htaccessTest): 
                $hasError = true;
            ?>
                <li class="error">
                    O arquivo .htaccess não foi encontrado ou não contém as configurações necessárias.
                    Verifique se o arquivo foi carregado corretamente.
                </li>
            <?php endif; ?>
            
            <?php if (!$hasError && !$hasWarning): ?>
                <li class="success">
                    Todas as verificações foram bem-sucedidas! Seu sistema parece estar configurado corretamente.
                </li>
            <?php elseif (!$hasError): ?>
                <li class="warning">
                    O sistema deve funcionar, mas existem algumas advertências que você deveria verificar.
                </li>
            <?php endif; ?>
            
            <li class="warning">
                <strong>IMPORTANTE:</strong> Depois de concluir o diagnóstico, 
                <strong>APAGUE ESTE ARQUIVO</strong> do servidor por razões de segurança!
            </li>
        </ul>
    </div>
    
    <div class="section">
        <h2>Próximos Passos</h2>
        <ol>
            <li>Corrigir quaisquer erros ou advertências listados acima</li>
            <li>Acessar o sistema em <a href="https://system.ams.swiss">https://system.ams.swiss</a></li>
            <li>Verificar se todas as funcionalidades estão funcionando corretamente</li>
            <li>Apagar este arquivo de verificação do servidor</li>
        </ol>
    </div>
</body>
</html>