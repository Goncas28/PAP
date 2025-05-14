<?php
if (!isset($_SESSION)) {
    session_start();
}

$title = "Serviços - G-Cars";
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
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .service-card {
            transition: transform 0.3s;
            margin-bottom: 20px;
        }
        .service-card:hover {
            transform: translateY(-5px);
        }
        main {
            flex: 1;
        }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<main>
    <div class="container my-4">
        <h1 class="mb-4">Os Nossos Serviços</h1>
        
        <!-- Conteúdo da página aqui -->
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100 service-card">
                    <div class="card-body">
                        <h5 class="card-title">Venda de Veículos</h5>
                        <p class="card-text">Oferecemos uma ampla gama de veículos novos e usados com as melhores condições do mercado.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 service-card">
                    <div class="card-body">
                        <h5 class="card-title">Garantia</h5>
                        <p class="card-text">Serviços completos de manutenção realizados por profissionais qualificados.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 service-card">
                    <div class="card-body">
                        <h5 class="card-title">Financiamento</h5>
                        <p class="card-text">Soluções de financiamento personalizadas para atender às suas necessidades.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

</body>
</html>