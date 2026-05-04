<?php
require_once './config/database.php';
$db = (new Database())->getConnection();
$stmt = $db->prepare("UPDATE fornecedores SET nome_fantasia=?, cnpj=?, telefone=?, email=? WHERE id=?");
$stmt->execute([$_POST['nome_fantasia'], $_POST['cnpj'], $_POST['telefone'], $_POST['email'], $_POST['id']]);
header("Location: listar.php");
?>