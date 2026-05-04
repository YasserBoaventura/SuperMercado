<?php
require_once './config/database.php';
$db = (new Database())->getConnection();
if(!empty($_POST['password'])) {
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE usuarios SET username=?, password=?, nivel_acesso=?, status=? WHERE id=?");
    $stmt->execute([$_POST['username'], $password, $_POST['nivel_acesso'], $_POST['status'], $_POST['id']]);
} else {
    $stmt = $db->prepare("UPDATE usuarios SET username=?, nivel_acesso=?, status=? WHERE id=?");
    $stmt->execute([$_POST['username'], $_POST['nivel_acesso'], $_POST['status'], $_POST['id']]);
}
header("Location: listar.php");
?>