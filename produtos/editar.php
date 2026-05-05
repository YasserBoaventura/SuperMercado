<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';

$id = $_GET['id'] ?? 0;

// Buscar produto
$stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
$stmt->execute([$id]);
$produto = $stmt->fetch();

if (!$produto) {
    header('Location: listar.php');
    exit();
}

// Processar exclusão
if (isset($_POST['excluir'])) {
    try {
        // Guardar dados para log antes de excluir
        $dados_excluidos = json_encode($produto);
        
        // Verificar se o produto existe em movimentações
        $check = $pdo->prepare("SELECT COUNT(*) FROM movimentacoes WHERE produto_id = ?");
        $check->execute([$id]);
        $tem_movimentacoes = $check->fetchColumn();
        
        if ($tem_movimentacoes > 0) {
            $erro = "Não é possível excluir este produto pois existem movimentações associadas a ele.";
        } else {
            // Excluir produto
            $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
            $stmt->execute([$id]);
            
            // Log
            $log = $pdo->prepare("INSERT INTO logs (usuario_id, acao, tabela_afetada, registro_id, dados_anteriores, ip_address) 
                                  VALUES (?, 'excluir', 'produtos', ?, ?, ?)");
            $log->execute([$_SESSION['usuario_id'], $id, $dados_excluidos, $_SERVER['REMOTE_ADDR']]);
            
            $_SESSION['sucesso'] = "Produto excluído com sucesso!";
            header('Location: listar.php');
            exit();
        }
    } catch(PDOException $e) {
        $erro = "Erro ao excluir: " . $e->getMessage();
    }
}

$categorias = $pdo->query("SELECT * FROM categorias WHERE ativo = 1 ORDER BY nome")->fetchAll();
$fornecedores = $pdo->query("SELECT * FROM fornecedores WHERE ativo = 1 ORDER BY nome_fantasia")->fetchAll();

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['excluir'])) {
    $codigo_barras = $_POST['codigo_barras'];
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $categoria_id = $_POST['categoria_id'];
    $fornecedor_id = $_POST['fornecedor_id'] ?: null;
    $preco_compra = str_replace(',', '.', str_replace('.', '', $_POST['preco_compra']));
    $preco_venda = str_replace(',', '.', str_replace('.', '', $_POST['preco_venda']));
    $quantidade = $_POST['quantidade'];
    $quantidade_minima = $_POST['quantidade_minima'];
    $unidade_medida = $_POST['unidade_medida'];
    $data_validade = $_POST['data_validade'] ?: null;
    $localizacao = $_POST['localizacao'];
    $status = $_POST['status'];
    
    try {
        // Guardar dados antigos para log
        $dados_antigos = json_encode($produto);
        
        $stmt = $pdo->prepare("
            UPDATE produtos SET 
            codigo_barras = ?, nome = ?, descricao = ?, categoria_id = ?, fornecedor_id = ?, 
            preco_compra = ?, preco_venda = ?, quantidade = ?, quantidade_minima = ?, 
            unidade_medida = ?, data_validade = ?, localizacao = ?, status = ?
            WHERE id = ?
        ");
        
        $stmt->execute([$codigo_barras, $nome, $descricao, $categoria_id, $fornecedor_id, 
            $preco_compra, $preco_venda, $quantidade, $quantidade_minima, $unidade_medida, 
            $data_validade, $localizacao, $status, $id]);
        
        // Log
        $dados_novos = json_encode($_POST);
        $log = $pdo->prepare("INSERT INTO logs (usuario_id, acao, tabela_afetada, registro_id, dados_anteriores, dados_novos, ip_address) 
                              VALUES (?, 'editar', 'produtos', ?, ?, ?, ?)");
        $log->execute([$_SESSION['usuario_id'], $id, $dados_antigos, $dados_novos, $_SERVER['REMOTE_ADDR']]);
        
        $sucesso = "Produto atualizado com sucesso!";
        
        // Recarregar dados
        $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
        $stmt->execute([$id]);
        $produto = $stmt->fetch();
        
    } catch(PDOException $e) {
        $erro = "Erro ao atualizar: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Produto - Supermercado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/custom.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="col-md-2 sidebar">
        <div class="nav flex-column">
            <a class="nav-link" href="../dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a class="nav-link" href="listar.php"><i class="fas fa-box"></i> Produtos</a>
            <a class="nav-link" href="cadastrar.php"><i class="fas fa-plus"></i> Novo Produto</a>
            <a class="nav-link active" href="editar.php?id=<?php echo $id; ?>"><i class="fas fa-edit"></i> Editar Produto</a>
        </div>
    </div>
    
    <div class="col-md-10 main-content">
        <h2><i class="fas fa-edit"></i> Editar Produto</h2>
        
        <?php if($sucesso): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $sucesso; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if($erro): ?>
            <div class="alert alert-danger"><?php echo $erro; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label>Código de Barras</label>
                                <input type="text" name="codigo_barras" class="form-control" value="<?php echo htmlspecialchars($produto['codigo_barras']); ?>">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label>Nome do Produto *</label>
                                <input type="text" name="nome" class="form-control" value="<?php echo htmlspecialchars($produto['nome']); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label>Descrição</label>
                        <textarea name="descricao" class="form-control" rows="3"><?php echo htmlspecialchars($produto['descricao']); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label>Categoria *</label>
                                <select name="categoria_id" class="form-control" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach($categorias as $categoria): ?>
                                        <option value="<?php echo $categoria['id']; ?>" <?php echo $produto['categoria_id'] == $categoria['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($categoria['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label>Fornecedor</label>
                                <select name="fornecedor_id" class="form-control">
                                    <option value="">Selecione...</option>
                                    <?php foreach($fornecedores as $fornecedor): ?>
                                        <option value="<?php echo $fornecedor['id']; ?>" <?php echo $produto['fornecedor_id'] == $fornecedor['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($fornecedor['nome_fantasia']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label>Preço Compra</label>
                                <input type="text" name="preco_compra" class="form-control money" value="<?php echo number_format($produto['preco_compra'], 2, ',', '.'); ?>" placeholder="0,00">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label>Preço Venda *</label>
                                <input type="text" name="preco_venda" class="form-control money" value="<?php echo number_format($produto['preco_venda'], 2, ',', '.'); ?>" required placeholder="0,00">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-3">
                                <label>Quantidade *</label>
                                <input type="number" name="quantidade" class="form-control" value="<?php echo $produto['quantidade']; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-3">
                                <label>Estoque Mínimo</label>
                                <input type="number" name="quantidade_minima" class="form-control" value="<?php echo $produto['quantidade_minima']; ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-3">
                                <label>Unidade</label>
                                <select name="unidade_medida" class="form-control">
                                    <option value="UN" <?php echo $produto['unidade_medida'] == 'UN' ? 'selected' : ''; ?>>Unidade</option>
                                    <option value="KG" <?php echo $produto['unidade_medida'] == 'KG' ? 'selected' : ''; ?>>Quilograma</option>
                                    <option value="L" <?php echo $produto['unidade_medida'] == 'L' ? 'selected' : ''; ?>>Litro</option>
                                    <option value="PCT" <?php echo $produto['unidade_medida'] == 'PCT' ? 'selected' : ''; ?>>Pacote</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label>Data Validade</label>
                                <input type="date" name="data_validade" class="form-control" value="<?php echo $produto['data_validade']; ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label>Localização</label>
                                <input type="text" name="localizacao" class="form-control" value="<?php echo htmlspecialchars($produto['localizacao']); ?>" placeholder="Ex: Corredor 1, Prateleira A">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="ativo" <?php echo $produto['status'] == 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                                    <option value="inativo" <?php echo $produto['status'] == 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-end">
                        <a href="listar.php" class="btn btn-secondary">Cancelar</a>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalExcluir">
                            <i class="fas fa-trash"></i> Excluir Produto
                        </button>
                        <button type="submit" class="btn btn-primary">Atualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal de Confirmação de Exclusão -->
    <div class="modal fade" id="modalExcluir" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle text-danger"></i> Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir o produto <strong><?php echo htmlspecialchars($produto['nome']); ?></strong>?</p>
                    <p class="text-danger"><small>Esta ação não pode ser desfeita!</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="excluir" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Sim, Excluir
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/main.js"></script>
</body>
</html>