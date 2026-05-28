
<?php

require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Processar exclusão
if (isset($_GET['excluir'])) {
    $id = $_GET['excluir'];
    
    // Buscar dados do produto para log
    try {
        // Buscar produto
        $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
        $stmt->execute([$id]);
        $produto_excluir = $stmt->fetch();
        
        if ($produto_excluir) {
            // Verificar movimentações
            $check1 = $pdo->prepare("SELECT COUNT(*) FROM movimentacoes_estoque WHERE produto_id = ?");
            $check1->execute([$id]);
            $tem_movimentacoes = $check1->fetchColumn();

            // Verificar vendas (ESSENCIAL)
            $check2 = $pdo->prepare("SELECT COUNT(*) FROM itens_venda WHERE produto_id = ?");
            $check2->execute([$id]);
            $tem_vendas = $check2->fetchColumn();

            if ($tem_movimentacoes > 0 || $tem_vendas > 0) {
                // Melhor prática: DESATIVAR em vez de apagar
                $stmt = $pdo->prepare("UPDATE produtos SET status = 'inativo' WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['msg'] = "Produto desativado com sucesso (já possui histórico).";
            } else {
                // Pode apagar com segurança
                $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['msg'] = "Produto excluído com sucesso.";
            }
        }
    } catch(PDOException $e) {
        $_SESSION['erro'] = "Erro ao excluir: " . $e->getMessage();
    }
    
    header('Location: listar.php');
    exit();
}

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
    <!-- Nenhum CSS customizado - apenas Bootstrap -->
</head>
<body class="bg-info bg-opacity-10">

    <!-- Header superior -->
    <nav class="navbar navbar-expand-lg bg-info bg-opacity-25 shadow-sm mb-3">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold text-info" href="../dashboard.php">
                <i class="fas fa-store me-2"></i>Supermercado Gestão
            </a>
            <div class="ms-auto d-flex align-items-center gap-3">
                <span class="text-secondary">
                    <i class="fas fa-user-circle me-1 text-info"></i>
                    <?php echo isset($_SESSION['usuario_nome']) ? htmlspecialchars($_SESSION['usuario_nome']) : 'Usuário'; ?>
                </span>
                <span class="badge bg-info bg-opacity-25 text-info px-3 py-2">
                    <i class="fas fa-tag me-1"></i>
                    <?php echo isset($_SESSION['nivel_acesso']) ? ucfirst($_SESSION['nivel_acesso']) : 'Admin'; ?>
                </span>
                <a href="../logout.php" class="btn btn-outline-info btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4">
        <div class="row g-3">
            
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2">
                <div class="card border border-info border-opacity-25 rounded-4 shadow-sm sticky-top" style="top: 20px;">
                    <div class="card-body p-0">
                        <div class="nav flex-column nav-pills p-3 gap-2">
                            <a class="nav-link text-secondary rounded-3" href="../dashboard.php">
                                <i class="fas fa-home me-2 text-info"></i> Dashboard
                            </a>
                            <a class="nav-link active bg-info bg-opacity-25 text-info fw-semibold rounded-3" href="listar.php">
                                <i class="fas fa-box me-2"></i> Produtos
                            </a>
                            <a class="nav-link text-secondary rounded-3" href="cadastrar.php">
                                <i class="fas fa-plus me-2 text-info"></i> Novo Produto
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div>
                        <h2 class="fw-bold text-info fs-4">
                            <i class="fas fa-box me-2"></i> Produtos
                        </h2>
                        <p class="text-secondary small mb-0">Gerencie o catálogo de produtos</p>
                    </div>
                    <a href="cadastrar.php" class="btn btn-info text-white rounded-3">
                        <i class="fas fa-plus me-1"></i> Novo Produto
                    </a>
                </div>
                
                <!-- Mensagens de feedback -->
                <?php if(isset($_SESSION['sucesso'])): ?>
                    <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
                        <i class="fas fa-check-circle me-2"></i> 
                        <?php 
                        echo $_SESSION['sucesso'];
                        unset($_SESSION['sucesso']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if(isset($_SESSION['erro'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php 
                        echo $_SESSION['erro'];
                        unset($_SESSION['erro']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if(isset($_SESSION['msg'])): ?>
                    <div class="alert alert-info alert-dismissible fade show rounded-3" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        <?php 
                        echo $_SESSION['msg'];
                        unset($_SESSION['msg']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Card Principal -->
                <div class="card border border-info border-opacity-25 rounded-3 shadow-sm">
                    <div class="card-header bg-info bg-opacity-10 border-0 rounded-top-3 py-2">
                        <h6 class="mb-0 text-info"><i class="fas fa-search me-1"></i> Filtros</h6>
                    </div>
                    <div class="card-body p-3">
                        <form method="GET" class="row g-2">
                            <div class="col-md-10">
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-info border-opacity-25">
                                        <i class="fas fa-search text-info"></i>
                                    </span>
                                    <input type="text" name="search" class="form-control border-info border-opacity-25" placeholder="Buscar por nome ou código de barras..." value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-info text-white w-100 rounded-2" type="submit">
                                    <i class="fas fa-search me-1"></i> Buscar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Tabela de Produtos -->
                <div class="card border border-info border-opacity-25 rounded-3 shadow-sm mt-3">
                    <div class="card-header bg-info bg-opacity-10 border-0 rounded-top-3 py-2 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 text-info"><i class="fas fa-list me-1"></i> Lista de Produtos</h6>
                        <span class="badge bg-info bg-opacity-25 text-info">
                            <i class="fas fa-box me-1"></i> <?php echo isset($total) ? $total : 0; ?> produtos
                        </span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr class="small">
                                        <th class="border-0 ps-3">Código</th>
                                        <th class="border-0">Nome</th>
                                        <th class="border-0">Categoria</th>
                                        <th class="border-0 text-end">Preço Venda</th>
                                        <th class="border-0 text-center">Estoque</th>
                                        <th class="border-0 text-center">Est. Mínimo</th>
                                        <th class="border-0 text-center">Validade</th>
                                        <th class="border-0 text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(isset($produtos) && !empty($produtos)): ?>
                                        <?php foreach($produtos as $produto): ?>
                                            <tr>
                                                <td class="ps-3">
                                                    <code class="small text-info"><?php echo htmlspecialchars($produto['codigo_barras']); ?></code>
                                                </td>
                                                <td class="fw-semibold"><?php echo htmlspecialchars($produto['nome']); ?></td>
                                                <td>
                                                    <span class="badge bg-info bg-opacity-25 text-info px-2 py-1">
                                                        <?php echo htmlspecialchars($produto['categoria_nome'] ?? 'Sem categoria'); ?>
                                                    </span>
                                                </td>
                                                <td class="text-end fw-semibold text-success">
                                                    R$ <?php echo number_format($produto['preco_venda'], 2, ',', '.'); ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php 
                                                    $estoqueBaixo = $produto['quantidade'] <= $produto['quantidade_minima'];
                                                    $badgeClass = $estoqueBaixo ? 'danger' : 'info';
                                                    ?>
                                                    <span class="badge bg-<?php echo $badgeClass; ?> bg-opacity-25 text-<?php echo $badgeClass; ?> px-2 py-1">
                                                        <i class="fas fa-cubes me-1"></i> <?php echo $produto['quantidade']; ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-secondary bg-opacity-25 text-secondary px-2 py-1">
                                                        <?php echo $produto['quantidade_minima']; ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <?php if($produto['data_validade']): 
                                                        $diasRestantes = (strtotime($produto['data_validade']) - time()) / 86400;
                                                        $validadeClass = $diasRestantes <= 30 ? 'warning' : 'info';
                                                    ?>
                                                        <span class="badge bg-<?php echo $validadeClass; ?> bg-opacity-25 text-<?php echo $validadeClass; ?> px-2 py-1">
                                                            <i class="fas fa-calendar-alt me-1"></i> <?php echo date('d/m/Y', strtotime($produto['data_validade'])); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-secondary small">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-flex gap-1 justify-content-center">
                                                        <a href="editar.php?id=<?php echo $produto['id']; ?>" class="btn btn-outline-info btn-sm rounded-2" title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-outline-danger btn-sm rounded-2" title="Excluir" data-bs-toggle="modal" data-bs-target="#modalExcluir<?php echo $produto['id']; ?>">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            
                                            <!-- Modal de Confirmação de Exclusão -->
                                            <div class="modal fade" id="modalExcluir<?php echo $produto['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content rounded-4 border border-info border-opacity-25">
                                                        <div class="modal-header bg-info bg-opacity-25 border-0 rounded-top-4 py-3">
                                                            <h6 class="modal-title text-info">
                                                                <i class="fas fa-exclamation-triangle me-2"></i> Confirmar Exclusão
                                                            </h6>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p class="mb-2">Tem certeza que deseja excluir o produto <strong class="text-info"><?php echo htmlspecialchars($produto['nome']); ?></strong>?</p>
                                                            <p class="text-danger small mb-2"><i class="fas fa-ban me-1"></i> Esta ação não pode ser desfeita!</p>
                                                            <?php if($produto['quantidade'] > 0): ?>
                                                                <div class="alert alert-warning py-1 mb-0 small">
                                                                    <i class="fas fa-exclamation-circle me-1"></i> Este produto possui <strong><?php echo $produto['quantidade']; ?></strong> unidade(s) em estoque.
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="modal-footer bg-light rounded-bottom-4 py-2">
                                                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-2" data-bs-dismiss="modal">Cancelar</button>
                                                            <a href="?excluir=<?php echo $produto['id']; ?>&page=<?php echo isset($page) ? $page : 1; ?>&search=<?php echo urlencode($search); ?>" class="btn btn-danger btn-sm rounded-2">
                                                                <i class="fas fa-trash-alt me-1"></i> Sim, Excluir
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4">
                                                <i class="fas fa-box-open fa-3x text-secondary mb-2 d-block"></i>
                                                <p class="text-secondary mb-0">Nenhum produto encontrado</p>
                                                <a href="cadastrar.php" class="btn btn-info text-white btn-sm mt-2 rounded-3">
                                                    <i class="fas fa-plus me-1"></i> Cadastrar Produto
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Paginação -->
                        <?php if(isset($totalPages) && $totalPages > 1): ?>
                        <div class="card-footer bg-white border-0 py-2">
                            <nav>
                                <ul class="pagination pagination-sm justify-content-center mb-0">
                                    <?php for($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?php echo $i == (isset($page) ? $page : 1) ? 'active' : ''; ?>">
                                            <a class="page-link <?php echo $i == (isset($page) ? $page : 1) ? 'bg-info border-info text-white' : 'text-info border-info border-opacity-25'; ?>" 
                                               href="?page=<?php echo $i; ?>&search=<?php echo isset($search) ? urlencode($search) : ''; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Rodapé -->
                <footer class="mt-3 pt-2 border-top border-info border-opacity-25">
                    <div class="text-center text-secondary small">
                        <i class="fas fa-store me-1 text-info"></i> Sistema de Gestão Supermercado &copy; <?php echo date('Y'); ?>
                    </div>
                </footer>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

