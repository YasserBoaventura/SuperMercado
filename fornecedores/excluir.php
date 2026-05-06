<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';

$id = $_GET['id'] ?? 0;

// Buscar fornecedor
$stmt = $pdo->prepare("SELECT * FROM fornecedores WHERE id = ?");
$stmt->execute([$id]);
$fornecedor = $stmt->fetch();

if (!$fornecedor) {
    header('Location: listar.php?msg=Fornecedor não encontrado&tipo=danger');
    exit();
}

// Verificar se o fornecedor possui produtos
$stmt = $pdo->prepare("SELECT COUNT(*) FROM produtos WHERE fornecedor_id = ?");
$stmt->execute([$id]);
$total_produtos = $stmt->fetchColumn();

try {
    $dados_antigos = json_encode($fornecedor);
    
    if($total_produtos > 0) {
        // Se tem produtos, apenas desativar
        $stmt = $pdo->prepare("UPDATE fornecedores SET ativo = 0 WHERE id = ?");
        $stmt->execute([$id]);
        $msg = "Fornecedor desativado com sucesso! Ele possui produtos vinculados.";
        $tipo = "warning";
    } else {
        // Se não tem produtos, excluir permanentemente
        $stmt = $pdo->prepare("DELETE FROM fornecedores WHERE id = ?");
        $stmt->execute([$id]);
        $msg = "Fornecedor excluído permanentemente com sucesso!";
        $tipo = "success";
    }
    
    // Log
    $log = $pdo->prepare("INSERT INTO logs (usuario_id, acao, tabela_afetada, registro_id, dados_anteriores, ip_address) 
                          VALUES (?, 'excluir', 'fornecedores', ?, ?, ?)");
    $log->execute([$_SESSION['usuario_id'], $id, $dados_antigos, $_SERVER['REMOTE_ADDR']]);
    
} catch(Exception $e) {
    $msg = "Erro ao excluir fornecedor: " . $e->getMessage();
    $tipo = "danger";
}

header("Location: listar.php?msg=" . urlencode($msg) . "&tipo=$tipo");
exit();
?>