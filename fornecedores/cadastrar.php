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
                            <a class="nav-link active bg-info bg-opacity-25 text-info fw-semibold rounded-3" href="cadastrar.php">
                                <i class="fas fa-plus me-2"></i> Novo Fornecedor
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="mb-3">
                    <h2 class="fw-bold text-info fs-4">
                        <i class="fas fa-plus me-2"></i> Cadastrar Fornecedor
                    </h2>
                    <p class="text-secondary small">Adicione um novo fornecedor ao sistema</p>
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
                        <form method="POST" id="formFornecedor">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Razão Social <span class="text-danger">*</span></label>
                                    <input type="text" name="razao_social" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" value="<?php echo htmlspecialchars($_POST['razao_social'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Nome Fantasia</label>
                                    <input type="text" name="nome_fantasia" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" value="<?php echo htmlspecialchars($_POST['nome_fantasia'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="row g-2 mt-1">
                                <div class="col-md-4">
                                    <label class="form-label text-secondary small fw-semibold mb-0">CNPJ <span class="text-danger">*</span></label>
                                    <input type="text" name="cnpj" class="form-control form-control-sm border border-info border-opacity-25 rounded-2 cnpj" value="<?php echo $_POST['cnpj'] ?? ''; ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Inscrição Estadual</label>
                                    <input type="text" name="ie" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" value="<?php echo htmlspecialchars($_POST['ie'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Telefone</label>
                                    <input type="text" name="telefone" class="form-control form-control-sm border border-info border-opacity-25 rounded-2 telefone" value="<?php echo htmlspecialchars($_POST['telefone'] ?? ''); ?>" placeholder="(00) 00000-0000">
                                </div>
                            </div>
                            
                            <div class="row g-2 mt-1">
                                <div class="col-md-6">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Email</label>
                                    <input type="email" name="email" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Status</label>
                                    <div class="form-check form-switch mt-1">
                                        <input class="form-check-input" type="checkbox" name="ativo" id="ativo" <?php echo isset($_POST['ativo']) ? 'checked' : 'checked'; ?>>
                                        <label class="form-check-label text-secondary small" for="ativo">Ativo</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-2">
                                <label class="form-label text-secondary small fw-semibold mb-0">Endereço</label>
                                <textarea name="endereco" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" rows="2"><?php echo htmlspecialchars($_POST['endereco'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="row g-2 mt-1">
                                <div class="col-md-6">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Nome do Contato</label>
                                    <input type="text" name="contato_nome" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" value="<?php echo htmlspecialchars($_POST['contato_nome'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Telefone do Contato</label>
                                    <input type="text" name="contato_telefone" class="form-control form-control-sm border border-info border-opacity-25 rounded-2 telefone" value="<?php echo htmlspecialchars($_POST['contato_telefone'] ?? ''); ?>" placeholder="(00) 00000-0000">
                                </div>
                            </div>
                            
                            <hr class="my-3">
                            
                            <div class="d-flex justify-content-end gap-2">
                                <a href="listar.php" class="btn btn-outline-secondary btn-sm rounded-2">
                                    <i class="fas fa-arrow-left me-1"></i> Cancelar
                                </a>
                                <button type="submit" class="btn btn-info text-white btn-sm rounded-2">
                                    <i class="fas fa-save me-1"></i> Salvar Fornecedor
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

