<?php
if (!isset($_SESSION)) {
    session_start();
}

require_once("config.php");
$pdo = connect_db();

// Default values
$config = [
    'horario_funcionamento' => 'Segunda a Sexta, 9h às 18h',
    'email_contacto' => 'standgcars@gmail.com',
    'telefone_contacto' => '969053456'
];

// Buscar configurações
try {
    $stmt = $pdo->query("SELECT * FROM configuracoes LIMIT 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Only update $config if we got results
    if ($result) {
        $config = $result;
    } else {
        // Insert default values if table is empty
        $sql = "INSERT INTO configuracoes (horario_funcionamento, email_contacto, telefone_contacto) 
                VALUES (:horario, :email, :telefone)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':horario' => $config['horario_funcionamento'],
            ':email' => $config['email_contacto'],
            ':telefone' => $config['telefone_contacto']
        ]);
    }
} catch(PDOException $e) {
    error_log("Erro ao carregar configurações: " . $e->getMessage());
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
                    <p><i class="bi bi-telephone-fill"></i> Telefone: +351 <?php echo htmlspecialchars($config['telefone_contacto']); ?></p>
                    <p><i class="bi bi-envelope-fill"></i> Email: <?php echo htmlspecialchars($config['email_contacto']); ?></p>
                    <p><i class="bi bi-clock-fill"></i> Horário de Funcionamento: <?php echo htmlspecialchars($config['horario_funcionamento']); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>


<?php require('includes/footer.php'); ?>
</body>
</html>