<?php
// Inicia a sessão e inclui o arquivo de configuração
session_start();
require_once  "../config.php";


?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Marca</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg bg-body-tertiary border border-success p-2 mb-2 border-opacity-25 bg-primary-subtle text-primary-emphasis">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">G-Cars</a>
            
        </div>
    </nav>
<body>
<?php require('../navbar.php'); ?>    

    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Gestão de Marcas</h2>
            <a href="Adminbackofice1.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Voltar ao Painel</a>
        </div>
        
        <?php
        // Verifica e exibe mensagens de erro ou sucesso
        if (isset($_GET['error'])) {
            echo '<div class="alert alert-danger">Erro ao adicionar marca.</div>';
        }
        if (isset($_GET['success'])) {
            echo '<div class="alert alert-success">Marca adicionada com sucesso!</div>';
        }
        ?>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Adicionar Nova Marca</h4>
                    </div>
                    <div class="card-body">
                        <form action="processar_marca.php" method="POST">
                            <div class="mb-3">
                                <label for="marca" class="form-label">Nome da Marca</label>
                                <input type="text" class="form-control" id="marca" name="marca" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Adicionar Marca</button>
                        </form>
                    </div>
                </div>
                
                <!-- Lista de marcas existentes -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h4>Marcas Existentes</h4>
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            <?php
                            try {
                                // Conecta ao banco de dados e busca todas as marcas
                                $link = connect_db();
                                $sql = "SELECT * FROM marca ORDER BY marca";
                                $result = $link->query($sql);
                                
                                // Exibe o total de marcas
                                echo "<li class='list-group-item bg-light'>Total de marcas: " . $result->rowCount() . "</li>";
                                
                                // Lista todas as marcas
                                while ($row = $result->fetch()) {
                                    echo "<li class='list-group-item'>";
                                    echo htmlspecialchars($row['marca']);
                                    echo "</li>";
                                }
                            } catch (PDOException $e) {
                                echo "<li class='list-group-item text-danger'>Erro ao carregar marcas: " . $e->getMessage() . "</li>";
                            }
                            ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="../js/script.js"></script>