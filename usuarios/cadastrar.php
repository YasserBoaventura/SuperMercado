<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Buscar funcionários ativos para vincular
$funcionarios = $pdo->query("
SELECT id, nome FROM funcionarios 
WHERE status = 'ativo' 
AND id NOT IN (SELECT funcionario_id FROM usuarios WHERE funcionario_id IS NOT NULL)
ORDER BY nome
")->fetchAll();

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$nome = $_POST['nome'];
$email = $_POST['email'];
$senha = $_POST['senha'];
$confirmar_senha = $_POST['confirmar_senha'];
$nivel_acesso = $_POST['nivel_acesso'];
$funcionario_id = $_POST['funcionario_id'] ?: null;

// Validações
if($senha != $confirmar_senha) {
    $erro = "As senhas não coincidem!";
} elseif(strlen($senha) < 6) {
    $erro = "A senha deve ter no mínimo 6 caracteres!";
} else {
    try {
        // Verificar se email já existe
        $check = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $check->execute([$email]);
        if($check->rowCount() > 0) {
            throw new Exception("Email já cadastrado no sistema!");
        }
        
        // Criptografar senha
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nome, email, senha, nivel_acesso, funcionario_id)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$nome, $email, $senha_hash, $nivel_acesso, $funcionario_id]);
        $usuario_id = $pdo->lastInsertId();
        
        // Log
        $log = $pdo->prepare("INSERT INTO logs (usuario_id, acao, tabela_afetada, registro_id, ip_address) 
                                VALUES (?, 'cadastrar', 'usuarios', ?, ?)");
        $log->execute([$_SESSION['usuario_id'], $usuario_id, $_SERVER['REMOTE_ADDR']]);
        
        $sucesso = "Usuário cadastrado com sucesso!";
        
        // Limpar formulário
        $_POST = [];
        
        // Recarregar lista de funcionários disponíveis
        $funcionarios = $pdo->query("
            SELECT id, nome FROM funcionarios 
            WHERE status = 'ativo' 
            AND id NOT IN (SELECT funcionario_id FROM usuarios WHERE funcionario_id IS NOT NULL)
            ORDER BY nome
        ")->fetchAll();
        
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
<title>Cadastrar Usuário - Supermercado</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="../css/custom.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="col-md-2 sidebar">
    <div class="nav flex-column">
        <a class="nav-link" href="../dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a class="nav-link" href="listar.php"><i class="fas fa-user-cog"></i> Usuários</a>
        <a class="nav-link active" href="cadastrar.php"><i class="fas fa-user-plus"></i> Novo Usuário</a>
    </div>
</div>

<div class="col-md-10 main-content">
    <h2><i class="fas fa-user-plus"></i> Cadastrar Usuário</h2>
    
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
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label>Nome Completo *</label>
                            <input type="text" name="nome" class="form-control" value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label>Email *</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label>Senha *</label>
                            <input type="password" name="senha" class="form-control" required>
                            <small class="text-muted">Mínimo 6 caracteres</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label>Confirmar Senha *</label>
                            <input type="password" name="confirmar_senha" class="form-control" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label>Nível de Acesso *</label>
                            <select name="nivel_acesso" class="form-control" required>
                                <option value="">Selecione...</option>
                                <option value="vendedor" <?php echo ($_POST['nivel_acesso'] ?? '') == 'vendedor' ? 'selected' : ''; ?>>Vendedor</option>
                                <option value="admin" <?php echo ($_POST['nivel_acesso'] ?? '') == 'admin' ? 'selected' : ''; ?>>Administrador</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label>Vincular a Funcionário</label>
                            <select name="funcionario_id" class="form-control">
                                <option value="">Não vincular</option>
                                <?php foreach($funcionarios as $funcionario): ?>
                                    <option value="<?php echo $funcionario['id']; ?>" <?php echo ($_POST['funcionario_id'] ?? '') == $funcionario['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($funcionario['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Vincular automaticamente o login ao funcionário</small>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Informação:</strong> Usuários com nível Administrador têm acesso total ao sistema. 
                    Vendedores só podem realizar vendas e visualizar produtos.
                </div>
                
                <div class="text-end">
                    <a href="listar.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-success">Cadastrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>