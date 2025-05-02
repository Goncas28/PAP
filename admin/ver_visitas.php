<?php
/**
 * Página de Visualização de Visitas (Admin)
 * 
 * Permite que administradores visualizem todas as visitas agendadas.
 * Exibe uma tabela com detalhes das visitas e opção de eliminação.
 */

session_start();

// Verifica se o usuário é administrador
if (!isset($_SESSION["Tipo"]) || $_SESSION["Tipo"] !== "A") {
    header('Location: ../Login.php');
    exit();
}

// Conexão com o banco de dados
require_once('../config.php');
$conn = connect_db();

// Configuração da paginação
$itens_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// Get total records for pagination
try {
    $sql_total = "SELECT COUNT(*) as total FROM marcarvisita";
    $stmt_total = $conn->query($sql_total);
    $total_registros = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];
    $total_paginas = ceil($total_registros / $itens_por_pagina);
} catch (PDOException $e) {
    $erro = "Erro ao contar registros: " . $e->getMessage();
    $total_registros = 0;
    $total_paginas = 0;
}

// Processar exclusão de visita se solicitado
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id_visita = $_GET['delete'];
    try {
        $sql_delete = "DELETE FROM marcarvisita WHERE Id_Visita = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->execute([$id_visita]);
        $mensagem = "Visita eliminada com sucesso!";
    } catch (PDOException $e) {
        $erro = "Erro ao eliminar visita: " . $e->getMessage();
    }
}

// Buscar todas as visitas do banco de dados
try {
    $sql = "SELECT mv.Id_Visita, mv.email, c.Nome, mv.DataVisita, mv.Hora_Visita, 
            car.ID_Carro, m.marca, mo.Modelo, car.Ano, mv.Observacoes, mv.status
            FROM marcarvisita mv
            JOIN clientes c ON mv.email = c.Email
            JOIN carros car ON mv.ID_Carro = car.ID_Carro
            JOIN modelo mo ON car.Id_Modelo = mo.Id_Modelo
            JOIN marca m ON mo.idMarca = m.idMarca
            ORDER BY mv.DataVisita ASC, mv.Hora_Visita ASC
            LIMIT :limit OFFSET :offset";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':limit', $itens_por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $visitas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = "Erro ao buscar visitas: " . $e->getMessage();
    $visitas = [];
}

$title = "Visualizar Visitas - GCars Admin";
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
        .table-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            padding: 20px;
        }

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
    <?php require('../navbar.php'); ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Visitas Agendadas</h2>
            <div>
                <a href="aprovar_visitas.php" class="btn btn-primary me-2">
                    <i class="bi bi-check-lg"></i> Aprovar Visitas
                </a>
                <a href="Adminbackofice1.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Voltar ao Painel
                </a>
            </div>
        </div>
        
        <?php if (isset($mensagem)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> <?php echo $mensagem; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($erro)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $erro; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="alert alert-info">
            <i class="bi bi-info-circle-fill me-2"></i>
            <strong>Nota:</strong> Todas as visitas têm uma margem de 1 hora entre si para evitar sobreposições. 
            As visitas precisam ser aprovadas para que os clientes possam comparecer.
        </div>
        
        <div class="table-container">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                       
                        <th>Cliente</th>
                        <th>Email</th>
                        <th>Veículo</th>
                        <th>Data da Visita</th>
                        <th>Hora</th>
                        <th>Observações</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($visitas)): ?>
                        <?php foreach ($visitas as $visita): ?>
                            <tr>
                                
                                <td><?php echo htmlspecialchars($visita['Nome']); ?></td>
                                <td><?php echo htmlspecialchars($visita['email']); ?></td>
                                <td><?php echo htmlspecialchars($visita['marca'] . ' ' . $visita['Modelo'] . ' (' . $visita['Ano'] . ')'); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($visita['DataVisita'])); ?></td>
                                <td><?php echo date('H:i', strtotime($visita['Hora_Visita'])); ?></td>
                                <td><?php echo htmlspecialchars($visita['Observacoes']); ?></td>
                                <td>
                                    <?php 
                                    if ($visita['status'] == 'Aprovado') {
                                        $statusClass = 'success';
                                        $statusText = 'Aprovado';
                                    } elseif ($visita['status'] == 'Não Aprovado') {
                                        $statusClass = 'danger';
                                        $statusText = 'Não Aprovado';
                                    } else {
                                        $statusClass = 'secondary';
                                        $statusText = 'Aguardando Aprovação';
                                    }
                                    ?>
                                    <span class="badge bg-<?php echo $statusClass; ?>">
                                        <?php echo $statusText; ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-danger btn-sm" onclick="confirmarExclusao(<?php echo $visita['Id_Visita']; ?>)">
                                        <i class="bi bi-trash"></i> Eliminar
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center">
                                <p class="my-3 text-muted"><i class="bi bi-calendar-x fs-3 d-block mb-2"></i> Nenhuma visita agendada</p>
                            </td>
                        </tr>
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

    <script>
    function confirmarExclusao(id) {
        if (confirm('Tem certeza que deseja eliminar esta visita?')) {
            window.location.href = 'ver_visitas.php?delete=' + id + '&pagina=<?php echo $pagina_atual; ?>';
        }
    }
    </script>

    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
