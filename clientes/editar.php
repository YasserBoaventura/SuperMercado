
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

// Histórico de Compras do Cliente
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
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Cliente - Supermercado</title>
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
                                <i class="fas fa-users me-2 text-info"></i> Clientes
                            </a>
                            <a class="nav-link text-secondary rounded-3" href="cadastrar.php">
                                <i class="fas fa-user-plus me-2 text-info"></i> Novo Cliente
                            </a>
                            <a class="nav-link active bg-info bg-opacity-25 text-info fw-semibold rounded-3" href="#">
                                <i class="fas fa-edit me-2"></i> Editar Cliente
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="mb-3">
                    <h2 class="fw-bold text-info fs-4">
                        <i class="fas fa-edit me-2"></i> Editar Cliente
                    </h2>
                    <p class="text-secondary small">Atualize os dados do cliente</p>
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
                        <h6 class="mb-0 text-info"><i class="fas fa-user me-1"></i> Dados Pessoais</h6>
                    </div>
                    <div class="card-body p-3">
                        <form method="POST">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Nome Completo <span class="text-danger">*</span></label>
                                    <input type="text" name="nome" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" value="<?php echo isset($cliente['nome']) ? htmlspecialchars($cliente['nome']) : ''; ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label text-secondary small fw-semibold mb-0">CPF <span class="text-danger">*</span></label>
                                    <input type="text" name="cpf" class="form-control form-control-sm border border-info border-opacity-25 rounded-2 cpf" value="<?php echo isset($cliente['cpf']) ? $cliente['cpf'] : ''; ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label text-secondary small fw-semibold mb-0">RG</label>
                                    <input type="text" name="rg" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" value="<?php echo isset($cliente['rg']) ? htmlspecialchars($cliente['rg']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="row g-2 mt-1">
                                <div class="col-md-4">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Data Nascimento</label>
                                    <input type="date" name="data_nascimento" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" value="<?php echo isset($cliente['data_nascimento']) ? $cliente['data_nascimento'] : ''; ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Telefone</label>
                                    <input type="text" name="telefone" class="form-control form-control-sm border border-info border-opacity-25 rounded-2 telefone" value="<?php echo isset($cliente['telefone']) ? htmlspecialchars($cliente['telefone']) : ''; ?>" placeholder="(00) 00000-0000">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Email</label>
                                    <input type="email" name="email" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" value="<?php echo isset($cliente['email']) ? htmlspecialchars($cliente['email']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="mt-2">
                                <label class="form-label text-secondary small fw-semibold mb-0">Endereço</label>
                                <textarea name="endereco" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" rows="2"><?php echo isset($cliente['endereco']) ? htmlspecialchars($cliente['endereco']) : ''; ?></textarea>
                            </div>
                            
                            <div class="row g-2 mt-1">
                                <div class="col-md-4">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Pontos</label>
                                    <input type="number" name="pontos" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" value="<?php echo isset($cliente['pontos']) ? $cliente['pontos'] : 0; ?>" min="0">
                                    <div class="form-text text-secondary small">Pontos de fidelidade do cliente</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Data Cadastro</label>
                                    <input type="text" class="form-control form-control-sm bg-light border border-info border-opacity-25 rounded-2" value="<?php echo isset($cliente['created_at']) ? date('d/m/Y H:i', strtotime($cliente['created_at'])) : '-'; ?>" disabled>
                                    <div class="form-text text-secondary small">Data de criação do cadastro</div>
                                </div>
                            </div>
                            
                            <hr class="my-3">
                            
                            <div class="d-flex justify-content-end gap-2">
                                <a href="listar.php" class="btn btn-outline-secondary btn-sm rounded-2">
                                    <i class="fas fa-arrow-left me-1"></i> Cancelar
                                </a>
                                <button type="submit" class="btn btn-info text-white btn-sm rounded-2">
                                    <i class="fas fa-save me-1"></i> Atualizar Cliente
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Histórico de Compras do Cliente -->
                <div class="card border border-info border-opacity-25 rounded-3 shadow-sm mt-3">
                    <div class="card-header bg-info bg-opacity-10 border-0 rounded-top-3 py-2">
                        <h6 class="mb-0 text-info"><i class="fas fa-shopping-cart me-1"></i> Histórico de Compras</h6>
                    </div>
                    <div class="card-body p-0">
                        <?php
                        if(isset($compras_cliente) && count($compras_cliente) > 0):
                        ?>
                            <div class="table-responsive">
                                <table class="table table-hover table-sm mb-0">
                                    <thead class="table-light">
                                        <tr class="small">
                                            <th class="border-0 ps-3">Nº Venda</th>
                                            <th class="border-0">Data</th>
                                            <th class="border-0">Vendedor</th>
                                            <th class="border-0 text-end">Total</th>
                                            <th class="border-0 text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($compras_cliente as $compra): ?>
                                            <tr class="align-middle">
                                                <td class="ps-3">
                                                    <code class="small text-info">#<?php echo $compra['numero_venda']; ?></code>
                                                </td>
                                                <td class="small"><?php echo date('d/m/Y H:i', strtotime($compra['data_venda'])); ?></td>
                                                <td class="small"><?php echo htmlspecialchars($compra['vendedor_nome'] ?? 'N/A'); ?></td>
                                                <td class="text-end fw-semibold text-success">R$ <?php echo number_format($compra['total'], 2, ',', '.'); ?></td>
                                                <td class="text-center">
                                                    <?php if($compra['status'] == 'concluida'): ?>
                                                        <span class="badge bg-success bg-opacity-25 text-success px-2 py-1">
                                                            <i class="fas fa-check-circle me-1"></i> Concluída
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning bg-opacity-25 text-warning px-2 py-1">
                                                            <i class="fas fa-clock me-1"></i> Pendente
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-shopping-bag fa-2x text-secondary mb-2 d-block"></i>
                                <p class="text-secondary small mb-0">Este cliente ainda não realizou nenhuma compra</p>
                            </div>
                        <?php endif; ?>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script>
        $(document).ready(function() {
            // Máscara para CPF
            $('.cpf').mask('000.000.000-00');
            
            // Máscara para telefone
            $('.telefone').mask('(00) 00000-0000');
        });
    </script>
</body>
</html>

