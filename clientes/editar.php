<?php
require_once '../config/database.php';
$db = (new Database())->getConnection();
$stmt = $db->prepare("UPDATE clientes SET nome=?, cpf=?, telefone=?, email=?, endereco=? WHERE id=?");
$stmt->execute([$_POST['nome'], $_POST['cpf'], $_POST['telefone'], $_POST['email'], $_POST['endereco'], $_POST['id']]);
header("Location: listar.php");
?>