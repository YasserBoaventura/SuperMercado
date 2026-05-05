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
    <link rel="stylesheet" href="../css/custom.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="col-md-2 sidebar">
        <div class="nav flex-column">
            <a class="nav-link" href="../dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a class="nav-link active" href="visualizar.php"><i class="fas fa-history"></i> Logs</a>
        </div>
    </div>
    
    <div class="col-md-10 main-content">
        <h2><i class="fas fa-history"></i> Logs do Sistema</h2>
        
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>Data/Hora</th>
                                <th>Usuário</th>
                                <th>Ação</th>
                                <th>Tabela</th>
                                <th>ID Registro</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($logs as $log): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i:s', strtotime($log['data_hora'])); ?></td>
                                    <td><?php echo htmlspecialchars($log['usuario_nome'] ?? 'Sistema'); ?></td>
                                    <td>
                                        <span class="badge bg-primary"><?php echo $log['acao']; ?></span>
                                    </td>
                                    <td><?php echo $log['tabela_afetada']; ?></td>
                                    <td><?php echo $log['registro_id']; ?></td>
                                    <td><?php echo $log['ip_address']; ?></td>
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
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
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