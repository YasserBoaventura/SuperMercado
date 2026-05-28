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
                            <a class="nav-link text-secondary rounded-3" href="vendas.php">
                                <i class="fas fa-chart-line me-2 text-info"></i> Vendas
                            </a>
                            <a class="nav-link active bg-info bg-opacity-25 text-info fw-semibold rounded-3" href="vendas_vendedor.php">
                                <i class="fas fa-user-chart me-2"></i> Por Vendedor
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="mb-3">
                    <h2 class="fw-bold text-info fs-4">
                        <i class="fas fa-user-chart me-2"></i> Vendas por Vendedor
                    </h2>
                    <p class="text-secondary small">Análise de desempenho individual dos vendedores</p>
                </div>
                
                <!-- Filtros -->
                <div class="card border border-info border-opacity-25 rounded-3 shadow-sm">
                    <div class="card-header bg-info bg-opacity-10 border-0 rounded-top-3 py-2">
                        <h6 class="mb-0 text-info"><i class="fas fa-calendar-alt me-1"></i> Período de Análise</h6>
                    </div>
                    <div class="card-body py-2">
                        <form method="GET" class="row g-2 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-semibold mb-0">Data Início</label>
                                <input type="date" name="data_inicio" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" value="<?php echo $data_inicio; ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-semibold mb-0">Data Fim</label>
                                <input type="date" name="data_fim" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" value="<?php echo $data_fim; ?>">
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-info text-white btn-sm w-100 rounded-2">
                                    <i class="fas fa-search me-1"></i> Filtrar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Cards Resumo -->
                <div class="row g-2 mt-3">
                    <div class="col-md-6">
                        <div class="card border border-primary border-opacity-25 rounded-3 shadow-sm">
                            <div class="card-body p-2 p-md-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-secondary mb-0 small">Total Geral de Vendas</p>
                                        <h4 class="fw-bold text-primary mb-0 fs-5">MT <?php echo number_format($total_geral, 2, ',', '.'); ?></h4>
                                    </div>
                                    <div class="bg-primary bg-opacity-10 rounded-circle p-2">
                                        <i class="fas fa-chart-line text-primary fs-6"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border border-info border-opacity-25 rounded-3 shadow-sm">
                            <div class="card-body p-2 p-md-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-secondary mb-0 small">Média por Vendedor</p>
                                        <h4 class="fw-bold text-info mb-0 fs-5">MT <?php echo number_format($media_geral, 2, ',', '.'); ?></h4>
                                    </div>
                                    <div class="bg-info bg-opacity-10 rounded-circle p-2">
                                        <i class="fas fa-users text-info fs-6"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Ranking de Vendedores -->
                <div class="card border border-success border-opacity-25 rounded-3 shadow-sm mt-3">
                    <div class="card-header bg-success bg-opacity-10 border-0 rounded-top-3 py-2">
                        <h6 class="mb-0 text-success"><i class="fas fa-trophy me-1"></i> Ranking de Vendedores</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr class="small">
                                        <th class="border-0 ps-3 text-center">Posição</th>
                                        <th class="border-0">Vendedor</th>
                                        <th class="border-0 text-center">Vendas</th>
                                        <th class="border-0 text-end">Valor Total</th>
                                        <th class="border-0 text-end">Ticket Médio</th>
                                        <th class="border-0 text-center">Clientes</th>
                                        <th class="border-0 text-center">Participação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $posicao = 1;
                                    foreach($vendas_por_vendedor as $vendedor): 
                                        $percentual = $total_geral > 0 ? ($vendedor['valor_total'] / $total_geral) * 100 : 0;
                                        $medalha = '';
                                        $medalhaClass = '';
                                        if($posicao == 1) {
                                            $medalha = '🥇';
                                            $medalhaClass = 'text-warning';
                                        } elseif($posicao == 2) {
                                            $medalha = '🥈';
                                            $medalhaClass = 'text-secondary';
                                        } elseif($posicao == 3) {
                                            $medalha = '🥉';
                                            $medalhaClass = 'text-danger';
                                        }
                                    ?>
                                        <tr class="align-middle">
                                            <td class="ps-3 text-center">
                                                <span class="fs-6 <?php echo $medalhaClass; ?>"><?php echo $medalha; ?></span> #<?php echo $posicao; ?>
                                            </td>
                                            <td class="fw-semibold small"><?php echo htmlspecialchars($vendedor['vendedor']); ?>
                                            <td class="text-center">
                                                <span class="badge bg-info bg-opacity-25 text-info px-2 py-1"><?php echo $vendedor['total_vendas']; ?> vendas</span>
                                            
                                            <td class="text-end text-success fw-semibold small">MT <?php echo number_format($vendedor['valor_total'], 2, ',', '.'); ?>
                                            <td class="text-end small">MT <?php echo number_format($vendedor['ticket_medio'], 2, ',', '.'); ?>
                                            <td class="text-center small"><?php echo $vendedor['clientes_atendidos']; ?>
                                            <td class="text-center">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="progress flex-grow-1" style="height: 6px;">
                                                        <div class="progress-bar bg-success" style="width: <?php echo $percentual; ?>%"></div>
                                                    </div>
                                                    <span class="small text-secondary"><?php echo number_format($percentual, 1); ?>%</span>
                                                </div>
                                            
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
                    <?php if($vendedor['total_vendas'] > 0 && isset($detalhes_vendedor[$vendedor['id']]) && !empty($detalhes_vendedor[$vendedor['id']])): ?>
                    <div class="card border border-info border-opacity-25 rounded-3 shadow-sm mt-3">
                        <div class="card-header bg-info bg-opacity-10 border-0 rounded-top-3 py-2 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 text-info">
                                <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($vendedor['vendedor']); ?>
                            </h6>
                            <span class="badge bg-success bg-opacity-25 text-success">
                                <i class="fas fa-dollar-sign me-1"></i> MT <?php echo number_format($vendedor['valor_total'], 2, ',', '.'); ?>
                            </span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="table-light">
                                        <tr class="small">
                                            <th class="border-0 ps-3">Nº Venda</th>
                                            <th class="border-0">Data</th>
                                            <th class="border-0">Cliente</th>
                                            <th class="border-0 text-end">Valor</th>
                                            <th class="border-0 text-end">Desconto</th>
                                            <th class="border-0 text-center">Pagamento</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($detalhes_vendedor[$vendedor['id']] as $venda): ?>
                                            <tr class="align-middle">
                                                <td class="ps-3">
                                                    <code class="small text-info">#<?php echo $venda['numero_venda']; ?></code>
                                                </td>
                                                <td class="small"><?php echo date('d/m/Y H:i', strtotime($venda['data_venda'])); ?>
                                                <td class="small"><?php echo htmlspecialchars($venda['cliente_nome'] ?? 'Não identificado'); ?>
                                                <td class="text-end text-success fw-semibold small">MT <?php echo number_format($venda['total'], 2, ',', '.'); ?>
                                                <td class="text-end text-danger small">- MT <?php echo number_format($venda['desconto'], 2, ',', '.'); ?>
                                                <td class="text-center">
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
                                                
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
                
                <!-- Informação do Período -->
                <div class="alert alert-info mt-3 py-2">
                    <i class="fas fa-info-circle me-1"></i> 
                    <small>Período analisado: <strong><?php echo date('d/m/Y', strtotime($data_inicio)); ?></strong> a <strong><?php echo date('d/m/Y', strtotime($data_fim)); ?></strong></small>
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

