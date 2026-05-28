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
    <!-- Nenhum CSS customizado - apenas classes Bootstrap -->
</head>
<body class="bg-info bg-opacity-10">

    <!-- Header superior (simulando o include) -->
    <nav class="navbar navbar-expand-lg bg-info bg-opacity-25 shadow-sm mb-4">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold text-info" href="dashboard.php">
                <i class="fas fa-store me-2"></i>Supermercado Gestão
            </a>
            <div class="ms-auto d-flex align-items-center gap-3">
                <span class="text-secondary">
                    <i class="fas fa-user-circle me-1 text-info"></i>
                    <?php echo isset($_SESSION['usuario_nome']) ? htmlspecialchars($_SESSION['usuario_nome']) : 'Usuário'; ?>
                </span>
                <span class="badge bg-info bg-opacity-25 text-info px-3 py-2">
                    <i class="fas fa-tag me-1"></i>
                    <?php echo isset($_SESSION['nivel_acesso']) ? ucfirst($_SESSION['nivel_acesso']) : 'Admin'; ?>
                </span>
                <a href="logout.php" class="btn btn-outline-info btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4">
        <div class="row g-4">
            
            <!-- Sidebar - mesmo estilo suave do login -->
            <div class="col-md-3 col-lg-2">
                <div class="card border border-info border-opacity-25 rounded-4 shadow-sm sticky-top" style="top: 20px;">
                    <div class="card-body p-0">
                        <div class="nav flex-column nav-pills p-3 gap-2">
                            <a class="nav-link active bg-info bg-opacity-25 text-info fw-semibold rounded-3" href="dashboard.php">
                                <i class="fas fa-home me-2"></i> Dashboard
                            </a>
                            
                            <?php if(isset($_SESSION['nivel_acesso']) && $_SESSION['nivel_acesso'] == 'admin'): ?>
                                <div class="dropdown-divider my-2"></div>
                                <div class="small text-secondary px-2 mb-1">GESTÃO</div>
                                <a class="nav-link text-secondary rounded-3" href="produtos/listar.php">
                                    <i class="fas fa-box me-2 text-info"></i> Produtos
                                </a>
                                <a class="nav-link text-secondary rounded-3" href="clientes/listar.php">
                                    <i class="fas fa-users me-2 text-info"></i> Clientes
                                </a>
                                <a class="nav-link text-secondary rounded-3" href="fornecedores/listar.php">
                                    <i class="fas fa-truck me-2 text-info"></i> Fornecedores
                                </a>
                                <a class="nav-link text-secondary rounded-3" href="funcionarios/listar.php">
                                    <i class="fas fa-user-tie me-2 text-info"></i> Funcionários
                                </a>
                                <a class="nav-link text-secondary rounded-3" href="usuarios/listar.php">
                                    <i class="fas fa-user-cog me-2 text-info"></i> Usuários
                                </a>
                                <a class="nav-link text-secondary rounded-3" href="categorias/listar.php">
                                    <i class="fas fa-tags me-2 text-info"></i> Categorias
                                </a>
                                <a class="nav-link text-secondary rounded-3" href="relatorios/estoque.php">
                                    <i class="fas fa-chart-line me-2 text-info"></i> Relatórios
                                </a>
                                <a class="nav-link text-secondary rounded-3" href="movimentacoes/listar.php">
                                    <i class="fas fa-exchange-alt me-2 text-info"></i> Movimentações
                                </a>
                                <a class="nav-link text-secondary rounded-3" href="logs/visualizar.php">
                                    <i class="fas fa-history me-2 text-info"></i> Logs
                                </a>
                            <?php endif; ?>
                            
                            <div class="dropdown-divider my-2"></div>
                            <div class="small text-secondary px-2 mb-1">VENDAS</div>
                            <a class="nav-link text-secondary rounded-3" href="vendas/nova_venda.php">
                                <i class="fas fa-shopping-cart me-2 text-info"></i> Nova Venda
                            </a>
                            <a class="nav-link text-secondary rounded-3" href="vendas/historico.php">
                                <i class="fas fa-list me-2 text-info"></i> Histórico de Vendas
                            </a>
                            
                            <?php if(isset($_SESSION['nivel_acesso']) && $_SESSION['nivel_acesso'] == 'vendedor'): ?>
                                <a class="nav-link text-secondary rounded-3" href="vendas/minhas_vendas.php">
                                    <i class="fas fa-chart-simple me-2 text-info"></i> Minhas Vendas
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="mb-4">
                    <h2 class="fw-bold text-info">Dashboard</h2>
                    <p class="text-secondary">Bem-vindo ao painel de controle</p>
                </div>
                
                <!-- Cards de estatísticas -->
                <div class="row g-3 mb-4">
                    <div class="col-sm-6 col-lg-3">
                        <div class="card border border-info border-opacity-25 rounded-4 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-secondary mb-1 small">Total Produtos</p>
                                        <h2 class="fw-bold text-info mb-0"><?php echo isset($totalProdutos) ? $totalProdutos : 0; ?></h2>
                                    </div>
                                    <div class="bg-info bg-opacity-10 rounded-circle p-3">
                                        <i class="fas fa-box fa-2x text-info"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-sm-6 col-lg-3">
                        <div class="card border border-info border-opacity-25 rounded-4 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-secondary mb-1 small">Vendas Hoje</p>
                                        <h2 class="fw-bold text-info mb-0"><?php echo isset($totalVendasHoje) ? $totalVendasHoje : 0; ?></h2>
                                    </div>
                                    <div class="bg-info bg-opacity-10 rounded-circle p-3">
                                        <i class="fas fa-shopping-cart fa-2x text-info"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-sm-6 col-lg-3">
                        <div class="card border border-info border-opacity-25 rounded-4 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-secondary mb-1 small">Faturamento Hoje</p>
                                        <h2 class="fw-bold text-info mb-0">R$ <?php echo isset($valorVendasHoje) ? number_format($valorVendasHoje, 2, ',', '.') : '0,00'; ?></h2>
                                    </div>
                                    <div class="bg-info bg-opacity-10 rounded-circle p-3">
                                        <i class="fas fa-dollar-sign fa-2x text-info"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-sm-6 col-lg-3">
                        <div class="card border border-info border-opacity-25 rounded-4 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-secondary mb-1 small">Estoque Baixo</p>
                                        <h2 class="fw-bold text-danger mb-0"><?php echo isset($produtosBaixoEstoque) ? $produtosBaixoEstoque : 0; ?></h2>
                                    </div>
                                    <div class="bg-danger bg-opacity-10 rounded-circle p-3">
                                        <i class="fas fa-exclamation-triangle fa-2x text-danger"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Produtos Próximos ao Vencimento (apenas para vendedor) -->
                <?php if(isset($_SESSION['nivel_acesso']) && $_SESSION['nivel_acesso'] == 'vendedor' && isset($produtosVencimento) && count($produtosVencimento) > 0): ?>
                <div class="card border border-warning border-opacity-25 rounded-4 shadow-sm mt-3">
                    <div class="card-header bg-warning bg-opacity-10 border-0 rounded-top-4 py-3">
                        <h5 class="mb-0 text-warning">
                            <i class="fas fa-calendar-alt me-2"></i> Produtos Próximos ao Vencimento
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0">Produto</th>
                                        <th class="border-0">Data Validade</th>
                                        <th class="border-0">Dias Restantes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($produtosVencimento as $produto): ?>
                                        <tr>
                                            <td class="fw-semibold"><?php echo htmlspecialchars($produto['nome']); ?></td>
                                            <td>
                                                <span class="badge bg-info bg-opacity-25 text-info px-3 py-2">
                                                    <?php echo date('d/m/Y', strtotime($produto['data_validade'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                $dias = (strtotime($produto['data_validade']) - time()) / 86400;
                                                $diasInt = floor($dias);
                                                $badgeClass = $diasInt <= 7 ? 'danger' : ($diasInt <= 15 ? 'warning' : 'info');
                                                ?>
                                                <span class="badge bg-<?php echo $badgeClass; ?> bg-opacity-25 text-<?php echo $badgeClass; ?> px-3 py-2">
                                                    <i class="fas fa-clock me-1"></i> <?php echo $diasInt; ?> dias
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php elseif(isset($_SESSION['nivel_acesso']) && $_SESSION['nivel_acesso'] == 'vendedor'): ?>
                <div class="card border border-success border-opacity-25 rounded-4 shadow-sm mt-3">
                    <div class="card-body text-center py-4">
                        <i class="fas fa-check-circle fa-3x text-success mb-2"></i>
                        <p class="text-success mb-0">Nenhum produto próximo ao vencimento!</p>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Rodapé informativo -->
                <footer class="mt-5 pt-3 border-top border-info border-opacity-25">
                    <div class="text-center text-secondary small">
                        <i class="fas fa-store me-1 text-info"></i> Sistema de Gestão Supermercado 
                        &copy; <?php echo date('Y'); ?>
                    </div>
                </footer>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

