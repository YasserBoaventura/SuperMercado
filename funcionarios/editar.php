<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM funcionarios WHERE id = ?");
$stmt->execute([$id]);
$funcionario = $stmt->fetch();

if (!$funcionario) {
    header('Location: listar.php');
    exit();
}

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
    $cargo = $_POST['cargo'];
    $salario = str_replace(',', '.', str_replace('.', '', $_POST['salario']));
    $status = $_POST['status'];
    
    // Formatar telefone
    if(strlen($telefone) == 11) {
        $telefone = '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7);
    }
    
    try {
        // Verificar se CPF já existe para outro funcionário
        $check = $pdo->prepare("SELECT id FROM funcionarios WHERE cpf = ? AND id != ?");
        $check->execute([$cpf, $id]);
        if($check->rowCount() > 0) {
            throw new Exception("CPF já cadastrado para outro funcionário!");
        }
        
        $dados_antigos = json_encode($funcionario);
        
        $stmt = $pdo->prepare("
            UPDATE funcionarios SET 
            nome = ?, cpf = ?, rg = ?, data_nascimento = ?, telefone = ?, 
            email = ?, endereco = ?, cargo = ?, salario = ?, status = ?
            WHERE id = ?
        ");
        
        $stmt->execute([$nome, $cpf, $rg, $data_nascimento, $telefone, $email, $endereco, $cargo, $salario, $status, $id]);
        
        // Log
        $dados_novos = json_encode($_POST);
        $log = $pdo->prepare("INSERT INTO logs (usuario_id, acao, tabela_afetada, registro_id, dados_anteriores, dados_novos, ip_address) 
                              VALUES (?, 'editar', 'funcionarios', ?, ?, ?, ?)");
        $log->execute([$_SESSION['usuario_id'], $id, $dados_antigos, $dados_novos, $_SERVER['REMOTE_ADDR']]);
        
        $sucesso = "Funcionário atualizado com sucesso!";
        
        // Recarregar dados
        $stmt = $pdo->prepare("SELECT * FROM funcionarios WHERE id = ?");
        $stmt->execute([$id]);
        $funcionario = $stmt->fetch();
        
    } catch(Exception $e) {
        $erro = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Funcionário - Supermercado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/custom.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="col-md-2 sidebar">
        <div class="nav flex-column">
            <a class="nav-link" href="../dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a class="nav-link" href="listar.php"><i class="fas fa-user-tie"></i> Funcionários</a>
            <a class="nav-link" href="cadastrar.php"><i class="fas fa-plus"></i> Novo Funcionário</a>
            <a class="nav-link active" href="editar.php?id=<?php echo $id; ?>"><i class="fas fa-edit"></i> Editar</a>
        </div>
    </div>
    
    <div class="col-md-10 main-content">
        <h2><i class="fas fa-edit"></i> Editar Funcionário</h2>
        
        <?php if($sucesso): ?>
            <div class="alert alert-success"><?php echo $sucesso; ?></div>
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
                                <input type="text" name="nome" class="form-control" value="<?php echo htmlspecialchars($funcionario['nome']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label>CPF *</label>
                                <input type="text" name="cpf" class="form-control cpf" value="<?php echo $funcionario['cpf']; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label>RG</label>
                                <input type="text" name="rg" class="form-control" value="<?php echo htmlspecialchars($funcionario['rg']); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label>Data Nascimento</label>
                                <input type="date" name="data_nascimento" class="form-control" value="<?php echo $funcionario['data_nascimento']; ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label>Telefone</label>
                                <input type="text" name="telefone" class="form-control telefone" value="<?php echo htmlspecialchars($funcionario['telefone']); ?>" placeholder="(00) 00000-0000">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($funcionario['email']); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label>Endereço</label>
                        <textarea name="endereco" class="form-control" rows="2"><?php echo htmlspecialchars($funcionario['endereco']); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label>Cargo *</label>
                                <select name="cargo" class="form-control" required>
                                    <option value="">Selecione...</option>
                                    <option value="Vendedor" <?php echo $funcionario['cargo'] == 'Vendedor' ? 'selected' : ''; ?>>Vendedor</option>
                                    <option value="Caixa" <?php echo $funcionario['cargo'] == 'Caixa' ? 'selected' : ''; ?>>Caixa</option>
                                    <option value="Estoquista" <?php echo $funcionario['cargo'] == 'Estoquista' ? 'selected' : ''; ?>>Estoquista</option>
                                    <option value="Gerente" <?php echo $funcionario['cargo'] == 'Gerente' ? 'selected' : ''; ?>>Gerente</option>
                                    <option value="Auxiliar" <?php echo $funcionario['cargo'] == 'Auxiliar' ? 'selected' : ''; ?>>Auxiliar</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label>Salário</label>
                                <input type="text" name="salario" class="form-control money" value="<?php echo number_format($funcionario['salario'], 2, ',', '.'); ?>" placeholder="0,00">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label>Data Admissão</label>
                                <input type="date" name="data_admissao" class="form-control" value="<?php echo $funcionario['data_admissao']; ?>" disabled readonly>
                                <small class="text-muted">Não editável</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="ativo" <?php echo $funcionario['status'] == 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                                    <option value="inativo" <?php echo $funcionario['status'] == 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                                </select>
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
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script src="../js/main.js"></script>
</body>
</html>