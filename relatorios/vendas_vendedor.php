<?php
require_once './includes/auth_check.php';



require_once './includes/header.php';
require_once './config/database.php';

$database = new Database();
$db = $database->getConnection();

$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');

$query = "SELECT 
            f.id,
            f.nome as vendedor,
            COUNT(v.id) as total_vendas,
            COALESCE(SUM(v.valor_total), 0) as valor_total,
            COALESCE(AVG(v.valor_total), 0) as ticket_medio
          FROM funcionarios f
          LEFT JOIN vendas v ON v.funcionario_id = f.id 
            AND DATE(v.data_venda) BETWEEN :data_inicio AND :data_fim
          WHERE f.cargo LIKE '%vendedor%' OR f.id IN (SELECT DISTINCT funcionario_id FROM vendas)
          GROUP BY f.id
          ORDER BY valor_total DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':data_inicio', $data_inicio);
$stmt->bindParam(':data_fim', $data_fim);
$stmt->execute();
?>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h4>Relatório de Vendas por Vendedor</h4>
            </div>
            <div class="card-body">
                <form method="GET" class="row mb-4">
                    <div class="col-md-3">
                        <label>Data Início</label>
                        <input type="date" name="data_inicio" class="form-control" value="<?php echo $data_inicio; ?>">
                    </div>
                    <div class="col-md-3">
                        <label>Data Fim</label>
                        <input type="date" name="data_fim" class="form-control" value="<?php echo $data_fim; ?>">
                    </div>
                    <div class="col-md-2">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary d-block">Filtrar</button>
                    </div>
                </form>
                
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Vendedor</th>
                                <th>Total de Vendas</th>
                                <th>Valor Total</th>
                                <th>Ticket Médio</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td><?php echo $row['vendedor']; ?></td>
                                <td><?php echo $row['total_vendas']; ?></td>
                                <td>R$ <?php echo number_format($row['valor_total'], 2, ',', '.'); ?></td>
                                <td>R$ <?php echo number_format($row['ticket_medio'], 2, ',', '.'); ?></td>
                                <td>
                                    <a href="vendas_detalhadas.php?vendedor_id=<?php echo $row['id']; ?>&data_inicio=<?php echo $data_inicio; ?>&data_fim=<?php echo $data_fim; ?>" 
                                       class="btn btn-sm btn-info">
                                        <i class="fas fa-chart-line"></i> Detalhes
                                    </a>
                                 </td>
                             </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>