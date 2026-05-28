
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

// Produtos deste Fornecedor
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
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Fornecedor - Supermercado</title>
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
                                <i class="fas fa-truck me-2 text-info"></i> Fornecedores
                            </a>
                            <a class="nav-link text-secondary rounded-3" href="cadastrar.php">
                                <i class="fas fa-plus me-2 text-info"></i> Novo Fornecedor
                            </a>
                            <a class="nav-link active bg-info bg-opacity-25 text-info fw-semibold rounded-3" href="#">
                                <i class="fas fa-edit me-2"></i> Editar Fornecedor
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="mb-3">
                    <h2 class="fw-bold text-info fs-4">
                        <i class="fas fa-edit me-2"></i> Editar Fornecedor
                    </h2>
                    <p class="text-secondary small">Atualize os dados do fornecedor</p>
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
                        <h6 class="mb-0 text-info"><i class="fas fa-building me-1"></i> Dados do Fornecedor</h6>
                    </div>
                    <div class="card-body p-3">
                        <form method="POST">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Razão Social <span class="text-danger">*</span></label>
                                    <input type="text" name="razao_social" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" value="<?php echo isset($fornecedor['razao_social']) ? htmlspecialchars($fornecedor['razao_social']) : ''; ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Nome Fantasia</label>
                                    <input type="text" name="nome_fantasia" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" value="<?php echo isset($fornecedor['nome_fantasia']) ? htmlspecialchars($fornecedor['nome_fantasia']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="row g-2 mt-1">
                                <div class="col-md-4">
                                    <label class="form-label text-secondary small fw-semibold mb-0">CNPJ <span class="text-danger">*</span></label>
                                    <input type="text" name="cnpj" class="form-control form-control-sm border border-info border-opacity-25 rounded-2 cnpj" value="<?php echo isset($fornecedor['cnpj']) ? $fornecedor['cnpj'] : ''; ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Inscrição Estadual</label>
                                    <input type="text" name="ie" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" value="<?php echo isset($fornecedor['ie']) ? htmlspecialchars($fornecedor['ie']) : ''; ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Telefone</label>
                                    <input type="text" name="telefone" class="form-control form-control-sm border border-info border-opacity-25 rounded-2 telefone" value="<?php echo isset($fornecedor['telefone']) ? htmlspecialchars($fornecedor['telefone']) : ''; ?>" placeholder="(00) 00000-0000">
                                </div>
                            </div>
                            
                            <div class="row g-2 mt-1">
                                <div class="col-md-6">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Email</label>
                                    <input type="email" name="email" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" value="<?php echo isset($fornecedor['email']) ? htmlspecialchars($fornecedor['email']) : ''; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Status</label>
                                    <div class="form-check form-switch mt-1">
                                        <input class="form-check-input" type="checkbox" name="ativo" id="ativo" <?php echo (isset($fornecedor['ativo']) && $fornecedor['ativo'] == 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label text-secondary small" for="ativo">Ativo</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-2">
                                <label class="form-label text-secondary small fw-semibold mb-0">Endereço</label>
                                <textarea name="endereco" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" rows="2"><?php echo isset($fornecedor['endereco']) ? htmlspecialchars($fornecedor['endereco']) : ''; ?></textarea>
                            </div>
                            
                            <div class="row g-2 mt-1">
                                <div class="col-md-6">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Nome do Contato</label>
                                    <input type="text" name="contato_nome" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" value="<?php echo isset($fornecedor['contato_nome']) ? htmlspecialchars($fornecedor['contato_nome']) : ''; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Telefone do Contato</label>
                                    <input type="text" name="contato_telefone" class="form-control form-control-sm border border-info border-opacity-25 rounded-2 telefone" value="<?php echo isset($fornecedor['contato_telefone']) ? htmlspecialchars($fornecedor['contato_telefone']) : ''; ?>" placeholder="(00) 00000-0000">
                                </div>
                            </div>
                            
                            <div class="row g-2 mt-1">
                                <div class="col-md-6">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Data Cadastro</label>
                                    <input type="text" class="form-control form-control-sm bg-light border border-info border-opacity-25 rounded-2" value="<?php echo isset($fornecedor['created_at']) ? date('d/m/Y H:i', strtotime($fornecedor['created_at'])) : '-'; ?>" disabled>
                                    <div class="form-text text-secondary small">Data de criação do cadastro</div>
                                </div>
                            </div>
                            
                            <hr class="my-3">
                            
                            <div class="d-flex justify-content-end gap-2">
                                <a href="listar.php" class="btn btn-outline-secondary btn-sm rounded-2">
                                    <i class="fas fa-arrow-left me-1"></i> Cancelar
                                </a>
                                <button type="submit" class="btn btn-info text-white btn-sm rounded-2">
                                    <i class="fas fa-save me-1"></i> Atualizar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Produtos deste Fornecedor -->
                <div class="card border border-info border-opacity-25 rounded-3 shadow-sm mt-3">
                    <div class="card-header bg-info bg-opacity-10 border-0 rounded-top-3 py-2">
                        <h6 class="mb-0 text-info"><i class="fas fa-box me-1"></i> Produtos deste Fornecedor</h6>
                    </div>
                    <div class="card-body p-0">
                        <?php
                        if(isset($produtos_fornecedor) && count($produtos_fornecedor) > 0):
                        ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="table-light">
                                        <tr class="small">
                                            <th class="border-0 ps-3">Produto</th>
                                            <th class="border-0">Categoria</th>
                                            <th class="border-0 text-end">Preço Venda</th>
                                            <th class="border-0 text-center">Estoque</th>
                                            <th class="border-0 text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($produtos_fornecedor as $produto): ?>
                                            <tr class="align-middle">
                                                <td class="ps-3 small fw-semibold"><?php echo htmlspecialchars($produto['nome']); ?>它
                                                <td class="small"><?php echo htmlspecialchars($produto['categoria_nome'] ?? '-'); ?>它
                                                <td class="text-end text-success small">R$ <?php echo number_format($produto['preco_venda'], 2, ',', '.'); ?>它
                                                <td class="text-center">
                                                    <?php 
                                                    $estoqueBaixo = $produto['quantidade'] <= $produto['quantidade_minima'];
                                                    ?>
                                                    <span class="badge bg-<?php echo $estoqueBaixo ? 'danger' : 'info'; ?> bg-opacity-25 text-<?php echo $estoqueBaixo ? 'danger' : 'info'; ?> px-2 py-1">
                                                        <?php echo $produto['quantidade']; ?>
                                                    </span>
                                                它
                                                <td class="text-center">
                                                    <?php if($produto['status'] == 'ativo'): ?>
                                                        <span class="badge bg-success bg-opacity-25 text-success px-2 py-1">
                                                            <i class="fas fa-check-circle me-1"></i> Ativo
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger bg-opacity-25 text-danger px-2 py-1">
                                                            <i class="fas fa-times-circle me-1"></i> Inativo
                                                        </span>
                                                    <?php endif; ?>
                                                它
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-box-open fa-2x text-secondary mb-2 d-block"></i>
                                <p class="text-secondary small mb-0">Nenhum produto cadastrado para este fornecedor</p>
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
            // Máscara para CNPJ
            $('.cnpj').mask('00.000.000/0000-00');
            
            // Máscara para telefone
            $('.telefone').mask('(00) 00000-0000');
        });
    </script>
</body>
</html>

