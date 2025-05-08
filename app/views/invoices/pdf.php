<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fatura #<?= \$inv['number'] ?></title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        h1 { font-size: 24px; }
        p { font-size: 14px; }
    </style>
</head>
<body>
    <h1>Fatura #<?= \$inv['number'] ?></h1>
    <p><strong>Cliente:</strong> <?= \$inv['client_name'] ?></p>
    <p><strong>E-mail:</strong> <?= \$inv['client_email'] ?></p>
    <p><strong>Valor:</strong> <?= number_format(\$inv['amount'],2,',','.') ?> <?= \$config['currency'] ?></p>
    <p><strong>Emissão:</strong> <?= \$inv['issue_date'] ?></p>
    <p><strong>Vencimento:</strong> <?= \$inv['due_date'] ?></p>
    <!-- Adicione detalhes de itens aqui -->
</body>
</html>
