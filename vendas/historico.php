
<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';

$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');
$search = $_GET['search'] ?? '';

$where = "WHERE v.data_venda BETWEEN '$data_inicio 00:00:00' AND '$data_fim 23:59:59'";
if($search) {
    $where .= " AND (v.numero_venda LIKE '%$search%' OR c.nome LIKE '%$search%')";
}

// Se for vendedor, mostrar apenas suas vendas
if($_SESSION['nivel_acesso'] == 'vendedor') {
    $where .= " AND v.usuario_id = " . $_SESSION['usuario_id'];
}

$total = $pdo->query("SELECT COUNT(*) FROM vendas v $where")->fetchColumn();
$totalPages = ceil($total / $limit);

$vendas = $pdo->query("
    SELECT v.*, u.nome as vendedor_nome, c.nome as cliente_nome,
           (SELECT COUNT(*) FROM itens_venda WHERE venda_id = v.id) as total_itens
    FROM vendas v
    LEFT JOIN usuarios u ON v.usuario_id = u.id
    LEFT JOIN clientes c ON v.cliente_id = c.id
    $where
    ORDER BY v.data_venda DESC
    LIMIT $offset, $limit
")->fetchAll();

// Resumo para o período
$resumo = $pdo->query("
    SELECT 
        COUNT(*) as total_vendas,
        COALESCE(SUM(total), 0) as valor_total,
        COALESCE(AVG(total), 0) as ticket_medio
    FROM vendas v
    $where
    AND v.status = 'concluida'
")->fetch();

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Histórico de Vendas - Supermercado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Nenhum CSS customizado - apenas Bootstrap -->
</head>
<body class="bg-info bg-opacity-10">

    <!-- Header superior (mesmo estilo do dashboard) -->
    <nav class="navbar navbar-expand-lg bg-info bg-opacity-25 shadow-sm mb-3">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold text-info" href="../dashboard.php">
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
                <a href="../logout.php" class="btn btn-outline-info btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4">
        <div class="row g-3">
            
            <!-- Sidebar - mesmo estilo -->
            <div class="col-md-3 col-lg-2">
                <div class="card border border-info border-opacity-25 rounded-4 shadow-sm sticky-top" style="top: 20px;">
                    <div class="card-body p-0">
                        <div class="nav flex-column nav-pills p-3 gap-2">
                            <a class="nav-link text-secondary rounded-3" href="nova_venda.php">
                                <i class="fas fa-shopping-cart me-2 text-info"></i> Nova Venda
                            </a>
                            <a class="nav-link active bg-info bg-opacity-25 text-info fw-semibold rounded-3" href="historico.php">
                                <i class="fas fa-list me-2"></i> Histórico
                            </a>
                            <?php if(isset($_SESSION['nivel_acesso']) && $_SESSION['nivel_acesso'] == 'vendedor'): ?>
                                <a class="nav-link text-secondary rounded-3" href="minhas_vendas.php">
                                    <i class="fas fa-chart-simple me-2 text-info"></i> Minhas Vendas
                                </a>
                            <?php endif; ?>
                            <a class="nav-link text-secondary rounded-3" href="../dashboard.php">
                                <i class="fas fa-home me-2 text-info"></i> Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="mb-3">
                    <h2 class="fw-bold text-info fs-4">
                        <i class="fas fa-list me-2"></i> Histórico de Vendas
                    </h2>
                    <p class="text-secondary small">Consulte e gerencie todas as vendas realizadas</p>
                </div>
                
                <!-- Filtros -->
                <div class="card border border-info border-opacity-25 rounded-3 shadow-sm mb-3">
                    <div class="card-body py-2">
                        <form method="GET" class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label text-secondary small fw-semibold mb-0">Data Início</label>
                                <input type="date" name="data_inicio" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" value="<?php echo isset($data_inicio) ? $data_inicio : date('Y-m-01'); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-secondary small fw-semibold mb-0">Data Fim</label>
                                <input type="date" name="data_fim" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" value="<?php echo isset($data_fim) ? $data_fim : date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-semibold mb-0">Buscar</label>
                                <input type="text" name="search" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" placeholder="Nº venda ou cliente" value="<?php echo isset($search) ? htmlspecialchars($search) : ''; ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-info text-white btn-sm w-100 rounded-2">
                                    <i class="fas fa-search me-1"></i> Filtrar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Cards Resumo - compactos -->
                <div class="row g-2 mb-3">
                    <div class="col-6 col-md-4">
                        <div class="card border border-info border-opacity-25 rounded-3 shadow-sm">
                            <div class="card-body p-2 p-md-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-secondary mb-0 small">Total Vendas</p>
                                        <h4 class="fw-bold text-info mb-0 fs-5"><?php echo isset($resumo['total_vendas']) ? $resumo['total_vendas'] : 0; ?></h4>
                                    </div>
                                    <div class="bg-info bg-opacity-10 rounded-circle p-2">
                                        <i class="fas fa-shopping-cart text-info fs-6"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="card border border-success border-opacity-25 rounded-3 shadow-sm">
                            <div class="card-body p-2 p-md-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-secondary mb-0 small">Valor Total</p>
                                        <h4 class="fw-bold text-success mb-0 fs-6">MT <?php echo isset($resumo['valor_total']) ? number_format($resumo['valor_total'], 2, ',', '.') : '0,00'; ?></h4>
                                    </div>
                                    <div class="bg-success bg-opacity-10 rounded-circle p-2">
                                        <i class="fas fa-dollar-sign text-success fs-6"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="card border border-info border-opacity-25 rounded-3 shadow-sm">
                            <div class="card-body p-2 p-md-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-secondary mb-0 small">Ticket Médio</p>
                                        <h4 class="fw-bold text-info mb-0 fs-6">MT <?php echo isset($resumo['ticket_medio']) ? number_format($resumo['ticket_medio'], 2, ',', '.') : '0,00'; ?></h4>
                                    </div>
                                    <div class="bg-info bg-opacity-10 rounded-circle p-2">
                                        <i class="fas fa-chart-line text-info fs-6"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Lista de Vendas -->
                <div class="card border border-info border-opacity-25 rounded-3 shadow-sm">
                    <div class="card-header bg-info bg-opacity-10 border-0 rounded-top-3 py-2 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 text-info"><i class="fas fa-history me-1"></i> Vendas Realizadas</h6>
                        <span class="badge bg-info bg-opacity-25 text-info">
                            <i class="fas fa-chart-simple me-1"></i> <?php echo isset($total) ? $total : 0; ?> registros
                        </span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-sm mb-0">
                                <thead class="table-light">
                                    <tr class="small">
                                        <th class="border-0 ps-3">Nº Venda</th>
                                        <th class="border-0">Data/Hora</th>
                                        <th class="border-0">Vendedor</th>
                                        <th class="border-0">Cliente</th>
                                        <th class="border-0 text-center">Itens</th>
                                        <th class="border-0 text-end">Subtotal</th>
                                        <th class="border-0 text-end">Desc.</th>
                                        <th class="border-0 text-end">Total</th>
                                        <th class="border-0">Pagamento</th>
                                        <th class="border-0 text-center">Status</th>
                                        <th class="border-0 text-center">Detalhes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(isset($vendas) && !empty($vendas)): ?>
                                        <?php foreach($vendas as $venda): ?>
                                            <tr class="align-middle">
                                                <td class="ps-3">
                                                    <span class="fw-semibold text-info small">#<?php echo $venda['numero_venda']; ?></span>
                                                </td>
                                                <td class="small"><?php echo date('d/m/Y H:i', strtotime($venda['data_venda'])); ?></td>
                                                <td class="small"><?php echo htmlspecialchars($venda['vendedor_nome'] ?? 'N/A'); ?></td>
                                                <td class="small"><?php echo htmlspecialchars($venda['cliente_nome'] ?? '—'); ?></td>
                                                <td class="text-center">
                                                    <span class="badge bg-info bg-opacity-25 text-info px-2 py-1">
                                                        <?php echo $venda['total_itens']; ?>
                                                    </span>
                                                </td>
                                                <td class="text-end small">MT <?php echo number_format($venda['subtotal'], 2, ',', '.'); ?></td>
                                                <td class="text-end small text-danger">- MT <?php echo number_format($venda['desconto'], 2, ',', '.'); ?></td>
                                                <td class="text-end fw-semibold text-success">MT <?php echo number_format($venda['total'], 2, ',', '.'); ?></td>
                                                <td>
                                                    <?php 
                                                    $formas = [
                                                        'dinheiro' => '<i class="fas fa-money-bill-wave me-1"></i> Dinheiro',
                                                        'cartao_credito' => '<i class="fas fa-credit-card me-1"></i> Crédito',
                                                        'cartao_debito' => '<i class="fas fa-credit-card me-1"></i> Débito',
                                                        'pix' => '<i class="fas fa-qrcode me-1"></i> PIX',
                                                        'boleto' => '<i class="fas fa-barcode me-1"></i> Boleto'
                                                    ];
                                                    ?>
                                                    <span class="badge bg-info bg-opacity-25 text-info px-2 py-1 small">
                                                        <?php echo $formas[$venda['forma_pagamento']] ?? $venda['forma_pagamento']; ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <?php
                                                    $status_class = [
                                                        'concluida' => 'success',
                                                        'pendente' => 'warning',
                                                        'cancelada' => 'danger'
                                                    ];
                                                    $status_icon = [
                                                        'concluida' => 'check-circle',
                                                        'pendente' => 'clock',
                                                        'cancelada' => 'times-circle'
                                                    ];
                                                    $status_text = [
                                                        'concluida' => 'OK',
                                                        'pendente' => 'Pend.',
                                                        'cancelada' => 'Cancel.'
                                                    ];
                                                    $class = $status_class[$venda['status']] ?? 'secondary';
                                                    $icon = $status_icon[$venda['status']] ?? 'info-circle';
                                                    $text = $status_text[$venda['status']] ?? ucfirst($venda['status']);
                                                    ?>
                                                    <span class="badge bg-<?php echo $class; ?> bg-opacity-25 text-<?php echo $class; ?> px-2 py-1">
                                                        <i class="fas fa-<?php echo $icon; ?> me-1"></i> <?php echo $text; ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-outline-info btn-sm rounded-2 py-0 px-2" data-bs-toggle="modal" data-bs-target="#modal<?php echo $venda['id']; ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    
                                                    <!-- Modal Detalhes -->
                                                    <div class="modal fade" id="modal<?php echo $venda['id']; ?>" tabindex="-1">
                                                        <div class="modal-dialog modal-dialog-centered modal-sm">
                                                            <div class="modal-content rounded-3 border border-info border-opacity-25">
                                                                <div class="modal-header bg-info bg-opacity-25 border-0 rounded-top-3 py-2">
                                                                    <h6 class="modal-title text-info">
                                                                        <i class="fas fa-receipt me-1"></i> Venda #<?php echo $venda['numero_venda']; ?>
                                                                    </h6>
                                                                    <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body p-2">
                                                                    <?php
                                                                    $itens = $pdo->prepare("
                                                                        SELECT iv.*, p.nome 
                                                                        FROM itens_venda iv
                                                                        JOIN produtos p ON iv.produto_id = p.id
                                                                        WHERE iv.venda_id = ?
                                                                    ");
                                                                    $itens->execute([$venda['id']]);
                                                                    $itens_venda = $itens->fetchAll();
                                                                    ?>
                                                                    <div class="table-responsive">
                                                                        <table class="table table-sm table-borderless mb-0">
                                                                            <tbody>
                                                                                <?php foreach($itens_venda as $item): ?>
                                                                                    <tr>
                                                                                        <td class="small"><?php echo htmlspecialchars($item['nome']); ?></td>
                                                                                        <td class="text-center small"><?php echo $item['quantidade']; ?>x</td>
                                                                                        <td class="text-end small">MT <?php echo number_format($item['subtotal'], 2, ',', '.'); ?></td>
                                                                                    </tr>
                                                                                <?php endforeach; ?>
                                                                            </tbody>
                                                                            <tfoot>
                                                                                <tr class="border-top">
                                                                                    <td colspan="2" class="fw-semibold small">Total:</td>
                                                                                    <td class="text-end fw-bold text-success">MT <?php echo number_format($venda['total'], 2, ',', '.'); ?></td>
                                                                                </tr>
                                                                            </tfoot>
                                                                        </table>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer bg-light rounded-bottom-3 py-1">
                                                                    <button type="button" class="btn btn-outline-info btn-sm rounded-2" data-bs-dismiss="modal">Fechar</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="11" class="text-center py-4">
                                                <i class="fas fa-shopping-bag fa-2x text-secondary mb-2 d-block"></i>
                                                <p class="text-secondary small mb-0">Nenhuma venda encontrada no período</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Paginação compacta -->
                        <?php if(isset($totalPages) && $totalPages > 1): ?>
                        <div class="card-footer bg-white border-0 py-2">
                            <nav>
                                <ul class="pagination pagination-sm justify-content-center mb-0">
                                    <?php for($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?php echo $i == ($page ?? 1) ? 'active' : ''; ?>">
                                            <a class="page-link <?php echo $i == ($page ?? 1) ? 'bg-info border-info text-white' : 'text-info border-info border-opacity-25'; ?>" 
                                               href="?page=<?php echo $i; ?>&data_inicio=<?php echo isset($data_inicio) ? $data_inicio : date('Y-m-01'); ?>&data_fim=<?php echo isset($data_fim) ? $data_fim : date('Y-m-d'); ?>&search=<?php echo isset($search) ? urlencode($search) : ''; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        </div>
                        <?php endif; ?>
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
