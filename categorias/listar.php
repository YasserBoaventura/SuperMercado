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
  $where .= " AND (nome LIKE '%$search%' OR descricao LIKE '%$search%')";
}
if($status != 'todos') {
  $where .= " AND ativo = " . ($status == 'ativo' ? 1 : 0);
}

$total = $pdo->query("SELECT COUNT(*) FROM categorias WHERE $where")->fetchColumn();
$totalPages = ceil($total / $limit);

$categorias = $pdo->query("
  SELECT c.*, 
          (SELECT COUNT(*) FROM produtos WHERE categoria_id = c.id) as total_produtos
  FROM categorias c
  WHERE $where
  ORDER BY c.nome
  LIMIT $offset, $limit
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Categorias - Supermercado</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="../css/custom.css">
</head>
<body>
  <?php include '../includes/header.php'; ?>
  
  <div class="col-md-2 sidebar">
      <div class="nav flex-column">
          <a class="nav-link" href="../dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
          <a class="nav-link active" href="listar.php"><i class="fas fa-tags"></i> Categorias</a>
          <a class="nav-link" href="cadastrar.php"><i class="fas fa-plus"></i> Nova Categoria</a>
      </div>
  </div>
  
  <div class="col-md-10 main-content">
      <div class="d-flex justify-content-between align-items-center mb-4">
          <h2><i class="fas fa-tags"></i> Categorias de Produtos</h2>
          <a href="cadastrar.php" class="btn btn-success">
              <i class="fas fa-plus"></i> Nova Categoria
          </a>
      </div>

<div class="card">
<div class="card-body">
<form method="GET" class="row mb-3">
    <div class="col-md-5">
        <input type="text" name="search" class="form-control" placeholder="Buscar por nome ou descrição..." value="<?php echo htmlspecialchars($search); ?>">
    </div>
    <div class="col-md-3">
        <select name="status" class="form-control">
            <option value="todos" <?php echo $status == 'todos' ? 'selected' : ''; ?>>Todos</option>
            <option value="ativo" <?php echo $status == 'ativo' ? 'selected' : ''; ?>>Ativas</option>
            <option value="inativo" <?php echo $status == 'inativo' ? 'selected' : ''; ?>>Inativas</option>
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
                <th>Descrição</th>
                <th>Qtd. Produtos</th>
                <th>Status</th>
                <th>Data Cadastro</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($categorias as $categoria): ?>
                <tr>
                    <td><?php echo $categoria['id']; ?>
                    <td><strong><?php echo htmlspecialchars($categoria['nome']); ?></strong>
                  <td><?php echo htmlspecialchars($categoria['descricao'] ?: '-'); ?>
                  <td>
                      <?php if($categoria['total_produtos'] > 0): ?>
                          <span class="badge bg-primary"><?php echo $categoria['total_produtos']; ?> produtos</span>
                      <?php else: ?>
                          <span class="badge bg-secondary">0 produtos</span>
                      <?php endif; ?>
                  
                  <td>
                      <?php if($categoria['ativo']): ?>
                          <span class="badge bg-success">Ativo</span>
                      <?php else: ?>
                          <span class="badge bg-danger">Inativo</span>
                      <?php endif; ?>
                  
                  <td><?php echo date('d/m/Y', strtotime($categoria['created_at'])); ?>
                  <td class="table-actions">
                      <a href="editar.php?id=<?php echo $categoria['id']; ?>" class="btn btn-sm btn-primary">
                          <i class="fas fa-edit"></i>
                      </a>
                      <?php if($categoria['total_produtos'] == 0): ?>
                          <button onclick="confirmarExclusao(<?php echo $categoria['id']; ?>, '<?php echo htmlspecialchars($categoria['nome']); ?>')" class="btn btn-sm btn-danger">
                              <i class="fas fa-trash"></i>
                          </button>
                      <?php else: ?>
                          <button class="btn btn-sm btn-secondary" disabled title="Não é possível excluir categoria com produtos vinculados">
                              <i class="fas fa-trash"></i>
                          </button>
                      <?php endif; ?>
                              
                            </tr>
                        <?php endforeach; ?>
                        <?php if(empty($categorias)): ?>
                            <tr>
                                <td colspan="7" class="text-center">Nenhuma categoria encontrada</td>
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
  function confirmarExclusao(id, nome) {
      if(confirm(`Tem certeza que deseja excluir a categoria "${nome}"?\n\nEsta ação não poderá ser desfeita!`)) {
          window.location.href = `excluir.php?id=${id}`;
      }
  }
  </script>
  
  <?php include '../includes/footer.php'; ?>
</body>
</html>