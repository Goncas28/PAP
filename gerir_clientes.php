<?php
// Inicia a sessão e inclui o arquivo de configuração
session_start();
require_once __DIR__ . "/config.php";

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerir Clientes</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg bg-body-tertiary border border-success p-2 mb-2 border-opacity-25 bg-primary-subtle text-primary-emphasis">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">G-Cars</a>
        </div>
    </nav>
    
    <?php require('navbar.php'); ?>    

    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Gestão de Clientes</h2>
            <a href="Adminbackofice1.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Voltar ao Painel</a>
        </div>
        
        <!-- Rest of your gerir_clientes content goes here -->
    </div>
    
    <?php include 'includes/footer.php'; ?>

</body>
</html>