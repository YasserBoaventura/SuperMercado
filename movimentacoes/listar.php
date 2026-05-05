<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Filtros
$produto_id = $_GET['produto_id'] ?? '';
$tipo = $_GET['tipo'] ?? '';
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');

// Buscar produtos para o filtro
$produtos = $pdo->query("SELECT id, nome FROM produtos ORDER BY nome")->fetchAll();

// Query de movimentações
$sql = "SELECT m.*, p.nome as produto_nome, u.nome as usuario_nome 
  FROM movimentacoes_estoque m
  LEFT JOIN produtos p ON m.produto_id = p.id
  LEFT JOIN usuarios u ON m.usuario_id = u.id
  WHERE 1=1";

$params = [];

if($produto_id) {
$sql .= " AND m.produto_id = ?";
$params[] = $produto_id;
}

if($tipo) {
$sql .= " AND m.tipo = ?";
$params[] = $tipo;
}

if($data_inicio && $data_fim) {
$sql .= " AND DATE(m.data_movimento) BETWEEN ? AND ?";
$params[] = $data_inicio;
$params[] = $data_fim;
}

$sql .= " ORDER BY m.data_movimento DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$movimentacoes = $stmt->fetchAll();

// Totais
$totais_sql = "SELECT     
COALESCE(SUM(CASE WHEN tipo='entrada' THEN quantidade ELSE 0 END),0) as total_entradas,
COALESCE(SUM(CASE WHEN tipo='saida' THEN quantidade ELSE 0 END),0) as total_saidas,
COALESCE(SUM(CASE WHEN tipo='venda' THEN quantidade ELSE 0 END),0) as total_vendas,
COALESCE(SUM(CASE WHEN tipo='ajuste' THEN quantidade ELSE 0 END),0) as total_ajustes
FROM movimentacoes_estoque m";

if($data_inicio && $data_fim) {
$totais_sql .= " WHERE DATE(m.data_movimento) BETWEEN '$data_inicio' AND '$data_fim'";
}

$totais = $pdo->query($totais_sql)->fetch();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Movimentações de Estoque</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container-fluid">
<div class="row">
  <div class="col-md-12">
      <div class="card">
          <div class="card-header bg-teal text-white d-flex justify-content-between align-items-center">
              <h5><i class="fas fa-exchange-alt"></i> Movimentações de Estoque</h5>
              <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalAjuste">
                  <i class="fas fa-plus"></i> Ajuste Manual
              </button>
          </div>
          
          <div class="card-body">
              <!-- Filtros -->
              <form method="GET" class="row g-2 mb-3">
                  <div class="col-md-3">
                      <label class="form-label">Produto</label>
                      <select name="produto_id" class="form-select form-select-sm">
                          <option value="">Todos</option>
                          <?php foreach($produtos as $p): ?>
                          <option value="<?php echo $p['id']; ?>" <?php echo $produto_id == $p['id'] ? 'selected' : ''; ?>>
                              <?php echo $p['nome']; ?>
                          </option>
                          <?php endforeach; ?>
                      </select>
                  </div>
                  <div class="col-md-2">
                      <label class="form-label">Tipo</label>
                      <select name="tipo" class="form-select form-select-sm">
                          <option value="">Todos</option>
                          <option value="entrada" <?php echo $tipo == 'entrada' ? 'selected' : ''; ?>>Entrada</option>
                          <option value="saida" <?php echo $tipo == 'saida' ? 'selected' : ''; ?>>Saída</option>
                          <option value="venda" <?php echo $tipo == 'venda' ? 'selected' : ''; ?>>Venda</option>
                          <option value="ajuste" <?php echo $tipo == 'ajuste' ? 'selected' : ''; ?>>Ajuste</option>
                      </select>
                  </div>
                  <div class="col-md-2">
                      <label class="form-label">Data Início</label>
                      <input type="date" name="data_inicio" class="form-control form-control-sm" value="<?php echo $data_inicio; ?>">
                  </div>
                  <div class="col-md-2">
                      <label class="form-label">Data Fim</label>
                      <input type="date" name="data_fim" class="form-control form-control-sm" value="<?php echo $data_fim; ?>">
                  </div>
                  <div class="col-md-3 d-flex align-items-end">
                      <button type="submit" class="btn btn-teal btn-sm me-2">Filtrar</button>
                      <a href="listar.php" class="btn btn-secondary btn-sm">Limpar</a>
                  </div>
              </form>
              
              <!-- Cards de Resumo -->
              <div class="row mb-3">
                  <div class="col-md-3">
                      <div class="card bg-success text-white">
                          <div class="card-body p-2">
                              <small>Total Entradas</small>
                              <h5 class="mb-0"><?php echo $totais['total_entradas']; ?> un</h5>
                          </div>
                      </div>
                  </div>
                  <div class="col-md-3">
                      <div class="card bg-danger text-white">
                          <div class="card-body p-2">
                              <small>Total Saídas</small>
                              <h5 class="mb-0"><?php echo $totais['total_saidas']; ?> un</h5>
                          </div>
                      </div>
                  </div>
                  <div class="col-md-3">
                      <div class="card bg-warning text-white">
                          <div class="card-body p-2">
                              <small>Total Vendas</small>
                              <h5 class="mb-0"><?php echo $totais['total_vendas']; ?> un</h5>
                          </div>
                      </div>
                  </div>
                  <div class="col-md-3">
                      <div class="card bg-info text-white">
                          <div class="card-body p-2">
                              <small>Total Ajustes</small>
                              <h5 class="mb-0"><?php echo $totais['total_ajustes']; ?> un</h5>
                          </div>
                      </div>
                  </div>
              </div>
              
              <!-- Tabela de Movimentações -->
              <div class="table-responsive">
                  <table class="table table-sm table-striped">
                      <thead>
                          <tr>
                              <th>Data/Hora</th>
                              <th>Produto</th>
                              <th>Tipo</th>
                              <th>Quantidade</th>
                              <th>Antes</th>
                              <th>Depois</th>
                              <th>Motivo</th>
                              <th>usuario</th>
                          </tr>
                      </thead>
                      <tbody>
                          <?php if(empty($movimentacoes)): ?>
                          <tr>
                              <td colspan="8" class="text-center">Nenhuma movimentação encontrada</td>
                          </tr>
                          <?php else: ?>
                              <?php foreach($movimentacoes as $m): ?>
                              <tr>
                                  <td><?php echo date('d/m/Y H:i', strtotime($m['data_movimento'])); ?></td>
                                  <td><?php echo $m['produto_nome']; ?></td>
                                  <td>
                                      <?php
                                      $badge = '';
                                      switch($m['tipo']) {
                                          case 'entrada': $badge = 'success'; break;
                                          case 'saida': $badge = 'danger'; break;
                                          case 'venda': $badge = 'warning'; break;
                                          case 'ajuste': $badge = 'info'; break;
                                      }
                                      ?>
                                      <span class="badge bg-<?php echo $badge; ?>"><?php echo strtoupper($m['tipo']); ?></span>
                                  </td>
                                  <td><?php echo $m['quantidade']; ?></td>
                                  <td><?php echo $m['quantidade_antes']; ?></td>
                                  <td><?php echo $m['quantidade_depois']; ?></td>
                                  <td><small><?php echo $m['motivo'] ?? '-'; ?></small></td>
                                  <td><?php echo $m['usuario_nome'] ?? 'Sistema'; ?></td>
                              </tr>
                              <?php endforeach; ?>
                          <?php endif; ?>
                      </tbody>
                  </table>
              </div>
          </div>
      </div>
  </div>
</div>
</div>

<!-- Modal Ajuste Manual -->
<div class="modal fade" id="modalAjuste" tabindex="-1">
<div class="modal-dialog modal-sm">
  <div class="modal-content">
      <div class="modal-header bg-teal text-white">
          <h5 class="modal-title">Ajuste Manual de Estoque</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="ajustar.php">
          <div class="modal-body">
              <div class="mb-2">
                  <label class="form-label">Produto *</label>
                  <select name="produto_id" class="form-select form-select-sm" required>
                      <option value="">Selecione</option>
                      <?php foreach($produtos as $p): ?>
                      <option value="<?php echo $p['id']; ?>"><?php echo $p['nome']; ?></option>
                      <?php endforeach; ?>
                  </select>
              </div>
              <div class="mb-2">
                  <label class="form-label">Tipo *</label>
                  <select name="tipo" class="form-select form-select-sm" required>
                      <option value="entrada">Entrada</option>
                      <option value="saida">Saída</option> 
                      <option value="venda">Venda</option>
                      <option value="ajustes"> Ajustes</option>
                  </select>
              </div>
              <div class="mb-2">
                  <label class="form-label">Quantidade *</label>
                  <input type="number" name="quantidade" class="form-control form-control-sm" min="1" required>
              </div>
              <div class="mb-2">
                  <label class="form-label">Motivo</label>
                  <textarea name="motivo" class="form-control form-control-sm" rows="2" placeholder="Ex: Compra de fornecedor, perda, etc"></textarea>
              </div>
          </div>
          <div class="modal-footer">
              <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-teal btn-sm">Ajustar Estoque</button>
          </div>
      </form>
  </div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>