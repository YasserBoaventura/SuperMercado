<?php
require_once './includes/auth_check.php';
if(!$auth->isAdmin()) { header("Location: ../acesso_negado.php"); exit(); }
require_once './includes/header.php';
$usuarios = $db->query("SELECT u.*, f.nome as funcionario FROM usuarios u LEFT JOIN funcionarios f ON u.funcionario_id=f.id")->fetchAll();
?>

<div class="card">
    <div class="card-header d-flex justify-content-between">
        <span>Usuários</span>
        <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalUsuario" onclick="limparForm()">Novo</button>
    </div> 
    <div class="card-body p-0">
        <table class="table table-sm">
            <thead><tr><th>Usuário</th><th>Funcionário</th><th>Nível</th><th>Status</th><th style="width:60px"></th></tr></thead>
            <tbody>
                <?php foreach($usuarios as $u): ?>
                <tr><td><?php echo $u['username']; ?></td><td><?php echo $u['funcionario']; ?></td><td><span class="badge bg-<?php echo $u['nivel_acesso']=='admin'?'danger':'info'; ?>"><?php echo $u['nivel_acesso']; ?></span></td><td><span class="badge bg-<?php echo $u['status']=='ativo'?'success':'secondary'; ?>"><?php echo $u['status']; ?></span></td><td><button class="btn btn-sm btn-outline-primary" onclick="editarUsuario(<?php echo htmlspecialchars(json_encode($u)); ?>)"><i class="fas fa-edit"></i></button></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalUsuario" tabindex="-1">
    <div class="modal-dialog modal-sm"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Usuário</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form method="POST" action="salvar.php">
            <div class="modal-body">
                <input type="hidden" name="id" id="user_id">
                <div class="mb-2"><label class="form-label">Usuário</label><input type="text" name="username" id="username" class="form-control form-control-sm" required></div>
                <div class="mb-2"><label class="form-label">Senha</label><input type="password" name="password" id="password" class="form-control form-control-sm" <?php echo !isset($_GET['edit'])?'required':''; ?>></div>
                <div class="mb-2"><label class="form-label">Nível</label><select name="nivel_acesso" id="nivel_acesso" class="form-select form-select-sm"><option value="vendedor">Vendedor</option><option value="admin">Admin</option></select></div>
                <div class="mb-2"><label class="form-label">Status</label><select name="status" id="status" class="form-select form-select-sm"><option value="ativo">Ativo</option><option value="inativo">Inativo</option></select></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-teal btn-sm">Salvar</button></div>
        </form>
    </div></div>
</div>

<script>
function limparForm() { document.querySelector('form').reset(); document.getElementById('user_id').value = ''; document.querySelector('form').action = 'salvar.php'; document.getElementById('password').required = true; }
function editarUsuario(u) {
    document.getElementById('user_id').value = u.id;
    document.getElementById('username').value = u.username;
    document.getElementById('nivel_acesso').value = u.nivel_acesso;
    document.getElementById('status').value = u.status;
    document.getElementById('password').required = false;
    document.querySelector('form').action = 'editar.php';
    new bootstrap.Modal(document.getElementById('modalUsuario')).show();
}
</script>

<?php require_once '../includes/footer.php'; ?>