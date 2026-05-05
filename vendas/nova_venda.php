<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';

$clientes = $pdo->query("SELECT * FROM clientes WHERE ativo = 1 ORDER BY nome")->fetchAll();
$produtos = $pdo->query("SELECT * FROM produtos WHERE status = 'ativo' AND quantidade > 0 ORDER BY nome")->fetchAll();

$carrinho = $_SESSION['carrinho'] ?? [];
$total = 0;
foreach($carrinho as $item) {
    $total += $item['subtotal'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(isset($_POST['add_produto'])) {
        $produto_id = $_POST['produto_id'];
        $quantidade = $_POST['quantidade'];
        
        $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
        $stmt->execute([$produto_id]);
        $produto = $stmt->fetch();
        
        if($produto && $quantidade <= $produto['quantidade']) {
            $subtotal = $produto['preco_venda'] * $quantidade;
            
            if(isset($carrinho[$produto_id])) {
                $carrinho[$produto_id]['quantidade'] += $quantidade;
                $carrinho[$produto_id]['subtotal'] = $carrinho[$produto_id]['quantidade'] * $produto['preco_venda'];
            } else {
                $carrinho[$produto_id] = [
                    'produto_id' => $produto['id'],
                    'nome' => $produto['nome'],
                    'preco' => $produto['preco_venda'],
                    'quantidade' => $quantidade,
                    'subtotal' => $subtotal
                ];
            }
            
            $_SESSION['carrinho'] = $carrinho;
        }
    } elseif(isset($_POST['finalizar'])) {
        $cliente_id = $_POST['cliente_id'] ?: null;
        $subtotal = $total;
        $desconto = str_replace(',', '.', str_replace('.', '', $_POST['desconto']));
        $total_final = $subtotal - $desconto;
        $forma_pagamento = $_POST['forma_pagamento'];
        
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("
                INSERT INTO vendas (usuario_id, cliente_id, subtotal, desconto, total, forma_pagamento)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$_SESSION['usuario_id'], $cliente_id, $subtotal, $desconto, $total_final, $forma_pagamento]);
            $venda_id = $pdo->lastInsertId();
            
            foreach($carrinho as $item) {
                $stmt = $pdo->prepare("
                    INSERT INTO itens_venda (venda_id, produto_id, quantidade, preco_unitario, subtotal)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$venda_id, $item['produto_id'], $item['quantidade'], $item['preco'], $item['subtotal']]);
                
                $stmt = $pdo->prepare("UPDATE produtos SET quantidade = quantidade - ? WHERE id = ?");
                $stmt->execute([$item['quantidade'], $item['produto_id']]);
            }
            
            $pdo->commit();
            unset($_SESSION['carrinho']);
            header('Location: historico.php?msg=Venda realizada com sucesso!');
            exit();
        } catch(Exception $e) {
            $pdo->rollBack();
            $erro = "Erro ao finalizar venda: " . $e->getMessage();
        }
    } elseif(isset($_POST['remover'])) {
        $produto_id = $_POST['produto_id'];
        unset($carrinho[$produto_id]);
        $_SESSION['carrinho'] = $carrinho;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Nova Venda - Supermercado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/custom.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="col-md-2 sidebar">
        <div class="nav flex-column">
            <a class="nav-link active" href="nova_venda.php"><i class="fas fa-shopping-cart"></i> Nova Venda</a>
            <a class="nav-link" href="historico.php"><i class="fas fa-list"></i> Histórico</a>
            <?php if($_SESSION['nivel_acesso'] == 'vendedor'): ?>
                <a class="nav-link" href="minhas_vendas.php"><i class="fas fa-chart-simple"></i> Minhas Vendas</a>
            <?php endif; ?>
            <a class="nav-link" href="../dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        </div>
    </div>
    
    <div class="col-md-10 main-content">
        <h2><i class="fas fa-shopping-cart"></i> Nova Venda</h2>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5>Produtos Disponíveis</h5>
                    </div>
                    <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                        <div class="row">
                            <?php foreach($produtos as $produto): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6><?php echo htmlspecialchars($produto['nome']); ?></h6>
                                            <p class="text-primary">R$ <?php echo number_format($produto['preco_venda'], 2, ',', '.'); ?></p>
                                            <p>Estoque: <?php echo $produto['quantidade']; ?></p>
                                            <form method="POST" class="d-flex">
                                                <input type="hidden" name="produto_id" value="<?php echo $produto['id']; ?>">
                                                <input type="number" name="quantidade" value="1" min="1" max="<?php echo $produto['quantidade']; ?>" class="form-control form-control-sm me-2" style="width: 70px;">
                                                <button type="submit" name="add_produto" class="btn btn-sm btn-success">
                                                    <i class="fas fa-cart-plus"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5>Carrinho de Compras</h5>
                    </div>
                    <div class="card-body">
                        <?php if(empty($carrinho)): ?>
                            <p class="text-muted">Carrinho vazio</p>
                        <?php else: ?>
                            <div style="max-height: 300px; overflow-y: auto;">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Produto</th>
                                            <th>Qtd</th>
                                            <th>Subtotal</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($carrinho as $id => $item): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($item['nome']); ?></td>
                                                <td><?php echo $item['quantidade']; ?></td>
                                                <td>R$ <?php echo number_format($item['subtotal'], 2, ',', '.'); ?></td>
                                                <td>
                                                    <form method="POST">
                                                        <input type="hidden" name="produto_id" value="<?php echo $id; ?>">
                                                        <button type="submit" name="remover" class="btn btn-sm btn-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <form method="POST">
                                <div class="mb-2">
                                    <label>Cliente</label>
                                    <select name="cliente_id" class="form-control form-control-sm">
                                        <option value="">Cliente Não Identificado</option>
                                        <?php foreach($clientes as $cliente): ?>
                                            <option value="<?php echo $cliente['id']; ?>"><?php echo htmlspecialchars($cliente['nome']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-2">
                                    <label>Desconto</label>
                                    <input type="text" name="desconto" class="form-control form-control-sm money" value="0,00">
                                </div>
                                
                                <div class="mb-2">
                                    <label>Forma Pagamento</label>
                                    <select name="forma_pagamento" class="form-control form-control-sm" required>
                                        <option value="dinheiro">Dinheiro</option>
                                        <option value="cartao_credito">Cartão Crédito</option>
                                        <option value="cartao_debito">Cartão Débito</option>
                                        <option value="pix">PIX</option>
                                        <option value="boleto">Boleto</option>
                                    </select>
                                </div>
                                
                                <hr>
                                <h5>Total: R$ <?php echo number_format($total, 2, ',', '.'); ?></h5>
                                
                                <button type="submit" name="finalizar" class="btn btn-success w-100 mt-2" onclick="return confirm('Confirmar venda?')">
                                    <i class="fas fa-check"></i> Finalizar Venda
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/main.js"></script>
</body>
</html>