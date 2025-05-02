<?php
/**
 * API para obter detalhes de um veículo específico
 * 
 * Este arquivo retorna todos os detalhes de um veículo baseado no ID.
 * Utilizado para exibir informações completas ao clicar em um carro.
 */

require_once __DIR__ . "/config.php";

// Habilitar exibição de erros para depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

try {
    if(isset($_POST['car_id']) && !empty($_POST['car_id'])) {
        $link = connect_db();
        
        // Log para depuração
        error_log("Recebido ID do carro: " . $_POST['car_id']);
        
        // Consulta atualizada para corresponder à estrutura do banco de dados
        $sql = "SELECT c.*, mo.Modelo, ma.marca as Nome_marca
                FROM carros c
                LEFT JOIN modelo mo ON c.Id_Modelo = mo.Id_Modelo
                LEFT JOIN marca ma ON mo.idMarca = ma.idMarca
                WHERE c.ID_Carro = ?";
        
        error_log("SQL query: " . $sql);
        
        $stmt = $link->prepare($sql);
        $stmt->execute([$_POST['car_id']]);
        
        $carro = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($carro) {
            error_log("Carro encontrado: " . print_r($carro, true));
            
            // Buscar imagens do carro - isto já está correto conforme seu banco de dados
            $sql_imagens = "SELECT * FROM imagem WHERE ID_Carro = ?";
            $stmt_imagens = $link->prepare($sql_imagens);
            $stmt_imagens->execute([$_POST['car_id']]);
            
            $imagens = $stmt_imagens->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($imagens)) {
                $carro['imagens'] = $imagens;
                error_log("Imagens encontradas: " . count($imagens));
            } else {
                error_log("Nenhuma imagem encontrada");
            }
            
            echo json_encode($carro);
        } else {
            error_log("Veículo não encontrado para ID: " . $_POST['car_id']);
            echo json_encode(['error' => 'Veículo não encontrado']);
        }
    } else {
        error_log("ID do veículo não fornecido");
        echo json_encode(['error' => 'ID do veículo não fornecido']);
    }
} catch (Exception $e) {
    error_log("Erro na API get_car_details: " . $e->getMessage());
    echo json_encode(['error' => 'Erro ao processar a solicitação: ' . $e->getMessage()]);
}
