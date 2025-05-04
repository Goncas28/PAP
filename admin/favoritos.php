<?php
// Iniciar sessão para verificar se o usuário está logado
if (!isset($_SESSION)) {
    session_start();
}

// Verificar se o usuário está logado (verificando ambas as variáveis possíveis)
if (!(isset($_SESSION['email']) || (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true))) {
    // Usuário não está logado, retornar erro
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Usuário não logado']);
    exit;
}

// Obter o email do usuário conforme a variável disponível
$userEmail = isset($_SESSION['email']) ? $_SESSION['email'] : $_SESSION['utilizador'];

// Verificar se os parâmetros necessários foram enviados
if (!isset($_POST['action']) || !isset($_POST['car_id'])) {
    // Parâmetros insuficientes, retornar erro
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Parâmetros insuficientes']);
    exit;
}

// Obter os parâmetros
$action = $_POST['action'];
$carId = (int)$_POST['car_id'];

// Verificar se o ID do carro é válido
if ($carId <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID de carro inválido']);
    exit;
}

// Conectar ao banco de dados
require_once "../config.php";

try {
    $conn = connect_db();
    
    // Verificar se a tabela favoritos existe
    $tablesResult = $conn->query("SHOW TABLES LIKE 'favoritos'");
    if ($tablesResult->rowCount() === 0) {
        // Criar a tabela se não existir
        $createTableSQL = "CREATE TABLE favoritos (
            id_favorito INT AUTO_INCREMENT PRIMARY KEY,
            Email VARCHAR(100) NOT NULL,
            ID_Carro INT NOT NULL,
            FOREIGN KEY (Email) REFERENCES clientes(Email) ON DELETE CASCADE ON UPDATE CASCADE,
            FOREIGN KEY (ID_Carro) REFERENCES carros(ID_Carro) ON DELETE CASCADE ON UPDATE CASCADE,
            UNIQUE KEY (Email, ID_Carro)
        )";
        $conn->exec($createTableSQL);
    }
    
    // Verificar ação
    if ($action === 'add') {
        // Verificar se o favorito já existe
        $stmt = $conn->prepare("SELECT * FROM favoritos WHERE Email = :email AND ID_Carro = :car_id");
        $stmt->bindParam(':email', $userEmail, PDO::PARAM_STR);
        $stmt->bindParam(':car_id', $carId, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            // Favorito já existe, retornar sucesso
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Carro já está nos favoritos']);
            exit;
        }
        
        // Adicionar aos favoritos
        $stmt = $conn->prepare("INSERT INTO favoritos (Email, ID_Carro) VALUES (:email, :car_id)");
        $stmt->bindParam(':email', $userEmail, PDO::PARAM_STR);
        $stmt->bindParam(':car_id', $carId, PDO::PARAM_INT);
        $success = $stmt->execute();
        
        if ($success) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Carro adicionado aos favoritos']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erro ao adicionar aos favoritos']);
        }
    } elseif ($action === 'remove') {
        // Remover dos favoritos
        $stmt = $conn->prepare("DELETE FROM favoritos WHERE Email = :email AND ID_Carro = :car_id");
        $stmt->bindParam(':email', $userEmail, PDO::PARAM_STR);
        $stmt->bindParam(':car_id', $carId, PDO::PARAM_INT);
        $success = $stmt->execute();
        
        if ($success) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Carro removido dos favoritos']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erro ao remover dos favoritos']);
        }
    } else {
        // Ação inválida
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Ação inválida']);
    }
} catch (PDOException $e) {
    // Erro de banco de dados
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erro de banco de dados: ' . $e->getMessage()]);
    exit;
}
?>
