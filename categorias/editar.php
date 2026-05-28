
<?php

require_once '../config/database.php';
require_once '../includes/auth_check.php';

$id = $_GET['id'] ?? 0;

// Buscar categoria
$stmt = $pdo->prepare("SELECT * FROM categorias WHERE id = ?");
$stmt->execute([$id]);
$categoria = $stmt->fetch();

if (!$categoria) {
    header('Location: listar.php');
    exit();
}

// Buscar quantidade de produtos
$stmtProdutos = $pdo->prepare("SELECT COUNT(*) FROM produtos WHERE categoria_id = ?");
$stmtProdutos->execute([$id]);
$totalProdutos = $stmtProdutos->fetchColumn();

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $descricao = trim($_POST['descricao']);
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    if(empty($nome)) {
        $erro = "O nome da categoria é obrigatório!";
    } else {
        try {
            // Verificar se nome já existe para outra categoria
            $check = $pdo->prepare("SELECT id FROM categorias WHERE nome = ? AND id != ?");
            $check->execute([$nome, $id]);
            if($check->rowCount() > 0) {
                throw new Exception("Já existe outra categoria com este nome!");
            }
            
            $dados_antigos = json_encode($categoria);
            
            $stmt = $pdo->prepare("UPDATE categorias SET nome = ?, descricao = ?, ativo = ? WHERE id = ?");
            $stmt->execute([$nome, $descricao, $ativo, $id]);
            
            // Log
            $dados_novos = json_encode($_POST);
            $log = $pdo->prepare("INSERT INTO logs (usuario_id, acao, tabela_afetada, registro_id, dados_anteriores, dados_novos, ip_address) 
                                  VALUES (?, 'editar', 'categorias', ?, ?, ?, ?)");
            $log->execute([$_SESSION['usuario_id'], $id, $dados_antigos, $dados_novos, $_SERVER['REMOTE_ADDR']]);
            
            $sucesso = "Categoria atualizada com sucesso!";
            
            // Recarregar dados
            $stmt = $pdo->prepare("SELECT * FROM categorias WHERE id = ?");
            $stmt->execute([$id]);
            $categoria = $stmt->fetch();
            
        } catch(Exception $e) {
            $erro = $e->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Categoria - Supermercado</title>
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
                                <i class="fas fa-tags me-2 text-info"></i> Categorias
                            </a>
                            <a class="nav-link text-secondary rounded-3" href="cadastrar.php">
                                <i class="fas fa-plus me-2 text-info"></i> Nova Categoria
                            </a>
                            <a class="nav-link active bg-info bg-opacity-25 text-info fw-semibold rounded-3" href="#">
                                <i class="fas fa-edit me-2"></i> Editar Categoria
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="mb-3">
                    <h2 class="fw-bold text-info fs-4">
                        <i class="fas fa-edit me-2"></i> Editar Categoria
                    </h2>
                    <p class="text-secondary small">Atualize os dados da categoria</p>
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
                        <h6 class="mb-0 text-info"><i class="fas fa-pencil-alt me-1"></i> Informações da Categoria</h6>
                    </div>
                    <div class="card-body p-3">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label text-secondary small fw-semibold mb-0">Nome da Categoria <span class="text-danger">*</span></label>
                                <input type="text" name="nome" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" value="<?php echo isset($categoria['nome']) ? htmlspecialchars($categoria['nome']) : ''; ?>" required>
                                <div class="form-text text-secondary small">Ex: Alimentos, Bebidas, Limpeza, etc.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label text-secondary small fw-semibold mb-0">Descrição</label>
                                <textarea name="descricao" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" rows="3"><?php echo isset($categoria['descricao']) ? htmlspecialchars($categoria['descricao']) : ''; ?></textarea>
                                <div class="form-text text-secondary small">Descrição detalhada da categoria (opcional)</div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="ativo" value="1" id="ativoSwitch" <?php echo (isset($categoria['ativo']) && $categoria['ativo']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label text-secondary small" for="ativoSwitch">
                                        Categoria Ativa
                                    </label>
                                </div>
                                <div class="form-text text-secondary small">Categorias inativas não aparecem na lista de seleção de produtos</div>
                            </div>
                            
                            <?php if(isset($totalProdutos) && $totalProdutos > 0): ?>
                                <div class="alert alert-warning py-2 small">
                                    <i class="fas fa-exclamation-triangle me-1"></i> 
                                    <strong>Atenção:</strong> Esta categoria possui <strong><?php echo $totalProdutos; ?> produto(s)</strong> vinculado(s). 
                                    Ao desativar a categoria, estes produtos ficarão sem categoria definida.
                                </div>
                            <?php endif; ?>
                            
                            <!-- Estatísticas -->
                            <div class="card bg-light border-0 rounded-2 mb-3">
                                <div class="card-body p-2">
                                    <h6 class="mb-2 text-secondary small fw-semibold"><i class="fas fa-chart-line me-1 text-info"></i> Estatísticas da Categoria</h6>
                                    <div class="row small">
                                        <div class="col-md-4">
                                            <span class="text-secondary">ID:</span><br>
                                            <code class="text-info"><?php echo isset($categoria['id']) ? $categoria['id'] : '-'; ?></code>
                                        </div>
                                        <div class="col-md-4">
                                            <span class="text-secondary">Data Cadastro:</span><br>
                                            <span><?php echo isset($categoria['created_at']) ? date('d/m/Y H:i', strtotime($categoria['created_at'])) : '-'; ?></span>
                                        </div>
                                        <div class="col-md-4">
                                            <span class="text-secondary">Produtos Vinculados:</span><br>
                                            <span class="fw-semibold text-info"><?php echo isset($totalProdutos) ? $totalProdutos : 0; ?> produtos</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <hr class="my-3">
                            
                            <div class="d-flex justify-content-end gap-2">
                                <a href="listar.php" class="btn btn-outline-secondary btn-sm rounded-2">
                                    <i class="fas fa-arrow-left me-1"></i> Cancelar
                                </a>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
