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
    <link rel="stylesheet" href="../css/custom.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="col-md-2 sidebar">
        <div class="nav flex-column">
            <a class="nav-link" href="../dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a class="nav-link active" href="listar.php"><i class="fas fa-user-tie"></i> Funcionários</a>
            <a class="nav-link" href="cadastrar.php"><i class="fas fa-plus"></i> Novo Funcionário</a>
        </div>
    </div>
    
    <div class="col-md-10 main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-user-tie"></i> Funcionários</h2>
            <a href="cadastrar.php" class="btn btn-success">
                <i class="fas fa-plus"></i> Novo Funcionário
            </a>
        </div>
        
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row mb-3">
                    <div class="col-md-5">
                        <input type="text" name="search" class="form-control" placeholder="Buscar por nome, CPF ou email..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-control">
                            <option value="todos" <?php echo $status == 'todos' ? 'selected' : ''; ?>>Todos</option>
                            <option value="ativo" <?php echo $status == 'ativo' ? 'selected' : ''; ?>>Ativos</option>
                            <option value="inativo" <?php echo $status == 'inativo' ? 'selected' : ''; ?>>Inativos</option>
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
                                <th>CPF</th>
                                <th>Cargo</th>
                                <th>Telefone</th>
                                <th>Email</th>
                                <th>Data Admissão</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($funcionarios as $funcionario): ?>
                                <tr>
                                    <td><?php echo $funcionario['id']; ?></td>
                                    <td><?php echo htmlspecialchars($funcionario['nome']); ?></td>
                                    <td><?php echo $funcionario['cpf']; ?></td>
                                    <td><?php echo htmlspecialchars($funcionario['cargo']); ?></td>
                                    <td><?php echo $funcionario['telefone']; ?></td>
                                    <td><?php echo htmlspecialchars($funcionario['email']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($funcionario['data_admissao'])); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $funcionario['status']; ?>">
                                            <?php echo ucfirst($funcionario['status']); ?>
                                        </span>
                                    </td>
                                    <td class="table-actions">
                                        <a href="editar.php?id=<?php echo $funcionario['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if($funcionario['status'] == 'ativo'): ?>
                                            <button onclick="confirmarDemissao(<?php echo $funcionario['id']; ?>, '<?php echo htmlspecialchars($funcionario['nome']); ?>')" class="btn btn-sm btn-danger">
                                                <i class="fas fa-user-slash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if(empty($funcionarios)): ?>
                                <tr>
                                    <td colspan="9" class="text-center">Nenhum funcionário encontrado</td>
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
    
    <script>
    function confirmarDemissao(id, nome) {
        if(confirm(`Tem certeza que deseja demitir ${nome}?`)) {
            window.location.href = `demitir.php?id=${id}`;
        }
    }
    </script>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>