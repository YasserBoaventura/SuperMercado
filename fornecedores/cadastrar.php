<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';

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
// Verificar se CNPJ já existe
$check = $pdo->prepare("SELECT id FROM fornecedores WHERE cnpj = ?");
$check->execute([$cnpj_formatado]);
if($check->rowCount() > 0) {
    throw new Exception("CNPJ já cadastrado!");
}

$stmt = $pdo->prepare("
    INSERT INTO fornecedores (razao_social, nome_fantasia, cnpj, ie, telefone, email, endereco, contato_nome, contato_telefone, ativo)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([$razao_social, $nome_fantasia, $cnpj_formatado, $ie, $telefone, $email, $endereco, $contato_nome, $contato_telefone, $ativo]);
$fornecedor_id = $pdo->lastInsertId();

// Log
$log = $pdo->prepare("INSERT INTO logs (usuario_id, acao, tabela_afetada, registro_id, ip_address) 
                        VALUES (?, 'cadastrar', 'fornecedores', ?, ?)");
$log->execute([$_SESSION['usuario_id'], $fornecedor_id, $_SERVER['REMOTE_ADDR']]);

$sucesso = "Fornecedor cadastrado com sucesso!";

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
<title>Cadastrar Fornecedor - Supermercado</title>
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
    <a class="nav-link active" href="cadastrar.php"><i class="fas fa-plus"></i> Novo Fornecedor</a>
</div>
</div>

<div class="col-md-10 main-content">
<h2><i class="fas fa-plus"></i> Cadastrar Fornecedor</h2>

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
        <form method="POST" id="formFornecedor">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label>Razão Social *</label>
                        <input type="text" name="razao_social" class="form-control" value="<?php echo htmlspecialchars($_POST['razao_social'] ?? ''); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label>Nome Fantasia</label>
                        <input type="text" name="nome_fantasia" class="form-control" value="<?php echo htmlspecialchars($_POST['nome_fantasia'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label>CNPJ *</label>
                        <input type="text" name="cnpj" class="form-control cnpj" value="<?php echo $_POST['cnpj'] ?? ''; ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label>Inscrição Estadual</label>
                        <input type="text" name="ie" class="form-control" value="<?php echo htmlspecialchars($_POST['ie'] ?? ''); ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label>Telefone</label>
                        <input type="text" name="telefone" class="form-control telefone" value="<?php echo htmlspecialchars($_POST['telefone'] ?? ''); ?>" placeholder="(00) 00000-0000">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label>Status</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="ativo" id="ativo" <?php echo isset($_POST['ativo']) ? 'checked' : 'checked'; ?>>
                            <label class="form-check-label" for="ativo">Ativo</label>
                        </div>
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
                        <label>Nome do Contato</label>
                        <input type="text" name="contato_nome" class="form-control" value="<?php echo htmlspecialchars($_POST['contato_nome'] ?? ''); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label>Telefone do Contato</label>
                        <input type="text" name="contato_telefone" class="form-control telefone" value="<?php echo htmlspecialchars($_POST['contato_telefone'] ?? ''); ?>" placeholder="(00) 00000-0000">
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