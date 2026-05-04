<?php
require_once './includes/auth_check.php';
require_once './includes/header.php';
$clientes = $db->query("SELECT * FROM clientes ORDER BY nome")->fetchAll();
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Clientes</span>
        <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalCliente" onclick="limparForm()">
            <i class="fas fa-plus"></i> Novo
        </button>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm">
            <thead><tr><th>Nome</th><th>CPF</th><th>Telefone</th><th style="width:60px"></th></tr></thead>
            <tbody>
                <?php foreach($clientes as $c): ?>
                <tr>
                    <td><?php echo $c['nome']; ?></td>
                    <td><?php echo $c['cpf']; ?></td>
                    <td><?php echo $c['telefone']; ?></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="editarCliente(<?php echo htmlspecialchars(json_encode($c)); ?>)"><i class="fas fa-edit"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalCliente" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Cliente</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form id="formCliente" method="POST" action="salvar.php">
                <div class="modal-body">
                    <input type="hidden" name="id" id="cliente_id">
                    <div class="mb-2"><label class="form-label">Nome</label><input type="text" name="nome" id="nome" class="form-control form-control-sm" required></div>
                    <div class="mb-2"><label class="form-label">CPF</label><input type="text" name="cpf" id="cpf" class="form-control form-control-sm" required></div>
                    <div class="mb-2"><label class="form-label">Telefone</label><input type="text" name="telefone" id="telefone" class="form-control form-control-sm"></div>
                    <div class="mb-2"><label class="form-label">Email</label><input type="email" name="email" id="email" class="form-control form-control-sm"></div>
                    <div class="mb-2"><label class="form-label">Endereço</label><input type="text" name="endereco" id="endereco" class="form-control form-control-sm"></div>
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
function limparForm() { document.getElementById('formCliente').reset(); document.getElementById('cliente_id').value = ''; document.getElementById('formCliente').action = 'salvar.php'; }
function editarCliente(c) {
    document.getElementById('cliente_id').value = c.id;
    document.getElementById('nome').value = c.nome;
    document.getElementById('cpf').value = c.cpf;
    document.getElementById('telefone').value = c.telefone;
    document.getElementById('email').value = c.email;
    document.getElementById('endereco').value = c.endereco;
    document.getElementById('formCliente').action = 'editar.php';
    new bootstrap.Modal(document.getElementById('modalCliente')).show();
}
</script>

<?php require_once '../includes/footer.php'; ?>