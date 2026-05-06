<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';

$id = $_GET['id'] ?? 0;

// Buscar fornecedor
$stmt = $pdo->prepare("SELECT * FROM fornecedores WHERE id = ?");
$stmt->execute([$id]);
$fornecedor = $stmt->fetch();

if (!$fornecedor) {
header('Location: listar.php?msg=Fornecedor não encontrado&tipo=danger');
exit();
}

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$razao_social = $_POST['razao_social'];
$nome_fantasia = $_POST['nome_fantasia'];
$cnpj = preg_replace('/[^0-9]/', '', $_POST['cnpj']);
$ie = $_POST['ie'];
$telefone = preg_replace('/[^0-9]/', '', $_POST['telefone']);
$email = $_POST['email'];
$endereco = $_POST['endereco'];
$contato_nome = $_POST['contato_nome'];
$contato_telefone = preg_replace('/[^0-9]/', '', $_POST['contato_telefone']);
$ativo = isset($_POST['ativo']) ? 1 : 0;

// Formatar CNPJ
$cnpj_formatado = substr($cnpj, 0, 2) . '.' . substr($cnpj, 2, 3) . '.' . substr($cnpj, 5, 3) . '/' . substr($cnpj, 8, 4) . '-' . substr($cnpj, 12, 2);

// Formatar telefone
if(strlen($telefone) == 11) {
    $telefone = '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7);
} elseif(strlen($telefone) == 10) {
    $telefone = '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 4) . '-' . substr($telefone, 6);
}

// Formatar telefone contato
if(strlen($contato_telefone) == 11) {
    $contato_telefone = '(' . substr($contato_telefone, 0, 2) . ') ' . substr($contato_telefone, 2, 5) . '-' . substr($contato_telefone, 7);
} elseif(strlen($contato_telefone) == 10) {
    $contato_telefone = '(' . substr($contato_telefone, 0, 2) . ') ' . substr($contato_telefone, 2, 4) . '-' . substr($contato_telefone, 6);
}

try {
    // Verificar se CNPJ já existe para outro fornecedor
    $check = $pdo->prepare("SELECT id FROM fornecedores WHERE cnpj = ? AND id != ?");
    $check->execute([$cnpj_formatado, $id]);
    if($check->rowCount() > 0) {
        throw new Exception("CNPJ já cadastrado para outro fornecedor!");
    }
    
    $dados_antigos = json_encode($fornecedor);
    
    $stmt = $pdo->prepare("
        UPDATE fornecedores SET 
        razao_social = ?, nome_fantasia = ?, cnpj = ?, ie = ?, telefone = ?, 
        email = ?, endereco = ?, contato_nome = ?, contato_telefone = ?, ativo = ?
        WHERE id = ?
    ");
    
    $stmt->execute([$razao_social, $nome_fantasia, $cnpj_formatado, $ie, $telefone, $email, $endereco, $contato_nome, $contato_telefone, $ativo, $id]);
    
    // Log
    $dados_novos = json_encode($_POST);
    $log = $pdo->prepare("INSERT INTO logs (usuario_id, acao, tabela_afetada, registro_id, dados_anteriores, dados_novos, ip_address) 
                          VALUES (?, 'editar', 'fornecedores', ?, ?, ?, ?)");
    $log->execute([$_SESSION['usuario_id'], $id, $dados_antigos, $dados_novos, $_SERVER['REMOTE_ADDR']]);
    
    $sucesso = "Fornecedor atualizado com sucesso!";
    
    // Recarregar dados
    $stmt = $pdo->prepare("SELECT * FROM fornecedores WHERE id = ?");
    $stmt->execute([$id]);
    $fornecedor = $stmt->fetch();
    
} catch(Exception $e) {
    $erro = $e->getMessage();
}
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Editar Fornecedor - Supermercado</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="../css/custom.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="col-md-2 sidebar">
    <div class="nav flex-column">
        <a class="nav-link" href="../dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a class="nav-link" href="listar.php"><i class="fas fa-truck"></i> Fornecedores</a>
        <a class="nav-link" href="cadastrar.php"><i class="fas fa-plus"></i> Novo Fornecedor</a>
        <a class="nav-link active" href="#"><i class="fas fa-edit"></i> Editar Fornecedor</a>
    </div>
</div>

<div class="col-md-10 main-content">
    <h2><i class="fas fa-edit"></i> Editar Fornecedor</h2>
    
    <?php if($sucesso): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo $sucesso; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if($erro): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $erro; ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label>Razão Social *</label>
                            <input type="text" name="razao_social" class="form-control" value="<?php echo htmlspecialchars($fornecedor['razao_social']); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label>Nome Fantasia</label>
                            <input type="text" name="nome_fantasia" class="form-control" value="<?php echo htmlspecialchars($fornecedor['nome_fantasia']); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label>CNPJ *</label>
                            <input type="text" name="cnpj" class="form-control cnpj" value="<?php echo $fornecedor['cnpj']; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label>Inscrição Estadual</label>
                            <input type="text" name="ie" class="form-control" value="<?php echo htmlspecialchars($fornecedor['ie']); ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label>Telefone</label>
                            <input type="text" name="telefone" class="form-control telefone" value="<?php echo htmlspecialchars($fornecedor['telefone']); ?>" placeholder="(00) 00000-0000">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($fornecedor['email']); ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label>Status</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="ativo" id="ativo" <?php echo $fornecedor['ativo'] == 1 ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="ativo">Ativo</label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label>Endereço</label>
                    <textarea name="endereco" class="form-control" rows="2"><?php echo htmlspecialchars($fornecedor['endereco']); ?></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label>Nome do Contato</label>
                            <input type="text" name="contato_nome" class="form-control" value="<?php echo htmlspecialchars($fornecedor['contato_nome']); ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label>Telefone do Contato</label>
                            <input type="text" name="contato_telefone" class="form-control telefone" value="<?php echo htmlspecialchars($fornecedor['contato_telefone']); ?>" placeholder="(00) 00000-0000">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label>Data Cadastro</label>
                            <input type="text" class="form-control" value="<?php echo date('d/m/Y H:i', strtotime($fornecedor['created_at'])); ?>" disabled>
                            <small class="text-muted">Data de criação do cadastro</small>
                        </div>
                    </div>
                </div>
                
                <div class="text-end">
                    <a href="listar.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Atualizar
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Produtos deste Fornecedor -->
    <div class="card mt-4">
        <div class="card-header bg-info text-white">
            <h5><i class="fas fa-box"></i> Produtos deste Fornecedor</h5>
        </div>
        <div class="card-body">
            <?php
            $produtos = $pdo->prepare("
                SELECT p.*, c.nome as categoria_nome
                FROM produtos p
                LEFT JOIN categorias c ON p.categoria_id = c.id
                WHERE p.fornecedor_id = ?
                ORDER BY p.nome
            ");
            $produtos->execute([$id]);
            $produtos_fornecedor = $produtos->fetchAll();
            ?>
            
            <?php if(count($produtos_fornecedor) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Categoria</th>
                                <th>Preço Venda</th>
                                <th>Estoque</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($produtos_fornecedor as $produto): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($produto['nome']); ?>
                                    <td><?php echo htmlspecialchars($produto['categoria_nome']); ?>
                                    <td>R$ <?php echo number_format($produto['preco_venda'], 2, ',', '.'); ?>
                                    <td class="<?php echo $produto['quantidade'] <= $produto['quantidade_minima'] ? 'text-danger' : ''; ?>">
                                        <?php echo $produto['quantidade']; ?>
                                    
                                    <td>
                                        <span class="badge bg-<?php echo $produto['status'] == 'ativo' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($produto['status']); ?>
                                        </span>
                                    
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    20
                </div>
            <?php else: ?>
                <p class="text-muted text-center">Nenhum produto cadastrado para este fornecedor.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script src="../js/main.js"></script>
</body>
</html> 