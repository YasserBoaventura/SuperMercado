<?php
require_once './includes/auth_check.php';
require_once './includes/header.php';

$produtos = $db->query("SELECT p.*, c.nome as categoria FROM produtos p LEFT JOIN categorias c ON p.categoria_id=c.id ORDER BY p.nome")->fetchAll();
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Produtos</span>
        <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalProduto" onclick="limparForm()">
            <i class="fas fa-plus"></i> Novo
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr><th>Código</th><th>Produto</th><th>Preço</th><th>Estoque</th><th>Status</th><th style="width:80px"></th></tr>
                </thead>
                <tbody>
                    <?php foreach($produtos as $p): ?>
                    <tr>
                        <td><?php echo $p['codigo_barras']; ?></td>
                        <td><?php echo $p['nome']; ?><br><small class="text-muted"><?php echo $p['categoria']; ?></small></td>
                        <td>R$ <?php echo number_format($p['preco_venda'],2,',','.'); ?></td>
                        <td class="<?php echo $p['quantidade_estoque'] <= $p['estoque_minimo'] ? 'text-danger' : ''; ?>"><?php echo $p['quantidade_estoque']; ?></td>
                        <td><span class="badge bg-<?php echo $p['status']=='ativo'?'success':'secondary'; ?>"><?php echo $p['status']; ?></span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="editarProduto(<?php echo htmlspecialchars(json_encode($p)); ?>)"><i class="fas fa-edit"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Produto -->
<div class="modal fade" id="modalProduto" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Produto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formProduto" method="POST" action="salvar.php">
                <div class="modal-body">
                    <input type="hidden" name="id" id="produto_id">
                    <div class="mb-2">
                        <label class="form-label">Código Barras</label>
                        <input type="text" name="codigo_barras" id="codigo_barras" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Nome</label>
                        <input type="text" name="nome" id="nome" class="form-control form-control-sm" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-2">
                            <label class="form-label">Preço Venda</label>
                            <input type="text" name="preco_venda" id="preco_venda" class="form-control form-control-sm money" required>
                        </div>
                        <div class="col-6 mb-2">
                            <label class="form-label">Estoque</label>
                            <input type="number" name="quantidade_estoque" id="quantidade_estoque" class="form-control form-control-sm" value="0">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Categoria</label>
                        <select name="categoria_id" id="categoria_id" class="form-select form-select-sm">
                            <option value="">Selecione</option>
                            <?php $cats = $db->query("SELECT * FROM categorias ORDER BY nome")->fetchAll(); foreach($cats as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo $c['nome']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Status</label>
                        <select name="status" id="status" class="form-select form-select-sm">
                            <option value="ativo">Ativo</option>
                            <option value="inativo">Inativo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-teal btn-sm">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function limparForm() {
    document.getElementById('formProduto').reset();
    document.getElementById('produto_id').value = '';
    document.getElementById('formProduto').action = 'salvar.php';
}

function editarProduto(p) {
    document.getElementById('produto_id').value = p.id;
    document.getElementById('codigo_barras').value = p.codigo_barras;
    document.getElementById('nome').value = p.nome;
    document.getElementById('preco_venda').value = p.preco_venda;
    document.getElementById('quantidade_estoque').value = p.quantidade_estoque;
    document.getElementById('categoria_id').value = p.categoria_id;
    document.getElementById('status').value = p.status;
    document.getElementById('formProduto').action = 'editar.php';
    new bootstrap.Modal(document.getElementById('modalProduto')).show();
}

document.querySelectorAll('.money').forEach(el => {
    el.addEventListener('input', e => {
        let v = e.target.value.replace(/\D/g,'');
        v = (v/100).toFixed(2).replace('.',',');
        e.target.value = 'R$ ' + v;
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>