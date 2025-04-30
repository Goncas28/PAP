<?php
$title = "Email Enviado - G-Cars";
include "includes/header.php";
require('navbar.php');
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 text-center">
            <h2>Email Enviado com Sucesso!</h2>
            <p>As instruções de recuperação foram enviadas para o seu email.</p>
            <p>Por favor, verifique sua caixa de entrada.</p>
            <a href="Login.php" class="btn btn-primary mt-3">Voltar para Login</a>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>