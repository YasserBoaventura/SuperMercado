<?php
require_once './includes/auth_check.php';
if(!$auth->isAdmin()) { header("Location: ../acesso_negado.php"); exit(); }
require_once './includes/header.php';
$funcionarios = $db->query("SELECT * FROM funcionarios ORDER BY nome")->fetchAll();
?>

<div class="card">
    <div class="card-header d-flex justify-content-between">
        <span>Funcionários</span>
        <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalFuncionario" onclick="limparForm()">Novo</button>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm">
            <thead><tr><th>Nome</th><th>Cargo</th><th>Telefone</th><th style="width:60px"></th></tr></thead>
            <tbody>
                <?php foreach($funcionarios as $f): ?>
                <tr><td><?php echo $f['nome']; ?></td><td><?php echo $f['cargo']; ?></td><td><?php echo $f['telefone']; ?></td><td><button class="btn btn-sm btn-outline-primary" onclick="editarFuncionario(<?php echo htmlspecialchars(json_encode($f)); ?>)"><i class="fas fa-edit"></i></button></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalFuncionario" tabindex="-1">
    <div class="modal-dialog modal-sm"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Funcionário</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form method="POST" action="salvar.php">
            <div class="modal-body">
                <input type="hidden" name="id" id="func_id">
                <div class="mb-2"><label class="form-label">Nome</label><input type="text" name="nome" id="nome" class="form-control form-control-sm" required></div>
                <div class="mb-2"><label class="form-label">CPF</label><input type="text" name="cpf" id="cpf" class="form-control form-control-sm" required></div>
                <div class="mb-2"><label class="form-label">Cargo</label><input type="text" name="cargo" id="cargo" class="form-control form-control-sm"></div>
                <div class="mb-2"><label class="form-label">Telefone</label><input type="text" name="telefone" id="telefone" class="form-control form-control-sm"></div>
                <div class="mb-2"><label class="form-label">Email</label><input type="email" name="email" id="email" class="form-control form-control-sm"></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-teal btn-sm">Salvar</button></div>
        </form>
    </div></div>
</div>

<script>
function limparForm() { document.querySelector('form').reset(); document.getElementById('func_id').value = ''; document.querySelector('form').action = 'salvar.php'; }
function editarFuncionario(f) {
    document.getElementById('func_id').value = f.id;
    document.getElementById('nome').value = f.nome;
    document.getElementById('cpf').value = f.cpf;
    document.getElementById('cargo').value = f.cargo;
    document.getElementById('telefone').value = f.telefone;
    document.getElementById('email').value = f.email;
    document.querySelector('form').action = 'editar.php';
    new bootstrap.Modal(document.getElementById('modalFuncionario')).show();
}
</script>

<?php require_once '../includes/footer.php'; ?>