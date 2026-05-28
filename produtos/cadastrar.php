

<?php

require_once '../config/database.php';
require_once '../includes/auth_check.php';

$categorias = $pdo->query("SELECT * FROM categorias WHERE ativo = 1 ORDER BY nome")->fetchAll();
$fornecedores = $pdo->query("SELECT * FROM fornecedores WHERE ativo = 1 ORDER BY nome_fantasia")->fetchAll();

$erro = '';
$sucesso = '';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    
    try {
        $pdo->beginTransaction();

        // Inserir produto
        $stmt = $pdo->prepare("
            INSERT INTO produtos 
            (codigo_barras, nome, descricao, categoria_id, fornecedor_id, preco_compra, preco_venda, quantidade, quantidade_minima, unidade_medida, data_validade, localizacao) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $codigo_barras, 
            $nome, 
            $descricao, 
            $categoria_id, 
            $fornecedor_id, 
            $preco_compra, 
            $preco_venda, 
            $quantidade, 
            $quantidade_minima, 
            $unidade_medida, 
            $data_validade, 
            $localizacao
        ]);

        // ID do produto
        $produto_id = $pdo->lastInsertId();

        // Registrar movimentação inicial
        if ($quantidade > 0) {
            $stmt = $pdo->prepare("
                INSERT INTO movimentacoes_estoque 
                (produto_id, tipo, quantidade, quantidade_antes, quantidade_depois, motivo, usuario_id)
                VALUES (?, 'entrada', ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $produto_id,
                $quantidade,
                0, // antes
                $quantidade, // depois
                'Cadastro inicial do produto',
                $_SESSION['usuario_id']
            ]);
        }

        // Log
        $log = $pdo->prepare("
            INSERT INTO logs (usuario_id, acao, tabela_afetada, registro_id, ip_address) 
            VALUES (?, 'cadastrar', 'produtos', ?, ?)
        ");
        $log->execute([
            $_SESSION['usuario_id'], 
            $produto_id, 
            $_SERVER['REMOTE_ADDR']
        ]);

        $pdo->commit();
        $sucesso = "Produto cadastrado com sucesso!";

    } catch(PDOException $e) {
        $pdo->rollBack();
        $erro = "Erro ao cadastrar: " . $e->getMessage();
    }
}



?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Produto - Supermercado</title>
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
                            <a class="nav-link active bg-info bg-opacity-25 text-info fw-semibold rounded-3" href="cadastrar.php">
                                <i class="fas fa-plus me-2"></i> Novo Produto
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="mb-3">
                    <h2 class="fw-bold text-info fs-4">
                        <i class="fas fa-plus me-2"></i> Cadastrar Produto
                    </h2>
                    <p class="text-secondary small">Adicione um novo produto ao catálogo</p>
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
                        <h6 class="mb-0 text-info"><i class="fas fa-box me-1"></i> Informações do Produto</h6>
                    </div>
                    <div class="card-body p-3">
                        <form method="POST">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Código de Barras</label>
                                    <input type="text" name="codigo_barras" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" value="<?php echo isset($_POST['codigo_barras']) ? htmlspecialchars($_POST['codigo_barras']) : ''; ?>">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Nome do Produto <span class="text-danger">*</span></label>
                                    <input type="text" name="nome" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>" required>
                                </div>
                            </div>
                            
                            <div class="mt-2">
                                <label class="form-label text-secondary small fw-semibold mb-0">Descrição</label>
                                <textarea name="descricao" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" rows="2"><?php echo isset($_POST['descricao']) ? htmlspecialchars($_POST['descricao']) : ''; ?></textarea>
                            </div>
                            
                            <div class="row g-2 mt-1">
                                <div class="col-md-6">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Categoria <span class="text-danger">*</span></label>
                                    <select name="categoria_id" class="form-select form-select-sm border border-info border-opacity-25 rounded-2" required>
                                        <option value="">Selecione...</option>
                                        <?php if(isset($categorias)): ?>
                                            <?php foreach($categorias as $categoria): ?>
                                                <option value="<?php echo $categoria['id']; ?>" <?php echo (isset($_POST['categoria_id']) && $_POST['categoria_id'] == $categoria['id']) ? 'selected' : ''; ?>>
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
                                                <option value="<?php echo $fornecedor['id']; ?>" <?php echo (isset($_POST['fornecedor_id']) && $_POST['fornecedor_id'] == $fornecedor['id']) ? 'selected' : ''; ?>>
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
                                        <input type="text" name="preco_compra" class="form-control form-control-sm border-info border-opacity-25 money" value="<?php echo isset($_POST['preco_compra']) ? $_POST['preco_compra'] : '0,00'; ?>" placeholder="0,00">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Preço Venda <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-info border-opacity-25">R$</span>
                                        <input type="text" name="preco_venda" class="form-control form-control-sm border-info border-opacity-25 money" value="<?php echo isset($_POST['preco_venda']) ? $_POST['preco_venda'] : '0,00'; ?>" required placeholder="0,00">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Quantidade <span class="text-danger">*</span></label>
                                    <input type="number" name="quantidade" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" value="<?php echo isset($_POST['quantidade']) ? $_POST['quantidade'] : '0'; ?>" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Estoque Mínimo</label>
                                    <input type="number" name="quantidade_minima" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" value="<?php echo isset($_POST['quantidade_minima']) ? $_POST['quantidade_minima'] : '5'; ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Unidade</label>
                                    <select name="unidade_medida" class="form-select form-select-sm border border-info border-opacity-25 rounded-2">
                                        <option value="UN" <?php echo (isset($_POST['unidade_medida']) && $_POST['unidade_medida'] == 'UN') ? 'selected' : ''; ?>>Unidade</option>
                                        <option value="KG" <?php echo (isset($_POST['unidade_medida']) && $_POST['unidade_medida'] == 'KG') ? 'selected' : ''; ?>>Quilograma</option>
                                        <option value="L" <?php echo (isset($_POST['unidade_medida']) && $_POST['unidade_medida'] == 'L') ? 'selected' : ''; ?>>Litro</option>
                                        <option value="PCT" <?php echo (isset($_POST['unidade_medida']) && $_POST['unidade_medida'] == 'PCT') ? 'selected' : ''; ?>>Pacote</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row g-2 mt-1">
                                <div class="col-md-6">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Data Validade</label>
                                    <input type="date" name="data_validade" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" value="<?php echo isset($_POST['data_validade']) ? $_POST['data_validade'] : ''; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Localização</label>
                                    <input type="text" name="localizacao" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" value="<?php echo isset($_POST['localizacao']) ? htmlspecialchars($_POST['localizacao']) : ''; ?>" placeholder="Ex: Corredor 1, Prateleira A">
                                </div>
                            </div>
                            
                            <hr class="my-3">
                            
                            <div class="d-flex justify-content-end gap-2">
                                <a href="listar.php" class="btn btn-outline-secondary btn-sm rounded-2">
                                    <i class="fas fa-times me-1"></i> Cancelar
                                </a>
                                <button type="submit" class="btn btn-info text-white btn-sm rounded-2">
                                    <i class="fas fa-save me-1"></i> Salvar Produto
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Máscara simples para campos de dinheiro
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
