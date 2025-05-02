<?php
if (!isset($_SESSION)) {
    session_start();
}

// Verificar se é administrador
if (!isset($_SESSION["Tipo"]) || $_SESSION["Tipo"] !== "A") {
    header('Location: Login.php');
    exit();
}

// Incluir arquivo de configuração
require_once('../config.php');

// Obter estatísticas para o dashboard
try {
    $link = connect_db();
    
    // Total de clientes registados
    $sql_total_clientes = "SELECT COUNT(*) as total FROM clientes";
    $result_total_clientes = $link->query($sql_total_clientes);
    $total_clientes = $result_total_clientes->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total de veículos cadastrados
    $sql_total_veiculos = "SELECT COUNT(*) as total FROM carros";
    $result_total_veiculos = $link->query($sql_total_veiculos);
    $total_veiculos = $result_total_veiculos->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total de visitas aprovadas
    $sql_visitas_aprovadas = "SELECT COUNT(*) as total FROM marcarvisita WHERE status = 'Aprovado'";
    $result_visitas_aprovadas = $link->query($sql_visitas_aprovadas);
    $visitas_aprovadas = $result_visitas_aprovadas->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total de visitas pendentes -  CORRIGIDO: considerando status 'Aguardar Aprovação' e status vazios
    $sql_visitas_pendentes = "SELECT COUNT(*) as total FROM marcarvisita WHERE status = 'Aguardar Aprovação' OR status = ''";
    $result_visitas_pendentes = $link->query($sql_visitas_pendentes);
    $visitas_pendentes = $result_visitas_pendentes->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Verificar quais status existem na tabela para depuração
    $sql_check_status = "SELECT status, COUNT(*) as count FROM marcarvisita GROUP BY status";
    $result_check_status = $link->query($sql_check_status);
    $status_counts = $result_check_status->fetchAll(PDO::FETCH_ASSOC);
    $status_debug = [];
    foreach ($status_counts as $status_count) {
        $status_value = empty($status_count['status']) ? "''" : $status_count['status'];
        $status_debug[] = "$status_value: {$status_count['count']}";
    }
    error_log("Status na tabela marcarvisita: " . implode(", ", $status_debug));
    
    // Adicionar log para depuração
    error_log("Clientes: " . $total_clientes . " | Veículos: " . $total_veiculos . " | Aprovadas: " . $visitas_aprovadas . " | Pendentes: " . $visitas_pendentes);
    
} catch (PDOException $e) {
    // Em caso de erro, definir valores padrão
    $total_clientes = 0;
    $total_veiculos = 0;
    $visitas_aprovadas = 0;
    $visitas_pendentes = 0;
    
    // Log do erro
    error_log("Erro na consulta: " . $e->getMessage());
}

$title = "Painel Administrativo - G-Cars";
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            padding-top: 60px;
        }
        .section-content {
            margin-bottom: 30px;
        }
        .dashboard-card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            padding: 20px;
            margin-bottom: 20px;
            cursor: pointer;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        }
        .dashboard-card .card-title {
            color: #6c757d;
            font-size: 1rem;
            font-weight: 500;
        }
        .dashboard-card .card-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #212529;
        }
        .dashboard-card .card-icon {
            font-size: 2.5rem;
            color: #6c757d;
        }
        .dashboard-card.primary {
            border-left: 5px solid #0d6efd;
        }
        .dashboard-card.success {
            border-left: 5px solid #198754;
        }
        .dashboard-card.danger {
            border-left: 5px solid #dc3545;
        }
        .dashboard-card.purple {
            border-left: 5px solid #6f42c1;
        }
        .section-header {
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
            margin-top: 2rem;
        }
        a.card-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }
    </style>
</head>
<body>

<?php require('../navbar.php'); ?>

<div class="container my-4">
    
    
    <!-- Dashboard com estatísticas -->
    <div class="row mb-5">
        <div class="col-12">
            <h2 class="mb-4">Estatísticas Gerais</h2>
        </div>
        
        <div class="col-md-6">
            <a href="admin/gerir_clientes.php" class="card-link">
                <div class="dashboard-card primary">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="card-title">Total de Clientes</div>
                            <div class="card-value"><?php echo $total_clientes; ?></div>
                        </div>
                        <div class="card-icon">
                            <i class="bi bi-people"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        
        <div class="col-md-6">
            <a href="admin/gerir_veiculos.php" class="card-link">
                <div class="dashboard-card success">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="card-title">Veículos Disponíveis</div>
                            <div class="card-value"><?php echo $total_veiculos; ?></div>
                        </div>
                        <div class="card-icon">
                            <i class="bi bi-car-front"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        
        <div class="col-md-6">
            <a href="admin/ver_visitas.php" class="card-link">
                <div class="dashboard-card danger">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="card-title">Visitas Aprovadas</div>
                            <div class="card-value"><?php echo $visitas_aprovadas; ?></div>
                        </div>
                        <div class="card-icon">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        
        <div class="col-md-6">
            <a href="admin/aprovar_visitas.php" class="card-link">
                <div class="dashboard-card purple">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="card-title">Visitas Pendentes</div>
                            <div class="card-value"><?php echo $visitas_pendentes; ?></div>
                        </div>
                        <div class="card-icon">
                            <i class="bi bi-calendar-plus"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>
    

    
                    

</script>

<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
