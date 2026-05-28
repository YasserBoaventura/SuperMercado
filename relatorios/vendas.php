
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                            <a class="nav-link text-secondary rounded-3" href="estoque.php">
                                <i class="fas fa-boxes me-2 text-info"></i> Estoque
                            </a>
                            <a class="nav-link active bg-info bg-opacity-25 text-info fw-semibold rounded-3" href="vendas.php">
                                <i class="fas fa-chart-line me-2"></i> Vendas
                            </a>
                            <a class="nav-link text-secondary rounded-3" href="vendas_vendedor.php">
                                <i class="fas fa-user-chart me-2 text-info"></i> Por Vendedor
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="mb-3">
                    <h2 class="fw-bold text-info fs-4">
                        <i class="fas fa-chart-line me-2"></i> Relatório de Vendas
                    </h2>
                    <p class="text-secondary small">Análise completa das vendas realizadas</p>
                </div>
                
                <!-- Filtros -->
                <div class="card border border-info border-opacity-25 rounded-3 shadow-sm">
                    <div class="card-header bg-info bg-opacity-10 border-0 rounded-top-3 py-2">
                        <h6 class="mb-0 text-info"><i class="fas fa-filter me-1"></i> Filtros</h6>
                    </div>
                    <div class="card-body py-2">
                        <form method="GET" class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label text-secondary small fw-semibold mb-0">Data Início</label>
                                <input type="date" name="data_inicio" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" value="<?php echo $data_inicio; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-secondary small fw-semibold mb-0">Data Fim</label>
                                <input type="date" name="data_fim" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" value="<?php echo $data_fim; ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label text-secondary small fw-semibold mb-0">Status</label>
                                <select name="status" class="form-select form-select-sm border border-info border-opacity-25 rounded-2">
                                    <option value="todos" <?php echo $status == 'todos' ? 'selected' : ''; ?>>Todos</option>
                                    <option value="concluida" <?php echo $status == 'concluida' ? 'selected' : ''; ?>>Concluídas</option>
                                    <option value="pendente" <?php echo $status == 'pendente' ? 'selected' : ''; ?>>Pendentes</option>
                                    <option value="cancelada" <?php echo $status == 'cancelada' ? 'selected' : ''; ?>>Canceladas</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label text-secondary small fw-semibold mb-0">Forma Pagamento</label>
                                <select name="forma_pagamento" class="form-select form-select-sm border border-info border-opacity-25 rounded-2">
                                    <option value="todos" <?php echo $forma_pagamento == 'todos' ? 'selected' : ''; ?>>Todos</option>
                                    <option value="dinheiro" <?php echo $forma_pagamento == 'dinheiro' ? 'selected' : ''; ?>>Dinheiro</option>
                                    <option value="cartao_credito" <?php echo $forma_pagamento == 'cartao_credito' ? 'selected' : ''; ?>>Cartão Crédito</option>
                                    <option value="cartao_debito" <?php echo $forma_pagamento == 'cartao_debito' ? 'selected' : ''; ?>>Cartão Débito</option>
                                    <option value="pix" <?php echo $forma_pagamento == 'pix' ? 'selected' : ''; ?>>PIX</option>
                                    <option value="boleto" <?php echo $forma_pagamento == 'boleto' ? 'selected' : ''; ?>>Boleto</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-info text-white btn-sm w-100 rounded-2">
                                    <i class="fas fa-search me-1"></i> Filtrar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Cards de Totais -->
                <div class="row g-2 mt-3">
                    <div class="col-md-3">
                        <div class="card border border-primary border-opacity-25 rounded-3 shadow-sm">
                            <div class="card-body p-2 p-md-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-secondary mb-0 small">Total Vendas</p>
                                        <h4 class="fw-bold text-primary mb-0 fs-4"><?php echo $totais['total_vendas']; ?></h4>
                                    </div>
                                    <div class="bg-primary bg-opacity-10 rounded-circle p-2">
                                        <i class="fas fa-shopping-cart text-primary fs-6"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border border-success border-opacity-25 rounded-3 shadow-sm">
                            <div class="card-body p-2 p-md-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-secondary mb-0 small">Valor Total</p>
                                        <h4 class="fw-bold text-success mb-0 fs-6">MT <?php echo number_format($totais['valor_total'], 2, ',', '.'); ?></h4>
                                    </div>
                                    <div class="bg-success bg-opacity-10 rounded-circle p-2">
                                        <i class="fas fa-dollar-sign text-success fs-6"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border border-info border-opacity-25 rounded-3 shadow-sm">
                            <div class="card-body p-2 p-md-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-secondary mb-0 small">Ticket Médio</p>
                                        <h4 class="fw-bold text-info mb-0 fs-6">MT <?php echo number_format($totais['ticket_medio'], 2, ',', '.'); ?></h4>
                                    </div>
                                    <div class="bg-info bg-opacity-10 rounded-circle p-2">
                                        <i class="fas fa-chart-line text-info fs-6"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border border-warning border-opacity-25 rounded-3 shadow-sm">
                            <div class="card-body p-2 p-md-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-secondary mb-0 small">Descontos</p>
                                        <h4 class="fw-bold text-warning mb-0 fs-6">MT <?php echo number_format($totais['total_descontos'], 2, ',', '.'); ?></h4>
                                    </div>
                                    <div class="bg-warning bg-opacity-10 rounded-circle p-2">
                                        <i class="fas fa-tag text-warning fs-6"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Gráfico de Vendas por Dia -->
                <?php if(!empty($vendas_por_dia)): ?>
                <div class="card border border-info border-opacity-25 rounded-3 shadow-sm mt-3">
                    <div class="card-header bg-info bg-opacity-10 border-0 rounded-top-3 py-2">
                        <h6 class="mb-0 text-info"><i class="fas fa-chart-bar me-1"></i> Vendas por Dia</h6>
                    </div>
                    <div class="card-body p-2">
                        <canvas id="vendasChart" height="60" style="max-height: 200px;"></canvas>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Produtos Mais Vendidos -->
                <?php if(!empty($produtos_top)): ?>
                <div class="card border border-info border-opacity-25 rounded-3 shadow-sm mt-3">
                    <div class="card-header bg-info bg-opacity-10 border-0 rounded-top-3 py-2">
                        <h6 class="mb-0 text-info"><i class="fas fa-crown me-1"></i> Produtos Mais Vendidos</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr class="small">
                                        <th class="border-0 ps-3">Produto</th>
                                        <th class="border-0 text-center">Qtd Vendida</th>
                                        <th class="border-0 text-end">Valor Total</th>
                                        <th class="border-0 text-center">% Participação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $total_geral = array_sum(array_column($produtos_top, 'valor_total'));
                                    foreach($produtos_top as $produto): 
                                        $percentual = $total_geral > 0 ? ($produto['valor_total'] / $total_geral) * 100 : 0;
                                    ?>
                                        <tr class="align-middle">
                                            <td class="ps-3 small fw-semibold"><?php echo htmlspecialchars($produto['nome']); ?>
                                            <td class="text-center">
                                                <span class="badge bg-info bg-opacity-25 text-info px-2 py-1">
                                                    <?php echo $produto['total_vendido']; ?> <?php echo $produto['unidade_medida'] ?? 'UN'; ?>
                                                </span>
                                            
                                            <td class="text-end text-success small">MT <?php echo number_format($produto['valor_total'], 2, ',', '.'); ?>
                                            <td class="text-center">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="progress flex-grow-1" style="height: 6px;">
                                                        <div class="progress-bar bg-info" style="width: <?php echo $percentual; ?>%"></div>
                                                    </div>
                                                    <span class="small text-secondary"><?php echo number_format($percentual, 1); ?>%</span>
                                                </div>
                                            
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Detalhamento das Vendas -->
                <div class="card border border-info border-opacity-25 rounded-3 shadow-sm mt-3">
                    <div class="card-header bg-info bg-opacity-10 border-0 rounded-top-3 py-2 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 text-info"><i class="fas fa-list me-1"></i> Detalhamento das Vendas</h6>
                        <span class="badge bg-info bg-opacity-25 text-info">
                            <i class="fas fa-chart-simple me-1"></i> <?php echo count($vendas_organizadas); ?> vendas
                        </span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr class="small">
                                        <th class="border-0 ps-3">Nº Venda</th>
                                        <th class="border-0">Data</th>
                                        <th class="border-0">Vendedor</th>
                                        <th class="border-0">Cliente</th>
                                        <th class="border-0">Produto</th>
                                        <th class="border-0 text-center">Qtd</th>
                                        <th class="border-0 text-end">Preço Unit.</th>
                                        <th class="border-0 text-end">Subtotal</th>
                                        <th class="border-0 text-end">Desconto</th>
                                        <th class="border-0 text-end">Total</th>
                                        <th class="border-0 text-center">Pagamento</th>
                                        <th class="border-0 text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(!empty($vendas_organizadas)): ?>
                                        <?php foreach($vendas_organizadas as $venda): ?>
                                            <?php if(!empty($venda['itens'])): ?>
                                                <?php foreach($venda['itens'] as $index => $item): ?>
                                                    <tr class="align-middle">
                                                        <?php if($index === 0): ?>
                                                            <td class="ps-3" rowspan="<?php echo count($venda['itens']); ?>">
                                                                <code class="small text-info">#<?php echo $venda['numero_venda']; ?></code>
                                                            </td>
                                                            <td class="small" rowspan="<?php echo count($venda['itens']); ?>">
                                                                <?php echo date('d/m/Y H:i', strtotime($venda['data_venda'])); ?>
                                                            </td>
                                                            <td class="small" rowspan="<?php echo count($venda['itens']); ?>">
                                                                <?php echo htmlspecialchars($venda['vendedor_nome'] ?? '—'); ?>
                                                            </td>
                                                            <td class="small" rowspan="<?php echo count($venda['itens']); ?>">
                                                                <?php echo htmlspecialchars($venda['cliente_nome'] ?? 'Não identificado'); ?>
                                                            </td>
                                                        <?php endif; ?>
                                                        <td class="small"><?php echo htmlspecialchars($item['produto_nome']); ?> <?php echo $item['unidade_medida']; ?></td>
                                                        <td class="text-center small"><?php echo $item['quantidade']; ?></td>
                                                        <td class="text-end small">MT <?php echo number_format($item['preco_unitario'], 2, ',', '.'); ?></td>
                                                        <td class="text-end small">MT <?php echo number_format($item['subtotal'], 2, ',', '.'); ?></td>
                                                        <?php if($index === 0): ?>
                                                            <td class="text-end text-danger small" rowspan="<?php echo count($venda['itens']); ?>">
                                                                - MT <?php echo number_format($venda['desconto'], 2, ',', '.'); ?>
                                                            </td>
                                                            <td class="text-end fw-semibold text-success small" rowspan="<?php echo count($venda['itens']); ?>">
                                                                MT <?php echo number_format($venda['total'], 2, ',', '.'); ?>
                                                            </td>
                                                            <td class="text-center" rowspan="<?php echo count($venda['itens']); ?>">
                                                                <span class="badge bg-info bg-opacity-25 text-info px-2 py-1 small">
                                                                    <?php 
                                                                    $formas = [
                                                                        'dinheiro' => '<i class="fas fa-money-bill-wave me-1"></i> Dinheiro',
                                                                        'cartao_credito' => '<i class="fas fa-credit-card me-1"></i> Crédito',
                                                                        'cartao_debito' => '<i class="fas fa-credit-card me-1"></i> Débito',
                                                                        'pix' => '<i class="fas fa-qrcode me-1"></i> PIX',
                                                                        'boleto' => '<i class="fas fa-barcode me-1"></i> Boleto'
                                                                    ];
                                                                    echo $formas[$venda['forma_pagamento']] ?? $venda['forma_pagamento'];
                                                                    ?>
                                                                </span>
                                                            </td>
                                                            <td class="text-center" rowspan="<?php echo count($venda['itens']); ?>">
                                                                <?php if($venda['status'] == 'concluida'): ?>
                                                                    <span class="badge bg-success bg-opacity-25 text-success px-2 py-1">
                                                                        <i class="fas fa-check-circle me-1"></i> OK
                                                                    </span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-warning bg-opacity-25 text-warning px-2 py-1">
                                                                        <i class="fas fa-clock me-1"></i> Pend.
                                                                    </span>
                                                                <?php endif; ?>
                                                            </td>
                                                        <?php endif; ?>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td class="ps-3"><code class="small text-info">#<?php echo $venda['numero_venda']; ?></code></td>
                                                    <td class="small"><?php echo date('d/m/Y H:i', strtotime($venda['data_venda'])); ?></td>
                                                    <td class="small"><?php echo htmlspecialchars($venda['vendedor_nome'] ?? '—'); ?></td>
                                                    <td class="small"><?php echo htmlspecialchars($venda['cliente_nome'] ?? 'Não identificado'); ?></td>
                                                    <td colspan="4" class="text-center text-secondary small">Nenhum produto encontrado</td>
                                                    <td class="text-end text-danger small">- MT <?php echo number_format($venda['desconto'], 2, ',', '.'); ?></td>
                                                    <td class="text-end fw-semibold text-success small">MT <?php echo number_format($venda['total'], 2, ',', '.'); ?></td>
                                                    <td class="text-center"><span class="badge bg-info bg-opacity-25 text-info px-2 py-1"><?php echo $formas[$venda['forma_pagamento']] ?? $venda['forma_pagamento']; ?></span></td>
                                                    <td class="text-center"><span class="badge bg-success bg-opacity-25 text-success px-2 py-1">OK</span></td>
                                                </tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="12" class="text-center py-4">
                                                <i class="fas fa-chart-line fa-2x text-secondary mb-2 d-block"></i>
                                                <p class="text-secondary small mb-0">Nenhuma venda encontrada no período</p>
                                            </td>
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
    <script>
        // Gráfico de vendas
        const vendasPorDia = <?php echo json_encode(array_reverse($vendas_por_dia)); ?>;
        
        if(vendasPorDia.length > 0) {
            const ctx = document.getElementById('vendasChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: vendasPorDia.map(item => {
                        const d = new Date(item.data);
                        return d.toLocaleDateString('pt-BR', {day:'2-digit', month:'2-digit'});
                    }),
                    datasets: [{
                        label: 'Valor (R$)',
                        data: vendasPorDia.map(item => parseFloat(item.total)),
                        borderColor: 'rgb(13, 110, 253)',
                        backgroundColor: 'rgba(13, 110, 253, 0.1)',
                        borderWidth: 2,
                        pointRadius: 3,
                        pointBackgroundColor: 'rgb(13, 110, 253)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'R$ ' + context.raw.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'R$ ' + value.toLocaleString('pt-BR');
                                },
                                font: { size: 9 }
                            },
                            grid: { color: '#e9ecef' }
                        },
                        x: {
                            ticks: { font: { size: 9 }, rotation: 0 }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>
