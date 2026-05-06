<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';

$id = $_GET['id'] ?? 0;

// Buscar cliente
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ? AND ativo = 1");
$stmt->execute([$id]);
$cliente = $stmt->fetch();

if (!$cliente) {
header('Location: listar.php?msg=Cliente não encontrado&tipo=danger');
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
$pontos = $_POST['pontos'];

// Formatar telefone
if(strlen($telefone) == 11) {
    $telefone = '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7);
} elseif(strlen($telefone) == 10) {
    $telefone = '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 4) . '-' . substr($telefone, 6);
}

try {
    // Verificar se CPF já existe para outro cliente
    $check = $pdo->prepare("SELECT id FROM clientes WHERE cpf = ? AND id != ? AND ativo = 1");
    $check->execute([$cpf, $id]);
    if($check->rowCount() > 0) {
        throw new Exception("CPF já cadastrado para outro cliente!");
    }
    
    // Verificar se email já existe para outro cliente
    if($email) {
        $check = $pdo->prepare("SELECT id FROM clientes WHERE email = ? AND id != ? AND ativo = 1");
        $check->execute([$email, $id]);
        if($check->rowCount() > 0) {
            throw new Exception("Email já cadastrado para outro cliente!");
        }
    }
    
    $dados_antigos = json_encode($cliente);
    
    $stmt = $pdo->prepare("
        UPDATE clientes SET 
        nome = ?, cpf = ?, rg = ?, data_nascimento = ?, telefone = ?, 
        email = ?, endereco = ?, pontos = ?
        WHERE id = ?
    ");
    
    $stmt->execute([$nome, $cpf, $rg, $data_nascimento, $telefone, $email, $endereco, $pontos, $id]);
    
    // Log
    $dados_novos = json_encode($_POST);
    $log = $pdo->prepare("INSERT INTO logs (usuario_id, acao, tabela_afetada, registro_id, dados_anteriores, dados_novos, ip_address) 
                          VALUES (?, 'editar', 'clientes', ?, ?, ?, ?)");
    $log->execute([$_SESSION['usuario_id'], $id, $dados_antigos, $dados_novos, $_SERVER['REMOTE_ADDR']]);
    
    $sucesso = "Cliente atualizado com sucesso!";
    
    // Recarregar dados
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->execute([$id]);
    $cliente = $stmt->fetch();
    
} catch(Exception $e) {
    $erro = $e->getMessage();
}
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Editar Cliente - Supermercado</title>
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
        <a class="nav-link" href="cadastrar.php"><i class="fas fa-user-plus"></i> Novo Cliente</a>
        <a class="nav-link active" href="#"><i class="fas fa-edit"></i> Editar Cliente</a>
    </div>
</div>

<div class="col-md-10 main-content">
    <h2><i class="fas fa-edit"></i> Editar Cliente</h2>
    
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
                            <label>Nome Completo *</label>
                            <input type="text" name="nome" class="form-control" value="<?php echo htmlspecialchars($cliente['nome']); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label>CPF *</label>
                            <input type="text" name="cpf" class="form-control cpf" value="<?php echo $cliente['cpf']; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label>RG</label>
                            <input type="text" name="rg" class="form-control" value="<?php echo htmlspecialchars($cliente['rg']); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label>Data Nascimento</label>
                            <input type="date" name="data_nascimento" class="form-control" value="<?php echo $cliente['data_nascimento']; ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label>Telefone</label>
                            <input type="text" name="telefone" class="form-control telefone" value="<?php echo htmlspecialchars($cliente['telefone']); ?>" placeholder="(00) 00000-0000">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($cliente['email']); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label>Endereço</label>
                    <textarea name="endereco" class="form-control" rows="2"><?php echo htmlspecialchars($cliente['endereco']); ?></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label>Pontos</label>
                            <input type="number" name="pontos" class="form-control" value="<?php echo $cliente['pontos']; ?>" min="0">
                            <small class="text-muted">Pontos de fidelidade do cliente</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label>Data Cadastro</label>
                            <input type="text" class="form-control" value="<?php echo date('d/m/Y H:i', strtotime($cliente['created_at'])); ?>" disabled>
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
    
    <!-- Histórico de Compras do Cliente -->
    <div class="card mt-4">
        <div class="card-header bg-info text-white">
            <h5><i class="fas fa-shopping-cart"></i> Histórico de Compras</h5>
        </div>
        <div class="card-body">
            <?php
            $compras = $pdo->prepare("
                SELECT v.*, u.nome as vendedor_nome
                FROM vendas v
                LEFT JOIN usuarios u ON v.usuario_id = u.id
                WHERE v.cliente_id = ?
                ORDER BY v.data_venda DESC
                LIMIT 10
            ");
            $compras->execute([$id]);
            $compras_cliente = $compras->fetchAll();
            ?>
            
            <?php if(count($compras_cliente) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>Nº Venda</th>
                                <th>Data</th>
                                <th>Vendedor</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($compras_cliente as $compra): ?>
                                <tr>
                                    <td><?php echo $compra['numero_venda']; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($compra['data_venda'])); ?></td>
                                    <td><?php echo htmlspecialchars($compra['vendedor_nome'] ?? 'N/A'); ?></td>
                                    <td class="fw-bold">R$ <?php echo number_format($compra['total'], 2, ',', '.'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $compra['status'] == 'concluida' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($compra['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted text-center">Este cliente ainda não realizou nenhuma compra.</p>
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