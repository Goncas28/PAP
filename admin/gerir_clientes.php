<?php
session_start();
require_once '../config.php';


if ($_SESSION['Tipo'] !== "A") {
    header("Location: ../index.php");
    exit;
}

$link = connect_db();
$sql_clientes = "SELECT * FROM clientes";
$result_clientes = $link->query($sql_clientes);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Clientes</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
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
                <?php while ($row = $result_clientes->fetch()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['Nome']) ?></td>
                    <td><?= htmlspecialchars($row['Email']) ?></td>
                    <td><?= htmlspecialchars($row['criado_em'] ?? 'N/A') ?></td>
                    <td>
                        <button onclick="confirmarRemocao('<?= htmlspecialchars($row['Email']) ?>')" class="btn btn-danger btn-sm">
                            Remover
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div class="mt-3">
            <a href="../Adminbackofice1.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <script>
    function confirmarRemocao(email) {
        if (confirm('Tem certeza que deseja remover este cliente?')) {
            window.location.href = '../remover_cliente.php?email=' + encodeURIComponent(email);
        }
    }
    </script>
    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
