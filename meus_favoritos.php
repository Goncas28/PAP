<?php
if (!isset($_SESSION)) {
    session_start();
}

// Verificar se o usuário está logado (verificando ambas as variáveis possíveis)
$isLoggedIn = (isset($_SESSION['email']) || (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true));
$userEmail = isset($_SESSION['email']) ? $_SESSION['email'] : (isset($_SESSION['utilizador']) ? $_SESSION['utilizador'] : '');

if (!$isLoggedIn) {
    header("Location: login.php?redirect=meus_favoritos.php");
    exit;
}

$title = "Meus Favoritos - G-Cars";
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            padding-top: 60px;
        }
        .carousel-item img {
            object-fit: cover;
            height: 300px;
        }
        .car-item {
            cursor: pointer;
            transition: transform 0.2s;
        }
        .car-item:hover {
            transform: scale(1.02);
        }
        
        /* Estilos para o botão de favorito */
        .favorite-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 100;
            cursor: pointer;
            border: none;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            transition: all 0.2s ease;
        }
        
        .favorite-btn:hover {
            background-color: rgba(255, 255, 255, 1);
            transform: scale(1.1);
        }
        
        .favorite-btn i {
            font-size: 1.5rem;
            color: #dc3545;
        }
        
        .empty-favorites {
            text-align: center;
            padding: 50px 20px;
        }
        
        .empty-favorites i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<?php require('navbar.php'); ?>

<div class="container my-4">
    
    
    <?php
    require_once __DIR__ . "/config.php";  

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
        
        // Buscar favoritos do usuário logado
        $sql = "SELECT c.*, mo.Modelo, ma.marca 
                FROM favoritos f 
                INNER JOIN carros c ON f.ID_Carro = c.ID_Carro 
                INNER JOIN modelo mo ON c.Id_Modelo = mo.Id_Modelo 
                INNER JOIN marca ma ON mo.idMarca = ma.idMarca 
                WHERE f.Email = :email";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':email', $userEmail, PDO::PARAM_STR);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            echo '<div class="row">';
            while ($viatura = $stmt->fetch(PDO::FETCH_ASSOC)) {
                try {
                    // Buscar as imagens da viatura da tabela imagem
                    $sql_imagens = "SELECT * FROM imagem WHERE ID_Carro = :id";
                    $stmt_imagens = $conn->prepare($sql_imagens);
                    $stmt_imagens->bindParam(':id', $viatura['ID_Carro'], PDO::PARAM_INT);
                    $stmt_imagens->execute();
                    
                    $carouselId = "carousel" . $viatura['ID_Carro'];
    ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100 position-relative">
                        <button type="button" class="favorite-btn" title="Remover dos favoritos" data-car-id="<?php echo $viatura['ID_Carro']; ?>" onclick="removeFavorite(this, event, <?php echo $viatura['ID_Carro']; ?>)">
                            <i class="bi bi-heart-fill"></i>
                        </button>
                        <a href="detalhes_carro.php?id=<?php echo $viatura['ID_Carro']; ?>" class="text-decoration-none text-dark">
                            <div id="<?php echo $carouselId; ?>" class="carousel slide" data-bs-ride="carousel">
                                <div class="carousel-inner">
                                    <?php
                                    $first = true;
                                    while ($imagem = $stmt_imagens->fetch(PDO::FETCH_ASSOC)) {
                                        $activeClass = $first ? 'active' : '';
                                    ?>
                                        <div class="carousel-item <?php echo $activeClass; ?>">
                                            <img src="<?php echo htmlspecialchars($imagem['imagem']); ?>" class="d-block w-100" alt="<?php echo htmlspecialchars($viatura['marca'] . ' ' . $viatura['Modelo']); ?>">
                                        </div>
                                    <?php
                                        $first = false;
                                    }
                                    
                                    // Se não tiver imagens, mostrar uma imagem padrão
                                    if ($stmt_imagens->rowCount() == 0) {
                                    ?>
                                        <div class="carousel-item active">
                                            <img src="images/no-image.png" class="d-block w-100" alt="Sem imagem">
                                        </div>
                                    <?php
                                    }
                                    ?>
                                </div>
                                <button class="carousel-control-prev" type="button" data-bs-target="#<?php echo $carouselId; ?>" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Anterior</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#<?php echo $carouselId; ?>" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Próximo</span>
                                </button>
                            </div>
                            
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($viatura['marca'] . ' ' . $viatura['Modelo']); ?></h5>
                                <div class="row">
                                    <div class="col-6">
                                        <p class="card-text"><i class="bi bi-calendar"></i> Ano: <?php echo $viatura['Ano']; ?></p>
                                        <p class="card-text"><i class="bi bi-fuel-pump"></i> <?php echo $viatura['Combustivel']; ?></p>
                                        <p class="card-text"><i class="bi bi-gear"></i> <?php echo $viatura['Transmissao']; ?></p>
                                    </div>
                                    <div class="col-6">
                                        <p class="card-text"><i class="bi bi-speedometer2"></i> <?php echo $viatura['Potencia']; ?> CV</p>
                                        <p class="card-text"><i class="bi bi-signpost"></i> <?php echo number_format($viatura['KM'], 0, ',', '.'); ?> km</p>
                                        <p class="card-text"><i class="bi bi-people"></i> <?php echo $viatura['Lotacao']; ?> lugares</p>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <span class="fs-5 fw-bold"><?php echo number_format($viatura['Preco'], 2, ',', '.'); ?>€</span>
                                    <a href="detalhes_carro.php?id=<?php echo $viatura['ID_Carro']; ?>" class="btn btn-primary">
                                        <i class="bi bi-info-circle"></i> Ver Detalhes
                                    </a>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
    <?php
                } catch (PDOException $e) {
                    // Log error silently and continue
                    error_log("Erro ao buscar imagens: " . $e->getMessage());
                    continue;
                }
            }
            echo '</div>';
        } else {
            // Não tem favoritos
            echo '<div class="empty-favorites">
                    <i class="bi bi-heart"></i>
                    <h3>Você ainda não tem carros favoritos</h3>
                    <p>Adicione carros aos favoritos para vê-los aqui</p>
                    <a href="VerViaturas.php" class="btn btn-primary mt-3">Ver Viaturas Disponíveis</a>
                  </div>';
        }
    } catch (PDOException $e) {
        echo '<div class="alert alert-danger">Erro ao carregar favoritos. Por favor, tente novamente mais tarde.</div>';
        error_log("Database Error: " . $e->getMessage());
    }
    ?>
</div>

<script src="js/bootstrap.bundle.min.js"></script>
<script>
    // Função para remover um favorito
    function removeFavorite(button, event, carId) {
        // Impedir que o clique propague para o link abaixo
        event.preventDefault();
        event.stopPropagation();
        
        // Desabilitar o botão durante a requisição
        button.disabled = true;
        
        // Construir os dados para enviar
        const formData = new FormData();
        formData.append('action', 'remove');
        formData.append('car_id', carId);
        
        // Enviar dados para o servidor
        fetch('favoritos.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro na resposta do servidor: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Remover o card do carro da página
                const cardElement = button.closest('.col-md-6');
                cardElement.remove();
                
                // Verificar se ainda há cards na página
                const cardsRemaining = document.querySelectorAll('.col-md-6').length;
                if (cardsRemaining === 0) {
                    // Não há mais cards, mostrar mensagem de lista vazia
                    const container = document.querySelector('.container');
                    container.innerHTML = `
                        <h1 class="mb-4">Meus Carros Favoritos</h1>
                        <div class="empty-favorites">
                            <i class="bi bi-heart"></i>
                            <h3>Você ainda não tem carros favoritos</h3>
                            <p>Adicione carros aos favoritos para vê-los aqui</p>
                            <a href="VerViaturas.php" class="btn btn-primary mt-3">Ver Viaturas Disponíveis</a>
                        </div>
                    `;
                }
            } else {
                alert('Erro: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao processar a solicitação. Por favor, tente novamente.');
        })
        .finally(() => {
            // Reabilitar o botão (caso a operação falhe e o card não seja removido)
            button.disabled = false;
        });
    }
</script>
</body>
</html>
