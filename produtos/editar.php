

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
                            <a class="nav-link text-secondary rounded-3" href="listar.php">
                                <i class="fas fa-box me-2 text-info"></i> Produtos
                            </a>
                            <a class="nav-link text-secondary rounded-3" href="cadastrar.php">
                                <i class="fas fa-plus me-2 text-info"></i> Novo Produto
                            </a>
                            <a class="nav-link active bg-info bg-opacity-25 text-info fw-semibold rounded-3" href="editar.php?id=<?php echo isset($id) ? $id : ''; ?>">
                                <i class="fas fa-edit me-2"></i> Editar Produto
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="mb-3">
                    <h2 class="fw-bold text-info fs-4">
                        <i class="fas fa-edit me-2"></i> Editar Produto
                    </h2>
                    <p class="text-secondary small">Altere as informações do produto</p>
                </div>
                
                <!-- Mensagens de feedback -->
                <?php if(isset($sucesso) && $sucesso): ?>
                    <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
                        <i class="fas fa-check-circle me-2"></i> <?php echo $sucesso; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if(isset($erro) && $erro): ?>
                    <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $erro; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Formulário -->
                <div class="card border border-info border-opacity-25 rounded-3 shadow-sm">
                    <div class="card-header bg-info bg-opacity-10 border-0 rounded-top-3 py-2">
                        <h6 class="mb-0 text-info"><i class="fas fa-pencil-alt me-1"></i> Informações do Produto</h6>
                    </div>
                    <div class="card-body p-3">
                        <form method="POST">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Código de Barras</label>
                                    <input type="text" name="codigo_barras" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" value="<?php echo isset($produto['codigo_barras']) ? htmlspecialchars($produto['codigo_barras']) : ''; ?>">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Nome do Produto <span class="text-danger">*</span></label>
                                    <input type="text" name="nome" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" value="<?php echo isset($produto['nome']) ? htmlspecialchars($produto['nome']) : ''; ?>" required>
                                </div>
                            </div>
                            
                            <div class="mt-2">
                                <label class="form-label text-secondary small fw-semibold mb-0">Descrição</label>
                                <textarea name="descricao" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" rows="2"><?php echo isset($produto['descricao']) ? htmlspecialchars($produto['descricao']) : ''; ?></textarea>
                            </div>
                            
                            <div class="row g-2 mt-1">
                                <div class="col-md-6">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Categoria <span class="text-danger">*</span></label>
                                    <select name="categoria_id" class="form-select form-select-sm border border-info border-opacity-25 rounded-2" required>
                                        <option value="">Selecione...</option>
                                        <?php if(isset($categorias)): ?>
                                            <?php foreach($categorias as $categoria): ?>
                                                <option value="<?php echo $categoria['id']; ?>" <?php echo (isset($produto['categoria_id']) && $produto['categoria_id'] == $categoria['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($categoria['nome']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Fornecedor</label>
                                    <select name="fornecedor_id" class="form-select form-select-sm border border-info border-opacity-25 rounded-2">
                                        <option value="">Selecione...</option>
                                        <?php if(isset($fornecedores)): ?>
                                            <?php foreach($fornecedores as $fornecedor): ?>
                                                <option value="<?php echo $fornecedor['id']; ?>" <?php echo (isset($produto['fornecedor_id']) && $produto['fornecedor_id'] == $fornecedor['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($fornecedor['nome_fantasia']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row g-2 mt-1">
                                <div class="col-md-3">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Preço Compra</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-info border-opacity-25">R$</span>
                                        <input type="text" name="preco_compra" class="form-control form-control-sm border-info border-opacity-25 money" value="<?php echo isset($produto['preco_compra']) ? number_format($produto['preco_compra'], 2, ',', '.') : '0,00'; ?>" placeholder="0,00">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Preço Venda <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-info border-opacity-25">R$</span>
                                        <input type="text" name="preco_venda" class="form-control form-control-sm border-info border-opacity-25 money" value="<?php echo isset($produto['preco_venda']) ? number_format($produto['preco_venda'], 2, ',', '.') : '0,00'; ?>" required placeholder="0,00">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Quantidade <span class="text-danger">*</span></label>
                                    <input type="number" name="quantidade" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" value="<?php echo isset($produto['quantidade']) ? $produto['quantidade'] : 0; ?>" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Estoque Mínimo</label>
                                    <input type="number" name="quantidade_minima" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" value="<?php echo isset($produto['quantidade_minima']) ? $produto['quantidade_minima'] : 0; ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Unidade</label>
                                    <select name="unidade_medida" class="form-select form-select-sm border border-info border-opacity-25 rounded-2">
                                        <option value="UN" <?php echo (isset($produto['unidade_medida']) && $produto['unidade_medida'] == 'UN') ? 'selected' : ''; ?>>Unidade</option>
                                        <option value="KG" <?php echo (isset($produto['unidade_medida']) && $produto['unidade_medida'] == 'KG') ? 'selected' : ''; ?>>Quilograma</option>
                                        <option value="L" <?php echo (isset($produto['unidade_medida']) && $produto['unidade_medida'] == 'L') ? 'selected' : ''; ?>>Litro</option>
                                        <option value="PCT" <?php echo (isset($produto['unidade_medida']) && $produto['unidade_medida'] == 'PCT') ? 'selected' : ''; ?>>Pacote</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row g-2 mt-1">
                                <div class="col-md-4">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Data Validade</label>
                                    <input type="date" name="data_validade" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" value="<?php echo isset($produto['data_validade']) ? $produto['data_validade'] : ''; ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Localização</label>
                                    <input type="text" name="localizacao" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" value="<?php echo isset($produto['localizacao']) ? htmlspecialchars($produto['localizacao']) : ''; ?>" placeholder="Ex: Corredor 1">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Status</label>
                                    <select name="status" class="form-select form-select-sm border border-info border-opacity-25 rounded-2">
                                        <option value="ativo" <?php echo (isset($produto['status']) && $produto['status'] == 'ativo') ? 'selected' : ''; ?>>Ativo</option>
                                        <option value="inativo" <?php echo (isset($produto['status']) && $produto['status'] == 'inativo') ? 'selected' : ''; ?>>Inativo</option>
                                    </select>
                                </div>
                            </div>
                            
                            <hr class="my-3">
                            
                            <div class="d-flex justify-content-end gap-2">
                                <a href="listar.php" class="btn btn-outline-secondary btn-sm rounded-2">
                                    <i class="fas fa-times me-1"></i> Cancelar
                                </a>
                                <button type="button" class="btn btn-outline-danger btn-sm rounded-2" data-bs-toggle="modal" data-bs-target="#modalExcluir">
                                    <i class="fas fa-trash-alt me-1"></i> Excluir
                                </button>
                                <button type="submit" class="btn btn-info text-white btn-sm rounded-2">
                                    <i class="fas fa-save me-1"></i> Atualizar
                                </button>
                            </div>
                        </form>
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
    
    <!-- Modal de Confirmação de Exclusão -->
    <div class="modal fade" id="modalExcluir" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border border-info border-opacity-25">
                <div class="modal-header bg-info bg-opacity-25 border-0 rounded-top-4 py-2">
                    <h6 class="modal-title text-info">
                        <i class="fas fa-exclamation-triangle me-2"></i> Confirmar Exclusão
                    </h6>
                    <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-3">
                    <p class="mb-2 small">Tem certeza que deseja excluir o produto <strong class="text-info"><?php echo isset($produto['nome']) ? htmlspecialchars($produto['nome']) : ''; ?></strong>?</p>
                    <p class="text-danger small mb-0"><i class="fas fa-ban me-1"></i> Esta ação não pode ser desfeita!</p>
                </div>
                <div class="modal-footer bg-light rounded-bottom-4 py-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-2" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="excluir" class="btn btn-danger btn-sm rounded-2">
                            <i class="fas fa-trash-alt me-1"></i> Sim, Excluir
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simples máscara para campos de dinheiro
        $(document).ready(function() {
            $('.money').on('input', function() {
                let value = this.value.replace(/[^0-9,]/g, '');
                if (value.indexOf(',') === -1) {
                    value = value.replace(/([0-9]+)([0-9]{2})$/, '$1,$2');
                }
                this.value = value;
            });
        });
    </script>
</body>
</html>
