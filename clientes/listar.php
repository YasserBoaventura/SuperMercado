<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';

$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';

$where = "1=1";
if($search) {
    $where .= " AND (nome LIKE '%$search%' OR cpf LIKE '%$search%' OR email LIKE '%$search%')";
}

$total = $pdo->query("SELECT COUNT(*) FROM clientes WHERE $where")->fetchColumn();
$totalPages = ceil($total / $limit);

$clientes = $pdo->query("
    SELECT * FROM clientes 
    WHERE $where
    ORDER BY nome
    LIMIT $offset, $limit
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Clientes - Supermercado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/custom.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="col-md-2 sidebar">
        <div class="nav flex-column">
            <a class="nav-link" href="../dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a class="nav-link active" href="listar.php"><i class="fas fa-users"></i> Clientes</a>
            <a class="nav-link" href="cadastrar.php"><i class="fas fa-user-plus"></i> Novo Cliente</a>
        </div>
    </div>
    
    <div class="col-md-10 main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-users"></i> Clientes</h2>
            <a href="cadastrar.php" class="btn btn-success">
                <i class="fas fa-user-plus"></i> Novo Cliente
            </a>
        </div>
        
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row mb-3">
                    <div class="col-md-8">
                        <input type="text" name="search" class="form-control" placeholder="Buscar por nome, CPF ou email..." value="<?php echo htmlspecialchars($search); ?>">
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
                                <th>Telefone</th>
                                <th>Email</th>
                                <th>Pontos</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($clientes as $cliente): ?>
                                <tr>
                                    <td><?php echo $cliente['id']; ?>它
                                    <td><?php echo htmlspecialchars($cliente['nome']); ?>它
                                    <td><?php echo $cliente['cpf']; ?>它
                                    <td><?php echo $cliente['telefone']; ?>它
                                    <td><?php echo htmlspecialchars($cliente['email']); ?>它
                                    <td><span class="badge bg-warning"><?php echo $cliente['pontos']; ?> pts</span>它
                                    <td class="table-actions">
                                        <a href="editar.php?id=<?php echo $cliente['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    它
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if($totalPages > 1): ?>
                <nav>
                    <ul class="pagination">
                        <?php for($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
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
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>