<?php
require_once '../config/database.php';
$db = (new Database())->getConnection();

$codigo_barras = $_POST['codigo_barras'];
$nome = $_POST['nome'];
$preco_venda = str_replace(',', '.', str_replace('R$ ', '', $_POST['preco_venda']));
$quantidade_estoque = $_POST['quantidade_estoque'];
$categoria_id = $_POST['categoria_id'] ?: null;
$status = $_POST['status'];
$estoque_minimo = 5;
$preco_custo = $preco_venda * 0.6;

$stmt = $db->prepare("INSERT INTO produtos (codigo_barras, nome, preco_custo, preco_venda, quantidade_estoque, estoque_minimo, categoria_id, status) VALUES (?,?,?,?,?,?,?,?)");
$stmt->execute([$codigo_barras, $nome, $preco_custo, $preco_venda, $quantidade_estoque, $estoque_minimo, $categoria_id, $status]);

header("Location: listar.php");
exit();
?>