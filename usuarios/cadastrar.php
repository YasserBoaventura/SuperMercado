<?php
require_once '../config/database.php';
$db = (new Database())->getConnection();
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
$stmt = $db->prepare("INSERT INTO usuarios (username, password, nivel_acesso, status) VALUES (?,?,?,?)");
$stmt->execute([$_POST['username'], $password, $_POST['nivel_acesso'], $_POST['status']]);
header("Location: listar.php");
?>