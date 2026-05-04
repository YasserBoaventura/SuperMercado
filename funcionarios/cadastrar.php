<?php
require_once './config/database.php';
$db = (new Database())->getConnection();
$stmt = $db->prepare("INSERT INTO funcionarios (nome, cpf, cargo, telefone, email, data_admissao) VALUES (?,?,?,?,?,CURDATE())");
$stmt->execute([$_POST['nome'], $_POST['cpf'], $_POST['cargo'], $_POST['telefone'], $_POST['email']]);
header("Location: listar.php");
?>