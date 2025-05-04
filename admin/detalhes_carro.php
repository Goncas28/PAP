<?php
if (!isset($_SESSION)) {
    session_start();
}

require_once  "../config.php";

// Verificar se o ID do carro foi fornecido
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ../VerViaturas.php");
    exit;
}

$id_carro = $_GET['id'];
$title = "Detalhes do Veículo - G-Cars";
$carro = null;
$imagens = [];
$error = null;

try {
    $conn = connect_db();
    
    // Consulta para obter os detalhes do carro
    $sql = "SELECT c.*, mo.Modelo, ma.marca as Nome_marca
            FROM carros c
            LEFT JOIN modelo mo ON c.Id_Modelo = mo.Id_Modelo
            LEFT JOIN marca ma ON mo.idMarca = ma.idMarca
            WHERE c.ID_Carro = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_carro]);
    
    $carro = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($carro) {
        // Buscar imagens do carro
        $sql_imagens = "SELECT * FROM imagem WHERE ID_Carro = ?";
        $stmt_imagens = $conn->prepare($sql_imagens);
        $stmt_imagens->execute([$id_carro]);
        
        $imagens = $stmt_imagens->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $error = "Veículo não encontrado.";
    }
} catch (PDOException $e) {
    $error = "Erro ao carregar detalhes do veículo.";
    error_log("Erro na página de detalhes: " . $e->getMessage());
}

try {
    $stmt = $conn->query("SELECT * FROM configuracoes LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $config = null;
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            padding-top: 60px;
        }
        .carousel-item img {
            object-fit: cover;
            height: 400px;
            width: 100%;
        }
        .spec-table {
            width: 100%;
            border-collapse: collapse;
        }
        .spec-table th {
            text-align: left;
            background-color: #f8f9fa;
            padding: 10px;
            width: 200px;
        }
        .spec-table td {
            padding: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        .back-button {
            margin-bottom: 20px;
        }
        .car-price {
            font-size: 2rem;
            color: #0d6efd;
            font-weight: bold;
        }
        .car-details-section {
            margin-top: 30px;
        }
    </style>
</head>
<body>

<?php require('../navbar.php'); ?>

<div class="container my-4">
    <div class="back-button">
        <a href="VerViaturas.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Voltar à lista de veículos
        </a>
    </div>

    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php elseif($carro): ?>
        <div class="row">
            <div class="col-md-8">
                <?php if(count($imagens) > 0): ?>
                    <div id="carouselCarImages" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-inner">
                            <?php foreach($imagens as $index => $imagem): ?>
                                <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                    <img src="<?php echo htmlspecialchars($imagem['imagem']); ?>" class="d-block w-100" alt="<?php echo htmlspecialchars($carro['Nome_marca'] . ' ' . $carro['Modelo']); ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if(count($imagens) > 1): ?>
                            <button class="carousel-control-prev" type="button" data-bs-target="#carouselCarImages" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Anterior</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#carouselCarImages" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Próximo</span>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <img src="images/no-image.png" class="img-fluid" alt="Sem imagem">
                <?php endif; ?>
            </div>
            
            <div class="col-md-4">
                <h1><?php echo htmlspecialchars($carro['Nome_marca'] . ' ' . $carro['Modelo']); ?></h1>
                <p class="car-price"><?php echo number_format($carro['Preco'], 2, ',', '.'); ?>€</p>
                
                <div class="d-grid gap-2 mt-4">
                    <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                        <?php if ($_SESSION["Tipo"] !== "A"): ?>
                            <a href="marcar_visita.php?id_carro=<?php echo $carro['ID_Carro']; ?>" class="btn btn-primary btn-lg">
                                <i class="bi bi-calendar2-check"></i> Marcar Visita
                            </a>
                            <a href="tel:+351969053456" class="btn btn-outline-success">
                                <i class="bi bi-telephone"></i> Contactar Vendedor
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="../login.php" class="btn btn-outline-primary">
                            <i class="bi bi-box-arrow-in-right"></i> Faça login para marcar uma visita
                        </a>
                        <a href="tel:+351969053456" class="btn btn-outline-success">
                            <i class="bi bi-telephone"></i> Contactar Vendedor
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="mt-4">
                    <h5>Características Principais</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span><i class="bi bi-calendar"></i> Ano</span>
                            <strong><?php echo $carro['Ano']; ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><i class="bi bi-speedometer2"></i> Potência</span>
                            <strong><?php echo $carro['Potencia']; ?> CV</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><i class="bi bi-fuel-pump"></i> Combustível</span>
                            <strong><?php echo $carro['Combustivel']; ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><i class="bi bi-gear"></i> Transmissão</span>
                            <strong><?php echo $carro['Transmissao']; ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><i class="bi bi-signpost"></i> Quilometragem</span>
                            <strong><?php echo number_format($carro['KM'], 0, ',', '.'); ?> km</strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="car-details-section">
            <h3>Especificações Completas</h3>
            <table class="spec-table">
                <tr>
                    <th>Marca</th>
                    <td><?php echo htmlspecialchars($carro['Nome_marca']); ?></td>
                </tr>
                <tr>
                    <th>Modelo</th>
                    <td><?php echo htmlspecialchars($carro['Modelo']); ?></td>
                </tr>
                <tr>
                    <th>Ano</th>
                    <td><?php echo $carro['Ano']; ?></td>
                </tr>
                <tr>
                    <th>Combustível</th>
                    <td><?php echo $carro['Combustivel']; ?></td>
                </tr>
                <tr>
                    <th>Transmissão</th>
                    <td><?php echo $carro['Transmissao']; ?></td>
                </tr>
                <tr>
                    <th>Potência</th>
                    <td><?php echo $carro['Potencia']; ?> CV</td>
                </tr>
                <tr>
                    <th>Lotação</th>
                    <td><?php echo $carro['Lotacao']; ?> lugares</td>
                </tr>
                <tr>
                    <th>Quilometragem</th>
                    <td><?php echo number_format($carro['KM'], 0, ',', '.'); ?> km</td>
                </tr>
                <tr>
                    <th>Preço</th>
                    <td><?php echo number_format($carro['Preco'], 2, ',', '.'); ?>€</td>
                </tr>
            </table>
        </div>
        
        <!-- Seção para mais informações ou formulário de contacto -->
        <div class="car-details-section">
            <h3>Interessado neste veículo?</h3>
            <p>Para mais informações sobre este veículo, pode contactar-nos diretamente ou agendar uma visita para ver o veículo pessoalmente.</p>
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Contacto</h5>
                            <p class="card-text">
                                <i class="bi bi-telephone"></i> 
                                <?php echo $config && $config['telefone_contacto'] ? '+351 ' . htmlspecialchars($config['telefone_contacto']) : '+351 969053456'; ?>
                            </p>
                            <p class="card-text">
                                <i class="bi bi-envelope"></i> 
                                <?php echo $config && $config['email_contacto'] ? htmlspecialchars($config['email_contacto']) : 'goncas1416@gmail.com'; ?>
                            </p>
                            <p class="card-text"><i class="bi bi-geo-alt"></i> Viseu 123</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Horário</h5>
                            <?php if ($config && $config['horario_funcionamento']): ?>
                                <p class="card-text"><?php echo htmlspecialchars($config['horario_funcionamento']); ?></p>
                            <?php else: ?>
                                <p class="card-text">Segunda a Sexta: 9h - 19h</p>
                                <p class="card-text">Sábados: 10h - 16h</p>
                                <p class="card-text">Domingos e Feriados: Fechado</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
