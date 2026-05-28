
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

                // Atualizar estoque
                $stmt = $pdo->prepare("SELECT quantidade FROM produtos WHERE id = ?");
                $stmt->execute([$item['produto_id']]);
                $quantidade_antes = $stmt->fetchColumn();
                
                // Calcular quantidade depois
                $quantidade_depois = $quantidade_antes - $item['quantidade'];
                
                // Registrar movimentação
                $stmt = $pdo->prepare("
                    INSERT INTO movimentacoes_estoque 
                    (produto_id, tipo, quantidade, quantidade_antes, quantidade_depois, motivo, usuario_id, venda_id) 
                    VALUES (?, 'venda', ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $item['produto_id'],
                    $item['quantidade'],
                    $quantidade_antes,
                    $quantidade_depois,
                    'Venda realizada',
                    $_SESSION['usuario_id'],
                    $venda_id
                ]);
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
    <!-- Nenhum CSS customizado - apenas Bootstrap -->
    <style>
        /* Apenas para animação suave da pesquisa - opcional, sem alterar funcionalidades */
        .product-card {
            transition: all 0.2s ease;
        }
        .product-card:hover {
            transform: translateY(-2px);
        }
        .search-highlight {
            background-color: rgba(13, 110, 253, 0.1);
            border-radius: 4px;
        }
    </style>
</head>
<body class="bg-info bg-opacity-10">

    <!-- Header superior -->
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
            
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2">
                <div class="card border border-info border-opacity-25 rounded-4 shadow-sm sticky-top" style="top: 20px;">
                    <div class="card-body p-0">
                        <div class="nav flex-column nav-pills p-3 gap-2">
                            <a class="nav-link active bg-info bg-opacity-25 text-info fw-semibold rounded-3" href="nova_venda.php">
                                <i class="fas fa-shopping-cart me-2"></i> Nova Venda
                            </a>
                            <a class="nav-link text-secondary rounded-3" href="historico.php">
                                <i class="fas fa-list me-2 text-info"></i> Histórico
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
                        <i class="fas fa-shopping-cart me-2"></i> Nova Venda
                    </h2>
                    <p class="text-secondary small">Adicione produtos ao carrinho e finalize a venda</p>
                </div>
                
                <div class="row g-3">
                    <!-- Produtos Disponíveis -->
                    <div class="col-md-8">
                        <div class="card border border-info border-opacity-25 rounded-3 shadow-sm">
                            <div class="card-header bg-info bg-opacity-10 border-0 rounded-top-3 py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <h6 class="mb-0 text-info"><i class="fas fa-box me-1"></i> Produtos Disponíveis</h6>
                                <div class="d-flex gap-2">
                                    <div class="input-group input-group-sm" style="max-width: 250px;">
                                        <span class="input-group-text bg-light border-info border-opacity-25">
                                            <i class="fas fa-search text-info fa-xs"></i>
                                        </span>
                                        <input type="text" id="searchProduto" class="form-control form-control-sm border-info border-opacity-25" placeholder="Buscar produto...">
                                    </div>
                                    <button class="btn btn-sm btn-outline-info" id="limparBusca" title="Limpar busca">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-3" style="max-height: 480px; overflow-y: auto;">
                                <div class="row g-2" id="produtosContainer">
                                    <?php foreach($produtos as $produto): ?>
                                        <div class="col-sm-6 col-md-4 col-lg-3 produto-item" data-nome="<?php echo strtolower(htmlspecialchars($produto['nome'])); ?>" data-preco="<?php echo $produto['preco_venda']; ?>">
                                            <div class="card border border-info border-opacity-25 rounded-3 product-card h-100">
                                                <div class="card-body p-2">
                                                    <div class="d-flex flex-column">
                                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                                            <h6 class="fw-semibold text-info mb-0 small"><?php echo htmlspecialchars($produto['nome']); ?></h6>
                                                            <span class="badge bg-info bg-opacity-25 text-info px-2 py-1 small"><?php echo $produto['quantidade']; ?> und</span>
                                                        </div>
                                                        <p class="text-success fw-bold mb-2 small">MT <?php echo number_format($produto['preco_venda'], 2, ',', '.'); ?></p>
                                                        <form method="POST" class="d-flex gap-1">
                                                            <input type="hidden" name="produto_id" value="<?php echo $produto['id']; ?>">
                                                            <input type="number" name="quantidade" value="1" min="1" max="<?php echo $produto['quantidade']; ?>" class="form-control form-control-sm rounded-2" style="width: 60px;">
                                                            <button type="submit" name="add_produto" class="btn btn-sm btn-info text-white rounded-2">
                                                                <i class="fas fa-cart-plus"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php if(empty($produtos)): ?>
                                    <div class="text-center text-secondary py-4">
                                        <i class="fas fa-box-open fa-2x mb-2 d-block"></i>
                                        <p class="small mb-0">Nenhum produto disponível no momento</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Carrinho de Compras -->
                    <div class="col-md-4">
                        <div class="card border border-info border-opacity-25 rounded-3 shadow-sm sticky-top" style="top: 20px;">
                            <div class="card-header bg-info bg-opacity-10 border-0 rounded-top-3 py-2">
                                <h6 class="mb-0 text-info"><i class="fas fa-shopping-cart me-1"></i> Carrinho</h6>
                            </div>
                            <div class="card-body p-3">
                                <?php if(empty($carrinho)): ?>
                                    <div class="text-center text-secondary py-4">
                                        <i class="fas fa-shopping-basket fa-3x mb-2 d-block text-info opacity-50"></i>
                                        <p class="small mb-0">Carrinho vazio</p>
                                        <p class="small">Adicione produtos ao lado</p>
                                    </div>
                                <?php else: ?>
                                    <div style="max-height: 320px; overflow-y: auto;" class="mb-3">
                                        <table class="table table-sm table-hover mb-0">
                                            <thead class="table-light">
                                                <tr class="small">
                                                    <th class="border-0">Produto</th>
                                                    <th class="border-0 text-center">Qtd</th>
                                                    <th class="border-0 text-end">Subtotal</th>
                                                    <th class="border-0 text-center"></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($carrinho as $id => $item): ?>
                                                    <tr>
                                                        <td class="small fw-semibold"><?php echo htmlspecialchars($item['nome']); ?></td>
                                                        <td class="text-center small"><?php echo $item['quantidade']; ?></td>
                                                        <td class="text-end small text-success">MT <?php echo number_format($item['subtotal'], 2, ',', '.'); ?></td>
                                                        <td class="text-center">
                                                            <form method="POST">
                                                                <input type="hidden" name="produto_id" value="<?php echo $id; ?>">
                                                                <button type="submit" name="remover" class="btn btn-sm btn-outline-danger rounded-2 py-0 px-1">
                                                                    <i class="fas fa-trash-alt fa-xs"></i>
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
                                            <label class="form-label text-secondary small fw-semibold mb-0">Cliente</label>
                                            <select name="cliente_id" class="form-select form-select-sm border border-info border-opacity-25 rounded-2">
                                                <option value="">— Cliente Não Identificado —</option>
                                                <?php foreach($clientes as $cliente): ?>
                                                    <option value="<?php echo $cliente['id']; ?>"><?php echo htmlspecialchars($cliente['nome']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-2">
                                            <label class="form-label text-secondary small fw-semibold mb-0">Desconto</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-light border-info border-opacity-25">R$</span>
                                                <input type="text" name="desconto" class="form-control form-control-sm border-info border-opacity-25" value="0,00" placeholder="0,00">
                                            </div>
                                        </div>
                                        
                                        <div class="mb-2">
                                            <label class="form-label text-secondary small fw-semibold mb-0">Forma de Pagamento</label>
                                            <select name="forma_pagamento" class="form-select form-select-sm border border-info border-opacity-25 rounded-2" required>
                                                <option value="dinheiro"><i class="fas fa-money-bill-wave"></i> Dinheiro</option>
                                                <option value="cartao_credito"><i class="fas fa-credit-card"></i> Cartão Crédito</option>
                                                <option value="cartao_debito"><i class="fas fa-credit-card"></i> Cartão Débito</option>
                                                <option value="pix"><i class="fas fa-qrcode"></i> PIX</option>
                                                <option value="boleto"><i class="fas fa-barcode"></i> Boleto</option>
                                            </select>
                                        </div>
                                        
                                        <hr class="my-2">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span class="fw-semibold text-secondary">Total:</span>
                                            <span class="fw-bold text-success fs-5">MT <?php echo number_format($total, 2, ',', '.'); ?></span>
                                        </div>
                                        
                                        <button type="submit" name="finalizar" class="btn btn-success w-100 rounded-3" onclick="return confirm('Confirmar esta venda?')">
                                            <i class="fas fa-check-circle me-2"></i> Finalizar Venda
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script para pesquisa de produtos em tempo real -->
    <script>
        $(document).ready(function() {
            // Função de pesquisa
            function filtrarProdutos() {
                var termo = $('#searchProduto').val().toLowerCase().trim();
                
                $('.produto-item').each(function() {
                    var nome = $(this).data('nome');
                    if (termo === '') {
                        $(this).show();
                        // Remove highlight
                        $(this).find('.card-body h6').removeClass('search-highlight');
                    } else if (nome.indexOf(termo) !== -1) {
                        $(this).show();
                        // Destaca o termo encontrado
                        var textoOriginal = $(this).find('.card-body h6').text();
                        var textoDestacado = textoOriginal.replace(new RegExp('(' + termo.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi'), '<span class="search-highlight">$1</span>');
                        $(this).find('.card-body h6').html(textoDestacado);
                    } else {
                        $(this).hide();
                    }
                });
            }
            
            // Limpar busca
            $('#limparBusca').click(function() {
                $('#searchProduto').val('');
                filtrarProdutos();
            });
            
            // Evento de digitação
            $('#searchProduto').on('keyup', function() {
                filtrarProdutos();
            });
            
            // Resetar highlight quando termo for apagado completamente
            $('#searchProduto').on('change', function() {
                if ($(this).val() === '') {
                    $('.produto-item .card-body h6').each(function() {
                        $(this).html($(this).text());
                    });
                }
            });
        });
    </script>
</body>
</html>

