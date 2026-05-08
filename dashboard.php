<?php
require_once 'config/database.php';
require_once 'includes/auth_check.php';

// Estatísticas
$totalProdutos = $pdo->query("SELECT COUNT(*) FROM produtos WHERE status = 'ativo'")->fetchColumn();
$totalVendasHoje = $pdo->query("SELECT COUNT(*) FROM vendas WHERE DATE(data_venda) = CURDATE()")->fetchColumn();
$valorVendasHoje = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM vendas WHERE DATE(data_venda) = CURDATE() AND status = 'concluida'")->fetchColumn();
$produtosBaixoEstoque = $pdo->query("SELECT COUNT(*) FROM produtos WHERE quantidade <= quantidade_minima AND status = 'ativo'")->fetchColumn();

// Produtos próximos ao vencimento (30 dias)
$produtosVencimento = $pdo->query("
    SELECT * FROM produtos 
    WHERE data_validade IS NOT NULL 
    AND data_validade BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    AND status = 'ativo'
    ORDER BY data_validade ASC
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Supermercado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/custom.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <!-- Sidebar -->
    <div class="col-md-2 sidebar">
        <div class="nav flex-column">
            <a class="nav-link active" href="dashboard.php">
                <i class="fas fa-home"></i> Dashboard
            </a>
            
            <?php if($_SESSION['nivel_acesso'] == 'admin'): ?>
                <a class="nav-link" href="produtos/listar.php">
                    <i class="fas fa-box"></i> Produtos
                </a>
                <a class="nav-link" href="clientes/listar.php">
                    <i class="fas fa-users"></i> Clientes
                </a>
                <a class="nav-link" href="fornecedores/listar.php">
                    <i class="fas fa-truck"></i> Fornecedores
                </a>
                <a class="nav-link" href="funcionarios/listar.php">
                    <i class="fas fa-user-tie"></i> Funcionários
                </a>
                <a class="nav-link" href="usuarios/listar.php">
                    <i class="fas fa-user-cog"></i> Usuários
                </a>
                <a class="nav-link" href="categorias/listar.php">
                    <i class="fas fa-tags"></i> Categorias
                </a>
                <a class="nav-link" href="relatorios/estoque.php">
                    <i class="fas fa-chart-line"></i> Relatórios
                </a>
                 <a class="nav-link" href="movimentacoes/listar.php">
                    <i class="fas fa-exchange-alt"></i> Movimentações
                </a>
                <a class="nav-link" href="logs/visualizar.php">
                    <i class="fas fa-history"></i> Logs
                </a> 
            <?php endif; ?>
            
            <a class="nav-link" href="vendas/nova_venda.php">
                <i class="fas fa-shopping-cart"></i> Nova Venda
            </a>
            <a class="nav-link" href="vendas/historico.php">
                <i class="fas fa-list"></i> Histórico de Vendas
            </a>
            
            <?php if($_SESSION['nivel_acesso'] == 'vendedor'): ?>
                <a class="nav-link" href="vendas/minhas_vendas.php">
                    <i class="fas fa-chart-simple"></i> Minhas Vendas
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="col-md-10 main-content">
        <h2 class="mb-4">Dashboard</h2>
        
        <div class="row">
            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-8">
                                <p class="text-muted mb-0">Total Produtos</p>
                                <h3><?php echo $totalProdutos; ?></h3>
                            </div>
                            <div class="col-4 text-end">
                                <i class="fas fa-box fa-3x text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-8">
                                <p class="text-muted mb-0">Vendas Hoje</p>
                                <h3><?php echo $totalVendasHoje; ?></h3>
                            </div>
                            <div class="col-4 text-end">
                                <i class="fas fa-shopping-cart fa-3x text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-8">
                                <p class="text-muted mb-0">Faturamento Hoje</p>
                                <h3>R$ <?php echo number_format($valorVendasHoje, 2, ',', '.'); ?></h3>
                            </div>
                            <div class="col-4 text-end">
                                <i class="fas fa-dollar-sign fa-3x text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-8">
                                <p class="text-muted mb-0">Estoque Baixo</p>
                                <h3><?php echo $produtosBaixoEstoque; ?></h3>
                            </div>
                            <div class="col-4 text-end">
                                <i class="fas fa-exclamation-triangle fa-3x text-danger"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php if($_SESSION['nivel_acesso'] == 'vendedor'  && count($produtosVencimento) > 0): ?>
  
        <div class="card mt-4">
            <div class="card-header bg-warning text-white">
                <h5><i class="fas fa-calendar-alt"></i> Produtos Próximos ao Vencimento</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Data Validade</th>
                                <th>Dias Restantes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($produtosVencimento as $produto): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($produto['nome']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($produto['data_validade'])); ?></td>
                                    <td>
                                        <?php 
                                        $dias = (strtotime($produto['data_validade']) - time()) / 86400;
                                        echo floor($dias) . ' dias';
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>