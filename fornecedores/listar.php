<?php
require_once './includes/auth_check.php';
if(!$auth->isAdmin()) { header("Location: ../acesso_negado.php"); exit(); }
require_once './includes/header.php';
$fornecedores = $db->query("SELECT * FROM fornecedores ORDER BY nome_fantasia")->fetchAll();
?>

<div class="card">
    <div class="card-header d-flex justify-content-between">
        <span>Fornecedores</span>
        <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalFornecedor" onclick="limparForm()">Novo</button>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm">
            <thead><tr><th>Nome</th><th>CNPJ</th><th>Telefone</th><th style="width:60px"></th></tr></thead>
            <tbody>
                <?php foreach($fornecedores as $f): ?>
                <tr><td><?php echo $f['nome_fantasia']; ?></td><td><?php echo $f['cnpj']; ?></td><td><?php echo $f['telefone']; ?></td><td><button class="btn btn-sm btn-outline-primary" onclick="editarFornecedor(<?php echo htmlspecialchars(json_encode($f)); ?>)"><i class="fas fa-edit"></i></button></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalFornecedor" tabindex="-1">
    <div class="modal-dialog modal-sm"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Fornecedor</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form method="POST" action="salvar.php">
            <div class="modal-body">
                <input type="hidden" name="id" id="for_id">
                <div class="mb-2"><label class="form-label">Nome Fantasia</label><input type="text" name="nome_fantasia" id="nome_fantasia" class="form-control form-control-sm" required></div>
                <div class="mb-2"><label class="form-label">CNPJ</label><input type="text" name="cnpj" id="cnpj" class="form-control form-control-sm" required></div>
                <div class="mb-2"><label class="form-label">Telefone</label><input type="text" name="telefone" id="telefone" class="form-control form-control-sm"></div>
                <div class="mb-2"><label class="form-label">Email</label><input type="email" name="email" id="email" class="form-control form-control-sm"></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-teal btn-sm">Salvar</button></div>
        </form>
    </div></div>
</div>

<script>
function limparForm() { document.querySelector('form').reset(); document.getElementById('for_id').value = ''; document.querySelector('form').action = 'salvar.php'; }
function editarFornecedor(f) {
    document.getElementById('for_id').value = f.id;
    document.getElementById('nome_fantasia').value = f.nome_fantasia;
    document.getElementById('cnpj').value = f.cnpj;
    document.getElementById('telefone').value = f.telefone;
    document.getElementById('email').value = f.email;
    document.querySelector('form').action = 'editar.php';
    new bootstrap.Modal(document.getElementById('modalFornecedor')).show();
}
</script>

<?php require_once '../includes/footer.php'; ?>