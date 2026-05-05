<?php
require_once '../config/database.php';
require_once '../config/auth.php';
verificarNivelAcesso('admin');

$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');

$vendas_por_vendedor = $pdo->query("
    SELECT 
        u.id,
        u.nome as vendedor,
        COUNT(v.id) as total_vendas,
        COALESCE(SUM(v.total), 0) as valor_total,
        COALESCE(AVG(v.total), 0) as ticket_medio,
        COUNT(DISTINCT v.cliente_id) as clientes_atendidos,
        MAX(v.data_venda) as ultima_venda
    FROM usuarios u
    LEFT JOIN vendas v ON u.id = v.usuario_id 
        AND v.data_venda BETWEEN '$data_inicio 00:00:00' AND '$data_fim 23:59:59'
        AND v.status = 'concluida'
    WHERE u.nivel_acesso = 'vendedor'
    GROUP BY u.id
    ORDER BY valor_total DESC
")->fetchAll();

// Vendas detalhadas por vendedor
$detalhes_vendedor = [];
foreach($vendas_por_vendedor as $vendedor) {
    if($vendedor['total_vendas'] > 0) {
        $detalhes = $pdo->prepare("
            SELECT v.*, c.nome as cliente_nome
            FROM vendas v
            LEFT JOIN clientes c ON v.cliente_id = c.id
            WHERE v.usuario_id = ? 
            AND v.data_venda BETWEEN ? AND ?
            AND v.status = 'concluida'
            ORDER BY v.data_venda DESC
        ");
        $detalhes->execute([$vendedor['id'], $data_inicio . ' 00:00:00', $data_fim . ' 23:59:59']);
        $detalhes_vendedor[$vendedor['id']] = $detalhes->fetchAll();
    }
}

$total_geral = array_sum(array_column($vendas_por_vendedor, 'valor_total'));
$media_geral = $total_geral / (count($vendas_por_vendedor) ?: 1);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Vendas por Vendedor - Supermercado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/custom.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="col-md-2 sidebar">
        <div class="nav flex-column">
            <a class="nav-link" href="../dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a class="nav-link" href="estoque.php"><i class="fas fa-boxes"></i> Estoque</a>
            <a class="nav-link" href="vendas.php"><i class="fas fa-chart-line"></i> Vendas</a>
            <a class="nav-link active" href="vendas_vendedor.php"><i class="fas fa-user-chart"></i> Por Vendedor</a>
        </div>
    </div>
    
    <div class="col-md-10 main-content">
        <h2><i class="fas fa-user-chart"></i> Vendas por Vendedor</h2>
        
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
        
        <!-- Cards Resumo -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h6>Total Geral de Vendas</h6>
                        <h3>R$ <?php echo number_format($total_geral, 2, ',', '.'); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h6>Média por Vendedor</h6>
                        <h3>R$ <?php echo number_format($media_geral, 2, ',', '.'); ?></h3>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Ranking de Vendedores -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5><i class="fas fa-trophy"></i> Ranking de Vendedores</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>Posição</th>
                                <th>Vendedor</th>
                                <th>Total Vendas</th>
                                <th>Valor Total</th>
                                <th>Ticket Médio</th>
                                <th>Clientes Atendidos</th>
                                <th>Última Venda</th>
                                <th>Participação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $posicao = 1;
                            foreach($vendas_por_vendedor as $vendedor): 
                                $percentual = $total_geral > 0 ? ($vendedor['valor_total'] / $total_geral) * 100 : 0;
                                $medalha = '';
                                if($posicao == 1) $medalha = '🥇';
                                elseif($posicao == 2) $medalha = '🥈';
                                elseif($posicao == 3) $medalha = '🥉';
                            ?>
                                70
                                    <td class="text-center">
                                        <?php echo $medalha . ' #' . $posicao; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($vendedor['vendedor']); ?></strong>
                                    </td>
                                    <td><?php echo $vendedor['total_vendas']; ?></td>
                                    <td class="fw-bold text-success">
                                        R$ <?php echo number_format($vendedor['valor_total'], 2, ',', '.'); ?>
                                    </td>
                                    <td>R$ <?php echo number_format($vendedor['ticket_medio'], 2, ',', '.'); ?></td>
                                    <td><?php echo $vendedor['clientes_atendidos']; ?></td>
                                    <td><?php echo $vendedor['ultima_venda'] ? date('d/m/Y', strtotime($vendedor['ultima_venda'])) : '-'; ?></td>
                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar" style="width: <?php echo $percentual; ?>%">
                                                <?php echo number_format($percentual, 1); ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php 
                                $posicao++;
                                endforeach; 
                                ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Detalhamento por Vendedor -->
        <?php foreach($vendas_por_vendedor as $vendedor): ?>
            <?php if($vendedor['total_vendas'] > 0): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5>
                        <i class="fas fa-user"></i> 
                        <?php echo htmlspecialchars($vendedor['vendedor']); ?>
                        <span class="badge bg-success float-end">
                            Total: R$ <?php echo number_format($vendedor['valor_total'], 2, ',', '.'); ?>
                        </span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>Nº Venda</th>
                                    <th>Data</th>
                                    <th>Cliente</th>
                                    <th>Valor</th>
                                    <th>Desconto</th>
                                    <th>Pagamento</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($detalhes_vendedor[$vendedor['id']] as $venda): ?>
                                    <tr>
                                        <td><?php echo $venda['numero_venda']; ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($venda['data_venda'])); ?></td>
                                        <td><?php echo htmlspecialchars($venda['cliente_nome'] ?? 'Não identificado'); ?></td>
                                        <td class="fw-bold">R$ <?php echo number_format($venda['total'], 2, ',', '.'); ?></td>
                                        <td>R$ <?php echo number_format($venda['desconto'], 2, ',', '.'); ?></td>
                                        <td>
                                            <?php 
                                            $formas = [
                                                'dinheiro' => 'Dinheiro',
                                                'cartao_credito' => 'Cartão Crédito',
                                                'cartao_debito' => 'Cartão Débito',
                                                'pix' => 'PIX',
                                                'boleto' => 'Boleto'
                                            ];
                                            ?>
                                            <span class="badge bg-secondary">
                                                <?php echo $formas[$venda['forma_pagamento']] ?? $venda['forma_pagamento']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
        
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> 
            Período analisado: <?php echo date('d/m/Y', strtotime($data_inicio)); ?> a <?php echo date('d/m/Y', strtotime($data_fim)); ?>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>