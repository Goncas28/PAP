<?php
if (!isset($_SESSION)) {
    session_start();
}

// Verifica se o usuário é administrador
if (!isset($_SESSION["Tipo"]) || $_SESSION["Tipo"] !== "A") {
    header("Location: login.php");
    exit;
}

// Verifica se o ID do carro foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: gerir_veiculos.php");
    exit;
}

$id_carro = intval($_GET['id']);

require_once('config.php');
$conn = connect_db();

try {
    // Consulta para obter detalhes do carro
    $sql = "SELECT c.*, ma.marca, mo.Modelo 
            FROM carros c
            JOIN modelo mo ON c.Id_Modelo = mo.Id_Modelo
            JOIN marca ma ON mo.idMarca = ma.idMarca
            WHERE c.ID_Carro = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_carro]);
    
    if ($stmt->rowCount() === 0) {
        // Se o carro não for encontrado, redirecione
        header("Location: gerir_veiculos.php");
        exit;
    }
    
    $carro = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Consulta para obter imagens do carro
    $sql_imagens = "SELECT * FROM imagem WHERE ID_Carro = ? ORDER BY ID_Imagem";
    $stmt_imagens = $conn->prepare($sql_imagens);
    $stmt_imagens->execute([$id_carro]);
    $imagens = $stmt_imagens->fetchAll(PDO::FETCH_ASSOC);
    
    // Ajustar URLs das imagens e remover duplicatas
    $image_urls = [];
    $unique_imagens = [];
    
    foreach ($imagens as $imagem) {
        // Verificar se a URL da imagem existe na coluna 'imagem' (conforme sua tabela)
        $url = isset($imagem['imagem']) ? $imagem['imagem'] : 
              (isset($imagem['URL_Imagem']) ? $imagem['URL_Imagem'] : '');
        
        // Somente adicionar se a URL não estiver vazia e não for duplicada
        if (!empty($url) && !in_array($url, $image_urls)) {
            $image_urls[] = $url;
            $imagem['URL_Imagem'] = $url; // Garantir que URL_Imagem está definida
            $unique_imagens[] = $imagem;
        }
    }
    
    // Substituir o array original com o array filtrado
    $imagens = $unique_imagens;
    
} catch (PDOException $e) {
    $erro = "Erro ao buscar dados do veículo: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Veículo - G-Cars</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { padding-top: 60px; }
        .car-image { 
            max-height: 300px; 
            object-fit: contain;
        }
        .thumbnail {
            width: 100px;
            height: 70px;
            object-fit: cover;
            cursor: pointer;
            border: 2px solid #dee2e6;
            border-radius: 4px;
            margin-right: 5px;
        }
        .thumbnail.active {
            border-color: #0d6efd;
        }
        .images-container {
            overflow-x: auto;
            white-space: nowrap;
            padding: 10px 0;
        }
        .spec-label {
            font-weight: bold;
            color: #6c757d;
        }
    </style>
</head>
<body>

<?php require('navbar.php'); ?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Detalhes do Veículo</h1>
        <div>
       
            <a href="gerir_veiculos.php" class="btn btn-secondary ms-2">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <?php if (isset($erro)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $erro; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-md-6">
                <!-- Imagem principal -->
                <div class="card mb-4">
                    <div class="card-body">
                        <?php if (!empty($imagens)): ?>
                            <img id="mainImage" src="<?php echo htmlspecialchars($imagens[0]['URL_Imagem']); ?>" alt="<?php echo htmlspecialchars($carro['marca'] . ' ' . $carro['Modelo']); ?>" class="img-fluid car-image mb-3">
                            
                            <!-- Thumbnails das imagens -->
                            <div class="images-container">
                                <?php foreach ($imagens as $index => $imagem): ?>
                                    <img 
                                        src="<?php echo htmlspecialchars($imagem['URL_Imagem']); ?>" 
                                        class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>"
                                        onclick="changeMainImage('<?php echo htmlspecialchars($imagem['URL_Imagem']); ?>', this)"
                                        alt="Imagem <?php echo $index + 1; ?>"
                                        data-image-id="<?php echo $imagem['ID_Imagem']; ?>"
                                    >
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center p-5 bg-light">
                                <i class="bi bi-image" style="font-size: 5rem; color: #ccc;"></i>
                                <p class="mt-3">Nenhuma imagem disponível</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <!-- Informações do veículo -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><?php echo htmlspecialchars($carro['marca'] . ' ' . $carro['Modelo'] . ' (' . $carro['Ano'] . ')'); ?></h4>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="spec-label">Preço:</p>
                                <h3 class="text-primary"><?php echo number_format($carro['Preco'], 2, ',', '.'); ?> €</h3>
                            </div>
                            <div class="col-md-6">
                                <p class="spec-label">Quilometragem:</p>
                                <h5><?php echo number_format($carro['KM'], 0, ',', '.'); ?> km</h5>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="row mb-2">
                            <div class="col-6">
                                <p class="spec-label">Combustível:</p>
                                <p><?php echo htmlspecialchars($carro['Combustivel']); ?></p>
                            </div>
                            <div class="col-6">
                                <p class="spec-label">Potência:</p>
                                <p><?php echo htmlspecialchars($carro['Potencia']); ?> CV</p>
                            </div>
                        </div>
                        
                        <div class="row mb-2">
                            <div class="col-6">
                                <p class="spec-label">Transmissão:</p>
                                <p><?php echo htmlspecialchars($carro['Transmissao']); ?></p>
                            </div>
                            <div class="col-6">
                                <p class="spec-label">Lotação:</p>
                                <p><?php echo htmlspecialchars($carro['Lotacao']); ?> lugares</p>
                            </div>
                        </div>
                        
                        <div class="row mb-2">
                            <div class="col-6">
                                <p class="spec-label">Ano:</p>
                                <p><?php echo htmlspecialchars($carro['Ano']); ?></p>
                            </div>
                            <?php if (isset($carro['Descricao']) && !empty($carro['Descricao'])): ?>
                            <div class="col-6">
                                <p class="spec-label">Descrição:</p>
                                <p><?php echo nl2br(htmlspecialchars($carro['Descricao'])); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <hr>
                        
                        <div class="row mb-2">
                            
                            <div class="col-6">
                                <p class="spec-label">Modelo:</p>
                                <p><?php echo htmlspecialchars($carro['marca'] . ' ' . $carro['Modelo']); ?></p>
                            </div>
                        </div>

                        <?php if (isset($carro['Data_Adicao']) && !empty($carro['Data_Adicao'])): ?>
                        <div class="row mb-2">
                            <div class="col-6">
                                <p class="spec-label">Data de Cadastro:</p>
                                <p><?php echo date('d/m/Y', strtotime($carro['Data_Adicao'])); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function changeMainImage(src, thumbnail) {
    // Alterar a imagem principal
    document.getElementById('mainImage').src = src;
    
    // Atualizar a classe active nos thumbnails
    const thumbnails = document.querySelectorAll('.thumbnail');
    thumbnails.forEach(thumb => thumb.classList.remove('active'));
    thumbnail.classList.add('active');
}

// Para depuração - vamos exibir no console os IDs das imagens
document.addEventListener('DOMContentLoaded', function() {
    const thumbnails = document.querySelectorAll('.thumbnail');
    console.log("Total de imagens carregadas: " + thumbnails.length);
    thumbnails.forEach(thumb => {
        console.log("ID da imagem: " + thumb.getAttribute('data-image-id') + ", URL: " + thumb.src);
    });
});
</script>

<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
