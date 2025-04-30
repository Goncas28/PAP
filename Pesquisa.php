<?php
if (!isset($_SESSION)) {
    session_start();
}

require_once __DIR__ . "/config.php";

$title = "Pesquisa - Teste";

// Inicializar variáveis
$marcas = [];
$resultados = [];
$mensagem = "";
$totalResultados = 0;

try {
    $conn = connect_db();
    
    // Buscar apenas as marcas que têm carros disponíveis no stand
    $sql_marcas = "SELECT ma.idMarca, ma.marca, COUNT(c.ID_Carro) as total_carros 
                   FROM marca ma 
                   INNER JOIN modelo mo ON ma.idMarca = mo.idMarca 
                   INNER JOIN carros c ON mo.Id_Modelo = c.Id_Modelo 
                   GROUP BY ma.idMarca, ma.marca 
                   ORDER BY ma.marca";
    $stmt_marcas = $conn->prepare($sql_marcas);
    $stmt_marcas->execute();
    $marcas = $stmt_marcas->fetchAll(PDO::FETCH_ASSOC);
    
    // Processar a pesquisa
    if ($_SERVER["REQUEST_METHOD"] == "GET" && (isset($_GET['q']) || isset($_GET['pesquisa_detalhada']))) {
        
        // Pesquisa rápida pelo nome
        if (isset($_GET['q'])) {
            if (!empty($_GET['q'])) {
                $termoPesquisa = "%" . $_GET['q'] . "%";
                
                $sql = "SELECT c.*, mo.Modelo, ma.marca as Nome_marca, 
                       (SELECT imagem FROM imagem WHERE ID_Carro = c.ID_Carro LIMIT 1) as primeira_imagem
                       FROM carros c
                       LEFT JOIN modelo mo ON c.Id_Modelo = mo.Id_Modelo
                       LEFT JOIN marca ma ON mo.idMarca = ma.idMarca
                       WHERE ma.marca LIKE ? OR mo.Modelo LIKE ? OR CONCAT(ma.marca, ' ', mo.Modelo) LIKE ?
                       ORDER BY c.ID_Carro DESC";
                       
                $stmt = $conn->prepare($sql);
                $stmt->execute([$termoPesquisa, $termoPesquisa, $termoPesquisa]);
            } else {
                // Se o termo de pesquisa estiver vazio, mostrar todos os carros
                $sql = "SELECT c.*, mo.Modelo, ma.marca as Nome_marca, 
                       (SELECT imagem FROM imagem WHERE ID_Carro = c.ID_Carro LIMIT 1) as primeira_imagem
                       FROM carros c
                       LEFT JOIN modelo mo ON c.Id_Modelo = mo.Id_Modelo
                       LEFT JOIN marca ma ON mo.idMarca = ma.idMarca
                       ORDER BY c.ID_Carro DESC";
                       
                $stmt = $conn->prepare($sql);
                $stmt->execute();
            }
            
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $totalResultados = count($resultados);
            
            if ($totalResultados == 0) {
                $mensagem = "Nenhum veículo encontrado para a sua pesquisa.";
            }
        }
        
        // Pesquisa detalhada
        elseif (isset($_GET['pesquisa_detalhada'])) {
            $params = [];
            $conditions = [];
            
            // Construir a consulta com base nos filtros selecionados
            if (!empty($_GET['marca'])) {
                $conditions[] = "ma.idMarca = ?";
                $params[] = $_GET['marca'];
            }
            
            if (!empty($_GET['modelo'])) {
                $conditions[] = "mo.Id_Modelo = ?";  // Changed from LIKE to exact match on Id_Modelo
                $params[] = $_GET['modelo'];
            }
            
            if (!empty($_GET['ano'])) {
                $conditions[] = "c.Ano = ?";
                $params[] = $_GET['ano'];
            }
            
            if (!empty($_GET['combustivel'])) {
                $conditions[] = "c.Combustivel = ?";
                $params[] = $_GET['combustivel'];
            }
            
            if (!empty($_GET['transmissao'])) {
                $conditions[] = "c.Transmissao = ?";
                $params[] = $_GET['transmissao'];
            }
            
            if (!empty($_GET['preco_min'])) {
                $conditions[] = "c.Preco >= ?";
                $params[] = $_GET['preco_min'];
            }
            
            if (!empty($_GET['preco_max'])) {
                $conditions[] = "c.Preco <= ?";
                $params[] = $_GET['preco_max'];
            }
            
            $sql = "SELECT c.*, mo.Modelo, ma.marca as Nome_marca, 
                   (SELECT imagem FROM imagem WHERE ID_Carro = c.ID_Carro LIMIT 1) as primeira_imagem
                   FROM carros c
                   LEFT JOIN modelo mo ON c.Id_Modelo = mo.Id_Modelo
                   LEFT JOIN marca ma ON mo.idMarca = ma.idMarca";
            
            if (!empty($conditions)) {
                $sql .= " WHERE " . implode(" AND ", $conditions);
            }
            
            $sql .= " ORDER BY c.ID_Carro DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $totalResultados = count($resultados);
            
            if ($totalResultados == 0) {
                $mensagem = "Nenhum veículo encontrado com os critérios selecionados.";
            }
        }
    }
} catch (PDOException $e) {
    $mensagem = "Erro ao realizar a pesquisa.";
    error_log("Erro na página de pesquisa: " . $e->getMessage());
}
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
            background-color: #f8f9fa;
            color: #343a40;
        }
        .search-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
            padding: 30px;
            margin-bottom: 30px;
        }
        .search-title {
            font-weight: 700;
            color: #212529;
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .search-tabs .nav-link {
            color: #495057;
            font-weight: 600;
            padding: 12px 20px;
            border: none;
            border-radius: 5px 5px 0 0;
        }
        .search-tabs .nav-link.active {
            color: #0d6efd;
            background-color: white;
            border-bottom: 3px solid #0d6efd;
        }
        .tab-content {
            background-color: white;
            border-radius: 0 10px 10px 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .form-label {
            font-weight: 600;
            color: #495057;
        }
        .form-control, .form-select {
            padding: 10px 15px;
            border-radius: 8px;
            border: 1px solid #ced4da;
            transition: all 0.3s;
        }
        .form-control:focus, .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        .btn-primary {
            background-color: #0d6efd;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            background-color: #0b5ed7;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(13, 110, 253, 0.2);
        }
        .btn-secondary {
            background-color: #6c757d;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        .car-card {
            height: 100%;
            transition: all 0.3s;
            border: none;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        .car-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        .car-img-container {
            height: 220px;
            overflow: hidden;
            position: relative;
        }
        .car-img-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        .car-card:hover .car-img-container img {
            transform: scale(1.05);
        }
        .card-body {
            padding: 20px;
        }
        .card-title {
            font-weight: 700;
            margin-bottom: 15px;
            color: #212529;
            font-size: 1.2rem;
        }
        .car-price {
            font-size: 1.4rem;
            font-weight: 700;
            color: #0d6efd;
            margin-bottom: 15px;
        }
        .car-specs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
        }
        .car-specs li {
            display: flex;
            align-items: center;
            color: #6c757d;
            font-size: 0.9rem;
        }
        .car-specs li i {
            color: #0d6efd;
            margin-right: 8px;
            font-size: 1rem;
        }
        .card-footer {
            background-color: white;
            border-top: 1px solid rgba(0,0,0,0.05);
            padding: 15px 20px;
        }
        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .results-count {
            background-color: #e9ecef;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            color: #495057;
        }
        .no-results {
            background-color: white;
            padding: 40px;
            text-align: center;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .no-results i {
            font-size: 3rem;
            color: #6c757d;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<?php require('navbar.php'); ?>

<div class="container my-5">
    <div class="search-container">
        <h1 class="search-title mb-4">Pesquisa de Veículos</h1>
        
        <!-- Abas para alternar entre pesquisa rápida e detalhada -->
        <ul class="nav nav-tabs search-tabs mb-4" id="searchTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="quick-tab" data-bs-toggle="tab" data-bs-target="#quick-search" type="button" role="tab" aria-controls="quick-search" aria-selected="true">
                    <i class="bi bi-lightning-fill me-2"></i>Pesquisa Rápida
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="detailed-tab" data-bs-toggle="tab" data-bs-target="#detailed-search" type="button" role="tab" aria-controls="detailed-search" aria-selected="false">
                    <i class="bi bi-sliders me-2"></i>Pesquisa Detalhada
                </button>
            </li>
        </ul>
        
        <div class="tab-content" id="searchTabContent">
            <!-- Pesquisa Rápida -->
            <div class="tab-pane fade show active" id="quick-search" role="tabpanel" aria-labelledby="quick-tab">
                <div class="card border-0 p-3">
                    <div class="card-body">
                        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="GET">
                            <div class="input-group">
                                <input type="text" class="form-control form-control-lg" name="q" placeholder="Pesquisar por marca ou modelo..." value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                                <button class="btn btn-primary btn-lg" type="submit">
                                    <i class="bi bi-search me-2"></i> Pesquisar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Pesquisa Detalhada -->
            <div class="tab-pane fade" id="detailed-search" role="tabpanel" aria-labelledby="detailed-tab">
                <div class="card border-0 p-3">
                    <div class="card-body">
                        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="GET">
                            <input type="hidden" name="pesquisa_detalhada" value="1">
                            <div class="row g-4">
                                <div class="col-md-4">
                                    <label for="marca" class="form-label">
                                        <i class="bi bi-award me-2"></i>Marca
                                    </label>
                                    <select class="form-select" name="marca" id="marca" onchange="carregarModelos(this.value)">
                                        <option value="">Todas</option>
                                        <?php foreach($marcas as $marca): ?>
                                        <option value="<?php echo $marca['idMarca']; ?>" <?php echo (isset($_GET['marca']) && $_GET['marca'] == $marca['idMarca']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($marca['marca']) . ' (' . $marca['total_carros'] . ')'; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="modelo" class="form-label">
                                        <i class="bi bi-car-front me-2"></i>Modelo
                                    </label>
                                    <select class="form-select" name="modelo" id="modelo">
                                        <option value="">Todos os Modelos</option>
                                        <?php 
                                        // Se uma marca estiver selecionada, carregar os modelos correspondentes
                                        if(isset($_GET['marca']) && !empty($_GET['marca'])) {
                                            try {
                                                $stmt_modelos = $conn->prepare("SELECT Id_Modelo, Modelo FROM modelo WHERE idMarca = ? ORDER BY Modelo");
                                                $stmt_modelos->execute([$_GET['marca']]);
                                                $modelos = $stmt_modelos->fetchAll(PDO::FETCH_ASSOC);
                                                
                                                foreach($modelos as $modelo) {
                                                    $selected = (isset($_GET['modelo']) && $_GET['modelo'] == $modelo['Id_Modelo']) ? 'selected' : '';
                                                    echo '<option value="' . $modelo['Id_Modelo'] . '" ' . $selected . '>' . htmlspecialchars($modelo['Modelo']) . '</option>';
                                                }
                                            } catch (PDOException $e) {
                                                error_log("Erro ao buscar modelos: " . $e->getMessage());
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="ano" class="form-label">
                                        <i class="bi bi-calendar-event me-2"></i>Ano
                                    </label>
                                    <select class="form-select" name="ano" id="ano">
                                        <option value="">Todos</option>
                                        <?php for ($i = date("Y"); $i >= 2000; $i--): ?>
                                        <option value="<?php echo $i; ?>" <?php echo (isset($_GET['ano']) && $_GET['ano'] == $i) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="combustivel" class="form-label">
                                        <i class="bi bi-fuel-pump me-2"></i>Combustível
                                    </label>
                                    <select class="form-select" name="combustivel" id="combustivel">
                                        <option value="">Todos</option>
                                        <option value="Gasolina" <?php echo (isset($_GET['combustivel']) && $_GET['combustivel'] == 'Gasolina') ? 'selected' : ''; ?>>Gasolina</option>
                                        <option value="Diesel" <?php echo (isset($_GET['combustivel']) && $_GET['combustivel'] == 'Diesel') ? 'selected' : ''; ?>>Diesel</option>
                                        <option value="GPL" <?php echo (isset($_GET['combustivel']) && $_GET['combustivel'] == 'GPL') ? 'selected' : ''; ?>>GPL</option>
                                        <option value="Elétrico" <?php echo (isset($_GET['combustivel']) && $_GET['combustivel'] == 'Elétrico') ? 'selected' : ''; ?>>Elétrico</option>
                                        <option value="Híbrido" <?php echo (isset($_GET['combustivel']) && $_GET['combustivel'] == 'Híbrido') ? 'selected' : ''; ?>>Híbrido</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="transmissao" class="form-label">
                                        <i class="bi bi-gear me-2"></i>Transmissão
                                    </label>
                                    <select class="form-select" name="transmissao" id="transmissao">
                                        <option value="">Todas</option>
                                        <option value="Manual" <?php echo (isset($_GET['transmissao']) && $_GET['transmissao'] == 'Manual') ? 'selected' : ''; ?>>Manual</option>
                                        <option value="Automática" <?php echo (isset($_GET['transmissao']) && $_GET['transmissao'] == 'Automática') ? 'selected' : ''; ?>>Automática</option>
                                        <option value="Semi-automática" <?php echo (isset($_GET['transmissao']) && $_GET['transmissao'] == 'Semi-automática') ? 'selected' : ''; ?>>Semi-automática</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="preco" class="form-label">
                                        <i class="bi bi-cash-coin me-2"></i>Faixa de Preço (€)
                                    </label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="preco_min" placeholder="Min" value="<?php echo isset($_GET['preco_min']) ? htmlspecialchars($_GET['preco_min']) : ''; ?>">
                                        <span class="input-group-text bg-light">até</span>
                                        <input type="number" class="form-control" name="preco_max" placeholder="Max" value="<?php echo isset($_GET['preco_max']) ? htmlspecialchars($_GET['preco_max']) : ''; ?>">
                                    </div>
                                </div>
                                <div class="col-12 mt-4 d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-search me-2"></i> Pesquisar
                                    </button>
                                    <button type="button" class="btn btn-secondary" id="limparFiltros">
                                        <i class="bi bi-x-circle me-2"></i> Limpar Filtros
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Resultados da pesquisa -->
    <?php if (isset($_GET['q']) || isset($_GET['pesquisa_detalhada'])): ?>
        <div class="search-container">
            <div class="results-header">
                <h2 class="mb-0">Resultados da Pesquisa</h2>
                <?php if (empty($mensagem)): ?>
                <span class="results-count">
                    <i class="bi bi-car-front me-2"></i><?php echo $totalResultados; ?> veículos encontrados
                </span>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($mensagem)): ?>
                <div class="no-results">
                    <i class="bi bi-search"></i>
                    <h4><?php echo $mensagem; ?></h4>
                    <p class="text-muted">Tente refinar sua pesquisa com termos diferentes ou menos filtros.</p>
                </div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php foreach ($resultados as $carro): ?>
                        <div class="col">
                            <div class="card h-100 car-card">
                                <div class="car-img-container">
                                    <?php if (!empty($carro['primeira_imagem'])): ?>
                                        <img src="<?php echo htmlspecialchars($carro['primeira_imagem']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($carro['Nome_marca'] . ' ' . $carro['Modelo']); ?>">
                                    <?php else: ?>
                                        <img src="images/no-image.png" class="card-img-top" alt="Sem imagem">
                                    <?php endif; ?>
                                    
                                    <?php if (isset($_SESSION['user_email'])): ?>
                                    <button type="button" 
                                            class="btn btn-favorite position-absolute" 
                                            data-car-id="<?php echo $carro['ID_Carro']; ?>"
                                            style="top: 10px; right: 10px; background: rgba(255,255,255,0.7); border-radius: 50%; width: 40px; height: 40px;">
                                        <i class="bi bi-heart favorite-icon"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($carro['Nome_marca'] . ' ' . $carro['Modelo']); ?></h5>
                                    <p class="car-price"><?php echo number_format($carro['Preco'], 2, ',', '.'); ?>€</p>
                                    <ul class="car-specs list-unstyled">
                                        <li><i class="bi bi-calendar-check"></i> <?php echo $carro['Ano']; ?></li>
                                        <li><i class="bi bi-fuel-pump"></i> <?php echo $carro['Combustivel']; ?></li>
                                        <li><i class="bi bi-gear-wide"></i> <?php echo $carro['Transmissao']; ?></li>
                                        <li><i class="bi bi-speedometer2"></i> <?php echo number_format($carro['KM'], 0, ',', '.'); ?> km</li>
                                    </ul>
                                </div>
                                <div class="card-footer">
                                    <a href="detalhes_carro.php?id=<?php echo $carro['ID_Carro']; ?>" class="btn btn-primary w-100">
                                        <i class="bi bi-eye me-2"></i>Ver Detalhes
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- JavaScript para iniciar na aba correta baseado na pesquisa -->
<script src="js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Ativar a aba adequada com base no tipo de pesquisa
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('pesquisa_detalhada')) {
            const detailedTab = document.getElementById('detailed-tab');
            const tab = new bootstrap.Tab(detailedTab);
            tab.show();
        }
        
        // Inicializar a seleção de marca se já houver uma marca selecionada
        const marcaSelecionada = document.getElementById('marca').value;
        if (marcaSelecionada) {
            carregarModelos(marcaSelecionada);
        }
        
        // Adicionar funcionalidade para o botão Limpar Filtros
        document.getElementById('limparFiltros').addEventListener('click', function() {
            // Limpar valores dos selects
            document.getElementById('marca').value = '';
            document.getElementById('modelo').innerHTML = '<option value="">Todos os Modelos</option>';
            document.getElementById('ano').value = '';
            document.getElementById('combustivel').value = '';
            document.getElementById('transmissao').value = '';
            
            // Limpar valores dos inputs de preço
            const precoMinInput = document.querySelector('input[name="preco_min"]');
            const precoMaxInput = document.querySelector('input[name="preco_max"]');
            if(precoMinInput) precoMinInput.value = '';
            if(precoMaxInput) precoMaxInput.value = '';
            
            // Se quiser redirecionar para limpar a URL também
            // window.location.href = '<?php echo $_SERVER['PHP_SELF']; ?>';
            
            // Alternative: submit the form with empty values
            // document.querySelector('form[name="pesquisa_detalhada"]').submit();
        });
        
        // Verificar e marcar os carros que já são favoritos do usuário
        <?php if (isset($_SESSION['user_email'])): ?>
        checkFavorites();
        
        // Adicionar event listeners para botões de favoritos
        document.querySelectorAll('.btn-favorite').forEach(button => {
            button.addEventListener('click', function() {
                toggleFavorite(this);
            });
        });
        <?php endif; ?>
    });
    
    function carregarModelos(idMarca) {
        const selectModelo = document.getElementById('modelo');
        const urlParams = new URLSearchParams(window.location.search);
        const modeloSelecionado = urlParams.get('modelo');
        
        // Limpar as opções atuais
        selectModelo.innerHTML = '<option value="">Todos os Modelos</option>';
        
        // Se não houver marca selecionada, não fazer nada
        if (!idMarca) {
            return;
        }
        
        // Buscar os modelos da marca selecionada via AJAX
        fetch('get_modelos.php?marca=' + idMarca)
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    data.forEach(modelo => {
                        const option = document.createElement('option');
                        option.value = modelo.Id_Modelo;
                        option.textContent = modelo.Modelo;
                        
                        // Set as selected if it matches the previously selected model
                        if (modeloSelecionado && modeloSelecionado == modelo.Id_Modelo) {
                            option.selected = true;
                        }
                        
                        selectModelo.appendChild(option);
                    });
                }
            })
            .catch(error => console.error('Erro ao carregar modelos:', error));
    }
    
    <?php if (isset($_SESSION['user_email'])): ?>
    // Função para verificar quais carros são favoritos do usuário
    function checkFavorites() {
        fetch('favoritos_api.php?action=check')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Marcar os botões de favoritos para os carros que já são favoritos
                    data.favorites.forEach(carId => {
                        const btn = document.querySelector(`.btn-favorite[data-car-id="${carId}"]`);
                        if (btn) {
                            const icon = btn.querySelector('.favorite-icon');
                            icon.classList.remove('bi-heart');
                            icon.classList.add('bi-heart-fill');
                            icon.style.color = '#dc3545'; // Cor vermelha
                        }
                    });
                }
            })
            .catch(error => console.error('Erro ao verificar favoritos:', error));
    }
    
    // Função para alternar o status de favorito de um carro
    function toggleFavorite(button) {
        const carId = button.getAttribute('data-car-id');
        const icon = button.querySelector('.favorite-icon');
        const isFavorite = icon.classList.contains('bi-heart-fill');
        
        // Preparar dados para envio
        const formData = new FormData();
        formData.append('car_id', carId);
        formData.append('action', isFavorite ? 'remove' : 'add');
        
        // Enviar solicitação para adicionar/remover dos favoritos
        fetch('favoritos_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Alternar a aparência do ícone
                if (isFavorite) {
                    icon.classList.remove('bi-heart-fill');
                    icon.classList.add('bi-heart');
                    icon.style.color = ''; // Remover cor
                } else {
                    icon.classList.remove('bi-heart');
                    icon.classList.add('bi-heart-fill');
                    icon.style.color = '#dc3545'; // Cor vermelha
                }
            } else {
                alert('Erro ao atualizar favorito: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro ao atualizar favorito:', error);
            alert('Ocorreu um erro ao processar sua solicitação.');
        });
    }
    <?php endif; ?>
</script>
</body>
</html>