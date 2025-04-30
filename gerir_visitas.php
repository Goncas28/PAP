<?php
if (!isset($_SESSION)) {
    session_start();
}

// Verifica se o usuário está logado e é um administrador
if (!isset($_SESSION["utilizador"]) || $_SESSION["loggedin"] !== true || $_SESSION["tipo"] !== 'A') {
    header("Location: login.php");
    exit;
}

require_once('config.php');
$conn = connect_db();

$mensagem = '';
$erro = '';

// Buscar todas as visitas
try {
    $sql_visitas = "SELECT mv.Id_Visita, mv.email, mv.DataVisita, mv.Hora_Visita, mv.Observacoes, mv.status,
                    c.ID_Carro, m.marca, mo.Modelo, c.Ano, cl.Nome as NomeCliente
                    FROM marcarvisita mv
                    JOIN carros c ON mv.ID_Carro = c.ID_Carro
                    JOIN modelo mo ON c.Id_Modelo = mo.Id_Modelo
                    JOIN marca m ON mo.idMarca = m.idMarca
                    JOIN clientes cl ON mv.email = cl.Email
                    ORDER BY mv.status = '' DESC, mv.DataVisita DESC, mv.Hora_Visita DESC";
    $stmt_visitas = $conn->prepare($sql_visitas);
    $stmt_visitas->execute();
    $visitas = $stmt_visitas->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = "Erro ao buscar visitas: " . $e->getMessage();
    $visitas = [];
}

$title = "Gerir Visitas - G-Cars";
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
        .table-actions form {
            display: inline;
        }
        .status-pendente {
            background-color: #f8f9fa;
        }
        .status-aprovado {
            background-color: #d1e7dd;
        }
        .status-nao-aprovado {
            background-color: #f8d7da;
        }
        #loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            display: none;
        }
        .spinner {
            width: 3rem;
            height: 3rem;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        .action-buttons .btn {
            width: 120px;
            font-weight: 500;
        }
        .status-badge {
            font-size: 0.9rem;
            padding: 6px 10px;
        }
    </style>
</head>
<body>

<?php require('navbar.php'); ?>

<div id="loading-overlay">
    <div class="spinner-border text-light spinner" role="status">
        <span class="visually-hidden">Carregando...</span>
    </div>
</div>

<div class="container my-4">
    <h1 class="mb-4">Gerir Visitas</h1>
    
    <div id="alert-container"></div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Lista de Visitas</h5>
        </div>
        <div class="card-body">
            <?php if (count($visitas) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                               
                                <th>Cliente</th>
                                <th>Carro</th>
                                <th>Data</th>
                                <th>Hora</th>
                                <th>Observações</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="visitas-table-body">
                            <?php foreach ($visitas as $visita): 
                                $rowClass = '';
                                if($visita['status'] == 'Aprovado') {
                                    $rowClass = 'status-aprovado';
                                } elseif($visita['status'] == 'Não Aprovado') {
                                    $rowClass = 'status-nao-aprovado';
                                }
                            ?>
                                <tr class="<?php echo $rowClass; ?>" data-id="<?php echo $visita['Id_Visita']; ?>">
                                    <td><?php echo $visita['Id_Visita']; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($visita['NomeCliente']); ?>
                                        <small class="d-block text-muted"><?php echo $visita['email']; ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($visita['marca'] . ' ' . $visita['Modelo'] . ' (' . $visita['Ano'] . ')'); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($visita['DataVisita'])); ?></td>
                                    <td><?php echo date('H:i', strtotime($visita['Hora_Visita'])); ?></td>
                                    <td><?php echo htmlspecialchars($visita['Observacoes']); ?></td>
                                    <td>
                                        <?php 
                                        if ($visita['status'] == 'Aprovado') {
                                            $statusClass = 'success';
                                            $statusText = 'Aprovado';
                                        } elseif ($visita['status'] == 'Não Aprovado') {
                                            $statusClass = 'danger';
                                            $statusText = 'Não Aprovado';
                                        } else {
                                            $statusClass = 'secondary';
                                            $statusText = 'Aguardando Aprovação';
                                        }
                                        ?>
                                        <span class="badge bg-<?php echo $statusClass; ?> status-badge">
                                            <?php echo $statusText; ?>
                                        </span>
                                    </td>
                                    <td class="table-actions">
                                        <div class="action-buttons">
                                            <?php if ($visita['status'] !== 'Aprovado'): ?>
                                                <button type="button" class="btn btn-success aprovar-btn" 
                                                      data-id="<?php echo $visita['Id_Visita']; ?>">
                                                    <i class="bi bi-check-lg me-1"></i> Aprovar
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($visita['status'] !== 'Não Aprovado'): ?>
                                                <button type="button" class="btn btn-danger rejeitar-btn"
                                                      data-id="<?php echo $visita['Id_Visita']; ?>">
                                                    <i class="bi bi-x-lg me-1"></i> Rejeitar
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info" role="alert">
                    Não há visitas agendadas no momento.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const loadingOverlay = document.getElementById('loading-overlay');
        const alertContainer = document.getElementById('alert-container');
        const aprovaBtns = document.querySelectorAll('.aprovar-btn');
        const rejeitaBtns = document.querySelectorAll('.rejeitar-btn');
        
        // Função para mostrar alerta
        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            alertContainer.innerHTML = '';
            alertContainer.appendChild(alertDiv);
            
            // Auto-close after 5 seconds
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alertDiv);
                bsAlert.close();
            }, 5000);
        }
        
        // Função para atualizar o status da visita
        function atualizarStatus(idVisita, status) {
            loadingOverlay.style.display = 'flex';
            
            fetch('atualizar_status_visita.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id_visita: idVisita,
                    status: status
                })
            })
            .then(response => response.json())
            .then(data => {
                loadingOverlay.style.display = 'none';
                
                if (data.success) {
                    showAlert(data.message, 'success');
                    atualizarUi(idVisita, status);
                } else {
                    showAlert(data.error, 'danger');
                }
            })
            .catch(error => {
                loadingOverlay.style.display = 'none';
                showAlert('Erro ao comunicar com o servidor', 'danger');
                console.error('Erro:', error);
            });
        }
        
        // Função para atualizar a UI após mudança de status
        function atualizarUi(idVisita, status) {
            const row = document.querySelector(`tr[data-id="${idVisita}"]`);
            if (row) {
                // Atualizar classe da linha
                row.className = '';
                if (status === 'Aprovado') {
                    row.classList.add('status-aprovado');
                } else if (status === 'Não Aprovado') {
                    row.classList.add('status-nao-aprovado');
                }
                
                // Atualizar badge de status
                const badgeCell = row.querySelector('.status-badge');
                if (badgeCell) {
                    badgeCell.className = 'badge status-badge';
                    let statusText = 'Aguardando Aprovação';
                    
                    if (status === 'Aprovado') {
                        badgeCell.classList.add('bg-success');
                        statusText = 'Aprovado';
                    } else if (status === 'Não Aprovado') {
                        badgeCell.classList.add('bg-danger');
                        statusText = 'Não Aprovado';
                    } else {
                        badgeCell.classList.add('bg-secondary');
                    }
                    
                    badgeCell.textContent = statusText;
                }
                
                // Atualizar botões de ação
                const actionsCell = row.querySelector('.table-actions');
                if (actionsCell) {
                    let buttonsHtml = '<div class="action-buttons">';
                    
                    if (status !== 'Aprovado') {
                        buttonsHtml += `
                            <button type="button" class="btn btn-success aprovar-btn" data-id="${idVisita}">
                                <i class="bi bi-check-lg me-1"></i> Aprovar
                            </button>
                        `;
                    }
                    
                    if (status !== 'Não Aprovado') {
                        buttonsHtml += `
                            <button type="button" class="btn btn-danger rejeitar-btn" data-id="${idVisita}">
                                <i class="bi bi-x-lg me-1"></i> Rejeitar
                            </button>
                        `;
                    }
                    
                    buttonsHtml += '</div>';
                    actionsCell.innerHTML = buttonsHtml;
                    
                    // Reattach event listeners to the new buttons
                    actionsCell.querySelectorAll('.aprovar-btn').forEach(btn => {
                        btn.addEventListener('click', handleAprovar);
                    });
                    
                    actionsCell.querySelectorAll('.rejeitar-btn').forEach(btn => {
                        btn.addEventListener('click', handleRejeitar);
                    });
                }
            }
        }
        
        // Event handlers
        function handleAprovar(e) {
            const idVisita = this.getAttribute('data-id');
            if (confirm('Tem certeza que deseja aprovar esta visita?')) {
                atualizarStatus(idVisita, 'Aprovado');
            }
        }
        
        function handleRejeitar(e) {
            const idVisita = this.getAttribute('data-id');
            if (confirm('Tem certeza que deseja rejeitar esta visita?')) {
                atualizarStatus(idVisita, 'Não Aprovado');
            }
        }
        
        // Adicionar event listeners aos botões
        aprovaBtns.forEach(btn => {
            btn.addEventListener('click', handleAprovar);
        });
        
        rejeitaBtns.forEach(btn => {
            btn.addEventListener('click', handleRejeitar);
        });
    });
</script>
</body>
</html>
