<?php
require_once '../config/database.php';

// Buscar quantidade atual do produto
$sql = "SELECT quantidade FROM produtos WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':id', $_POST['produto_id']);
$stmt->execute();
$produto = $stmt->fetch();
$quantidade_antes = $produto['quantidade'];

$produto_id = $_POST['produto_id'];
$tipo = $_POST['tipo'];
$quantidade = $_POST['quantidade'];
$motivo = $_POST['motivo'] ?? 'Ajuste manual';

// Calcular nova quantidade
if($tipo == 'entrada') {
    $nova_quantidade = $quantidade_antes + $quantidade;
} else {
    if($quantidade > $quantidade_antes) {
        die("<script>alert('Quantidade insuficiente em estoque! Estoque atual: $quantidade_antes'); window.location='listar.php';</script>");
    }
    $nova_quantidade = $quantidade_antes - $quantidade;
}

try {
    $pdo->beginTransaction();
    
    // Atualizar estoque do produto
    $sql = "UPDATE produtos SET quantidade = :nova WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':nova', $nova_quantidade);
    $stmt->bindParam(':id', $produto_id);
    $stmt->execute();
    
    // Registrar movimentação
    $sql = "INSERT INTO movimentacoes_estoque (produto_id, tipo, quantidade, quantidade_antes, quantidade_depois, motivo, usuario_id,venda_id) 
            VALUES (:produto_id, :tipo, :quantidade, :antes, :depois, :motivo, :usuario_id, :venda_id)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':produto_id', $produto_id);
    $stmt->bindParam(':tipo', $tipo);
    $stmt->bindParam(':quantidade', $quantidade);
    $stmt->bindParam(':antes', $quantidade_antes);
    $stmt->bindParam(':depois', $nova_quantidade);
    $stmt->bindParam(':motivo', $motivo);
    $stmt->bindParam(':usuario_id', $_SESSION['usuario_id']);
    $stmt->bindParam(':venda_id', $_SESSION['venda_id']  );
    $stmt->execute();
    
    $pdo->commit();
    
    echo "<script>alert('Estoque ajustado com sucesso!'); window.location='listar.php';</script>";
} catch(Exception $e) {
    $pdo->rollBack();
    echo "<script>alert('Erro ao ajustar estoque: " . $e->getMessage() . "'); window.location='listar.php';</script>";
}
?>