

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
                                <i class="fas fa-user-cog me-2 text-info"></i> Usuários
                            </a>
                            <a class="nav-link active bg-info bg-opacity-25 text-info fw-semibold rounded-3" href="cadastrar.php">
                                <i class="fas fa-user-plus me-2"></i> Novo Usuário
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="mb-3">
                    <h2 class="fw-bold text-info fs-4">
                        <i class="fas fa-user-plus me-2"></i> Cadastrar Usuário
                    </h2>
                    <p class="text-secondary small">Adicione um novo usuário ao sistema</p>
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
                        <h6 class="mb-0 text-info"><i class="fas fa-user me-1"></i> Dados do Usuário</h6>
                    </div>
                    <div class="card-body p-3">
                        <form method="POST">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Nome Completo <span class="text-danger">*</span></label>
                                    <input type="text" name="nome" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Email <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                </div>
                            </div>
                            
                            <div class="row g-2 mt-1">
                                <div class="col-md-6">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Senha <span class="text-danger">*</span></label>
                                    <input type="password" name="senha" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" required>
                                    <div class="form-text text-secondary small">Mínimo 6 caracteres</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Confirmar Senha <span class="text-danger">*</span></label>
                                    <input type="password" name="confirmar_senha" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" required>
                                </div>
                            </div>
                            
                            <div class="row g-2 mt-1">
                                <div class="col-md-6">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Nível de Acesso <span class="text-danger">*</span></label>
                                    <select name="nivel_acesso" class="form-select form-select-sm border border-info border-opacity-25 rounded-2" required>
                                        <option value="">Selecione...</option>
                                        <option value="vendedor" <?php echo (isset($_POST['nivel_acesso']) && $_POST['nivel_acesso'] == 'vendedor') ? 'selected' : ''; ?>>Vendedor</option>
                                        <option value="admin" <?php echo (isset($_POST['nivel_acesso']) && $_POST['nivel_acesso'] == 'admin') ? 'selected' : ''; ?>>Administrador</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Vincular a Funcionário</label>
                                    <select name="funcionario_id" class="form-select form-select-sm border border-info border-opacity-25 rounded-2">
                                        <option value="">Não vincular</option>
                                        <?php if(isset($funcionarios) && !empty($funcionarios)): ?>
                                            <?php foreach($funcionarios as $funcionario): ?>
                                                <option value="<?php echo $funcionario['id']; ?>" <?php echo (isset($_POST['funcionario_id']) && $_POST['funcionario_id'] == $funcionario['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($funcionario['nome']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                    <div class="form-text text-secondary small">Vincular automaticamente o login ao funcionário</div>
                                </div>
                            </div>
                            
                            <div class="alert alert-info mt-2 py-2">
                                <i class="fas fa-info-circle me-1"></i> 
                                <small><strong>Informação:</strong> Usuários com nível Administrador têm acesso total ao sistema. Vendedores só podem realizar vendas e visualizar produtos.</small>
                            </div>
                            
                            <hr class="my-3">
                            
                            <div class="d-flex justify-content-end gap-2">
                                <a href="listar.php" class="btn btn-outline-secondary btn-sm rounded-2">
                                    <i class="fas fa-arrow-left me-1"></i> Cancelar
                                </a>
                                <button type="submit" class="btn btn-info text-white btn-sm rounded-2">
                                    <i class="fas fa-save me-1"></i> Cadastrar Usuário
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
