<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Negado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
        }
    </style>
</head>
<body>
    <div class="container text-center">
        <div class="card">
            <div class="card-body p-5">
                <i class="fas fa-ban fa-5x text-danger mb-3"></i>
                <h2 class="text-danger">Acesso Negado!</h2>
                <p>Você não tem permissão para acessar esta página.</p>
                <a href="dashboard.php" class="btn btn-primary">
                    <i class="fas fa-home"></i> Voltar ao Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>