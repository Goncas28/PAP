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
    <title>Adicionar Modelo</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    
</head>
<body>
    <nav class="navbar navbar-expand-lg bg-body-tertiary border border-success p-2 mb-2 border-opacity-25 bg-primary-subtle text-primary-emphasis">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">G-Cars</a>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav ms-auto me-2 mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="Adminbackofice1.php">Voltar Painel</a>
                    </li>
                   
                </ul>
            </div>
        </div>
    </nav>
    
    <?php require('../navbar.php'); ?>    

    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Gestão de Modelos</h2>
            <a href="Adminbackofice1.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Voltar ao Painel</a>
        </div>
        <?php
        // Verifica e exibe mensagens de erro
        if (isset($_GET['error'])) {
            if ($_GET['error'] == 'duplicate') {
                echo '<div class="alert alert-warning">Este modelo já existe no sistema.</div>';
            } else if ($_GET['error'] == 'insert') {
                echo '<div class="alert alert-danger">Erro ao inserir modelo.</div>';
            } else {
                echo '<div class="alert alert-danger">Erro: ' . htmlspecialchars($_GET['error']) . '</div>';
            }
        }
        // Exibe mensagem de sucesso
        if (isset($_GET['success'])) {
            echo '<div class="alert alert-success">Modelo adicionado com sucesso!</div>';
        }
        ?>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Adicionar Novo Modelo</h4>
                    </div>
                    <div class="card-body">
                        <form action="processar_modelo.php" method="POST">
                            <div class="mb-3">
                                <label for="modelo" class="form-label">Nome do Modelo</label>
                                <input type="text" class="form-control" id="modelo" name="modelo" required>
                            </div>
                            <div class="mb-3">
                                <label for="marca_select" class="form-label">Selecionar Marca</label>
                                <select class="form-select" id="marca_select" name="id_marca" required>
                                    <?php
                                    try {
                                        // Conecta ao banco de dados e busca todas as marcas
                                        $link = connect_db();
                                        $sql = "SELECT * FROM marca ORDER BY marca";
                                        $result = $link->query($sql);
                                        while ($row = $result->fetch()) {
                                            echo "<option value='" . $row['idMarca'] . "'>" . $row['marca'] . "</option>";
                                        }
                                    } catch (PDOException $e) {
                                        echo "Erro ao carregar marcas: " . $e->getMessage();
                                    }
                                    ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Adicionar Modelo</button>
                        </form>
                    </div>
                </div>

                <!-- Lista de modelos existentes -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h4>Modelos Existentes</h4>
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            <?php
                            try {
                                // Busca todos os modelos com suas respectivas marcas
                                $sql = "SELECT m.Id_Modelo, m.Modelo, ma.marca 
                                       FROM modelo m 
                                       JOIN marca ma ON m.idMarca = ma.idMarca 
                                       ORDER BY ma.marca, m.Modelo";
                                $result = $link->query($sql);
                                
                                // Exibe o total de modelos
                                echo "<li class='list-group-item bg-light'>Total de modelos: " . $result->rowCount() . "</li>";
                                
                                // Lista todos os modelos
                                while ($row = $result->fetch()) {
                                    echo "<li class='list-group-item'>";
                                    echo htmlspecialchars($row['marca']) . " ";
                                    echo htmlspecialchars($row['Modelo']);
                                    echo "</li>";
                                }
                            } catch (PDOException $e) {
                                echo "<li class='list-group-item text-danger'>Erro ao carregar modelos: " . $e->getMessage() . "</li>";
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
    <script src="../js/jquery.min.js"></script>
