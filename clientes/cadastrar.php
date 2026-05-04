<?php
require_once '../config/database.php';
$db = (new Database())->getConnection();
$stmt = $db->prepare("INSERT INTO clientes (nome, cpf, telefone, email, endereco) VALUES (?,?,?,?,?)");
$stmt->execute([$_POST['nome'], $_POST['cpf'], $_POST['telefone'], $_POST['email'], $_POST['endereco']]);
header("Location: listar.php");
?>