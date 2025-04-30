<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
es
if (!isset($_SESSION)) {
    session_start();PTIONS');tipo"] !== 'A') && 
};!== "A")) {
t-Type: application/json');
// Verificar se o usuário está logado e é um administrador/ Handle preflight requests   echo json_encode(['error' => 'Não autorizado']);
if (!isset($_SESSION["utilizador"]) || $_SESSION["loggedin"] !== true || $_SESSION["tipo"] !== 'A') {if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {    exit;
    header('Content-Type: application/json');');
    echo json_encode(['error' => 'Não autorizado']);
    exit;}require_once('config.php');
}
 administrador
require_once('config.php');SION["loggedin"] !== true || $_SESSION["tipo"] !== 'A') {
$conn = connect_db();
rizado']);ão
// Verificar se a requisição é POSTexit;$json_data = file_get_contents('php://input');
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Obter os dados do corpo da requisição
    $json_data = file_get_contents('php://input');idos
    $data = json_decode($json_data, true);a['status'])) {
    ta = $data['id_visita'];
    // Verificar se os dados necessários foram fornecidoso é POSTatus'];
    if (isset($data['id_visita']) && isset($data['status'])) {
        $id_visita = $data['id_visita'];
        $status = $data['status'];
        decode($json_data, true);r('Content-Type: application/json');
        // Validar o statusho json_encode(['error' => 'Status inválido']);
        if (!in_array($status, ['Não Aprovado', 'Aprovado'])) {erificar se os dados necessários foram fornecidos    exit;
            header('Content-Type: application/json');($data['id_visita']) && isset($data['status'])) {
            echo json_encode(['error' => 'Status inválido']);
            exit;
        }
        alidar o status$sql = "UPDATE marcarvisita SET status = ? WHERE Id_Visita = ?";
        try {o'])) {
            // Atualizar o status da visita
            $sql = "UPDATE marcarvisita SET status = ? WHERE Id_Visita = ?";
            $stmt = $conn->prepare($sql);'Content-Type: application/json');
            
            if ($stmt->execute([$status, $id_visita])) {
                header('Content-Type: application/json');   header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Status atualizado com sucesso']);a visitaerror' => 'Erro ao atualizar status']);
            } else {? WHERE Id_Visita = ?";
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Erro ao atualizar status']);      header('Content-Type: application/json');
            }if ($stmt->execute([$status, $id_visita])) {echo json_encode(['error' => 'Erro ao atualizar status: ' . $e->getMessage()]);
        } catch (PDOException $e) {/json');
            header('Content-Type: application/json');ge' => 'Status atualizado com sucesso']);
            echo json_encode(['error' => 'Erro ao atualizar status: ' . $e->getMessage()]);       } else {   header('Content-Type: application/json');
        }        header('Content-Type: application/json');echo json_encode(['error' => 'Dados incompletos']);
    } else {'Erro ao atualizar status']);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Dados incompletos']);       } catch (PDOException $e) {   header('Content-Type: application/json');
    }          header('Content-Type: application/json');  echo json_encode(['error' => 'Método não permitido']);
} else {            echo json_encode(['error' => 'Erro ao atualizar status: ' . $e->getMessage()]);}











?>}    echo json_encode(['error' => 'Método não permitido']);    header('Content-Type: application/json');} else {?>}    echo json_encode(['error' => 'Método não permitido']);    header('Content-Type: application/json');




    }        echo json_encode(['error' => 'Dados incompletos']);        header('Content-Type: application/json');    } else {        }?>
