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
<link rel="stylesheet" href="../css/custom.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="col-md-2 sidebar">
<div class="nav flex-column">
    <a class="nav-link" href="../dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
    <a class="nav-link" href="listar.php"><i class="fas fa-tags"></i> Categorias</a>
    <a class="nav-link" href="cadastrar.php"><i class="fas fa-plus"></i> Nova Categoria</a>
    <a class="nav-link active" href="editar.php?id=<?php echo $id; ?>"><i class="fas fa-edit"></i> Editar</a>
</div>
</div>

<div class="col-md-10 main-content">
<h2><i class="fas fa-edit"></i> Editar Categoria</h2>

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
            <div class="mb-3">
                <label>Nome da Categoria *</label>
                <input type="text" name="nome" class="form-control" value="<?php echo htmlspecialchars($categoria['nome']); ?>" required>
                <small class="text-muted">Ex: Alimentos, Bebidas, Limpeza, etc.</small>
            </div>
            
            <div class="mb-3">
                <label>Descrição</label>
                <textarea name="descricao" class="form-control" rows="4"><?php echo htmlspecialchars($categoria['descricao']); ?></textarea>
                <small class="text-muted">Descrição detalhada da categoria (opcional)</small>
            </div>
            
            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="ativo" value="1" id="ativoSwitch" <?php echo $categoria['ativo'] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="ativoSwitch">
                        Categoria Ativa
                    </label>
                </div>
                <small class="text-muted">Categorias inativas não aparecem na lista de seleção de produtos</small>
            </div>
            
            <?php if($totalProdutos > 0): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <strong>Atenção:</strong> Esta categoria possui <strong><?php echo $totalProdutos; ?> produto(s)</strong> vinculado(s). 
                    Ao desativar a categoria, estes produtos ficarão sem categoria definida.
                </div>
            <?php endif; ?>
            
            <div class="card bg-light mb-3">
                <div class="card-body">
                    <h6><i class="fas fa-chart-line"></i> Estatísticas da Categoria</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <small>ID da Categoria:</small><br>
                            <strong><?php echo $categoria['id']; ?></strong>
                        </div>
                        <div class="col-md-4">
                            <small>Data de Cadastro:</small><br>
                            <strong><?php echo date('d/m/Y H:i', strtotime($categoria['created_at'])); ?></strong>
                        </div>
                        <div class="col-md-4">
                            <small>Produtos Vinculados:</small><br>
                            <strong><?php echo $totalProdutos; ?> produtos</strong>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-end">
                <a href="listar.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Atualizar</button>
            </div>
        </form>
    </div>
</div>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>