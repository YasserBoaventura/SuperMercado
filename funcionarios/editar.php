<?php
require_once './config/database.php';
$db = (new Database())->getConnection();
$stmt = $db->prepare("UPDATE funcionarios SET nome=?, cpf=?, cargo=?, telefone=?, email=? WHERE id=?");
$stmt->execute([$_POST['nome'], $_POST['cpf'], $_POST['cargo'], $_POST['telefone'], $_POST['email'], $_POST['id']]);
header("Location: listar.php");
?>