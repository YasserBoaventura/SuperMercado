<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';

$categorias = $pdo->query("SELECT * FROM categorias WHERE ativo = 1 ORDER BY nome")->fetchAll();
$fornecedores = $pdo->query("SELECT * FROM fornecedores WHERE ativo = 1 ORDER BY nome_fantasia")->fetchAll();

$erro = '';
$sucesso = '';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo_barras = $_POST['codigo_barras'];
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $categoria_id = $_POST['categoria_id'];
    $fornecedor_id = $_POST['fornecedor_id'] ?: null;
    $preco_compra = str_replace(',', '.', str_replace('.', '', $_POST['preco_compra']));
    $preco_venda = str_replace(',', '.', str_replace('.', '', $_POST['preco_venda']));
    $quantidade = $_POST['quantidade'];
    $quantidade_minima = $_POST['quantidade_minima'];
    $unidade_medida = $_POST['unidade_medida'];
    $data_validade = $_POST['data_validade'] ?: null;
    $localizacao = $_POST['localizacao'];
    
    try {

    $pdo->beginTransaction();

    // Inserir produto
    $stmt = $pdo->prepare("
        INSERT INTO produtos 
        (codigo_barras, nome, descricao, categoria_id, fornecedor_id, preco_compra, preco_venda, quantidade, quantidade_minima, unidade_medida, data_validade, localizacao) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $codigo_barras, 
        $nome, 
        $descricao, 
        $categoria_id, 
        $fornecedor_id, 
        $preco_compra, 
        $preco_venda, 
        $quantidade, 
        $quantidade_minima, 
        $unidade_medida, 
        $data_validade, 
        $localizacao
    ]);

    //  ID do produto
    $produto_id = $pdo->lastInsertId();

    // Registrar movimentação inicial
    if ($quantidade > 0) {
        $stmt = $pdo->prepare("
            INSERT INTO movimentacoes_estoque 
            (produto_id, tipo, quantidade, quantidade_antes, quantidade_depois, motivo, usuario_id)
            VALUES (?, 'entrada', ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $produto_id,
            $quantidade,
            0, // antes
            $quantidade, // depois
            'Cadastro inicial do produto',
            $_SESSION['usuario_id']
        ]);
    }

    // Log
    $log = $pdo->prepare("
        INSERT INTO logs (usuario_id, acao, tabela_afetada, registro_id, ip_address) 
        VALUES (?, 'cadastrar', 'produtos', ?, ?)
    ");

    $log->execute([
        $_SESSION['usuario_id'], 
        $produto_id, 
        $_SERVER['REMOTE_ADDR']
    ]);

    $pdo->commit();

    $sucesso = "Produto cadastrado com sucesso!";

} catch(PDOException $e) {
    $pdo->rollBack();
    $erro = "Erro ao cadastrar: " . $e->getMessage();
}
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Produto - Supermercado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/custom.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="col-md-2 sidebar">
        <div class="nav flex-column">
            <a class="nav-link" href="../dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a class="nav-link" href="listar.php"><i class="fas fa-box"></i> Produtos</a>
            <a class="nav-link active" href="cadastrar.php"><i class="fas fa-plus"></i> Novo Produto</a>
        </div>
    </div>
  
  <div class="col-md-10 main-content">
      <h2><i class="fas fa-plus"></i> Cadastrar Produto</h2>
      
      <?php if($sucesso): ?>
          <div class="alert alert-success"><?php echo $sucesso; ?></div>
      <?php endif; ?>
      
      <?php if($erro): ?>
          <div class="alert alert-danger"><?php echo $erro; ?></div>
      <?php endif; ?>
      
      <div class="card">
          <div class="card-body">
              <form method="POST">
                  <div class="row">
                      <div class="col-md-4">
                          <div class="mb-3">
                              <label>Código de Barras</label>
                              <input type="text" name="codigo_barras" class="form-control">
                          </div>
                      </div>
                      <div class="col-md-8">
                          <div class="mb-3">
                              <label>Nome do Produto *</label>
                              <input type="text" name="nome" class="form-control" required>
                          </div>
                      </div>
                  </div>
                  
                  <div class="mb-3">
                      <label>Descrição</label>
                      <textarea name="descricao" class="form-control" rows="3"></textarea>
                  </div>
                  
                  <div class="row">
                      <div class="col-md-6">
                          <div class="mb-3">
                              <label>Categoria *</label>
                              <select name="categoria_id" class="form-control" required>
                                  <option value="">Selecione...</option>
                                  <?php foreach($categorias as $categoria): ?>
                                      <option value="<?php echo $categoria['id']; ?>"><?php echo htmlspecialchars($categoria['nome']); ?></option>
                                  <?php endforeach; ?>
                              </select>
                          </div>
                      </div>
                      <div class="col-md-6">
                          <div class="mb-3">
                              <label>Fornecedor</label>
                              <select name="fornecedor_id" class="form-control">
                                  <option value="">Selecione...</option>
                                  <?php foreach($fornecedores as $fornecedor): ?>
                                      <option value="<?php echo $fornecedor['id']; ?>"><?php echo htmlspecialchars($fornecedor['nome_fantasia']); ?></option>
                                  <?php endforeach; ?>
                              </select>
                          </div>
                      </div>
                  </div>
                  
                  <div class="row">
                      <div class="col-md-3">
                          <div class="mb-3">
                              <label>Preço Compra</label>
                              <input type="text" name="preco_compra" class="form-control money" placeholder="0,00">
                          </div>
                      </div>
                      <div class="col-md-3">
                          <div class="mb-3">
                              <label>Preço Venda *</label>
                              <input type="text" name="preco_venda" class="form-control money" required placeholder="0,00">
                          </div>
                      </div>
                      <div class="col-md-2">
                          <div class="mb-3">
                              <label>Quantidade *</label>
                              <input type="number" name="quantidade" class="form-control" value="0" required>
                          </div>
                      </div>
                      <div class="col-md-2">
                          <div class="mb-3">
                              <label>Estoque Mínimo</label>
                              <input type="number" name="quantidade_minima" class="form-control" value="5">
                          </div>
                      </div>
                      <div class="col-md-2">
                          <div class="mb-3">
                              <label>Unidade</label>
                              <select name="unidade_medida" class="form-control">
                                  <option value="UN">Unidade</option>
                                  <option value="KG">Quilograma</option>
                                  <option value="L">Litro</option>
                                  <option value="PCT">Pacote</option>
                              </select>
                          </div>
                      </div>
                  </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label>Data Validade</label>
                                <input type="date" name="data_validade" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label>Localização</label>
                                <input type="text" name="localizacao" class="form-control" placeholder="Ex: Corredor 1, Prateleira A">
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-end">
                        <a href="listar.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-success">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/main.js"></script>
</body>
</html>