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

// Buscar produtos do fornecedor
$produtos = $pdo->prepare("
SELECT p.*, c.nome as categoria_nome
FROM produtos p
LEFT JOIN categorias c ON p.categoria_id = c.id
WHERE p.fornecedor_id = ?
ORDER BY p.nome
");
$produtos->execute([$id]);
$produtos_fornecedor = $produtos->fetchAll();

// Estatísticas
$stats = $pdo->prepare("
SELECT 
  COUNT(*) as total_produtos,
  COALESCE(SUM(quantidade), 0) as total_estoque,
  COALESCE(SUM(preco_venda * quantidade), 0) as valor_estoque
FROM produtos
WHERE fornecedor_id = ? AND status = 'ativo'
");
$stats->execute([$id]);
$estatisticas = $stats->fetch();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Visualizar Fornecedor - Supermercado</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="../css/custom.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="col-md-2 sidebar">
  <div class="nav flex-column">
      <a class="nav-link" href="../dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
      <a class="nav-link" href="listar.php"><i class="fas fa-truck"></i> Fornecedores</a>
      <a class="nav-link" href="cadastrar.php"><i class="fas fa-plus"></i> Novo Fornecedor</a>
      <a class="nav-link active" href="#"><i class="fas fa-eye"></i> Visualizar</a>
  </div>
</div>

<div class="col-md-10 main-content">
  <div class="d-flex justify-content-between align-items-center mb-4">
      <h2><i class="fas fa-eye"></i> Visualizar Fornecedor</h2>
      <div>
          <a href="editar.php?id=<?php echo $id; ?>" class="btn btn-primary">
              <i class="fas fa-edit"></i> Editar
          </a>
          <a href="listar.php" class="btn btn-secondary">
              <i class="fas fa-arrow-left"></i> Voltar
          </a>
      </div>
  </div>
  
  <div class="row">
      <div class="col-md-4">
          <div class="card mb-4">
              <div class="card-header bg-primary text-white">
                  <h5><i class="fas fa-building"></i> Dados do Fornecedor</h5>
              </div>
              <div class="card-body">
                  <p><strong>Razão Social:</strong> <?php echo htmlspecialchars($fornecedor['razao_social']); ?></p>
                  <p><strong>Nome Fantasia:</strong> <?php echo htmlspecialchars($fornecedor['nome_fantasia']); ?></p>
                  <p><strong>CNPJ:</strong> <?php echo $fornecedor['cnpj']; ?></p>
                  <p><strong>Inscrição Estadual:</strong> <?php echo $fornecedor['ie'] ?: '-'; ?></p>
                  <p><strong>Telefone:</strong> <?php echo $fornecedor['telefone'] ?: '-'; ?></p>
                  <p><strong>Email:</strong> <?php echo $fornecedor['email'] ?: '-'; ?></p>
                  <p><strong>Endereço:</strong> <?php echo nl2br(htmlspecialchars($fornecedor['endereco'])) ?: '-'; ?></p>
                  <p><strong>Status:</strong> 
                      <span class="badge bg-<?php echo $fornecedor['ativo'] == 1 ? 'success' : 'danger'; ?>">
                          <?php echo $fornecedor['ativo'] == 1 ? 'Ativo' : 'Inativo'; ?>
                      </span>
                  </p>
                  <p><strong>Data Cadastro:</strong> <?php echo date('d/m/Y H:i', strtotime($fornecedor['created_at'])); ?></p>
              </div>
          </div>
          
          <div class="card mb-4">
              <div class="card-header bg-info text-white">
                  <h5><i class="fas fa-user"></i> Contato</h5>
              </div>
              <div class="card-body">
                  <p><strong>Nome:</strong> <?php echo htmlspecialchars($fornecedor['contato_nome']) ?: '-'; ?></p>
                  <p><strong>Telefone:</strong> <?php echo $fornecedor['contato_telefone'] ?: '-'; ?></p>
              </div>
          </div>
          
          <div class="card">
              <div class="card-header bg-success text-white">
                  <h5><i class="fas fa-chart-bar"></i> Estatísticas</h5>
              </div>
              <div class="card-body">
                  <p><strong>Total Produtos:</strong> <?php echo $estatisticas['total_produtos']; ?></p>
                  <p><strong>Total em Estoque:</strong> <?php echo $estatisticas['total_estoque']; ?> unidades</p>
                  <p><strong>Valor em Estoque:</strong> R$ <?php echo number_format($estatisticas['valor_estoque'], 2, ',', '.'); ?></p>
              </div>
          </div>
      </div>
      
      <div class="col-md-8">
  <div class="card">
      <div class="card-header bg-warning text-dark">
          <h5><i class="fas fa-box"></i> Produtos do Fornecedor</h5>
      </div>
      <div class="card-body">
          <?php if(count($produtos_fornecedor) > 0): ?>
              <div class="table-responsive">
                  <table class="table table-bordered">
                      <thead class="table-dark">
                          <tr>
                              <th>Produto</th>
                              <th>Categoria</th>
                              <th>Preço Venda</th>
                              <th>Estoque</th>
                              <th>Status</th>
                              <th>Ações</th>
                          </tr>
                      </thead>
                      <tbody>
                          <?php foreach($produtos_fornecedor as $produto): ?>
                              <tr>
                                  <td><?php echo htmlspecialchars($produto['nome']); ?>
                                  <td><?php echo htmlspecialchars($produto['categoria_nome']); ?>
                                  <td>MT <?php echo number_format($produto['preco_venda'], 2, ',', '.'); ?>
                                  <td class="<?php echo $produto['quantidade'] <= $produto['quantidade_minima'] ? 'text-danger' : ''; ?>">
                                      <?php echo $produto['quantidade']; ?> <?php echo $produto['unidade_medida']; ?>
                                  
                                  <td>
                                      <span class="badge bg-<?php echo $produto['status'] == 'ativo' ? 'success' : 'danger'; ?>">
                                          <?php echo ucfirst($produto['status']); ?>
                                      </span>
                                  
                                  <td>
                                      <a href="../produtos/editar.php?id=<?php echo $produto['id']; ?>" class="btn btn-sm btn-primary">
                                          <i class="fas fa-edit"></i>
                                      </a>
                                  
                              </tr>
                          <?php endforeach; ?>
                      </tbody>
                  20
                      </div>
                  <?php else: ?>
                      <p class="text-muted text-center">Nenhum produto cadastrado para este fornecedor.</p>
                  <?php endif; ?>
              </div>
          </div>
      </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>