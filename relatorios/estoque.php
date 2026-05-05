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
    <link rel="stylesheet" href="../css/custom.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="col-md-2 sidebar">
        <div class="nav flex-column">
            <a class="nav-link" href="../dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a class="nav-link active" href="estoque.php"><i class="fas fa-boxes"></i> Relatório Estoque</a>
            <a class="nav-link" href="vendas.php"><i class="fas fa-chart-line"></i> Relatório Vendas</a>
            <a class="nav-link" href="vendas_vendedor.php"><i class="fas fa-user-chart"></i> Vendas por Vendedor</a>
        </div>
    </div>
    
    <div class="col-md-10 main-content">
        <h2><i class="fas fa-boxes"></i> Relatório de Estoque</h2>
        
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5>Valor de Compra</h5>
                        <h3>R$ <?php echo number_format($valorEstoque['total_compra'] ?? 0, 2, ',', '.'); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5>Valor de Venda</h5>
                        <h3>R$ <?php echo number_format($valorEstoque['total_venda'] ?? 0, 2, ',', '.'); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h5>Lucro Potencial</h5>
                        <h3>R$ <?php echo number_format(($valorEstoque['total_venda'] ?? 0) - ($valorEstoque['total_compra'] ?? 0), 2, ',', '.'); ?></h3>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-danger text-white">
                <h5><i class="fas fa-exclamation-triangle"></i> Produtos com Estoque Baixo</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>Produto</th>
                                <th>Categoria</th>
                                <th>Estoque Atual</th>
                                <th>Estoque Mínimo</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($produtosBaixoEstoque as $produto): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($produto['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($produto['categoria_nome']); ?></td>
                                    <td class="text-danger fw-bold"><?php echo $produto['quantidade']; ?></td>
                                    <td><?php echo $produto['quantidade_minima']; ?></td>
                                    <td>
                                        <span class="badge bg-danger">Urgente</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if(empty($produtosBaixoEstoque)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">Nenhum produto com estoque baixo</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-warning text-white">
                <h5><i class="fas fa-calendar-alt"></i> Produtos Próximos ao Vencimento</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>Produto</th>
                                <th>Categoria</th>
                                <th>Data Validade</th>
                                <th>Dias Restantes</th>
                                <th>Qtd Estoque</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($produtosVencimento as $produto): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($produto['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($produto['categoria_nome']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($produto['data_validade'])); ?></td>
                                    <td class="<?php echo $produto['dias_restantes'] <= 7 ? 'text-danger fw-bold' : 'text-warning'; ?>">
                                        <?php echo $produto['dias_restantes']; ?> dias
                                    </td>
                                    <td><?php echo $produto['quantidade']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if(empty($produtosVencimento)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">Nenhum produto próximo ao vencimento</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>