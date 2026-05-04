<?php

session_start();
require_once 'includes/auth_check.php';
require_once 'includes/header.php';


$vendas_hoje = $db->query("SELECT COALESCE(SUM(valor_total),0) as total FROM vendas WHERE DATE(data_venda)=CURDATE()")->fetch()['total'];
$produtos = $db->query("SELECT COUNT(*) as total FROM produtos WHERE status='ativo'")->fetch()['total'];
$clientes = $db->query("SELECT COUNT(*) as total FROM clientes")->fetch()['total'];
$estoque_baixo = $db->query("SELECT COUNT(*) as total FROM produtos WHERE quantidade_estoque <= estoque_minimo")->fetch()['total'];

if(!$auth->isAdmin()) {
    $minhas_vendas = $db->prepare("SELECT COALESCE(SUM(valor_total),0) as total FROM vendas WHERE funcionario_id = ? AND DATE(data_venda)=CURDATE()");
    $minhas_vendas->execute([$_SESSION['funcionario_id']]);
    $minhas_vendas_hoje = $minhas_vendas->fetch()['total'];
}
?>

<div class="row">
    <div class="col-md-3 col-6 mb-2">
        <div class="card bg-teal text-white">
            <div class="card-body p-3">
                <small>Vendas Hoje</small>
                <h5 class="mb-0">R$ <?php echo number_format($vendas_hoje,2,',','.'); ?></h5>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-2">
        <div class="card bg-info text-white">
            <div class="card-body p-3">
                <small>Produtos</small>
                <h5 class="mb-0"><?php echo $produtos; ?></h5>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-2">
        <div class="card bg-success text-white">
            <div class="card-body p-3">
                <small>Clientes</small>
                <h5 class="mb-0"><?php echo $clientes; ?></h5>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-2">
        <div class="card bg-warning text-white">
            <div class="card-body p-3">
                <small>Estoque Baixo</small>
                <h5 class="mb-0"><?php echo $estoque_baixo; ?></h5>
            </div>
        </div>
    </div>
</div>

<?php if(!$auth->isAdmin()): ?>
<div class="row mt-2">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Minhas Vendas Hoje</div>
            <div class="card-body text-center p-3">
                <h3 class="text-teal">R$ <?php echo number_format($minhas_vendas_hoje,2,',','.'); ?></h3>
                <a href="vendas/minhas_vendas.php" class="btn btn-teal btn-sm">Ver Vendas</a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Ações Rápidas</div>
            <div class="card-body p-3">
                <div class="d-grid gap-2">
                    <a href="vendas/nova_venda.php" class="btn btn-teal btn-sm">Nova Venda</a>
                    <a href="clientes/cadastrar.php" class="btn btn-outline-secondary btn-sm">Cadastrar Cliente</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>