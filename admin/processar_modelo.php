<?php
require_once '../config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $modelo = trim($_POST['modelo']);
    $id_marca = $_POST['id_marca'];

    try {
        $link = connect_db();
        
        // Buscar o maior ID atual dos modelos
        $sql_max_id = "SELECT MAX(Id_Modelo) as max_id FROM modelo";
        $result = $link->query($sql_max_id);
        $row = $result->fetch(PDO::FETCH_ASSOC);
        $next_id = ($row['max_id'] ?? 0) + 1;
        
        // Inserir modelo com o próximo ID
        $sql = "INSERT INTO modelo (Id_Modelo, Modelo, idMarca) VALUES (:id, :modelo, :id_marca)";
        $stmt = $link->prepare($sql);
        $stmt->bindParam(':id', $next_id);
        $stmt->bindParam(':modelo', $modelo);
        $stmt->bindParam(':id_marca', $id_marca);
        
        if ($stmt->execute()) {
            header("Location: adicionar_modelo.php?success=1");
        } else {
            header("Location: adicionar_modelo.php?error=insert");
        }
    } catch(PDOException $e) {
        if ($e->getCode() == 23000) { // Código para violação de chave única
            header("Location: adicionar_modelo.php?error=duplicate");
        } else {
            header("Location: adicionar_modelo.php?error=" . urlencode($e->getMessage()));
        }
    }
}
?>
