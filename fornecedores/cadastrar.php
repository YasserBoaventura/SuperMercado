<?php
require_once './config/database.php';
$db = (new Database())->getConnection();
$stmt = $db->prepare("INSERT INTO fornecedores (nome_fantasia, cnpj, telefone, email) VALUES (?,?,?,?)");
$stmt->execute([$_POST['nome_fantasia'], $_POST['cnpj'], $_POST['telefone'], $_POST['email']]);
header("Location: listar.php");
?>