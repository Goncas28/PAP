<?php
/**
 * Página de Aprovação de Visitas (Admin)
 * 
 * Permite que administradores aprovem ou rejeitem visitas pendentes.
 */

session_start();

// Verifica se o usuário é administrador
if (!isset($_SESSION["Tipo"]) || $_SESSION["Tipo"] !== "A") {
    header('Location: ../Login.php');
    exit();
}

// Conexão com o banco de dados
require_once('../config.php');
$conn = connect_db();

// Processar aprovação ou rejeição de visitas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['visita_id']) && isset($_POST['action'])) {
    $visita_id = intval($_POST['visita_id']);
    $action = $_POST['action'];
    
    try {
        if ($action === 'aprovar') {
            $status = 'Aprovado';
            $mensagem_tipo = "aprovada";
        } else {
            $status = 'Não Aprovado';
            $mensagem_tipo = "rejeitada";
        }
        
        $sql = "UPDATE marcarvisita SET status = ? WHERE Id_Visita = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$status, $visita_id]);
        
        $mensagem = "Visita {$mensagem_tipo} com sucesso!";
    } catch (PDOException $e) {
        $erro = "Erro ao processar a solicitação: " . $e->getMessage();
    }
}

// Buscar todas as visitas pendentes
try {
    $today = date('Y-m-d');
    $current_time = date('H:i:s');
    
    // Query to fetch only pending visits
    $sql = "SELECT mv.Id_Visita, mv.email, c.Nome, mv.DataVisita, mv.Hora_Visita, 
            car.ID_Carro, m.marca, mo.Modelo, car.Ano, mv.Observacoes, mv.status
            FROM marcarvisita mv
            JOIN clientes c ON mv.email = c.Email
            JOIN carros car ON mv.ID_Carro = car.ID_Carro
            JOIN modelo mo ON car.Id_Modelo = mo.Id_Modelo
            JOIN marca m ON mo.idMarca = m.idMarca
            WHERE (mv.status = '' OR mv.status = 'Aguardar Aprovação' OR mv.status IS NULL)
            ORDER BY mv.DataVisita ASC, mv.Hora_Visita ASC";
    
    $stmt = $conn->query($sql);
    $visitas_pendentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = "Erro ao buscar visitas: " . $e->getMessage();
    $visitas_pendentes = [];
}

$title = "Aprovar Visitas - GCars Admin";
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
        .table-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            padding: 20px;
        }
        .action-btns {
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>
    <?php require('../navbar.php'); ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Aprovar Visitas</h2>
            <div>
                <a href="ver_visitas.php" class="btn btn-primary me-2">
                    <i class="bi bi-calendar-check"></i> Ver Todas Visitas
                </a>
                <a href="Adminbackofice1.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Voltar ao Painel
                </a>
            </div>
        </div>
        
        <?php if (isset($mensagem)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> <?php echo $mensagem; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($erro)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $erro; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="table-container">
            <?php if (!empty($visitas_pendentes)): ?>
                <div class="alert alert-info mb-4">
                    <i class="bi bi-info-circle-fill me-2"></i> 
                    <strong>Informação:</strong> Há <?php echo count($visitas_pendentes); ?> visita(s) pendente(s) de aprovação.
                </div>
                
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                          
                            <th>Cliente</th>
                            <th>Email</th>
                            <th>Veículo</th>
                            <th>Data da Visita</th>
                            <th>Hora</th>
                            <th>Observações</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($visitas_pendentes as $visita): ?>
                            <tr>
                                
                                <td><?php echo htmlspecialchars($visita['Nome']); ?></td>
                                <td><?php echo htmlspecialchars($visita['email']); ?></td>
                                <td><?php echo htmlspecialchars($visita['marca'] . ' ' . $visita['Modelo'] . ' (' . $visita['Ano'] . ')'); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($visita['DataVisita'])); ?></td>
                                <td><?php echo date('H:i', strtotime($visita['Hora_Visita'])); ?></td>
                                <td><?php echo htmlspecialchars($visita['Observacoes']); ?></td>
                                <td class="action-btns">
                                    <form method="post" class="d-inline" onsubmit="return confirm('Confirma a aprovação desta visita?');">
                                        <input type="hidden" name="visita_id" value="<?php echo $visita['Id_Visita']; ?>">
                                        <input type="hidden" name="action" value="aprovar">
                                        <button type="submit" class="btn btn-success btn-sm">
                                            <i class="bi bi-check-lg"></i> Aprovar
                                        </button>
                                    </form>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Confirma a rejeição desta visita?');">
                                        <input type="hidden" name="visita_id" value="<?php echo $visita['Id_Visita']; ?>">
                                        <input type="hidden" name="action" value="rejeitar">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="bi bi-x-lg"></i> Rejeitar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-calendar-check fs-1 text-muted"></i>
                    <h4 class="mt-3 text-muted">Não há visitas pendentes de aprovação</h4>
                    <p class="text-muted">Todas as visitas já foram processadas.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
