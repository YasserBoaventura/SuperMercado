<?php
session_start();
if(isset($_SESSION['user_id'])) { header("Location: dashboard.php"); exit(); }
require_once 'config/database.php';
require_once 'config/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db); 
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if($auth->login($_POST['username'], $_POST['password'])) {
        header("Location: dashboard.php");
        exit();
    } else { $error = 'Usuário/senha inválidos!'; }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Supermercado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #008080 0%, #20B2AA 100%); height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; }
        .login-box { background: white; border-radius: 10px; padding: 25px; width: 100%; max-width: 350px; box-shadow: 0 5px 20px rgba(0,0,0,0.2); }
        .login-header { text-align: center; margin-bottom: 20px; }
        .login-header i { font-size: 50px; color: #008080; }
        .login-header h3 { font-size: 20px; margin-top: 10px; }
        .form-control { font-size: 14px; padding: 8px 12px; }
        .btn-login { background: #008080; width: 100%; padding: 8px; font-size: 14px; border: none; }
        .btn-login:hover { background: #20B2AA; }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="login-header">
            <i class="fas fa-store"></i>
            <h3>Sistema Supermercado</h3>
            <small class="text-muted">Faça login para continuar</small>
        </div>
        <?php if($error): ?>
            <div class="alert alert-danger alert-sm" style="padding: 8px; font-size: 12px;"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-2">
                <label class="form-label">Usuário</label>
                <input type="text" name="username" class="form-control" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label">Senha</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary btn-login">Entrar</button>
        </form>
        <hr class="my-3">
        <div class="text-center">
            <small class="text-muted">admin/admin123 | vendedor/vendedor123</small>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>