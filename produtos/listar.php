<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';

$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$where = "status = 'ativo'";
if($search) {
    $where .= " AND (nome LIKE '%$search%' OR codigo_barras LIKE '%$search%')";
}

$total = $pdo->query("SELECT COUNT(*) FROM produtos WHERE $where")->fetchColumn();
$totalPages = ceil($total / $limit);

$produtos = $pdo->query("
    SELECT p.*, c.nome as categoria_nome 
    FROM produtos p
    LEFT JOIN categorias c ON p.categoria_id = c.id
    WHERE $where
    ORDER BY p.nome
    LIMIT $offset, $limit
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Produtos - Supermercado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/custom.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="col-md-2 sidebar">
        <div class="nav flex-column">
            <a class="nav-link" href="../dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a class="nav-link active" href="listar.php"><i class="fas fa-box"></i> Produtos</a>
            <a class="nav-link" href="cadastrar.php"><i class="fas fa-plus"></i> Novo Produto</a>
        </div>
    </div>
    
    <div class="col-md-10 main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-box"></i> Produtos</h2>
            <a href="cadastrar.php" class="btn btn-success">
                <i class="fas fa-plus"></i> Novo Produto
            </a>
        </div>
        
        <div class="card">
            <div class="card-body">
                <form method="GET" class="mb-3">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Buscar produto..." value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                    </div>
                </form>
                
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>Código</th>
                                <th>Nome</th>
                                <th>Categoria</th>
                                <th>Preço Venda</th>
                                <th>Estoque</th>
                                <th>Est. Mínimo</th>
                                <th>Validade</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($produtos as $produto): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($produto['codigo_barras']); ?></td>
                                    <td><?php echo htmlspecialchars($produto['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($produto['categoria_nome']); ?></td>
                                    <td>R$ <?php echo number_format($produto['preco_venda'], 2, ',', '.'); ?></td>
                                    <td class="<?php echo $produto['quantidade'] <= $produto['quantidade_minima'] ? 'text-danger fw-bold' : ''; ?>">
                                        <?php echo $produto['quantidade']; ?>
                                    </td>
                                    <td><?php echo $produto['quantidade_minima']; ?></td>
                                    <td><?php echo $produto['data_validade'] ? date('d/m/Y', strtotime($produto['data_validade'])) : '-'; ?></td>
                                    <td class="table-actions">
                                        <a href="editar.php?id=<?php echo $produto['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
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
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
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