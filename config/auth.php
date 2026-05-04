<?php


class Auth {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function login($username, $password) {
        $query = "SELECT u.*, f.nome as funcionario_nome FROM usuarios u LEFT JOIN funcionarios f ON u.funcionario_id = f.id WHERE u.username = :username AND u.status = 'ativo'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if(password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nivel_acesso'] = $user['nivel_acesso'];
                $_SESSION['funcionario_id'] = $user['funcionario_id'];
                $_SESSION['funcionario_nome'] = $user['funcionario_nome'];
                return true;
            }
        }
        return false;
    }
    
    public function logout() {
        session_destroy();
        return true;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function isAdmin() {
        return isset($_SESSION['nivel_acesso']) && $_SESSION['nivel_acesso'] == 'admin';
    }
}
?>