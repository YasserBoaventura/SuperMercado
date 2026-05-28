

<?php

require_once 'config/database.php';

if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit();
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND ativo = 1");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();
    
    if ($usuario && password_verify($senha, $usuario['senha'])) {
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['nivel_acesso'] = $usuario['nivel_acesso'];
          
        // Registrar log
        $stmt = $pdo->prepare("INSERT INTO logs (usuario_id, acao, tabela_afetada, ip_address) VALUES (?, 'login', 'usuarios', ?)");
        $stmt->execute([$usuario['id'], $_SERVER['REMOTE_ADDR']]);
        
        header('Location: dashboard.php');
        exit();
    } else {
        $erro = 'Email ou senha inválidos!';
    }
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Supermercado</title>
    <!-- Bootstrap 5 + Icons - SEM CSS CUSTOMIZADO, apenas classes nativas -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<!-- 
    UTILIZANDO APENAS CLASSES DO BOOTSTRAP:
    - bg-info bg-opacity-10: fundo azul bem clarinho e suave
    - text-info: texto no tom azul suave
    - btn-outline-info: botão com contorno azul suave
    - border-info: bordas no tom azul
-->
<body class="bg-info bg-opacity-10" style="min-height: 100vh;">

    <!-- Container centralizado com flex do Bootstrap -->
    <div class="container d-flex align-items-center justify-content-center min-vh-100 py-5">
        <div class="row justify-content-center w-100">
            <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-4">
                
                <!-- Card principal - classes Bootstrap puras -->
                <div class="card border border-info border-opacity-25 shadow-lg rounded-4">
                    
                    <!-- Header do card com fundo azul bem suave (bg-info bg-opacity-25) -->
                    <div class="card-header bg-info bg-opacity-25 text-info text-center rounded-top-4 border-0 py-4">
                        <div class="d-flex flex-column align-items-center">
                            <div class="bg-info bg-opacity-10 rounded-circle p-3 mb-2">
                                <i class="fas fa-store fa-2x text-info"></i>
                            </div>
                            <h3 class="fw-bold mb-0 text-info">Supermercado</h3>
                            <p class="text-secondary mb-0 small">Sistema de Gestão</p>
                        </div>
                    </div>
                    
                    <!-- Corpo do card -->
                    <div class="card-body p-4 p-lg-5">
                        
                        <!-- Mensagem de erro (PHP) -->
                        <?php if(isset($erro) && !empty($erro)): ?>
                            <div class="alert alert-info alert-dismissible fade show rounded-3 d-flex align-items-center" role="alert">
                                <i class="fas fa-info-circle me-2"></i>
                                <div><?php echo htmlspecialchars($erro); ?></div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Formulário de login -->
                        <form method="POST" action="">
                            <!-- Campo Email -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold text-secondary">
                                    <i class="fas fa-envelope me-1 text-info"></i> E-mail
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border border-info border-opacity-25 rounded-start-3">
                                        <i class="fas fa-envelope text-info"></i>
                                    </span>
                                    <input type="email" name="email" class="form-control border border-info border-opacity-25 rounded-end-3 py-2" 
                                           placeholder="seu@email.com" required autofocus>
                                </div>
                                <div class="form-text text-secondary">Digite seu e-mail cadastrado</div>
                            </div>
                            
                            <!-- Campo Senha -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold text-secondary">
                                    <i class="fas fa-lock me-1 text-info"></i> Senha
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border border-info border-opacity-25 rounded-start-3">
                                        <i class="fas fa-key text-info"></i>
                                    </span>
                                    <input type="password" name="senha" class="form-control border border-info border-opacity-25 rounded-end-3 py-2" 
                                           placeholder="••••••••" required>
                                </div>
                            </div>
                            
                            <!-- Botão de Login - estilo outline azul suave -->
                            <button type="submit" class="btn btn-outline-info btn-lg w-100 rounded-3 py-2 fw-semibold mb-3">
                                <i class="fas fa-sign-in-alt me-2"></i> Entrar no sistema
                            </button>
                        </form>
                        
                       
                <!-- Linha decorativa com Bootstrap -->
                <div class="text-center mt-3">
                    <small class="text-secondary opacity-75">
                        <i class="fas fa-database me-1 text-info"></i> Sistema Integrado de Gestão
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS (necessário para o alerta dismissible) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
