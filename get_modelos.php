<?php
require_once __DIR__ . "/config.php";

// Assegurar que o método é GET e que temos um ID de marca
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['marca']) && !empty($_GET['marca'])) {
    $idMarca = $_GET['marca'];
    $modelos = [];
    
    try {
        $conn = connect_db();
        
        // Preparar e executar a query para buscar os modelos da marca
        $stmt = $conn->prepare("SELECT Id_Modelo, Modelo FROM modelo WHERE idMarca = ? ORDER BY Modelo");
        $stmt->execute([$idMarca]);
        
        $modelos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Retornar os modelos em formato JSON
        header('Content-Type: application/json');
        echo json_encode($modelos);
        
    } catch (PDOException $e) {
        // Em caso de erro, retornar um array vazio e registrar o erro
        header('Content-Type: application/json');
        echo json_encode([]);
        error_log("Erro ao buscar modelos: " . $e->getMessage());
    }
} else {
    // Se não houver ID de marca, retornar um array vazio
    header('Content-Type: application/json');
    echo json_encode([]);
}
