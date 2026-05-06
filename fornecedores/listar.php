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
$where .= " AND (razao_social LIKE '%$search%' OR nome_fantasia LIKE '%$search%' OR cnpj LIKE '%$search%' OR email LIKE '%$search%')";
}
if($status != 'todos') {
$where .= " AND ativo = " . ($status == 'ativo' ? 1 : 0);
}

$total = $pdo->query("SELECT COUNT(*) FROM fornecedores WHERE $where")->fetchColumn();
$totalPages = ceil($total / $limit);

$fornecedores = $pdo->query("
SELECT * FROM fornecedores 
WHERE $where
ORDER BY nome_fantasia
LIMIT $offset, $limit
")->fetchAll();

// Mensagem de feedback
$msg = $_GET['msg'] ?? '';
$tipo_msg = $_GET['tipo'] ?? 'success';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Fornecedores - Supermercado</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="../css/custom.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="col-md-2 sidebar">
<div class="nav flex-column">
<a class="nav-link" href="../dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
<a class="nav-link active" href="listar.php"><i class="fas fa-truck"></i> Fornecedores</a>
<a class="nav-link" href="cadastrar.php"><i class="fas fa-plus"></i> Novo Fornecedor</a>
</div>
</div>

<div class="col-md-10 main-content">
<div class="d-flex justify-content-between align-items-center mb-4">
<h2><i class="fas fa-truck"></i> Fornecedores</h2>
<a href="cadastrar.php" class="btn btn-success">
<i class="fas fa-plus"></i> Novo Fornecedor
</a>
</div>

<?php if($msg): ?>
<div class="alert alert-<?php echo $tipo_msg; ?> alert-dismissible fade show" role="alert">
<i class="fas fa-<?php echo $tipo_msg == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i> <?php echo $msg; ?>
<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card">
<div class="card-body">
<form method="GET" class="row mb-3">
    <div class="col-md-5">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Buscar por razão social, nome fantasia, CNPJ ou email..." value="<?php echo htmlspecialchars($search); ?>">
            <button class="btn btn-primary" type="submit">
                <i class="fas fa-search"></i> Buscar
            </button>
        </div>
    </div>
    <div class="col-md-3">
        <select name="status" class="form-control">
            <option value="todos" <?php echo $status == 'todos' ? 'selected' : ''; ?>>Todos</option>
            <option value="ativo" <?php echo $status == 'ativo' ? 'selected' : ''; ?>>Ativos</option>
            <option value="inativo" <?php echo $status == 'inativo' ? 'selected' : ''; ?>>Inativos</option>
        </select>
    </div>
    <div class="col-md-4">
        <a href="listar.php" class="btn btn-secondary">
            <i class="fas fa-eraser"></i> Limpar Filtros
        </a>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-bordered table-hover">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Razão Social</th>
                <th>Nome Fantasia</th>
                <th>CNPJ</th>
                <th>Telefone</th>
                <th>Email</th>
                <th>Status</th>
                <th width="120">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if(count($fornecedores) > 0): ?>
                <?php foreach($fornecedores as $fornecedor): ?>
                    <tr>
                        <td><?php echo $fornecedor['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($fornecedor['razao_social']); ?></strong></td>
                        <td><?php echo htmlspecialchars($fornecedor['nome_fantasia']); ?></td>
                        <td><?php echo $fornecedor['cnpj']; ?></td>
                        <td><?php echo $fornecedor['telefone']; ?>
                        <td><?php echo htmlspecialchars($fornecedor['email']); ?>
                        <td>
                            <span class="badge bg-<?php echo $fornecedor['ativo'] == 1 ? 'success' : 'danger'; ?>">
                                <?php echo $fornecedor['ativo'] == 1 ? 'Ativo' : 'Inativo'; ?>
                            </span>
                    
                        <td class="table-actions">
                            <a href="editar.php?id=<?php echo $fornecedor['id']; ?>" class="btn btn-sm btn-primary" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-danger" title="Excluir" onclick="confirmarExclusao(<?php echo $fornecedor['id']; ?>, '<?php echo addslashes($fornecedor['razao_social']); ?>')">
                                <i class="fas fa-trash"></i>
                            </button>
                            <a href="visualizar.php?id=<?php echo $fornecedor['id']; ?>" class="btn btn-sm btn-info" title="Visualizar">
                                <i class="fas fa-eye"></i>
                            </a>
                    
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center">
                        <i class="fas fa-info-circle"></i> Nenhum fornecedor encontrado
    
                </tr>
            <?php endif; ?>
        </tbody>

</div>

<?php if($totalPages > 1): ?>
<nav>
    <ul class="pagination justify-content-center">
        <?php for($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>">
                    <?php echo $i; ?>
                </a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
</div>
</div>
</div>

<!-- Modal de Confirmação de Exclusão -->
<div class="modal fade" id="modalExcluir" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
<div class="modal-header bg-danger text-white">
    <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirmar Exclusão</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <p>Tem certeza que deseja excluir o fornecedor <strong id="fornecedorNome"></strong>?</p>
    <p class="text-danger"><small>Esta ação não poderá ser desfeita!</small></p>
    <?php if(isset($fornecedor)): ?>
        <div class="alert alert-warning">
            <i class="fas fa-info-circle"></i> Fornecedores com produtos vinculados serão apenas desativados.
        </div>
    <?php endif; ?>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
    <a href="#" id="btnConfirmarExcluir" class="btn btn-danger">Sim, Excluir</a>
</div>
</div>
</div>
</div>

<script>
function confirmarExclusao(id, nome) {
document.getElementById('fornecedorNome').innerText = nome;
document.getElementById('btnConfirmarExcluir').href = 'excluir.php?id=' + id;
var myModal = new bootstrap.Modal(document.getElementById('modalExcluir'));
myModal.show();
}
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>