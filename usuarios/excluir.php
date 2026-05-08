<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';

$id = $_GET['id'] ?? 0;

// Verificar se é o próprio usuário
if($id == $_SESSION['usuario_id']) {
  header('Location: listar.php?erro=Não é possível excluir seu próprio usuário');
  exit();
}

// Buscar usuário
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$id]);
$usuario = $stmt->fetch();

if (!$usuario) {
  header('Location: listar.php?erro=Usuário não encontrado');
  exit();
}

try {
  // Verificar se o usuário possui vendas
  $checkVendas = $pdo->prepare("SELECT COUNT(*) FROM vendas WHERE usuario_id = ?");
  $checkVendas->execute([$id]);
  $totalVendas = $checkVendas->fetchColumn();
  
  if($totalVendas > 0) {
      // Se tiver vendas, apenas desativar ao invés de excluir
      $stmt = $pdo->prepare("UPDATE usuarios SET ativo = 0 WHERE id = ?");
      $stmt->execute([$id]);
      
      // Log da desativação
      $dados_antigos = json_encode($usuario);
      $log = $pdo->prepare("INSERT INTO logs (usuario_id, acao, tabela_afetada, registro_id, dados_anteriores, ip_address) 
                            VALUES (?, 'desativar', 'usuarios', ?, ?, ?)");
      $log->execute([$_SESSION['usuario_id'], $id, $dados_antigos, $_SERVER['REMOTE_ADDR']]);
      
      $mensagem = "Usuário possui $totalVendas venda(s) registrada(s). O usuário foi desativado mas não excluído para manter a integridade dos dados.";
      header("Location: listar.php?msg=" . urlencode($mensagem));
  } else {
      // Se não tiver vendas, pode excluir
      $dados_antigos = json_encode($usuario);
      
      $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
      $stmt->execute([$id]);
      
      // Log da exclusão
      $log = $pdo->prepare("INSERT INTO logs (usuario_id, acao, tabela_afetada, registro_id, dados_anteriores, ip_address) 
                            VALUES (?, 'excluir', 'usuarios', ?, ?, ?)");
      $log->execute([$_SESSION['usuario_id'], $id, $dados_antigos, $_SERVER['REMOTE_ADDR']]);
      
      header("Location: listar.php?msg=Usuário excluído com sucesso!");
  }
  
} catch(PDOException $e) {
  header("Location: listar.php?erro=" . urlencode("Erro ao excluir usuário: " . $e->getMessage()));
}
exit();
?> 