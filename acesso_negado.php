<?php require_once 'includes/auth_check.php'; require_once 'includes/header.php'; ?>
<div class="card text-center">
    <div class="card-body p-5">
        <i class="fas fa-ban text-danger" style="font-size: 60px;"></i>
        <h4 class="mt-3">Acesso Negado!</h4>
        <p>Você não tem permissão para acessar esta página.</p>
        <a href="dashboard.php" class="btn btn-teal btn-sm">Voltar</a>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>