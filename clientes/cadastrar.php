<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf']);
    $rg = $_POST['rg'];
    $data_nascimento = $_POST['data_nascimento'];
    $telefone = preg_replace('/[^0-9]/', '', $_POST['telefone']);
    $email = $_POST['email'];
    $endereco = $_POST['endereco'];
    $pontos = $_POST['pontos'] ?? 0;
    
    // Formatar telefone
    if(strlen($telefone) == 11) {
        $telefone = '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7);
    } elseif(strlen($telefone) == 10) {
        $telefone = '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 4) . '-' . substr($telefone, 6);
    }
    
    try {
        // Verificar se CPF já existe
        $check = $pdo->prepare("SELECT id FROM clientes WHERE cpf = ? AND ativo = 1");
        $check->execute([$cpf]);
        if($check->rowCount() > 0) {
            throw new Exception("CPF já cadastrado!");
        }
        
        // Verificar se email já existe
        if($email) {
            $check = $pdo->prepare("SELECT id FROM clientes WHERE email = ? AND ativo = 1");
            $check->execute([$email]);
            if($check->rowCount() > 0) {
                throw new Exception("Email já cadastrado!");
            }
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO clientes (nome, cpf, rg, data_nascimento, telefone, email, endereco, pontos)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$nome, $cpf, $rg, $data_nascimento, $telefone, $email, $endereco, $pontos]);
        $cliente_id = $pdo->lastInsertId();
        
        // Log
        $log = $pdo->prepare("INSERT INTO logs (usuario_id, acao, tabela_afetada, registro_id, ip_address) 
                              VALUES (?, 'cadastrar', 'clientes', ?, ?)");
        $log->execute([$_SESSION['usuario_id'], $cliente_id, $_SERVER['REMOTE_ADDR']]);
        
        $sucesso = "Cliente cadastrado com sucesso!";
        
        // Limpar formulário
        $_POST = [];
    } catch(Exception $e) {
        $erro = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Cliente - Supermercado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/custom.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="col-md-2 sidebar">
        <div class="nav flex-column">
            <a class="nav-link" href="../dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a class="nav-link" href="listar.php"><i class="fas fa-users"></i> Clientes</a>
            <a class="nav-link active" href="cadastrar.php"><i class="fas fa-user-plus"></i> Novo Cliente</a>
        </div>
    </div>
    
    <div class="col-md-10 main-content">
        <h2><i class="fas fa-user-plus"></i> Cadastrar Cliente</h2>
        
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
                <form method="POST" id="formCliente">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label>Nome Completo *</label>
                                <input type="text" name="nome" class="form-control" value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label>CPF *</label>
                                <input type="text" name="cpf" class="form-control cpf" value="<?php echo $_POST['cpf'] ?? ''; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label>RG</label>
                                <input type="text" name="rg" class="form-control" value="<?php echo htmlspecialchars($_POST['rg'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label>Data Nascimento</label>
                                <input type="date" name="data_nascimento" class="form-control" value="<?php echo $_POST['data_nascimento'] ?? ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label>Telefone</label>
                                <input type="text" name="telefone" class="form-control telefone" value="<?php echo htmlspecialchars($_POST['telefone'] ?? ''); ?>" placeholder="(00) 00000-0000">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label>Endereço</label>
                        <textarea name="endereco" class="form-control" rows="2"><?php echo htmlspecialchars($_POST['endereco'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label>Pontos Iniciais</label>
                                <input type="number" name="pontos" class="form-control" value="<?php echo $_POST['pontos'] ?? 0; ?>" min="0">
                                <small class="text-muted">Cliente ganha pontos a cada compra</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-end">
                        <a href="listar.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Salvar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script src="../js/main.js"></script>
</body>
</html>