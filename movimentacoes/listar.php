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
    <title>Movimentações de Estoque - Supermercado</title>
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
                <span class="text-secondary small">
                    <i class="fas fa-user-circle me-1 text-info"></i>
                    <?php echo isset($_SESSION['usuario_nome']) ? htmlspecialchars($_SESSION['usuario_nome']) : 'Usuário'; ?>
                </span>
                <span class="badge bg-info bg-opacity-25 text-info px-2 py-1">
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
                                <i class="fas fa-exchange-alt me-2"></i> Movimentações
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="mb-3">
                    <h2 class="fw-bold text-info fs-4">
                        <i class="fas fa-exchange-alt me-2"></i> Movimentações de Estoque
                    </h2>
                    <p class="text-secondary small">Histórico de entradas e saídas do estoque</p>
                </div>
                
                <!-- Filtros -->
                <div class="card border border-info border-opacity-25 rounded-3 shadow-sm">
                    <div class="card-header bg-info bg-opacity-10 border-0 rounded-top-3 py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h6 class="mb-0 text-info"><i class="fas fa-filter me-1"></i> Filtros</h6>
                        <button class="btn btn-info text-white btn-sm rounded-2" data-bs-toggle="modal" data-bs-target="#modalAjuste">
                            <i class="fas fa-plus me-1"></i> Ajuste Manual
                        </button>
                    </div>
                    <div class="card-body py-2">
                        <form method="GET" class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label text-secondary small fw-semibold mb-0">Produto</label>
                                <select name="produto_id" class="form-select form-select-sm border border-info border-opacity-25 rounded-2">
                                    <option value="">Todos</option>
                                    <?php if(isset($produtos)): ?>
                                        <?php foreach($produtos as $p): ?>
                                            <option value="<?php echo $p['id']; ?>" <?php echo (isset($produto_id) && $produto_id == $p['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($p['nome']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label text-secondary small fw-semibold mb-0">Tipo</label>
                                <select name="tipo" class="form-select form-select-sm border border-info border-opacity-25 rounded-2">
                                    <option value="">Todos</option>
                                    <option value="entrada" <?php echo (isset($tipo) && $tipo == 'entrada') ? 'selected' : ''; ?>>Entrada</option>
                                    <option value="saida" <?php echo (isset($tipo) && $tipo == 'saida') ? 'selected' : ''; ?>>Saída</option>
                                    <option value="venda" <?php echo (isset($tipo) && $tipo == 'venda') ? 'selected' : ''; ?>>Venda</option>
                                    <option value="ajuste" <?php echo (isset($tipo) && $tipo == 'ajuste') ? 'selected' : ''; ?>>Ajuste</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label text-secondary small fw-semibold mb-0">Data Início</label>
                                <input type="date" name="data_inicio" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" value="<?php echo isset($data_inicio) ? $data_inicio : date('Y-m-01'); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label text-secondary small fw-semibold mb-0">Data Fim</label>
                                <input type="date" name="data_fim" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" value="<?php echo isset($data_fim) ? $data_fim : date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-3 d-flex gap-2">
                                <button type="submit" class="btn btn-info text-white btn-sm rounded-2">
                                    <i class="fas fa-search me-1"></i> Filtrar
                                </button>
                                <a href="listar.php" class="btn btn-outline-secondary btn-sm rounded-2">
                                    <i class="fas fa-eraser me-1"></i> Limpar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Cards de Resumo -->
                <div class="row g-2 mt-3">
                    <div class="col-md-3">
                        <div class="card border border-success border-opacity-25 rounded-3 shadow-sm">
                            <div class="card-body p-2 p-md-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-secondary mb-0 small">Total Entradas</p>
                                        <h5 class="fw-bold text-success mb-0"><?php echo isset($totais['total_entradas']) ? $totais['total_entradas'] : 0; ?> un</h5>
                                    </div>
                                    <div class="bg-success bg-opacity-10 rounded-circle p-2">
                                        <i class="fas fa-arrow-down text-success fs-6"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border border-danger border-opacity-25 rounded-3 shadow-sm">
                            <div class="card-body p-2 p-md-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-secondary mb-0 small">Total Saídas</p>
                                        <h5 class="fw-bold text-danger mb-0"><?php echo isset($totais['total_saidas']) ? $totais['total_saidas'] : 0; ?> un</h5>
                                    </div>
                                    <div class="bg-danger bg-opacity-10 rounded-circle p-2">
                                        <i class="fas fa-arrow-up text-danger fs-6"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border border-warning border-opacity-25 rounded-3 shadow-sm">
                            <div class="card-body p-2 p-md-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-secondary mb-0 small">Total Vendas</p>
                                        <h5 class="fw-bold text-warning mb-0"><?php echo isset($totais['total_vendas']) ? $totais['total_vendas'] : 0; ?> un</h5>
                                    </div>
                                    <div class="bg-warning bg-opacity-10 rounded-circle p-2">
                                        <i class="fas fa-shopping-cart text-warning fs-6"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border border-info border-opacity-25 rounded-3 shadow-sm">
                            <div class="card-body p-2 p-md-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-secondary mb-0 small">Total Ajustes</p>
                                        <h5 class="fw-bold text-info mb-0"><?php echo isset($totais['total_ajustes']) ? $totais['total_ajustes'] : 0; ?> un</h5>
                                    </div>
                                    <div class="bg-info bg-opacity-10 rounded-circle p-2">
                                        <i class="fas fa-sliders-h text-info fs-6"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tabela de Movimentações -->
                <div class="card border border-info border-opacity-25 rounded-3 shadow-sm mt-3">
                    <div class="card-header bg-info bg-opacity-10 border-0 rounded-top-3 py-2 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 text-info"><i class="fas fa-list me-1"></i> Histórico de Movimentações</h6>
                        <span class="badge bg-info bg-opacity-25 text-info">
                            <i class="fas fa-database me-1"></i> <?php echo isset($movimentacoes) ? count($movimentacoes) : 0; ?> registros
                        </span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr class="small">
                                        <th class="border-0 ps-3">Data/Hora</th>
                                        <th class="border-0">Produto</th>
                                        <th class="border-0 text-center">Tipo</th>
                                        <th class="border-0 text-center">Quantidade</th>
                                        <th class="border-0 text-center">Antes</th>
                                        <th class="border-0 text-center">Depois</th>
                                        <th class="border-0">Motivo</th>
                                        <th class="border-0">Usuário</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(isset($movimentacoes) && !empty($movimentacoes)): ?>
                                        <?php foreach($movimentacoes as $m): ?>
                                            <tr class="align-middle">
                                                <td class="ps-3 small"><?php echo date('d/m/Y H:i', strtotime($m['data_movimento'])); ?>
                                                <td class="fw-semibold small"><?php echo htmlspecialchars($m['produto_nome']); ?>
                                                <td class="text-center">
                                                    <?php
                                                    $badgeClass = '';
                                                    $badgeIcon = '';
                                                    switch($m['tipo']) {
                                                        case 'entrada': 
                                                            $badgeClass = 'success'; 
                                                            $badgeIcon = 'arrow-down';
                                                            break;
                                                        case 'saida': 
                                                            $badgeClass = 'danger'; 
                                                            $badgeIcon = 'arrow-up';
                                                            break;
                                                        case 'venda': 
                                                            $badgeClass = 'warning'; 
                                                            $badgeIcon = 'shopping-cart';
                                                            break;
                                                        case 'ajuste': 
                                                            $badgeClass = 'info'; 
                                                            $badgeIcon = 'sliders-h';
                                                            break;
                                                        default: 
                                                            $badgeClass = 'secondary';
                                                            $badgeIcon = 'circle';
                                                    }
                                                    ?>
                                                    <span class="badge bg-<?php echo $badgeClass; ?> bg-opacity-25 text-<?php echo $badgeClass; ?> px-2 py-1">
                                                        <i class="fas fa-<?php echo $badgeIcon; ?> me-1"></i> <?php echo strtoupper($m['tipo']); ?>
                                                    </span>
                                                
                                                <td class="text-center fw-semibold"><?php echo $m['quantidade']; ?>
                                                <td class="text-center small"><?php echo $m['quantidade_antes']; ?>
                                                <td class="text-center small"><?php echo $m['quantidade_depois']; ?>
                                                <td class="small"><?php echo htmlspecialchars($m['motivo'] ?? '-'); ?>
                                                <td class="small"><?php echo htmlspecialchars($m['usuario_nome'] ?? 'Sistema'); ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4">
                                                <i class="fas fa-exchange-alt fa-2x text-secondary mb-2 d-block"></i>
                                                <p class="text-secondary small mb-0">Nenhuma movimentação encontrada</p>
                                            
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
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

    <!-- Modal Ajuste Manual -->
    <div class="modal fade" id="modalAjuste" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border border-info border-opacity-25">
                <div class="modal-header bg-info bg-opacity-25 border-0 rounded-top-4 py-2">
                    <h6 class="modal-title text-info">
                        <i class="fas fa-sliders-h me-2"></i> Ajuste Manual de Estoque
                    </h6>
                    <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="ajustar.php">
                    <div class="modal-body py-3">
                        <div class="mb-2">
                            <label class="form-label text-secondary small fw-semibold mb-0">Produto <span class="text-danger">*</span></label>
                            <select name="produto_id" class="form-select form-select-sm border border-info border-opacity-25 rounded-2" required>
                                <option value="">Selecione...</option>
                                <?php if(isset($produtos)): ?>
                                    <?php foreach($produtos as $p): ?>
                                        <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['nome']); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-secondary small fw-semibold mb-0">Tipo <span class="text-danger">*</span></label>
                            <select name="tipo" class="form-select form-select-sm border border-info border-opacity-25 rounded-2" required>
                                <option value="entrada">Entrada (+)</option>
                                <option value="saida">Saída (-)</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-secondary small fw-semibold mb-0">Quantidade <span class="text-danger">*</span></label>
                            <input type="number" name="quantidade" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" min="1" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-secondary small fw-semibold mb-0">Motivo</label>
                            <textarea name="motivo" class="form-control form-control-sm border border-info border-opacity-25 rounded-2" rows="2" placeholder="Ex: Compra de fornecedor, perda, quebra, etc"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light rounded-bottom-4 py-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-2" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-info text-white btn-sm rounded-2">
                            <i class="fas fa-save me-1"></i> Ajustar Estoque
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

