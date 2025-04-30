<?php
session_start();
require_once 'config.php';

if ($_SESSION['Tipo'] !== "A") {
    header('Location: login.php');
    exit();
}

if (isset($_GET['id'])) {
    try {
        $conn = connect_db();
        $id = (int)$_GET['id'];

        // Remove only the car record
        $sql_delete_car = "DELETE FROM carros WHERE ID_Carro = :id";
        $stmt = $conn->prepare($sql_delete_car);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Veículo removido com sucesso!";
        } else {
            $_SESSION['error_message'] = "Erro ao remover veículo.";
        }
    } catch(PDOException $e) {
        $_SESSION['error_message'] = "Erro ao remover veículo: " . $e->getMessage();
    }
}

// Redirect back to management page
header("Location: admin/gerir_veiculos.php");
exit();
?>
