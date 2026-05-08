<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';

$id = $_GET['id'] ?? 0;

// Buscar usuário
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$id]);
$usuario = $stmt->fetch();

if (!$usuario) {
header('Location: listar.php');
exit();
}

// Buscar funcionários ativos para vincular (excluindo o já vinculado)
$funcionarios = $pdo->prepare("
SELECT id, nome FROM funcionarios 
WHERE status = 'ativo' 
AND (id NOT IN (SELECT funcionario_id FROM usuarios WHERE funcionario_id IS NOT NULL AND funcionario_id IS NOT NULL)
OR id = ?)
ORDER BY nome
");
$funcionarios->execute([$usuario['funcionario_id']]);
$funcionarios = $funcionarios->fetchAll();

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$nome = $_POST['nome'];
$email = $_POST['email'];
$nivel_acesso = $_POST['nivel_acesso'];
$funcionario_id = $_POST['funcionario_id'] ?: null;
$ativo = isset($_POST['ativo']) ? 1 : 0;
$senha = $_POST['senha'] ?? '';

try {
    // Verificar se email já existe para outro usuário
    $check = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
    $check->execute([$email, $id]);
    if($check->rowCount() > 0) {
        throw new Exception("Email já cadastrado para outro usuário!");
    }
    
    $dados_antigos = json_encode($usuario);
    
    // Atualizar dados básicos
    $query = "UPDATE usuarios SET nome = ?, email = ?, nivel_acesso = ?, funcionario_id = ?, ativo = ?";
    $params = [$nome, $email, $nivel_acesso, $funcionario_id, $ativo];
    
    // Se senha foi informada, atualizar também
    if(!empty($senha)) {
        if(strlen($senha) < 6) {
            throw new Exception("A senha deve ter no mínimo 6 caracteres!");
        }
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        $query .= ", senha = ?";
        $params[] = $senha_hash;
    }
    
    $query .= " WHERE id = ?";
    $params[] = $id;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    
    // Log
    $dados_novos = json_encode($_POST);
    $log = $pdo->prepare("INSERT INTO logs (usuario_id, acao, tabela_afetada, registro_id, dados_anteriores, dados_novos, ip_address) 
                            VALUES (?, 'editar', 'usuarios', ?, ?, ?, ?)");
    $log->execute([$_SESSION['usuario_id'], $id, $dados_antigos, $dados_novos, $_SERVER['REMOTE_ADDR']]);
    
    $sucesso = "Usuário atualizado com sucesso!";
    
    // Recarregar dados
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    $usuario = $stmt->fetch();
    
} catch(Exception $e) {
    $erro = $e->getMessage();
}
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Editar Usuário - Supermercado</title>
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
        <a class="nav-link" href="cadastrar.php"><i class="fas fa-user-plus"></i> Novo Usuário</a>
        <a class="nav-link active" href="editar.php?id=<?php echo $id; ?>"><i class="fas fa-edit"></i> Editar Usuário</a>
    </div>
</div>

<div class="col-md-10 main-content">
    <h2><i class="fas fa-edit"></i> Editar Usuário</h2>
    
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
                            <input type="text" name="nome" class="form-control" value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label>Email *</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label>Nova Senha</label>
                            <input type="password" name="senha" class="form-control" placeholder="Deixe em branco para manter a atual">
                            <small class="text-muted">Mínimo 6 caracteres. Preencha apenas se quiser alterar</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label>Nível de Acesso *</label>
                            <select name="nivel_acesso" class="form-control" required>
                                <option value="vendedor" <?php echo $usuario['nivel_acesso'] == 'vendedor' ? 'selected' : ''; ?>>Vendedor</option>
                                <option value="admin" <?php echo $usuario['nivel_acesso'] == 'admin' ? 'selected' : ''; ?>>Administrador</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label>Vincular a Funcionário</label>
                            <select name="funcionario_id" class="form-control">
                                <option value="">Não vincular</option>
                                <?php foreach($funcionarios as $funcionario): ?>
                                    <option value="<?php echo $funcionario['id']; ?>" <?php echo $usuario['funcionario_id'] == $funcionario['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($funcionario['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Vincular o login a um funcionário existente</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label>Status</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="ativo" value="1" id="ativoSwitch" <?php echo $usuario['ativo'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="ativoSwitch">
                                    <?php echo $usuario['ativo'] ? 'Usuário Ativo' : 'Usuário Inativo'; ?>
                                </label>
                            </div>
                            <small class="text-muted">Usuários inativos não podem fazer login</small>
                        </div>
                    </div>
                </div>
                
                <?php if($usuario['id'] == $_SESSION['usuario_id']): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> 
                        Você está editando seu próprio usuário. Cuidado ao alterar seu nível de acesso ou status!
                    </div>
                <?php endif; ?>
                
                <div class="text-end">
                    <a href="listar.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Atualizar</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card mt-3">
        <div class="card-header bg-info text-white">
            <h6><i class="fas fa-info-circle"></i> Informações Adicionais</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <small><strong>ID do Usuário:</strong></small><br>
                    <span><?php echo $usuario['id']; ?></span>
                </div>
                <div class="col-md-4">
                    <small><strong>Data de Cadastro:</strong></small><br>
                    <span><?php echo date('d/m/Y H:i:s', strtotime($usuario['created_at'])); ?></span>
                </div>
                <div class="col-md-4">
                    <small><strong>Última Atualização:</strong></small><br>
                    <span><?php echo date('d/m/Y H:i:s', strtotime($usuario['created_at'])); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Atualizar texto do switch
    document.getElementById('ativoSwitch').addEventListener('change', function() {
        const label = document.querySelector('label[for="ativoSwitch"]');
        if(this.checked) {
            label.textContent = 'Usuário Ativo';
        } else {
            label.textContent = 'Usuário Inativo';
        }
    });
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>