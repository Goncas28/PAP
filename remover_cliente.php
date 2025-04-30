<?php
session_start();
require_once __DIR__ . "/config.php";

// Verifica se o usuário está logado
if (!isset($_SESSION['username'])) {
    header("Location: Login.php");
    exit;
}

if (isset($_GET['email'])) {
    try {
        $link = connect_db();
        $sql = "DELETE FROM clientes WHERE Email = :email";
        $stmt = $link->prepare($sql);
        $stmt->bindParam(':email', $_GET['email'], PDO::PARAM_STR);
        $stmt->execute();
        
        header("Location: Adminbackofice1.php");
    } catch(PDOException $e) {
        die("Erro ao remover cliente: " . $e->getMessage());
    }
    unset($link);
}
?>
