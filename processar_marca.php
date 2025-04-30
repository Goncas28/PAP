<?php
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $marca = trim($_POST['marca']);

    try {
        $link = connect_db();
        
        // Buscar o maior ID atual
        $sql_max_id = "SELECT MAX(idMarca) as max_id FROM marca";
        $result = $link->query($sql_max_id);
        $row = $result->fetch(PDO::FETCH_ASSOC);
        $next_id = ($row['max_id'] ?? 0) + 1;
        
        // Inserir marca com o próximo ID
        $sql = "INSERT INTO marca (idMarca, marca) VALUES (:id, :marca)";
        $stmt = $link->prepare($sql);
        $stmt->bindParam(':id', $next_id);
        $stmt->bindParam(':marca', $marca);
        
        if ($stmt->execute()) {
            header("Location: adicionar_marca.php?success=1");
        } else {
            header("Location: adicionar_marca.php?error=insert");
        }
    } catch(PDOException $e) {
        if ($e->getCode() == 23000) { // Código para violação de chave única
            header("Location: adicionar_marca.php?error=duplicate");
        } else {
            header("Location: adicionar_marca.php?error=" . urlencode($e->getMessage()));
        }
    }
}
?>
