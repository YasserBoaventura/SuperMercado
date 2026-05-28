<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';

$id = $_GET['id'] ?? 0;

// Buscar cliente
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->execute([$id]);
$cliente = $stmt->fetch();

if (!$cliente) {
header('Location: listar.php');
exit();
}

// Buscar movimentações de pontos
$movimentacoes = $pdo->prepare("
SELECT mp.*, v.numero_venda, v.data_venda, v.total
FROM movimentacoes_pontos mp
LEFT JOIN vendas v ON mp.venda_id = v.id
WHERE mp.cliente_id = ?
ORDER BY mp.data_movimento DESC
");
$movimentacoes->execute([$id]);
$movimentacoes = $movimentacoes->fetchAll();

// Calcular total de pontos ganhos e usados
$total_ganhos = array_sum(array_column($movimentacoes, 'pontos_ganhos'));
$total_usados = array_sum(array_column($movimentacoes, 'pontos_usados'));
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Extrato de Pontos - <?php echo htmlspecialchars($cliente['nome']); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="../css/custom.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="col-md-2 sidebar">
  <div class="nav flex-column">
      <a class="nav-link" href="../dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
      <a class="nav-link" href="listar.php"><i class="fas fa-users"></i> Clientes</a>
  </div>
</div>

<div class="col-md-10 main-content">
  <h2><i class="fas fa-star"></i> Extrato de Pontos</h2>
  
  <div class="card mb-4">
      <div class="card-header bg-primary text-white">
          <h5><?php echo htmlspecialchars($cliente['nome']); ?></h5>
      </div>
      <div class="card-body">
          <div class="row">
              <div class="col-md-4">
                  <div class="alert alert-info text-center">
                      <h6>Saldo Atual</h6>
                      <h2><?php echo $cliente['pontos']; ?> pontos</h2>
                  </div>
              </div>
              <div class="col-md-4">
                  <div class="alert alert-success text-center">
                      <h6>Total Pontos Ganhos</h6>
                      <h2><?php echo $total_ganhos; ?> pontos</h2>
                  </div>
              </div>
              <div class="col-md-4">
                  <div class="alert alert-warning text-center">
                      <h6>Total Pontos Utilizados</h6>
                      <h2><?php echo $total_usados; ?> pontos</h2>
                  </div>
              </div>
          </div>
          
          <div class="alert alert-info">
              <i class="fas fa-info-circle"></i> 
              <strong>Regras do Programa de Pontos:</strong><br>
              - Cada R$ 10,00 em compras equivale a 1 ponto<br>
              - 10 pontos equivalem a R$ 1,00 de desconto<br>
              - Mínimo de 100 pontos para resgatar desconto<br>
              - Os pontos expiram após 12 meses sem movimentação
          </div>
      </div>
  </div>
  
  <div class="card">
      <div class="card-header">
          <h5>Histórico de Movimentações</h5>
      </div>
      <div class="card-body">
          <div class="table-responsive">
              <table class="table table-bordered">
                  <thead class="table-dark">
                      <tr>
                          <th>Data</th>
                          <th>Venda</th>
                          <th>Pontos Ganhos</th>
                          <th>Pontos Usados</th>
                          <th>Saldo Após</th>
                          <th>Valor da Compra</th>
                      </tr>
                  </thead>
                  <tbody>
                      <?php foreach($movimentacoes as $mov): ?>
                          <tr>
                              <td><?php echo date('d/m/Y H:i', strtotime($mov['data_movimento'])); ?></td>
                              <td><?php echo $mov['numero_venda'] ?? '-'; ?></td>
                              <td class="text-success">+<?php echo $mov['pontos_ganhos']; ?></td>
                              <td class="text-danger">-<?php echo $mov['pontos_usados']; ?></td>
                              <td><strong><?php echo $mov['saldo_apos']; ?></strong></td>
                              <td>R$ <?php echo number_format($mov['total'] ?? 0, 2, ',', '.'); ?></td>
                          </tr>
                      <?php endforeach; ?>
                      <?php if(empty($movimentacoes)): ?>
                          <tr>
                              <td colspan="6" class="text-center">Nenhuma movimentação de pontos encontrada</td>
                          </tr>
                      <?php endif; ?>
                  </tbody>
              </table>
          </div>
      </div>
  </div>
  
  <div class="mt-3 text-end">
      <a href="listar.php" class="btn btn-primary">
          <i class="fas fa-arrow-left"></i> Voltar
      </a>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>