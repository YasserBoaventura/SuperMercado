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
<link rel="stylesheet" href="../css/custom.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="col-md-2 sidebar">
    <div class="nav flex-column">
        <a class="nav-link" href="nova_venda.php"><i class="fas fa-shopping-cart"></i> Nova Venda</a>
        <a class="nav-link active" href="historico.php"><i class="fas fa-list"></i> Histórico</a>
        <?php if($_SESSION['nivel_acesso'] == 'vendedor'): ?>
            <a class="nav-link" href="minhas_vendas.php"><i class="fas fa-chart-simple"></i> Minhas Vendas</a>
        <?php endif; ?>
        <a class="nav-link" href="../dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
    </div>
</div>

<div class="col-md-10 main-content">
    <h2><i class="fas fa-list"></i> Histórico de Vendas</h2>
    
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
                <div class="col-md-4">
                    <label>Buscar</label>
                    <input type="text" name="search" class="form-control" placeholder="Nº venda ou cliente" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Cards Resumo -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h6>Total de Vendas</h6>
                    <h3><?php echo $resumo['total_vendas']; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h6>Valor Total</h6>
                    <h3>R$ <?php echo number_format($resumo['valor_total'], 2, ',', '.'); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h6>Ticket Médio</h6>
                    <h3>R$ <?php echo number_format($resumo['ticket_medio'], 2, ',', '.'); ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Lista de Vendas -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Nº Venda</th>
                            <th>Data/Hora</th>
                            <th>Vendedor</th>
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
                                <td><strong><?php echo $venda['numero_venda']; ?></strong></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($venda['data_venda'])); ?></td>
                                <td><?php echo htmlspecialchars($venda['vendedor_nome'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($venda['cliente_nome'] ?? 'Não identificado'); ?></td>
                                <td><?php echo $venda['total_itens']; ?>它
                                <td>R$ <?php echo number_format($venda['subtotal'], 2, ',', '.'); ?>它
                                <td>R$ <?php echo number_format($venda['desconto'], 2, ',', '.'); ?>它
                                <td class="fw-bold">R$ <?php echo number_format($venda['total'], 2, ',', '.'); ?>它
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
                                它
                                <td>
                                    <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modal<?php echo $venda['id']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    
                                    <!-- Modal Detalhes -->
                                    <div class="modal fade" id="modal<?php echo $venda['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-primary text-white">
                                                    <h5 class="modal-title">Detalhes da Venda - <?php echo $venda['numero_venda']; ?></h5>
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
                                                    $itens_venda = $itens->fetchAll();
                                                    ?>
                                                    <table class="table table-sm">
                                                        <thead>
                                                            <tr><th>Produto</th><th>Qtd</th><th>Preço</th><th>Subtotal</th></tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach($itens_venda as $item): ?>
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
                                                    <p><strong>Total:</strong> R$ <?php echo number_format($venda['total'], 2, ',', '.'); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                它
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
                            <a class="page-link" href="?page=<?php echo $i; ?>&data_inicio=<?php echo $data_inicio; ?>&data_fim=<?php echo $data_fim; ?>&search=<?php echo urlencode($search); ?>">
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

<?php include '../includes/footer.php'; ?>
</body>
</html>