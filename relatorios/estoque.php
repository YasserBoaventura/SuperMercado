<?php
require_once './includes/auth_check.php';
require_once './includes/header.php';
$produtos = $db->query("SELECT nome, quantidade_estoque, estoque_minimo FROM produtos WHERE quantidade_estoque <= estoque_minimo ORDER BY quantidade_estoque")->fetchAll();
?>

<div class="card">
    <div class="card-header">Produtos com Estoque Baixo</div>
    <div class="card-body p-0">
        <table class="table table-sm">
            <thead><tr><th>Produto</th><th>Estoque Atual</th><th>Mínimo</th></tr></thead>
            <tbody>
                <?php foreach($produtos as $p): ?>
                <tr><td><?php echo $p['nome']; ?></td><td class="text-danger"><?php echo $p['quantidade_estoque']; ?></td><td><?php echo $p['estoque_minimo']; ?></td></tr>
                <?php endforeach; ?>
                <?php if(empty($produtos)): ?><tr><td colspan="3" class="text-center">Nenhum produto com estoque baixo</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>