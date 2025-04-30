<?php
if (!isset($_SESSION)) {
    session_start();
}

$title = "Ver Viaturas - G-Cars";
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
        
        /* Removendo estilos do modal que não serão mais usados */
        
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
        
        .favorite-btn-disabled {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: rgba(200, 200, 200, 0.5);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 100;
            cursor: not-allowed;
            border: none;
        }
        
        .favorite-btn-disabled i {
            font-size: 1.5rem;
            color: #777;
        }
    </style>
</head>
<body>

<?php require('navbar.php'); ?>

<div class="container my-4">
    <h1 class="mb-4"></h1>
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
        
        // Verificar se o usuário está logado para preparar a consulta de favoritos
        $userFavorites = [];
        $isLoggedIn = (isset($_SESSION['email']) || (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true));
        $userEmail = isset($_SESSION['email']) ? $_SESSION['email'] : (isset($_SESSION['utilizador']) ? $_SESSION['utilizador'] : '');
        
        if ($isLoggedIn && !empty($userEmail)) {
            $stmt_fav = $conn->prepare("SELECT ID_Carro FROM favoritos WHERE Email = :email");
            $stmt_fav->bindParam(':email', $userEmail, PDO::PARAM_STR);
            $stmt_fav->execute();
            while ($fav = $stmt_fav->fetch(PDO::FETCH_ASSOC)) {
                $userFavorites[] = $fav['ID_Carro'];
            }
        }

        // Buscar todas as viaturas com informações da marca e modelo usando prepared statement
        $sql = "SELECT c.*, mo.Modelo, ma.marca 
                FROM carros c 
                INNER JOIN modelo mo ON c.Id_Modelo = mo.Id_Modelo 
                INNER JOIN marca ma ON mo.idMarca = ma.idMarca";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt;

        if ($result && $result->rowCount() > 0) {
            echo '<div class="row">';
            while ($viatura = $result->fetch(PDO::FETCH_ASSOC)) {
                try {
                    // Buscar as imagens da viatura da tabela imagem com prepared statement
                    $sql_imagens = "SELECT * FROM imagem WHERE ID_Carro = :id";
                    $stmt_imagens = $conn->prepare($sql_imagens);
                    $stmt_imagens->bindParam(':id', $viatura['ID_Carro'], PDO::PARAM_INT);
                    $stmt_imagens->execute();
                    
                    $carouselId = "carousel" . $viatura['ID_Carro'];
                    
                    // Verificar se esta viatura está nos favoritos do usuário
                    $isFavorite = in_array($viatura['ID_Carro'], $userFavorites);
        ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100 position-relative">
                        <?php if ($isLoggedIn): ?>
                            <button type="button" class="favorite-btn" title="<?php echo $isFavorite ? 'Remover dos favoritos' : 'Adicionar aos favoritos'; ?>" data-car-id="<?php echo $viatura['ID_Carro']; ?>" onclick="toggleFavorite(this, event, <?php echo $viatura['ID_Carro']; ?>)">
                                <i class="bi <?php echo $isFavorite ? 'bi-heart-fill' : 'bi-heart'; ?>"></i>
                            </button>
                        <?php else: ?>
                            <div class="favorite-btn-disabled" title="Faça login para adicionar aos favoritos">
                                <i class="bi bi-heart"></i>
                            </div>
                        <?php endif; ?>
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
            echo '<div class="alert alert-info">Não foram encontradas viaturas.</div>';
        }
    } catch (PDOException $e) {
        echo '<div class="alert alert-danger">Erro ao carregar viaturas. Por favor, tente novamente mais tarde.</div>';
        error_log("Database Error: " . $e->getMessage());
    }
    ?>
    </div>
</div>

<?php require('includes/footer.php'); ?>

<script>
    // Função para manipular clique no favorito
    function toggleFavorite(button, event, carId) {
        // Impedir que o clique propague para o link abaixo
        event.preventDefault();
        event.stopPropagation();
        
        const icon = button.querySelector('i');
        
        // Desabilitar o botão durante a requisição para evitar cliques múltiplos
        button.disabled = true;
        
        // Toggle do ícone (coração vazio/preenchido)
        const isAdding = icon.classList.contains('bi-heart');
        if (isAdding) {
            icon.classList.replace('bi-heart', 'bi-heart-fill');
        } else {
            icon.classList.replace('bi-heart-fill', 'bi-heart');
        }
        
        // Construir os dados para enviar
        const action = isAdding ? 'add' : 'remove';
        const formData = new FormData();
        formData.append('action', action);
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
                // Atualizar o atributo title do botão
                button.title = isAdding ? 'Remover dos favoritos' : 'Adicionar aos favoritos';
            } else {
                // Reverter a mudança do ícone em caso de erro
                if (isAdding) {
                    icon.classList.replace('bi-heart-fill', 'bi-heart');
                } else {
                    icon.classList.replace('bi-heart', 'bi-heart-fill');
                }
                alert('Erro: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            // Reverter a mudança do ícone em caso de erro
            if (isAdding) {
                icon.classList.replace('bi-heart-fill', 'bi-heart');
            } else {
                icon.classList.replace('bi-heart', 'bi-heart-fill');
            }
            alert('Erro ao processar a solicitação. Por favor, tente novamente.');
        })
        .finally(() => {
            // Reabilitar o botão
            button.disabled = false;
        });
    }
</script>
</body>
</html>
