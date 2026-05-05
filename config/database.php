<?php
// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'supermercado_db');
define('DB_USER', 'root');
define('DB_PASS', 'Boaventura');

try { 
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}
 
// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>