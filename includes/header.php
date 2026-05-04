<?php

$base_path = '/SUPERMARKET/';
if(!isset($auth)) {
  
    require_once __DIR__ . '/../config/database.php';  // Dois pontos pra subir um nível
    require_once __DIR__ . '/../config/auth.php';
    $database = new Database();
    $db = $database->getConnection();
    $auth = new Auth($db);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supermercado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --teal: #008080; --teal-light: #20B2AA; }
        .bg-teal { background-color: var(--teal); }
        .btn-teal { background-color: var(--teal); color: white; border: none; padding: 5px 12px; font-size: 13px; }
        .btn-teal:hover { background-color: var(--teal-light); color: white; }
        .btn-sm { padding: 3px 8px; font-size: 12px; }
        .card { border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 15px; }
        .card-header { background-color: var(--teal); color: white; padding: 8px 12px; font-size: 14px; font-weight: 500; border-radius: 8px 8px 0 0; }
        .table { font-size: 13px; margin-bottom: 0; }
        .table th { background-color: #e6f3f3; padding: 8px; font-size: 12px; }
        .table td { padding: 6px 8px; vertical-align: middle; }
        .modal-content { border-radius: 8px; }
        .modal-header { background-color: var(--teal); color: white; padding: 10px 15px; }
        .modal-header .btn-close { filter: brightness(0) invert(1); }
        .modal-body { padding: 15px; }
        .form-label { font-size: 12px; margin-bottom: 3px; font-weight: 500; }
        .form-control, .form-select { font-size: 13px; padding: 5px 10px; border-radius: 5px; }
        .input-group-text { font-size: 12px; padding: 5px 10px; }
        .navbar { padding: 5px 15px; background-color: var(--teal); }
        .navbar-brand { font-size: 18px; font-weight: 600; }
        .nav-link { font-size: 13px; padding: 8px 12px; }
        .dropdown-menu { font-size: 13px; }
        .badge { font-size: 10px; padding: 3px 6px; }
        h1, h2, h3, h4, h5 { margin-bottom: 10px; }
        h4 { font-size: 16px; }
        .container { max-width: 1400px; padding: 0 15px; }
        .mt-4 { margin-top: 15px; }
        .mb-3 { margin-bottom: 10px; }
        .p-3 { padding: 12px; }
    </style>
</head>
<body style="background-color: #f0f4f4;">
    <nav class="navbar navbar-expand-lg navbar-dark bg-teal">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo $base_path; ?>dashboard.php">
                <i class="fas fa-store"></i> Supermercado
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="<?php echo $base_path; ?>dashboard.php"><i class="fas fa-tachometer-alt"></i> Início</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $base_path; ?>vendas/nova_venda.php"><i class="fas fa-cart-plus"></i> Venda</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $base_path; ?>produtos/listar.php"><i class="fas fa-box"></i> Produtos</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $base_path; ?>clientes/listar.php"><i class="fas fa-users"></i> Clientes</a></li>
                    <?php if($auth->isAdmin()): ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $base_path; ?>fornecedores/listar.php"><i class="fas fa-truck"></i> Fornecedores</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $base_path; ?>funcionarios/listar.php"><i class="fas fa-user-tie"></i> Funcionários</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $base_path; ?>usuarios/listar.php"><i class="fas fa-key"></i> Usuários</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $base_path; ?>relatorios/estoque.php"><i class="fas fa-chart-bar"></i> Relatórios</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php echo $_SESSION['funcionario_nome'] ?? $_SESSION['username']; ?>
                            <span class="badge bg-light text-dark"><?php echo $auth->isAdmin() ? 'Admin' : 'Vendedor'; ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo $base_path; ?>logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-3">