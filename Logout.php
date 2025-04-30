<?php
// Iniciar sessão (se ainda não estiver iniciada)
if (!isset($_SESSION)) {
    session_start();
}

// Limpar todas as variáveis de sessão
$_SESSION = array();

// Destruir a sessão
session_destroy();

// Redirecionar para a página de login
header("location: login.php");
exit;
?>
