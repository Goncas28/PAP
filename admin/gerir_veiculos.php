<?php
session_start();
require_once '../config.php';

if ($_SESSION['Tipo'] !== "A") {
    header("Location: ../index.php");
    exit;
}

$link = connect_db();
$sql_carros = "SELECT c.*, m.Modelo, ma.marca 
               FROM carros c 
               JOIN modelo m ON c.Id_Modelo = m.Id_Modelo 
               JOIN marca ma ON m.idMarca = ma.idMarca";
$result_carros = $link->query($sql_carros);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Veículos</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                    echo $_SESSION['success_message']; 
                    unset($_SESSION['success_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                    echo $_SESSION['error_message']; 
                    unset($_SESSION['error_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <h2 class="mb-4">Gestão de Veículos</h2>
        
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Marca</th>
                    <th>Modelo</th>
                    <th>Ano</th>
                    <th>Preço</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($carro = $result_carros->fetch()): ?>
                <tr>
                    <td><?= htmlspecialchars($carro['marca']) ?></td>
                    <td><?= htmlspecialchars($carro['Modelo']) ?></td>
                    <td><?= htmlspecialchars($carro['Ano']) ?></td>
                    <td><?= number_format($carro['Preco'], 2, ',', '.') ?>€</td>
                    <td>
                        <a href="../VerViatura.php?id=<?= $carro['ID_Carro'] ?>" class="btn btn-primary btn-sm">Ver</a>
                        <a href="../remover_carro.php?id=<?= $carro['ID_Carro'] ?>" 
                           onclick="return confirm('Tem certeza que deseja remover este veículo?')" 
                           class="btn btn-danger btn-sm">Remover</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div class="mt-3">
            <a href="../Adminbackofice1.php" class="btn btn-secondary">Voltar</a>
        </div>
    </div>

    <script>
    function confirmarRemocao(id) {
        if (confirm('Tem certeza que deseja remover este veículo?')) {
            window.location.href = '../remover_carro.php?id=' + id;
        }
    }
    </script>
    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
