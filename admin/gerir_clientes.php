<?php
session_start();
require_once '../config.php';

if ($_SESSION['Tipo'] !== "A") {
    header("Location: ../index.php");
    exit;
}

$link = connect_db();

// Configuração da paginação
$itens_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// Get total records count
try {
    $sql_total = "SELECT COUNT(*) as total FROM clientes";
    $stmt_total = $link->query($sql_total);
    $total_registros = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];
    $total_paginas = ceil($total_registros / $itens_por_pagina);
} catch (PDOException $e) {
    $erro = "Erro ao contar registros: " . $e->getMessage();
    $total_registros = 0;
    $total_paginas = 0;
}

// Modify the clients query to include pagination
try {
    $sql_clientes = "SELECT * FROM clientes ORDER BY Nome LIMIT :limit OFFSET :offset";
    $stmt = $link->prepare($sql_clientes);
    $stmt->bindValue(':limit', $itens_por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
} catch (PDOException $e) {
    $erro = "Erro ao buscar clientes: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Clientes</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
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
            <h2>Gestão de Clientes</h2>
            <a href="../Adminbackofice1.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Voltar ao Painel
            </a>
        </div>
        
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Data Registo</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $stmt->fetch()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['Nome']) ?></td>
                    <td><?= htmlspecialchars($row['Email']) ?></td>
                    <td><?= htmlspecialchars($row['criado_em'] ?? 'N/A') ?></td>
                    <td>
                        <button onclick="confirmarRemocao('<?= htmlspecialchars($row['Email']) ?>')" class="btn btn-danger btn-sm">
                            <i class="bi bi-trash"></i> Remover
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
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

        <div class="mt-3">
            <a href="../Adminbackofice1.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <script>
    function confirmarRemocao(email) {
        if (confirm('Tem certeza que deseja remover este cliente?')) {
            window.location.href = '../remover_cliente.php?email=' + encodeURIComponent(email) + '&pagina=<?php echo $pagina_atual; ?>';
        }
    }
    </script>
    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
