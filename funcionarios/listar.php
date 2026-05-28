
<?php

require_once '../config/database.php';
require_once '../includes/auth_check.php';

$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'todos';

$where = "1=1";
if($search) {
$where .= " AND (nome LIKE '%$search%' OR cpf LIKE '%$search%' OR email LIKE '%$search%')";
}
if($status != 'todos') {
$where .= " AND status = '$status'";
}

$total = $pdo->query("SELECT COUNT(*) FROM funcionarios WHERE $where")->fetchColumn();
$totalPages = ceil($total / $limit);

$funcionarios = $pdo->query("
SELECT * FROM funcionarios 
WHERE $where
ORDER BY nome
LIMIT $offset, $limit
")->fetchAll();


?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Funcionários - Supermercado</title>
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
                    <a class="nav-link active bg-info bg-opacity-25 text-info fw-semibold rounded-3" href="listar.php">
                        <i class="fas fa-user-tie me-2"></i> Funcionários
                    </a>
                    <a class="nav-link text-secondary rounded-3" href="cadastrar.php">
                        <i class="fas fa-plus me-2 text-info"></i> Novo Funcionário
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="col-md-9 col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h2 class="fw-bold text-info fs-4">
                    <i class="fas fa-user-tie me-2"></i> Funcionários
                </h2>
                <p class="text-secondary small mb-0">Gerencie os funcionários da empresa</p>
            </div>
            <a href="cadastrar.php" class="btn btn-info text-white rounded-3">
                <i class="fas fa-plus me-1"></i> Novo Funcionário
            </a>
        </div>
        
        <!-- Card Filtros -->
        <div class="card border border-info border-opacity-25 rounded-3 shadow-sm">
            <div class="card-header bg-info bg-opacity-10 border-0 rounded-top-3 py-2">
                <h6 class="mb-0 text-info"><i class="fas fa-search me-1"></i> Filtros</h6>
            </div>
            <div class="card-body py-2">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-info border-opacity-25">
                                <i class="fas fa-search text-info"></i>
                            </span>
                            <input type="text" name="search" class="form-control form-control-sm border-info border-opacity-25" placeholder="Buscar por nome, CPF ou email..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select form-select-sm border border-info border-opacity-25 rounded-2">
                            <option value="todos" <?php echo isset($status) && $status == 'todos' ? 'selected' : ''; ?>>Todos</option>
                            <option value="ativo" <?php echo isset($status) && $status == 'ativo' ? 'selected' : ''; ?>>Ativos</option>
                            <option value="inativo" <?php echo isset($status) && $status == 'inativo' ? 'selected' : ''; ?>>Inativos</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex gap-2">
                            <button class="btn btn-info text-white btn-sm rounded-2" type="submit">
                                <i class="fas fa-search me-1"></i> Buscar
                            </button>
                            <a href="listar.php" class="btn btn-outline-secondary btn-sm rounded-2">
                                <i class="fas fa-eraser me-1"></i> Limpar
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Tabela de Funcionários -->
        <div class="card border border-info border-opacity-25 rounded-3 shadow-sm mt-3">
            <div class="card-header bg-info bg-opacity-10 border-0 rounded-top-3 py-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-info"><i class="fas fa-list me-1"></i> Lista de Funcionários</h6>
                <span class="badge bg-info bg-opacity-25 text-info">
                    <i class="fas fa-user-tie me-1"></i> <?php echo isset($total) ? $total : 0; ?>
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr class="small">
                                <th class="border-0 ps-3">ID</th>
                                <th class="border-0">Nome</th>
                                <th class="border-0">BI</th> 
                                <th class="border-0">Cargo</th>
                                <th class="border-0">Telefone</th>
                                <th class="border-0">Email</th>
                                <th class="border-0">Admissão</th>
                                <th class="border-0 text-center">Status</th>
                                <th class="border-0 text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(isset($funcionarios) && !empty($funcionarios)): ?>
                                <?php foreach($funcionarios as $funcionario): ?>
                                    <tr class="align-middle">
                                        <td class="ps-3">
                                            <code class="small text-info"><?php echo $funcionario['id']; ?></code>
                                        </td>
                                        <td class="fw-semibold small"><?php echo htmlspecialchars($funcionario['nome']); ?>
                                        <td><code class="small"><?php echo $funcionario['cpf']; ?></code>
                                        <td class="small"><?php echo htmlspecialchars($funcionario['cargo']); ?>
                                        <td class="small"><?php echo $funcionario['telefone']; ?>
                                        <td class="small"><?php echo htmlspecialchars($funcionario['email']); ?>
                                        <td class="small"><?php echo date('d/m/Y', strtotime($funcionario['data_admissao'])); ?>
                                        <td class="text-center">
                                            <?php if($funcionario['status'] == 'ativo'): ?>
                                                <span class="badge bg-success bg-opacity-25 text-success px-2 py-1">
                                                    <i class="fas fa-check-circle me-1"></i> Ativo
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-danger bg-opacity-25 text-danger px-2 py-1">
                                                    <i class="fas fa-times-circle me-1"></i> Inativo
                                                </span>
                                            <?php endif; ?>
                                        
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <a href="editar.php?id=<?php echo $funcionario['id']; ?>" class="btn btn-outline-info btn-sm rounded-2 py-0 px-2" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if($funcionario['status'] == 'ativo'): ?>
                                                    <button onclick="confirmarDemissao(<?php echo $funcionario['id']; ?>, '<?php echo htmlspecialchars($funcionario['nome']); ?>')" class="btn btn-outline-danger btn-sm rounded-2 py-0 px-2" title="Demitir">
                                                        <i class="fas fa-user-slash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <i class="fas fa-users-slash fa-2x text-secondary mb-2 d-block"></i>
                                        <p class="text-secondary small mb-0">Nenhum funcionário encontrado</p>
                                        <a href="cadastrar.php" class="btn btn-info text-white btn-sm mt-2 rounded-3">
                                            <i class="fas fa-plus me-1"></i> Cadastrar
                                        </a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginação -->
                <?php if(isset($totalPages) && $totalPages > 1): ?>
                <div class="card-footer bg-white border-0 py-2">
                    <nav>
                        <ul class="pagination pagination-sm justify-content-center mb-0">
                            <?php for($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i == (isset($page) ? $page : 1) ? 'active' : ''; ?>">
                                    <a class="page-link <?php echo $i == (isset($page) ? $page : 1) ? 'bg-info border-info text-white' : 'text-info border-info border-opacity-25'; ?>" 
                                        href="?page=<?php echo $i; ?>&search=<?php echo isset($search) ? urlencode($search) : ''; ?>&status=<?php echo isset($status) ? $status : 'todos'; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function confirmarDemissao(id, nome) {
    if(confirm(`Tem certeza que deseja demitir ${nome}?`)) {
        window.location.href = `demitir.php?id=${id}`;
    }
}
</script>
</body>
</html>

