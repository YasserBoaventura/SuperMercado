
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
    $cargo = $_POST['cargo'];
    $salario = str_replace(',', '.', str_replace('.', '', $_POST['salario']));
    $data_admissao = $_POST['data_admissao'];
    
    // Formatar telefone
    if(strlen($telefone) == 11) {
        $telefone = '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7);
    }
    
    try {
        // Verificar se CPF já existe
        $check = $pdo->prepare("SELECT id FROM funcionarios WHERE cpf = ?");
        $check->execute([$cpf]);
        if($check->rowCount() > 0) {
            throw new Exception("CPF já cadastrado!");
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO funcionarios (nome, cpf, rg, data_nascimento, telefone, email, endereco, cargo, salario, data_admissao)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$nome, $cpf, $rg, $data_nascimento, $telefone, $email, $endereco, $cargo, $salario, $data_admissao]);
        
        // Log
        $log = $pdo->prepare("INSERT INTO logs (usuario_id, acao, tabela_afetada, registro_id, ip_address) 
                              VALUES (?, 'cadastrar', 'funcionarios', ?, ?)");
        $log->execute([$_SESSION['usuario_id'], $pdo->lastInsertId(), $_SERVER['REMOTE_ADDR']]);
        
        $sucesso = "Funcionário cadastrado com sucesso!";
    } catch(Exception $e) {
        $erro = $e->getMessage();
    }
}



?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Funcionário - Supermercado</title>
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
                                <i class="fas fa-user-tie me-2 text-info"></i> Funcionários
                            </a>
                            <a class="nav-link active bg-info bg-opacity-25 text-info fw-semibold rounded-3" href="cadastrar.php">
                                <i class="fas fa-plus me-2"></i> Novo Funcionário
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="mb-3">
                    <h2 class="fw-bold text-info fs-4">
                        <i class="fas fa-plus me-2"></i> Cadastrar Funcionário
                    </h2>
                    <p class="text-secondary small">Adicione um novo funcionário ao sistema</p>
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
                                    <input type="text" name="nome" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label text-secondary small fw-semibold mb-0">CPF <span class="text-danger">*</span></label>
                                    <input type="text" name="cpf" class="form-control form-control-sm border border-info border-opacity-25 rounded-2 cpf" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label text-secondary small fw-semibold mb-0">RG</label>
                                    <input type="text" name="rg" class="form-control form-control-sm border border-info border-opacity-25 rounded-2">
                                </div>
                            </div>
                            
                            <div class="row g-2 mt-1">
                                <div class="col-md-4">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Data Nascimento</label>
                                    <input type="date" name="data_nascimento" class="form-control form-control-sm border border-info border-opacity-25 rounded-2">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Telefone</label>
                                    <input type="text" name="telefone" class="form-control form-control-sm border border-info border-opacity-25 rounded-2 telefone" placeholder="(00) 00000-0000">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Email</label>
                                    <input type="email" name="email" class="form-control form-control-sm border border-info border-opacity-25 rounded-2">
                                </div>
                            </div>
                            
                            <div class="mt-2">
                                <label class="form-label text-secondary small fw-semibold mb-0">Endereço</label>
                                <textarea name="endereco" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" rows="2"></textarea>
                            </div>
                            
                            <div class="row g-2 mt-1">
                                <div class="col-md-4">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Cargo <span class="text-danger">*</span></label>
                                    <select name="cargo" class="form-select form-select-sm border border-info border-opacity-25 rounded-2" required>
                                        <option value="">Selecione...</option>
                                        <option value="Vendedor">Vendedor</option>
                                        <option value="Caixa">Caixa</option>
                                        <option value="Estoquista">Estoquista</option>
                                        <option value="Gerente">Gerente</option>
                                        <option value="Auxiliar">Auxiliar</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Salário</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-info border-opacity-25">R$</span>
                                        <input type="text" name="salario" class="form-control form-control-sm border-info border-opacity-25 money" placeholder="0,00">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-secondary small fw-semibold mb-0">Data Admissão <span class="text-danger">*</span></label>
                                    <input type="date" name="data_admissao" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" required value="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                            
                            <hr class="my-3">
                            
                            <div class="d-flex justify-content-end gap-2">
                                <a href="listar.php" class="btn btn-outline-secondary btn-sm rounded-2">
                                    <i class="fas fa-arrow-left me-1"></i> Cancelar
                                </a>
                                <button type="submit" class="btn btn-info text-white btn-sm rounded-2">
                                    <i class="fas fa-save me-1"></i> Salvar Funcionário
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
            // Máscara para CPF
            $('.cpf').mask('000.000.000-00');
            
            // Máscara para telefone
            $('.telefone').mask('(00) 00000-0000');
            
            // Máscara para dinheiro
            $('.money').on('input', function() {
                let value = this.value.replace(/[^0-9,]/g, '');
                if (value.indexOf(',') === -1) {
                    value = value.replace(/([0-9]+)([0-9]{2})$/, '$1,$2');
                }
                this.value = value;
            });
        });
    </script>
</body>
</html>

