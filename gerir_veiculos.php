<?php
if (!isset($_SESSION)) {
    session_start();
}

// Verifica se o usuário é administrador
if (!isset($_SESSION["Tipo"]) || $_SESSION["Tipo"] !== "A") {
    header("Location: login.php");
    exit;
}

require_once('config.php');
$conn = connect_db();

// Configuração da paginação
$itens_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// Get total records count
try {
    $sql_total = "SELECT COUNT(*) as total FROM carros";
    $stmt_total = $conn->query($sql_total);
    $total_registros = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];
    $total_paginas = ceil($total_registros / $itens_por_pagina);
} catch (PDOException $e) {
    $erro = "Erro ao contar registros: " . $e->getMessage();
    $total_registros = 0;
    $total_paginas = 0;
}

// Processar exclusão se solicitado
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_carro = intval($_GET['id']);
    
    try {
        // Inicie uma transação
        $conn->beginTransaction();
        
        // Primeiro, exclua todas as imagens relacionadas aos carros
        // (Se a restrição de chave estrangeira tiver CASCADE, isso pode ser desnecessário)
        $stmt = $conn->prepare("DELETE FROM imagem WHERE ID_Carro = ?");
        $stmt->execute([$id_carro]);
        
        // Então exclua o carro
        $stmt = $conn->prepare("DELETE FROM carros WHERE ID_Carro = ?");
        $stmt->execute([$id_carro]);
        
        // Confirme a transação
        $conn->commit();
        
        $mensagem = "Veículo excluído com sucesso.";
    } catch (Exception $e) {
        // Reverta a transação em caso de erro
        $conn->rollBack();
        $erro = "Erro ao excluir o veículo: " . $e->getMessage();
    }
}

// Obtenha a lista de carros com suas marcas e modelos
try {
    $sql = "SELECT c.ID_Carro, ma.marca, mo.Modelo, c.Ano, c.Preco, c.KM, c.Combustivel
            FROM carros c
            JOIN modelo mo ON c.Id_Modelo = mo.Id_Modelo
            JOIN marca ma ON mo.idMarca = ma.idMarca
            ORDER BY c.ID_Carro DESC
            LIMIT :limit OFFSET :offset";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':limit', $itens_por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $veiculos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = "Erro ao buscar veículos: " . $e->getMessage();
    $veiculos = [];
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerir Veículos - G-Cars</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { padding-top: 60px; }
        .table img { max-height: 50px; }
        .action-buttons { white-space: nowrap; }
        .btn-view { background-color: #17a2b8; color: white; }
        .btn-view:hover { background-color: #138496; color: white; }
        .pagination .page-link {
            color: #212529;
            background-color: #fff;
            border: 1px solid #dee2e6;
        }

        .pagination .page-item.active .page-link {
            background-color: #212529;
            border-color: #212529;
            color: #fff;
        }

        .pagination .page-item.disabled .page-link {
            color: #6c757d;
            pointer-events: none;
            background-color: #fff;
            border-color: #dee2e6;
        }
    </style>
</head>
<body>

<?php require('navbar.php'); ?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Gerir Veículos</h1>
        <div>
            <a href="adicionar_carro.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Adicionar Novo Veículo
            </a>
            <a href="Adminbackofice1.php" class="btn btn-secondary ms-2">
                <i class="bi bi-arrow-left"></i> Voltar ao Painel
            </a>
        </div>
    </div>

    <?php if (isset($mensagem)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill"></i> <?php echo $mensagem; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($erro)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $erro; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            
                            <th>Marca</th>
                            <th>Modelo</th>
                            <th>Ano</th>
                            <th>Preço</th>
                            <th>KM</th>
                            <th>Combustível</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($veiculos)): ?>
                            <tr>
                                <td colspan="8" class="text-center">Nenhum veículo encontrado.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($veiculos as $veiculo): ?>
                                <tr>
                                    
                                    <td><?php echo htmlspecialchars($veiculo['marca']); ?></td>
                                    <td><?php echo htmlspecialchars($veiculo['Modelo']); ?></td>
                                    <td><?php echo htmlspecialchars($veiculo['Ano']); ?></td>
                                    <td><?php echo number_format($veiculo['Preco'], 2, ',', '.'); ?> €</td>
                                    <td><?php echo number_format($veiculo['KM'], 0, ',', '.'); ?> km</td>
                                    <td><?php echo htmlspecialchars($veiculo['Combustivel']); ?></td>
                                    <td class="action-buttons">
                                        <!-- Ver Button - New Action -->
                                        <a href="ver_carro.php?id=<?php echo $veiculo['ID_Carro']; ?>" 
                                           class="btn btn-sm btn-info" title="Ver Detalhes">
                                            <i class="bi bi-eye"></i> Ver
                                        </a>
                                        <!-- Edit Button -->
                                        <a href="edit_carro.php?id=<?php echo $veiculo['ID_Carro']; ?>" 
                                           class="btn btn-sm btn-info ms-1" title="Editar">
                                            <i class="bi bi-pencil-square"></i> Editar
                                        </a>
                                        <!-- Delete Button -->
                                        <a href="javascript:void(0);" 
                                           onclick="confirmarExclusao(<?php echo $veiculo['ID_Carro']; ?>)" 
                                           class="btn btn-sm btn-danger ms-1" title="Excluir">
                                            <i class="bi bi-trash"></i> Excluir
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <?php if ($total_paginas > 1): ?>
                <nav aria-label="Navegação de páginas" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <!-- Botão Anterior -->
                        <li class="page-item <?php echo ($pagina_atual <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?pagina=<?php echo $pagina_atual - 1; ?>" aria-label="Anterior">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        
                        <!-- Números das Páginas -->
                        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                            <li class="page-item <?php echo ($pagina_atual == $i) ? 'active' : ''; ?>">
                                <a class="page-link" href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <!-- Botão Próximo -->
                        <li class="page-item <?php echo ($pagina_atual >= $total_paginas) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?pagina=<?php echo $pagina_atual + 1; ?>" aria-label="Próximo">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <nav aria-label="Page navigation example">
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <li class="page-item <?php echo $i == $pagina_atual ? 'active' : ''; ?>">
                    <a class="page-link" href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>

<script>
function confirmarExclusao(id) {
    if (confirm('Tem certeza que deseja excluir este veículo? Esta ação não pode ser desfeita.')) {
        window.location.href = 'gerir_veiculos.php?action=delete&id=' + id + '&pagina=<?php echo $pagina_atual; ?>';
    }
}
</script>

<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
