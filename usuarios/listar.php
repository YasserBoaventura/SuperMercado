<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';

$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$nivel = $_GET['nivel'] ?? 'todos';

$where = "1=1";
if($search) {
$where .= " AND (nome LIKE '%$search%' OR email LIKE '%$search%')";
}
if($nivel != 'todos') {
$where .= " AND nivel_acesso = '$nivel'";
}

$total = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE $where")->fetchColumn();
$totalPages = ceil($total / $limit);

$usuarios = $pdo->query("
SELECT u.*, f.nome as funcionario_nome 
FROM usuarios u
LEFT JOIN funcionarios f ON u.funcionario_id = f.id
WHERE $where
ORDER BY u.nome
LIMIT $offset, $limit
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Usuários - Supermercado</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="../css/custom.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="col-md-2 sidebar">
    <div class="nav flex-column">
        <a class="nav-link" href="../dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a class="nav-link active" href="listar.php"><i class="fas fa-user-cog"></i> Usuários</a>
        <a class="nav-link" href="cadastrar.php"><i class="fas fa-user-plus"></i> Novo Usuário</a>
    </div>
</div>

<div class="col-md-10 main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-user-cog"></i> Usuários do Sistema</h2>
        <a href="cadastrar.php" class="btn btn-success">
            <i class="fas fa-user-plus"></i> Novo Usuário
        </a>
    </div>
    
    <div class="card">
        <div class="card-body">
            <form method="GET" class="row mb-3">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control" placeholder="Buscar por nome ou email..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <select name="nivel" class="form-control">
                        <option value="todos" <?php echo $nivel == 'todos' ? 'selected' : ''; ?>>Todos os níveis</option>
                        <option value="admin" <?php echo $nivel == 'admin' ? 'selected' : ''; ?>>Administradores</option>
                        <option value="vendedor" <?php echo $nivel == 'vendedor' ? 'selected' : ''; ?>>Vendedores</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                    <a href="listar.php" class="btn btn-secondary">
                        <i class="fas fa-eraser"></i> Limpar
                    </a>
                </div>
            </form>
            
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Nível Acesso</th>
                            <th>Funcionário</th>
                            <th>Status</th>
                            <th>Data Cadastro</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($usuarios as $usuario): ?>
                            <tr>
                                <td><?php echo $usuario['id']; ?></td>
                                <td><?php echo htmlspecialchars($usuario['nome']); ?></a>
                                <td><?php echo htmlspecialchars($usuario['email']); ?>
                                <td>
                                    <?php if($usuario['nivel_acesso'] == 'admin'): ?>
                                        <span class="badge bg-danger">
                                            <i class="fas fa-crown"></i> Administrador
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-info">
                                            <i class="fas fa-user"></i> Vendedor
                                        </span>
                                    <?php endif; ?>
                            
                                <td>
                                    <?php if($usuario['funcionario_id']): ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($usuario['funcionario_nome']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Não vinculado</span>
                                    <?php endif; ?>
                                
                                <td>
                                    <?php if($usuario['ativo']): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inativo</span>
                                    <?php endif; ?>
                                
                                <td><?php echo date('d/m/Y', strtotime($usuario['created_at'])); ?>它
                                <td class="table-actions">
                                    <a href="editar.php?id=<?php echo $usuario['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if($usuario['id'] != $_SESSION['usuario_id']): ?>
                                        <button onclick="confirmarExclusao(<?php echo $usuario['id']; ?>, '<?php echo htmlspecialchars($usuario['nome']); ?>')" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-secondary" disabled title="Não é possível excluir seu próprio usuário">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                
                            </tr>
                        <?php endforeach; ?>
                        <?php if(empty($usuarios)): ?>
                            <tr>
                                <td colspan="8" class="text-center">Nenhum usuário encontrado</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if($totalPages > 1): ?>
            <nav>
                <ul class="pagination">
                    <?php for($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&nivel=<?php echo $nivel; ?>">
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

<script>
function confirmarExclusao(id, nome) {
    if(confirm(`Tem certeza que deseja excluir o usuário "${nome}"?\n\nEsta ação não poderá ser desfeita!`)) {
        window.location.href = `excluir.php?id=${id}`;
    }
}
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>