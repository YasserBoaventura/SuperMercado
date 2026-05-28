<?php

require_once '../config/database.php';
require_once '../config/auth.php';
verificarNivelAcesso('admin');

$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$total = $pdo->query("SELECT COUNT(*) FROM logs")->fetchColumn();
$totalPages = ceil($total / $limit);

$logs = $pdo->query("
    SELECT l.*, u.nome as usuario_nome
    FROM logs l
    LEFT JOIN usuarios u ON l.usuario_id = u.id
    ORDER BY l.data_hora DESC
    LIMIT $offset, $limit
")->fetchAll();

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Logs do Sistema - Supermercado</title>
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
                            <a class="nav-link active bg-info bg-opacity-25 text-info fw-semibold rounded-3" href="visualizar.php">
                                <i class="fas fa-history me-2"></i> Logs
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="mb-3">
                    <h2 class="fw-bold text-info fs-4">
                        <i class="fas fa-history me-2"></i> Logs do Sistema
                    </h2>
                    <p class="text-secondary small">Histórico de atividades realizadas no sistema</p>
                </div>
                
                <!-- Tabela de Logs -->
                <div class="card border border-info border-opacity-25 rounded-3 shadow-sm">
                    <div class="card-header bg-info bg-opacity-10 border-0 rounded-top-3 py-2 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 text-info"><i class="fas fa-list me-1"></i> Registros de Atividades</h6>
                        <span class="badge bg-info bg-opacity-25 text-info">
                            <i class="fas fa-database me-1"></i> <?php echo isset($total) ? $total : 0; ?> registros
                        </span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr class="small">
                                        <th class="border-0 ps-3">Data/Hora</th>
                                        <th class="border-0">Usuário</th>
                                        <th class="border-0 text-center">Ação</th>
                                        <th class="border-0">Tabela</th>
                                        <th class="border-0 text-center">ID</th>
                                        <th class="border-0">IP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(isset($logs) && !empty($logs)): ?>
                                        <?php foreach($logs as $log): ?>
                                            <tr class="align-middle">
                                                <td class="ps-3 small"><?php echo date('d/m/Y H:i:s', strtotime($log['data_hora'])); ?>
                                                <td class="small">
                                                    <span class="fw-semibold"><?php echo htmlspecialchars($log['usuario_nome'] ?? 'Sistema'); ?></span>
                                                
                                                <td class="text-center">
                                                    <?php 
                                                    $acaoClass = '';
                                                    $acaoIcon = '';
                                                    switch($log['acao']) {
                                                        case 'login':
                                                            $acaoClass = 'info';
                                                            $acaoIcon = 'sign-in-alt';
                                                            break;
                                                        case 'cadastrar':
                                                            $acaoClass = 'success';
                                                            $acaoIcon = 'plus-circle';
                                                            break;
                                                        case 'editar':
                                                            $acaoClass = 'warning';
                                                            $acaoIcon = 'edit';
                                                            break;
                                                        case 'excluir':
                                                            $acaoClass = 'danger';
                                                            $acaoIcon = 'trash-alt';
                                                            break;
                                                        default:
                                                            $acaoClass = 'secondary';
                                                            $acaoIcon = 'info-circle';
                                                    }
                                                    ?>
                                                    <span class="badge bg-<?php echo $acaoClass; ?> bg-opacity-25 text-<?php echo $acaoClass; ?> px-2 py-1">
                                                        <i class="fas fa-<?php echo $acaoIcon; ?> me-1"></i> <?php echo ucfirst($log['acao']); ?>
                                                    </span>
                                                
                                                <td class="small">
                                                    <code class="text-info"><?php echo $log['tabela_afetada']; ?></code>
                                                
                                                <td class="text-center small"><?php echo $log['registro_id']; ?>
                                                <td class="small">
                                                    <code><?php echo $log['ip_address']; ?></code>
                                                
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4">
                                                <i class="fas fa-history fa-2x text-secondary mb-2 d-block"></i>
                                                <p class="text-secondary small mb-0">Nenhum registro de log encontrado</p>
                                            
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
                                               href="?page=<?php echo $i; ?>">
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

