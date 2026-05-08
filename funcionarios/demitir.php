<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM funcionarios WHERE id = ?");
$stmt->execute([$id]);
$funcionario = $stmt->fetch();

if (!$funcionario) {
    header('Location: listar.php');
    exit();
}

$dados_antigos = json_encode($funcionario);

$stmt = $pdo->prepare("UPDATE funcionarios SET status = 'inativo', data_demissao = CURDATE() WHERE id = ?");
$stmt->execute([$id]);

// Log
$log = $pdo->prepare("INSERT INTO logs (usuario_id, acao, tabela_afetada, registro_id, dados_anteriores, ip_address) 
                      VALUES (?, 'demitir', 'funcionarios', ?, ?, ?)");
$log->execute([$_SESSION['usuario_id'], $id, $dados_antigos, $_SERVER['REMOTE_ADDR']]);

header('Location: listar.php?msg=Funcionário demitido com sucesso!');
exit();
?>