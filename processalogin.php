<?php
session_start();
require_once __DIR__ . "/config.php";

// Receber os dados do formulário
$email = $_POST['email']; // ou username, conforme seu sistema
$password = $_POST['password'];

try {
    $conn = connect_db();
    
    // Verificar credenciais 
    $sql = "SELECT * FROM usuarios WHERE email = :email"; // ajuste nome da tabela se necessário
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verificar senha (ajuste conforme sua implementação)
        if(password_verify($password, $user['password']) || $password === $user['password']) {
            // Definir variáveis de sessão básicas
            $_SESSION['user'] = $user['email']; // ou username/nome conforme sua preferência  
            $_SESSION['user_id'] = $user['id'];
            
            // IMPORTANTE: Definir a flag de admin corretamente
            if($user['tipo'] == 'admin' || $user['is_admin'] == 1) { // ajuste conforme sua tabela
                $_SESSION['is_admin'] = true;
            } else {
                $_SESSION['is_admin'] = false;
            }
            
            // Debug temporário - remover depois de testar
            // echo "Debug: is_admin = " . $_SESSION['is_admin'];
            // exit;
            
            // Redirecionar para a página principal
            header("Location: index.php");
            exit();
        } else {
            $_SESSION['error'] = "Senha incorreta";
            header("Location: Login.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Usuário não encontrado";
        header("Location: Login.php");
        exit();
    }
} catch(PDOException $e) {
    $_SESSION['error'] = "Erro ao fazer login: " . $e->getMessage();
    header("Location: Login.php");
    exit();
}
?>