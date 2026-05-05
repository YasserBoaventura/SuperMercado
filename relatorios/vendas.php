<?php
require_once '../config/database.php';
require_once '../config/auth.php';
verificarNivelAcesso('admin');

// Filtros
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');
$status = $_GET['status'] ?? 'todos';
$forma_pagamento = $_GET['forma_pagamento'] ?? 'todos';

$where = "WHERE v.data_venda BETWEEN '$data_inicio 00:00:00' AND '$data_fim 23:59:59'";
if($status != 'todos') {
    $where .= " AND v.status = '$status'";
}
if($forma_pagamento != 'todos') {
    $where .= " AND v.forma_pagamento = '$forma_pagamento'";
}

// Buscar vendas com os itens detalhados
$sql = "
    SELECT 
        v.id as venda_id,
        v.numero_venda,
        v.data_venda,
        v.subtotal as venda_subtotal,
        v.desconto as venda_desconto,
        v.total as venda_total,
        v.forma_pagamento,
        v.status,
        u.nome as vendedor_nome,
        c.nome as cliente_nome,
        iv.id as item_id,
        iv.produto_id,
        iv.quantidade,
        iv.preco_unitario,
        iv.subtotal as item_subtotal,
        p.nome as produto_nome,
        p.unidade_medida
    FROM vendas v
    LEFT JOIN usuarios u ON v.usuario_id = u.id
    LEFT JOIN clientes c ON v.cliente_id = c.id
    LEFT JOIN itens_venda iv ON v.id = iv.venda_id
    LEFT JOIN produtos p ON iv.produto_id = p.id
    $where
    ORDER BY v.data_venda DESC, v.id, iv.id
";

$vendas = $pdo->query($sql)->fetchAll();

// Organizar os dados por venda
$vendas_organizadas = [];
foreach($vendas as $row) {
    $venda_id = $row['venda_id'];
    if(!isset($vendas_organizadas[$venda_id])) {
        $vendas_organizadas[$venda_id] = [
            'id' => $row['venda_id'],
            'numero_venda' => $row['numero_venda'],
            'data_venda' => $row['data_venda'],
            'vendedor_nome' => $row['vendedor_nome'],
            'cliente_nome' => $row['cliente_nome'],
            'subtotal' => $row['venda_subtotal'],
            'desconto' => $row['venda_desconto'],
            'total' => $row['venda_total'],
            'forma_pagamento' => $row['forma_pagamento'],
            'status' => $row['status'],
            'itens' => []
        ];
    }
    
    if($row['produto_id']) {
        $vendas_organizadas[$venda_id]['itens'][] = [
            'produto_nome' => $row['produto_nome'],
            'quantidade' => $row['quantidade'],
            'unidade_medida' => $row['unidade_medida'],
            'preco_unitario' => $row['preco_unitario'],
            'subtotal' => $row['item_subtotal']
        ];
    }
}

// Totais
$totais = $pdo->query("
    SELECT 
        COUNT(*) as total_vendas,
        COALESCE(SUM(total), 0) as valor_total,
        COALESCE(AVG(total), 0) as ticket_medio,
        COALESCE(SUM(desconto), 0) as total_descontos
    FROM vendas v
    $where
")->fetch();

// Vendas por dia
$vendas_por_dia = $pdo->query("
    SELECT 
        DATE(v.data_venda) as data,
        COUNT(*) as quantidade,
        SUM(v.total) as total
    FROM vendas v
    $where
    GROUP BY DATE(v.data_venda)
    ORDER BY data DESC
")->fetchAll();

// Produtos mais vendidos
$produtos_top = $pdo->query("
    SELECT 
        p.nome,
        p.unidade_medida,
        SUM(iv.quantidade) as total_vendido,
        SUM(iv.subtotal) as valor_total,
        AVG(iv.preco_unitario) as preco_medio
    FROM itens_venda iv
    JOIN produtos p ON iv.produto_id = p.id
    JOIN vendas v ON iv.venda_id = v.id
    $where
    GROUP BY p.id
    ORDER BY total_vendido DESC
    LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Vendas - Supermercado</title>
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
            <a class="nav-link active" href="vendas.php"><i class="fas fa-chart-line"></i> Vendas</a>
            <a class="nav-link" href="vendas_vendedor.php"><i class="fas fa-user-chart"></i> Por Vendedor</a>
        </div>
    </div>
    
    <div class="col-md-10 main-content">
        <h2><i class="fas fa-chart-line"></i> Relatório de Vendas</h2>
        
        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row">
                    <div class="col-md-3">
                        <label>Data Início</label>
                        <input type="date" name="data_inicio" class="form-control" value="<?php echo $data_inicio; ?>">
                    </div>
                    <div class="col-md-3">
                        <label>Data Fim</label>
                        <input type="date" name="data_fim" class="form-control" value="<?php echo $data_fim; ?>">
                    </div>
                    <div class="col-md-2">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="todos" <?php echo $status == 'todos' ? 'selected' : ''; ?>>Todos</option>
                            <option value="concluida" <?php echo $status == 'concluida' ? 'selected' : ''; ?>>Concluídas</option>
                            <option value="pendente" <?php echo $status == 'pendente' ? 'selected' : ''; ?>>Pendentes</option>
                            <option value="cancelada" <?php echo $status == 'cancelada' ? 'selected' : ''; ?>>Canceladas</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>Forma Pagamento</label>
                        <select name="forma_pagamento" class="form-control">
                            <option value="todos" <?php echo $forma_pagamento == 'todos' ? 'selected' : ''; ?>>Todos</option>
                            <option value="dinheiro" <?php echo $forma_pagamento == 'dinheiro' ? 'selected' : ''; ?>>Dinheiro</option>
                            <option value="cartao_credito" <?php echo $forma_pagamento == 'cartao_credito' ? 'selected' : ''; ?>>Cartão Crédito</option>
                            <option value="cartao_debito" <?php echo $forma_pagamento == 'cartao_debito' ? 'selected' : ''; ?>>Cartão Débito</option>
                            <option value="pix" <?php echo $forma_pagamento == 'pix' ? 'selected' : ''; ?>>PIX</option>
                            <option value="boleto" <?php echo $forma_pagamento == 'boleto' ? 'selected' : ''; ?>>Boleto</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Cards de Totais -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h6>Total de Vendas</h6>
                        <h3><?php echo $totais['total_vendas']; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h6>Valor Total</h6>
                        <h3>R$ <?php echo number_format($totais['valor_total'], 2, ',', '.'); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h6>Ticket Médio</h6>
                        <h3>R$ <?php echo number_format($totais['ticket_medio'], 2, ',', '.'); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <h6>Total Descontos</h6>
                        <h3>R$ <?php echo number_format($totais['total_descontos'], 2, ',', '.'); ?></h3>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Gráfico de Vendas por Dia -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Vendas por Dia</h5>
            </div>
            <div class="card-body">
                <canvas id="vendasChart" height="100"></canvas>
            </div>
        </div>
        
        <!-- Produtos Mais Vendidos -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Produtos Mais Vendidos</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>Produto</th>
                                <th>Quantidade Vendida</th>
                                <th>Valor Total</th>
                                <th>% Participação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_geral = array_sum(array_column($produtos_top, 'valor_total'));
                            foreach($produtos_top as $produto): 
                                $percentual = $total_geral > 0 ? ($produto['valor_total'] / $total_geral) * 100 : 0;
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($produto['nome']); ?></td>
                                    <td><?php echo $produto['total_vendido']; ?> <?php echo $produto['unidade_medida'] ?? 'UN'; ?></td>
                                    <td>R$ <?php echo number_format($produto['valor_total'], 2, ',', '.'); ?></td>
                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar" style="width: <?php echo $percentual; ?>%">
                                                <?php echo number_format($percentual, 1); ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Lista de Vendas - REPETINDO OS DADOS EM CADA LINHA -->
        <div class="card">
            <div class="card-header">
                <h5>Detalhamento das Vendas</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>Nº Venda</th>
                                <th>Data</th>
                                <th>Vendedor</th>
                                <th>Cliente</th>
                                <th>Produto</th>
                                <th>Quantidade</th>
                                <th>Preço Unitário</th>
                                <th>Subtotal</th>
                                <th>Desconto</th>
                                <th>Total</th>
                                <th>Pagamento</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($vendas_organizadas as $venda): ?>
                                <?php foreach($venda['itens'] as $item): ?>
                                    <tr>
                                        <!-- Repete os dados da venda em CADA linha -->
                                        <td><?php echo $venda['numero_venda']; ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($venda['data_venda'])); ?></td>
                                        <td><?php echo htmlspecialchars($venda['vendedor_nome'] ?? 'Vendedor'); ?></td>
                                        <td><?php echo htmlspecialchars($venda['cliente_nome'] ?? 'Não identificado'); ?></td>
                                        
                                        <!-- Dados do produto -->
                                        <td><?php echo htmlspecialchars($item['produto_nome']); ?> (<?php echo $item['unidade_medida']; ?>)</td>
                                        <td class="text-center"><?php echo $item['quantidade']; ?></td>
                                        <td class="text-end">R$ <?php echo number_format($item['preco_unitario'], 2, ',', '.'); ?></td>
                                        <td class="text-end">R$ <?php echo number_format($item['subtotal'], 2, ',', '.'); ?></td>
                                        
                                        <!-- Dados da venda que se repetem -->
                                        <td class="text-end">R$ <?php echo number_format($venda['desconto'], 2, ',', '.'); ?></td>
                                        <td class="fw-bold text-end">R$ <?php echo number_format($venda['total'], 2, ',', '.'); ?></td>
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
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = [
                                                'concluida' => 'success',
                                                'pendente' => 'warning',
                                                'cancelada' => 'danger'
                                            ];
                                            ?>
                                            <span class="badge bg-<?php echo $status_class[$venda['status']] ?? 'secondary'; ?>">
                                                <?php echo ucfirst($venda['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                
                                <?php if(empty($venda['itens'])): ?>
                                    <tr>
                                        <td><?php echo $venda['numero_venda']; ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($venda['data_venda'])); ?></td>
                                        <td><?php echo htmlspecialchars($venda['vendedor_nome'] ?? 'Vendedor'); ?></td>
                                        <td><?php echo htmlspecialchars($venda['cliente_nome'] ?? 'Não identificado'); ?></td>
                                        <td colspan="3" class="text-center">Nenhum produto encontrado</td>
                                        <td class="text-end">R$ <?php echo number_format($venda['subtotal'], 2, ',', '.'); ?></td>
                                        <td class="text-end">R$ <?php echo number_format($venda['desconto'], 2, ',', '.'); ?></td>
                                        <td class="fw-bold text-end">R$ <?php echo number_format($venda['total'], 2, ',', '.'); ?></td>
                                        <td><span class="badge bg-secondary"><?php echo $formas[$venda['forma_pagamento']] ?? $venda['forma_pagamento']; ?></span></td>
                                        <td><span class="badge bg-<?php echo $status_class[$venda['status']] ?? 'secondary'; ?>"><?php echo ucfirst($venda['status']); ?></span></td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            
                            <?php if(empty($vendas_organizadas)): ?>
                                <tr>
                                    <td colspan="12" class="text-center">Nenhuma venda encontrada no período</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Gráfico de vendas
        const ctx = document.getElementById('vendasChart').getContext('2d');
        const vendasPorDia = <?php echo json_encode(array_reverse($vendas_por_dia)); ?>;
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: vendasPorDia.map(item => item.data),
                datasets: [{
                    label: 'Valor (R$)',
                    data: vendasPorDia.map(item => item.total),
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
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
    </script>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>