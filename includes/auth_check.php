<?php
// C:/xampp/htdocs/supermercado/includes/auth_check.php


// Caminhos absolutos usando __DIR__
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

// Verificar se usuário está logado
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Opção: Verificar nível de acesso para páginas específicas
function checkNivelAcesso($nivel_required = null) {
    if($nivel_required && $_SESSION['nivel_acesso'] != $nivel_required) {
        header("Location: ../dashboard.php");
        exit();
    }
}
?>