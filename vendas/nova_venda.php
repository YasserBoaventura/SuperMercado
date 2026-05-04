<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/header.php';

session_start(); 
if(!isset($_SESSION['carrinho'])) $_SESSION['carrinho'] = [];

$produtos = $db->query("SELECT id, nome, preco_venda, quantidade_estoque FROM produtos WHERE status='ativo' AND quantidade_estoque>0")->fetchAll();
$clientes = $db->query("SELECT id, nome, cpf FROM clientes ORDER BY nome")->fetchAll();

if(isset($_POST['add'])) {
    $prod = $db->prepare("SELECT * FROM produtos WHERE id=?");
    $prod->execute([$_POST['produto_id']]);
    $p = $prod->fetch();
    if($p && $_POST['qtd'] <= $p['quantidade_estoque']) {
        if(isset($_SESSION['carrinho'][$p['id']])) $_SESSION['carrinho'][$p['id']]['qtd'] += $_POST['qtd'];
        else $_SESSION['carrinho'][$p['id']] = ['id'=>$p['id'], 'nome'=>$p['nome'], 'preco'=>$p['preco_venda'], 'qtd'=>$_POST['qtd']];
    }
    header("Location: nova_venda.php");
    exit();
}

if(isset($_GET['remove'])) { unset($_SESSION['carrinho'][$_GET['remove']]); header("Location: nova_venda.php"); exit(); }

if(isset($_POST['finalizar'])) {
    $total = 0;
    foreach($_SESSION['carrinho'] as $item) $total += $item['preco'] * $item['qtd'];
    $desconto = str_replace(',', '.', $_POST['desconto']);
    $total -= $desconto;
    
    try {
        $db->beginTransaction();
        $stmt = $db->prepare("INSERT INTO vendas (cliente_id, funcionario_id, valor_total, desconto, forma_pagamento) VALUES (?,?,?,?,?)");
        $stmt->execute([$_POST['cliente_id'] ?: null, $_SESSION['funcionario_id'], $total, $desconto, $_POST['forma_pagamento']]);
        $venda_id = $db->lastInsertId();
        
        foreach($_SESSION['carrinho'] as $item) {
            $subtotal = $item['preco'] * $item['qtd'];
            $stmt = $db->prepare("INSERT INTO itens_venda (venda_id, produto_id, quantidade, preco_unitario, subtotal) VALUES (?,?,?,?,?)");
            $stmt->execute([$venda_id, $item['id'], $item['qtd'], $item['preco'], $subtotal]);
            $stmt = $db->prepare("UPDATE produtos SET quantidade_estoque = quantidade_estoque - ? WHERE id = ?");
            $stmt->execute([$item['qtd'], $item['id']]);
        }
        $db->commit();
        $_SESSION['carrinho'] = [];
        echo "<script>alert('Venda finalizada!'); window.location='minhas_vendas.php';</script>";
    } catch(Exception $e) { $db->rollBack(); echo "<script>alert('Erro: {$e->getMessage()}');</script>"; }
}

$total_carrinho = 0;
foreach($_SESSION['carrinho'] as $item) $total_carrinho += $item['preco'] * $item['qtd'];
?>

<div class="row">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">Adicionar Produto</div>
            <div class="card-body p-2">
                <form method="POST" class="row g-1">
                    <div class="col-8">
                        <select name="produto_id" class="form-select form-select-sm" required>
                            <option value="">Selecione</option>
                            <?php foreach($produtos as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo $p['nome'] . " - R$ " . number_format($p['preco_venda'],2,',','.'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-2"><input type="number" name="qtd" class="form-control form-control-sm" value="1" min="1" required></div>
                    <div class="col-2"><button type="submit" name="add" class="btn btn-teal btn-sm w-100">+</button></div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">Carrinho</div>
            <div class="card-body p-0">
                <?php if(empty($_SESSION['carrinho'])): ?>
                    <p class="text-muted p-2">Vazio</p>
                <?php else: ?>
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Produto</th><th>Qtd</th><th>Subtotal</th><th style="width:40px"></th></tr></thead>
                        <tbody>
                            <?php foreach($_SESSION['carrinho'] as $id=>$item): ?>
                            <tr><td><?php echo $item['nome']; ?></td><td><?php echo $item['qtd']; ?></td><td>R$ <?php echo number_format($item['preco']*$item['qtd'],2,',','.'); ?></td><td><a href="?remove=<?php echo $id; ?>" class="btn btn-sm btn-danger">X</a></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot><tr class="table-active"><td colspan="2"><strong>Total</strong></td><td><strong>R$ <?php echo number_format($total_carrinho,2,',','.'); ?></strong></td><td></td></tr></tfoot>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if(!empty($_SESSION['carrinho'])): ?>
<div class="card mt-2">
    <div class="card-header">Finalizar Venda</div>
    <div class="card-body p-2">
        <form method="POST" class="row g-2">
            <div class="col-md-4"><select name="cliente_id" class="form-select form-select-sm"><option value="">Cliente não identificado</option><?php foreach($clientes as $c): ?><option value="<?php echo $c['id']; ?>"><?php echo $c['nome']; ?></option><?php endforeach; ?></select></div>
            <div class="col-md-3"><select name="forma_pagamento" class="form-select form-select-sm" required><option value="dinheiro">Dinheiro</option><option value="cartao">Cartão</option><option value="pix">PIX</option></select></div>
            <div class="col-md-2"><input type="text" name="desconto" class="form-control form-control-sm" placeholder="Desconto R$" value="0,00"></div>
            <div class="col-md-3"><button type="submit" name="finalizar" class="btn btn-teal btn-sm w-100">Finalizar Venda</button></div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>