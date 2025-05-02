<?php
/**
 * API para obter visitas do usuário logado
 * 
 * Este arquivo retorna as visitas do usuário atual em formato JSON.
 * É usado para atualizar a interface via AJAX.
 */

if (!isset($_SESSION)) {
    session_start();
}

// Verificar se o usuário está logado
if (!isset($_SESSION["utilizador"]) || $_SESSION["loggedin"] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

require_once('config.php');
$conn = connect_db();

try {
    $email = $_SESSION["utilizador"];
    
    // Buscar visitas do usuário atual
    $sql = "SELECT mv.Id_Visita, mv.DataVisita, mv.Hora_Visita, mv.Observacoes, mv.status,
           m.marca, mo.Modelo, c.Ano 
           FROM marcarvisita mv
           JOIN carros c ON mv.ID_Carro = c.ID_Carro
           JOIN modelo mo ON c.Id_Modelo = mo.Id_Modelo
           JOIN marca m ON mo.idMarca = m.idMarca
           WHERE mv.email = ?
           ORDER BY mv.DataVisita DESC, mv.Hora_Visita DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$email]);
    $visitas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Retornar como JSON
    header('Content-Type: application/json');
    echo json_encode($visitas);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Erro ao buscar visitas: ' . $e->getMessage()]);
}
?>
