
<?php

require_once '../config/database.php';
require_once '../config/auth.php';
verificarNivelAcesso('admin');

$produtosBaixoEstoque = $pdo->query("
    SELECT p.*, c.nome as categoria_nome 
    FROM produtos p
    LEFT JOIN categorias c ON p.categoria_id = c.id
    WHERE p.quantidade <= p.quantidade_minima AND p.status = 'ativo'
    ORDER BY p.quantidade ASC
")->fetchAll();

$produtosVencimento = $pdo->query("
    SELECT p.*, c.nome as categoria_nome,
    DATEDIFF(p.data_validade, CURDATE()) as dias_restantes
    FROM produtos p
    LEFT JOIN categorias c ON p.categoria_id = c.id
    WHERE p.data_validade IS NOT NULL 
    AND p.data_validade <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    AND p.status = 'ativo'
    ORDER BY p.data_validade ASC
")->fetchAll();

$valorEstoque = $pdo->query("
    SELECT SUM(preco_compra * quantidade) as total_compra,
           SUM(preco_venda * quantidade) as total_venda
    FROM produtos
    WHERE status = 'ativo'
")->fetch();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório Estoque - Supermercado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Nenhum CSS customizado - apenas Bootstrap -->
</head>
<body class="bg-info bg-opacity-10">

    <!-- Header superior -->
    <nav class="navbar navbar-expand-lg bg-info bg-opacity-25 shadow-sm mb-3">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold text-info" href="../dashboard.php">
                <i class="fas fa-store me-2"></i>Supermercado Gestão
            </a>
            <div class="ms-auto d-flex align-items-center gap-3">
                <span class="text-secondary small">
                    <i class="fas fa-user-circle me-1 text-info"></i>
                    <?php echo isset($_SESSION['usuario_nome']) ? htmlspecialchars($_SESSION['usuario_nome']) : 'Usuário'; ?>
                </span>
                <span class="badge bg-info bg-opacity-25 text-info px-2 py-1">
                    <i class="fas fa-tag me-1"></i>
                    <?php echo isset($_SESSION['nivel_acesso']) ? ucfirst($_SESSION['nivel_acesso']) : 'Admin'; ?>
                </span>
                <a href="../logout.php" class="btn btn-outline-info btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4">
        <div class="row g-3">
            
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2">
                <div class="card border border-info border-opacity-25 rounded-4 shadow-sm sticky-top" style="top: 20px;">
                    <div class="card-body p-0">
                        <div class="nav flex-column nav-pills p-3 gap-2">
                            <a class="nav-link text-secondary rounded-3" href="../dashboard.php">
                                <i class="fas fa-home me-2 text-info"></i> Dashboard
                            </a>
                            <a class="nav-link active bg-info bg-opacity-25 text-info fw-semibold rounded-3" href="estoque.php">
                                <i class="fas fa-boxes me-2"></i> Relatório Estoque
                            </a>
                            <a class="nav-link text-secondary rounded-3" href="vendas.php">
                                <i class="fas fa-chart-line me-2 text-info"></i> Relatório Vendas
                            </a>
                            <a class="nav-link text-secondary rounded-3" href="vendas_vendedor.php">
                                <i class="fas fa-user-chart me-2 text-info"></i> Vendas por Vendedor
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="mb-3">
                    <h2 class="fw-bold text-info fs-4">
                        <i class="fas fa-boxes me-2"></i> Relatório de Estoque
                    </h2>
                    <p class="text-secondary small">Análise completa do estoque</p>
                </div>
                
                <!-- Cards de Valores -->
                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <div class="card border border-primary border-opacity-25 rounded-3 shadow-sm">
                            <div class="card-body p-2 p-md-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-secondary mb-0 small">Valor de Compra</p>
                                        <h4 class="fw-bold text-primary mb-0 fs-5">MT <?php echo number_format($valorEstoque['total_compra'] ?? 0, 2, ',', '.'); ?></h4>
                                    </div>
                                    <div class="bg-primary bg-opacity-10 rounded-circle p-2">
                                        <i class="fas fa-shopping-cart text-primary fs-6"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border border-success border-opacity-25 rounded-3 shadow-sm">
                            <div class="card-body p-2 p-md-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-secondary mb-0 small">Valor de Venda</p>
                                        <h4 class="fw-bold text-success mb-0 fs-5">MT <?php echo number_format($valorEstoque['total_venda'] ?? 0, 2, ',', '.'); ?></h4>
                                    </div>
                                    <div class="bg-success bg-opacity-10 rounded-circle p-2">
                                        <i class="fas fa-tag text-success fs-6"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border border-info border-opacity-25 rounded-3 shadow-sm">
                            <div class="card-body p-2 p-md-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-secondary mb-0 small">Lucro Potencial</p>
                                        <h4 class="fw-bold text-info mb-0 fs-5">MT <?php echo number_format(($valorEstoque['total_venda'] ?? 0) - ($valorEstoque['total_compra'] ?? 0), 2, ',', '.'); ?></h4>
                                    </div>
                                    <div class="bg-info bg-opacity-10 rounded-circle p-2">
                                        <i class="fas fa-chart-line text-info fs-6"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Produtos com Estoque Baixo -->
                <div class="card border border-danger border-opacity-25 rounded-3 shadow-sm mb-3">
                    <div class="card-header bg-danger bg-opacity-10 border-0 rounded-top-3 py-2">
                        <h6 class="mb-0 text-danger"><i class="fas fa-exclamation-triangle me-1"></i> Produtos com Estoque Baixo</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr class="small">
                                        <th class="border-0 ps-3">Produto</th>
                                        <th class="border-0">Categoria</th>
                                        <th class="border-0 text-center">Estoque Atual</th>
                                        <th class="border-0 text-center">Estoque Mínimo</th>
                                        <th class="border-0 text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(isset($produtosBaixoEstoque) && !empty($produtosBaixoEstoque)): ?>
                                        <?php foreach($produtosBaixoEstoque as $produto): ?>
                                            <tr class="align-middle">
                                                <td class="ps-3 small fw-semibold"><?php echo htmlspecialchars($produto['nome']); ?>
                                                <td class="small"><?php echo htmlspecialchars($produto['categoria_nome'] ?? '-'); ?>
                                                <td class="text-center">
                                                    <span class="badge bg-danger bg-opacity-25 text-danger px-2 py-1">
                                                        <?php echo $produto['quantidade']; ?> und
                                                    </span>
                                                
                                                <td class="text-center small"><?php echo $produto['quantidade_minima']; ?>它
                                                <td class="text-center">
                                                    <span class="badge bg-danger bg-opacity-25 text-danger px-2 py-1">
                                                        <i class="fas fa-clock me-1"></i> Urgente
                                                    </span>
                                                
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-3">
                                                <i class="fas fa-check-circle text-success me-1"></i>
                                                <span class="small">Nenhum produto com estoque baixo</span>
                                            
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Produtos Próximos ao Vencimento -->
                <div class="card border border-warning border-opacity-25 rounded-3 shadow-sm">
                    <div class="card-header bg-warning bg-opacity-10 border-0 rounded-top-3 py-2">
                        <h6 class="mb-0 text-warning"><i class="fas fa-calendar-alt me-1"></i> Produtos Próximos ao Vencimento</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr class="small">
                                        <th class="border-0 ps-3">Produto</th>
                                        <th class="border-0">Categoria</th>
                                        <th class="border-0">Data Validade</th>
                                        <th class="border-0 text-center">Dias Restantes</th>
                                        <th class="border-0 text-center">Qtd Estoque</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(isset($produtosVencimento) && !empty($produtosVencimento)): ?>
                                        <?php foreach($produtosVencimento as $produto): ?>
                                            <tr class="align-middle">
                                                <td class="ps-3 small fw-semibold"><?php echo htmlspecialchars($produto['nome']); ?>
                                                <td class="small"><?php echo htmlspecialchars($produto['categoria_nome'] ?? '-'); ?>
                                                <td class="small"><?php echo date('d/m/Y', strtotime($produto['data_validade'])); ?>
                                                <td class="text-center">
                                                    <?php 
                                                    $diasRestantes = $produto['dias_restantes'];
                                                    $badgeClass = $diasRestantes <= 7 ? 'danger' : 'warning';
                                                    ?>
                                                    <span class="badge bg-<?php echo $badgeClass; ?> bg-opacity-25 text-<?php echo $badgeClass; ?> px-2 py-1">
                                                        <i class="fas fa-hourglass-half me-1"></i> <?php echo $diasRestantes; ?> dias
                                                    </span>
                                                
                                                <td class="text-center small"><?php echo $produto['quantidade']; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-3">
                                                <i class="fas fa-check-circle text-success me-1"></i>
                                                <span class="small">Nenhum produto próximo ao vencimento</span>
                                            
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Rodapé -->
                <footer class="mt-3 pt-2 border-top border-info border-opacity-25">
                    <div class="text-center text-secondary small">
                        <i class="fas fa-store me-1 text-info"></i> Sistema de Gestão Supermercado &copy; <?php echo date('Y'); ?>
                    </div>
                </footer>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

