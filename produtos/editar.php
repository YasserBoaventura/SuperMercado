<?php
require_once '../config/database.php';
$db = (new Database())->getConnection();

$id = $_POST['id'];
$nome = $_POST['nome'];
$preco_venda = str_replace(',', '.', str_replace('R$ ', '', $_POST['preco_venda']));
$quantidade_estoque = $_POST['quantidade_estoque'];
$categoria_id = $_POST['categoria_id'] ?: null;
$status = $_POST['status'];

$stmt = $db->prepare("UPDATE produtos SET nome=?, preco_venda=?, quantidade_estoque=?, categoria_id=?, status=? WHERE id=?");
$stmt->execute([$nome, $preco_venda, $quantidade_estoque, $categoria_id, $status, $id]);

header("Location: listar.php");
exit();
?> 