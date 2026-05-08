<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$nome = trim($_POST['nome']);
$descricao = trim($_POST['descricao']);
$ativo = isset($_POST['ativo']) ? 1 : 0;

if(empty($nome)) {
$erro = "O nome da categoria é obrigatório!";
} else {
try {
  // Verificar se categoria já existe
  $check = $pdo->prepare("SELECT id FROM categorias WHERE nome = ?");
  $check->execute([$nome]);
  if($check->rowCount() > 0) {
      throw new Exception("Já existe uma categoria com este nome!");
  }
  
  $stmt = $pdo->prepare("INSERT INTO categorias (nome, descricao, ativo) VALUES (?, ?, ?)");
  $stmt->execute([$nome, $descricao, $ativo]);
  $categoria_id = $pdo->lastInsertId();
  
  // Log
  $log = $pdo->prepare("INSERT INTO logs (usuario_id, acao, tabela_afetada, registro_id, ip_address) 
                        VALUES (?, 'cadastrar', 'categorias', ?, ?)");
  $log->execute([$_SESSION['usuario_id'], $categoria_id, $_SERVER['REMOTE_ADDR']]);
  
  $sucesso = "Categoria cadastrada com sucesso!";
  
  // Limpar formulário
  $_POST = [];
  
} catch(Exception $e) {
  $erro = $e->getMessage();
}
}
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Cadastrar Categoria - Supermercado</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="../css/custom.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="col-md-2 sidebar">
<div class="nav flex-column">
  <a class="nav-link" href="../dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
  <a class="nav-link" href="listar.php"><i class="fas fa-tags"></i> Categorias</a>
  <a class="nav-link active" href="cadastrar.php"><i class="fas fa-plus"></i> Nova Categoria</a>
</div>
</div>

<div class="col-md-10 main-content">
<h2><i class="fas fa-plus"></i> Cadastrar Categoria</h2>

<?php if($sucesso): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
      <?php echo $sucesso; ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<?php if($erro): ?>
  <div class="alert alert-danger"><?php echo $erro; ?></div>
<?php endif; ?>

<div class="card">
  <div class="card-body">
      <form method="POST">
          <div class="mb-3">
              <label>Nome da Categoria *</label>
              <input type="text" name="nome" class="form-control" value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>" required>
              <small class="text-muted">Ex: Alimentos, Bebidas, Limpeza, etc.</small>
          </div>
          
          <div class="mb-3">
              <label>Descrição</label>
              <textarea name="descricao" class="form-control" rows="4"><?php echo htmlspecialchars($_POST['descricao'] ?? ''); ?></textarea>
              <small class="text-muted">Descrição detalhada da categoria (opcional)</small>
          </div>
          
          <div class="mb-3">
              <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" name="ativo" value="1" id="ativoSwitch" <?php echo ($_POST['ativo'] ?? '1') == '1' ? 'checked' : ''; ?>>
                  <label class="form-check-label" for="ativoSwitch">
                      Categoria Ativa
                  </label>
              </div>
              <small class="text-muted">Categorias inativas não aparecem na lista de seleção de produtos</small>
          </div>
          
          <div class="alert alert-info">
              <i class="fas fa-info-circle"></i> 
              <strong>Dica:</strong> Organize seus produtos em categorias para facilitar a gestão e a busca.
          </div>
          
          <div class="text-end">
              <a href="listar.php" class="btn btn-secondary">Cancelar</a>
              <button type="submit" class="btn btn-success">Cadastrar</button>
          </div>
      </form>
  </div>
</div>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>