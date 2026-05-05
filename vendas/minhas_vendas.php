<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Verificar se é vendedor
if($_SESSION['nivel_acesso'] != 'vendedor') {
    header('Location: ../dashboard.php');
    exit();
}

$page = $_GET['page'] ?? 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');

$where = "WHERE v.usuario_id = " . $_SESSION['usuario_id'] . " 
          AND v.data_venda BETWEEN '$data_inicio 00:00:00' AND '$data_fim 23:59:59'";

$total = $pdo->query("SELECT COUNT(*) FROM vendas v $where")->fetchColumn();
$totalPages = ceil($total / $limit);

$vendas = $pdo->query("
    SELECT v.*, c.nome as cliente_nome,
           (SELECT COUNT(*) FROM itens_venda WHERE venda_id = v.id) as total_itens
    FROM vendas v
    LEFT JOIN clientes c ON v.cliente_id = c.id
    $where
    ORDER BY v.data_venda DESC
    LIMIT $offset, $limit
")->fetchAll();

// Estatísticas pessoais
$estatisticas = $pdo->query("
    SELECT 
        COUNT(*) as total_vendas,
        COALESCE(SUM(total), 0) as valor_total,
        COALESCE(AVG(total), 0) as ticket_medio,
        MAX(total) as maior_venda,
        COUNT(DISTINCT cliente_id) as clientes_distintos
    FROM vendas v
    $where
    AND v.status = 'concluida'
")->fetch();

// Vendas por dia (gráfico)
$vendas_por_dia = $pdo->query("
    SELECT 
        DATE(v.data_venda) as data,
        COUNT(*) as quantidade,
        SUM(v.total) as total
    FROM vendas v
    $where
    AND v.status = 'concluida'
    GROUP BY DATE(v.data_venda)
    ORDER BY data DESC
    LIMIT 15
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Minhas Vendas - Supermercado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/custom.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="col-md-2 sidebar">
        <div class="nav flex-column">
            <a class="nav-link" href="nova_venda.php"><i class="fas fa-shopping-cart"></i> Nova Venda</a>
            <a class="nav-link" href="historico.php"><i class="fas fa-list"></i> Histórico</a>
            <a class="nav-link active" href="minhas_vendas.php"><i class="fas fa-chart-simple"></i> Minhas Vendas</a>
            <a class="nav-link" href="../dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        </div>
    </div>
    
    <div class="col-md-10 main-content">
        <h2><i class="fas fa-chart-simple"></i> Minhas Vendas</h2>
        
        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row">
                    <div class="col-md-4">
                        <label>Data Início</label>
                        <input type="date" name="data_inicio" class="form-control" value="<?php echo $data_inicio; ?>">
                    </div>
                    <div class="col-md-4">
                        <label>Data Fim</label>
                        <input type="date" name="data_fim" class="form-control" value="<?php echo $data_fim; ?>">
                    </div>
                    <div class="col-md-4">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Cards de Estatísticas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h6><i class="fas fa-shopping-cart"></i> Total Vendas</h6>
                        <h2><?php echo $estatisticas['total_vendas']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h6><i class="fas fa-dollar-sign"></i> Valor Total</h6>
                        <h2>R$ <?php echo number_format($estatisticas['valor_total'], 2, ',', '.'); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h6><i class="fas fa-chart-line"></i> Ticket Médio</h6>
                        <h2>R$ <?php echo number_format($estatisticas['ticket_medio'], 2, ',', '.'); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <h6><i class="fas fa-users"></i> Clientes Atendidos</h6>
                        <h2><?php echo $estatisticas['clientes_distintos']; ?></h2>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Gráfico de Vendas -->
        <?php if(!empty($vendas_por_dia)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5>Vendas nos Últimos Dias</h5>
            </div>
            <div class="card-body">
                <canvas id="myChart" height="80"></canvas>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Lista de Vendas -->
        <div class="card">
            <div class="card-header">
                <h5>Histórico de Vendas</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>Nº Venda</th>
                                <th>Data/Hora</th>
                                <th>Cliente</th>
                                <th>Itens</th>
                                <th>Subtotal</th>
                                <th>Desconto</th>
                                <th>Total</th>
                                <th>Pagamento</th>
                                <th>Status</th>
                                <th>Detalhes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($vendas as $venda): ?>
                                <tr>
                                    <td><strong><?php echo $venda['numero_venda']; ?></strong>它
                                    <td><?php echo date('d/m/Y H:i', strtotime($venda['data_venda'])); ?>它
                                    <td><?php echo htmlspecialchars($venda['cliente_nome'] ?? 'Não identificado'); ?>它
                                    <td><?php echo $venda['total_itens']; ?>它
                                    <td>R$ <?php echo number_format($venda['subtotal'], 2, ',', '.'); ?>它
                                    <td>R$ <?php echo number_format($venda['desconto'], 2, ',', '.'); ?>它
                                    <td class="fw-bold text-success">R$ <?php echo number_format($venda['total'], 2, ',', '.'); ?>它
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php 
                                            $formas = [
                                                'dinheiro' => 'Dinheiro',
                                                'cartao_credito' => 'Cartão Crédito',
                                                'cartao_debito' => 'Cartão Débito',
                                                'pix' => 'PIX',
                                                'boleto' => 'Boleto'
                                            ];
                                            echo $formas[$venda['forma_pagamento']] ?? $venda['forma_pagamento'];
                                            ?>
                                        </span>
                                    它
                                    <td>
                                        <span class="badge bg-<?php echo $venda['status'] == 'concluida' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($venda['status']); ?>
                                        </span>
                                    它
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modal<?php echo $venda['id']; ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        
                                        <div class="modal fade" id="modal<?php echo $venda['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-primary text-white">
                                                        <h5 class="modal-title">Detalhes - <?php echo $venda['numero_venda']; ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <?php
                                                        $itens = $pdo->prepare("
                                                            SELECT iv.*, p.nome 
                                                            FROM itens_venda iv
                                                            JOIN produtos p ON iv.produto_id = p.id
                                                            WHERE iv.venda_id = ?
                                                        ");
                                                        $itens->execute([$venda['id']]);
                                                        ?>
                                                        <table class="table table-sm">
                                                            <thead><tr><th>Produto</th><th>Qtd</th><th>Preço</th><th>Subtotal</th></tr></thead>
                                                            <tbody>
                                                                <?php foreach($itens as $item): ?>
                                                                    <tr>
                                                                        <td><?php echo htmlspecialchars($item['nome']); ?>它
                                                                        <td><?php echo $item['quantidade']; ?>它
                                                                        <td>R$ <?php echo number_format($item['preco_unitario'], 2, ',', '.'); ?>它
                                                                        <td>R$ <?php echo number_format($item['subtotal'], 2, ',', '.'); ?>它
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                        <hr>
                                                        <h5>Total: R$ <?php echo number_format($venda['total'], 2, ',', '.'); ?></h5>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    
                                </tr>
                            <?php endforeach; ?>
                            <?php if(empty($vendas)): ?>
                                <tr>
                                    <td colspan="11" class="text-center">Nenhuma venda encontrada no período它
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if($totalPages > 1): ?>
                <nav>
                    <ul class="pagination">
                        <?php for($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&data_inicio=<?php echo $data_inicio; ?>&data_fim=<?php echo $data_fim; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        <?php if(!empty($vendas_por_dia)): ?>
        const ctx = document.getElementById('myChart').getContext('2d');
        const vendasData = <?php echo json_encode(array_reverse($vendas_por_dia)); ?>;
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: vendasData.map(item => item.data),
                datasets: [{
                    label: 'Valor (R$)',
                    data: vendasData.map(item => item.total),
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgb(54, 162, 235)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'R$ ' + value.toLocaleString('pt-BR');
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>