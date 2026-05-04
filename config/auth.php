<?php
function verificarNivelAcesso($nivel_necessario) {
    if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['nivel_acesso'])) {
        header('Location: ../login.php');
        exit();
    }
    
    if ($_SESSION['nivel_acesso'] !== $nivel_necessario && $_SESSION['nivel_acesso'] !== 'admin') {
        header('Location: ../acesso_negado.php');
        exit();
    }
}

function estaLogado() {
    return isset($_SESSION['usuario_id']);
}

function getUsuarioLogado() {
    return [
        'id' => $_SESSION['usuario_id'] ?? null,
        'nome' => $_SESSION['usuario_nome'] ?? null,
        'nivel' => $_SESSION['nivel_acesso'] ?? null
    ];
}
?>