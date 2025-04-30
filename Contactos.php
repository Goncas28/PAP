<?php
if (!isset($_SESSION)) {
    session_start();
}

$title = "Contactos - G-Cars";
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
    </style>
</head>
<body>

<?php require('navbar.php'); ?>

<div class="container my-4">
    <h1 class="mb-4">Entre em Contacto</h1>
    
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Informações de Contacto</h5>
                    <p><i class="bi bi-geo-alt-fill"></i> Endereço: Viseu 123</p>
                    <p><i class="bi bi-telephone-fill"></i> Telefone: 969053456</p>
                    <p><i class="bi bi-envelope-fill"></i> Email: goncas1416@gmail.com</p>
                    <p><i class="bi bi-clock-fill"></i> Horário de Funcionamento: Segunda a Sexta, 9h às 18h</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Certifique-se de incluir o Bootstrap JS no final da página -->
<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>