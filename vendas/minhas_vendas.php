<?php
require_once './includes/auth_check.php';
require_once './includes/header.php';

$vendas = $db->prepare("SELECT v.*, c.nome as cliente FROM vendas v LEFT JOIN clientes c ON v.cliente_id=c.id WHERE v.funcionario_id=? ORDER BY v.data_venda DESC");
$vendas->execute([$_SESSION['funcionario_id']]);
$stats = $db->prepare("SELECT COUNT(*) as total, COALESCE(SUM(valor_total),0) as valor FROM vendas WHERE funcionario_id=?");
$stats->execute([$_SESSION['funcionario_id']]);
$s = $stats->fetch();
?>

<div class="row mb-2">
    <div class="col-6"><div class="card bg-teal text-white"><div class="card-body p-2"><small>Total Vendas</small><h5><?php echo $s['total']; ?></h5></div></div></div>
    <div class="col-6"><div class="card bg-info text-white"><div class="card-body p-2"><small>Valor Total</small><h5>R$ <?php echo number_format($s['valor'],2,',','.'); ?></h5></div></div></div>
</div>

<div class="card">
    <div class="card-header">Minhas Vendas</div>
    <div class="card-body p-0">
        <table class="table table-sm">
            <thead><tr><th>Data</th><th>Cliente</th><th>Valor</th><th>Pagamento</th></tr></thead>
            <tbody>
                <?php while($v = $vendas->fetch()): ?>
                <tr><td><?php echo date('d/m H:i', strtotime($v['data_venda'])); ?></td><td><?php echo $v['cliente'] ?? 'N/I'; ?></td><td>R$ <?php echo number_format($v['valor_total'],2,',','.'); ?></td><td><?php echo $v['forma_pagamento']; ?></td></tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>