<?php
if (!isset($_SESSION)) {
    session_start();
}

// Verifica se o usuário está logado
if (!isset($_SESSION["utilizador"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit;
}

require_once('config.php');
$conn = connect_db();

$mensagem = '';
$erro = '';

// Pegar o ID do carro da URL se existir
$selected_car_id = isset($_GET['id_carro']) ? $_GET['id_carro'] : '';

// Processamento do formulário quando enviado
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['marcar_visita'])) {
    $id_carro = $_POST['id_carro'] ?? '';
    $data_visita = $_POST['data_visita'] ?? '';
    $hora_visita = $_POST['hora_visita'] ?? '';
    $observacoes = $_POST['observacoes'] ?? '';
    $email = $_SESSION["utilizador"]; // Usando o email como identificador

    if (empty($id_carro) || empty($data_visita) || empty($hora_visita)) {
        $erro = "Por favor, preencha todos os campos obrigatórios.";
    } else {
        try {
            // Verificar se já existe uma marcação para o mesmo carro na mesma data com diferença de menos de 1 hora
            $hora_visita_obj = new DateTime($hora_visita);
            $uma_hora_antes = (clone $hora_visita_obj)->modify('-1 hour')->format('H:i:s');
            $uma_hora_depois = (clone $hora_visita_obj)->modify('+1 hour')->format('H:i:s');
            
            $sql_verificar = "SELECT * FROM marcarvisita 
                              WHERE ID_Carro = ? 
                              AND DataVisita = ? 
                              AND Hora_Visita BETWEEN ? AND ?";
            
            $stmt_verificar = $conn->prepare($sql_verificar);
            $stmt_verificar->execute([$id_carro, $data_visita, $uma_hora_antes, $uma_hora_depois]);
            
            if ($stmt_verificar->rowCount() > 0) {
                $erro = "Já existe uma marcação para este carro em um horário próximo. Por favor, escolha outra hora ou data.";
            } else {
                // Modified INSERT query - using empty string for status initially
                $sql = "INSERT INTO marcarvisita (email, ID_Carro, DataVisita, Observacoes, Hora_Visita, status) 
                        VALUES (?, ?, ?, ?, ?, 'Aguardar Aprovação')";
                
                $stmt = $conn->prepare($sql);
                
                if ($stmt->execute([$email, $id_carro, $data_visita, $observacoes, $hora_visita])) {
                    $mensagem = "Visita agendada com sucesso!";
                    
                    // Notificar o administrador sobre a nova visita
                    $visita_id = $conn->lastInsertId();
                    notificar_admin_nova_visita($visita_id, $email, $id_carro, $data_visita, $hora_visita, $observacoes);
                } else {
                    $erro = "Erro ao agendar visita. Por favor, tente novamente.";
                }
            }
        } catch (PDOException $e) {
            $erro = "Erro ao agendar visita: " . $e->getMessage();
        }
    }
}

// Buscar carros disponíveis para o dropdown
try {
    $sql_carros = "SELECT c.ID_Carro, m.marca, mo.Modelo, c.Ano 
                  FROM carros c 
                  JOIN modelo mo ON c.Id_Modelo = mo.Id_Modelo 
                  JOIN marca m ON mo.idMarca = m.idMarca";
    $carros = $conn->query($sql_carros)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = "Erro ao buscar carros: " . $e->getMessage();
    $carros = [];
}

// Buscar as visitas marcadas pelo usuário atual
try {
    $email = $_SESSION["utilizador"];
    $sql_visitas = "SELECT mv.Id_Visita, mv.DataVisita, mv.Hora_Visita, mv.Observacoes, mv.Status,
                     m.marca, mo.Modelo, c.Ano 
                     FROM marcarvisita mv
                     JOIN carros c ON mv.ID_Carro = c.ID_Carro
                     JOIN modelo mo ON c.Id_Modelo = mo.Id_Modelo
                     JOIN marca m ON mo.idMarca = m.idMarca
                     WHERE mv.email = ?
                     ORDER BY mv.DataVisita DESC, mv.Hora_Visita DESC";
    $stmt_visitas = $conn->prepare($sql_visitas);
    $stmt_visitas->execute([$email]);
    $visitas = $stmt_visitas->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = "Erro ao buscar visitas: " . $e->getMessage();
    $visitas = [];
}

$title = "Marcar Visita - G-Cars";

// Função para notificar o administrador sobre nova visita
function notificar_admin_nova_visita($visita_id, $email_cliente, $id_carro, $data_visita, $hora_visita, $observacoes) {
    $conn = connect_db();
    
    // Obter detalhes do carro
    $stmt = $conn->prepare("SELECT c.*, m.Modelo, ma.marca 
                            FROM carros c 
                            JOIN modelo m ON c.Id_Modelo = m.Id_Modelo 
                            JOIN marca ma ON m.idMarca = ma.idMarca 
                            WHERE c.ID_Carro = :id_carro");
    $stmt->bindParam(':id_carro', $id_carro);
    $stmt->execute();
    $carro = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Obter nome do cliente
    $stmt = $conn->prepare("SELECT Nome FROM clientes WHERE Email = :email");
    $stmt->bindParam(':email', $email_cliente);
    $stmt->execute();
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Email do administrador
    $admin_email = 'standgcars@gmail.com';
    
    // Assunto do email
    $subject = 'Nova marcação de visita pendente';
    
    // Corpo do email
    $body = "
    <h2>Nova Marcação de Visita Pendente</h2>
  
    <p><strong>Cliente:</strong> {$cliente['Nome']} ($email_cliente)</p>
    <p><strong>Carro:</strong> {$carro['marca']} {$carro['Modelo']} ({$carro['Ano']})</p>
    <p><strong>Data:</strong> " . date('d/m/Y', strtotime($data_visita)) . "</p>
    <p><strong>Hora:</strong> " . date('H:i', strtotime($hora_visita)) . "</p>
    <p><strong>Observações:</strong> $observacoes</p>
    <br>
    <p>Por favor, acesse o painel administrativo para aprovar ou rejeitar esta solicitação.</p>
   
    ";
    
    // Enviar email para o administrador
    return send_email($admin_email, $subject, $body);
}
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
        #loading-indicator {
            display: none;
            color: #007bff;
            margin-left: 10px;
        }
    </style>
</head>
<body>

<?php require('navbar.php'); ?>

<div class="container my-4">
    <h1 class="mb-4">Marcar Visita</h1>
    
    <?php if (!empty($mensagem)): ?>
        <div class="alert alert-success" role="alert">
            <?php echo $mensagem; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($erro)): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo $erro; ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="form-visita">
                <div class="mb-3">
                    <label for="id_carro" class="form-label">Carro</label>
                    <select class="form-select" id="id_carro" name="id_carro" required>
                        <option value="">Selecione um carro</option>
                        <?php foreach ($carros as $carro): ?>
                            <option value="<?php echo $carro['ID_Carro']; ?>" <?php echo ($carro['ID_Carro'] == $selected_car_id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($carro['marca'] . ' ' . $carro['Modelo'] . ' (' . $carro['Ano'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="data_visita" class="form-label">Data da Visita</label>
                    <input type="date" class="form-control" id="data_visita" name="data_visita" required 
                           min="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="mb-3">
                    <label for="hora_visita" class="form-label">Hora da Visita</label>
                    <input type="time" class="form-control" id="hora_visita" name="hora_visita" required 
                           min="09:00" max="18:00">
                    <small class="form-text text-muted">Horário de funcionamento: 9h às 18h</small>
                </div>
                
                <div class="mb-3">
                    <label for="observacoes" class="form-label">Observações</label>
                    <textarea class="form-control" id="observacoes" name="observacoes" rows="3"></textarea>
                </div>
                
                <button type="submit" name="marcar_visita" class="btn btn-primary">Agendar Visita</button>
                <span id="loading-indicator"><i class="bi bi-arrow-repeat"></i> Atualizando...</span>
            </form>
        </div>
    </div>
    
    <!-- Seção para exibir as visitas marcadas -->
    <div class="mt-5">
        <h2 class="mb-3">Suas Visitas Agendadas <button id="refresh-btn" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-clockwise"></i> Atualizar</button></h2>
        <div id="visitas-container">
            <?php if (count($visitas) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Carro</th>
                                <th>Data</th>
                                <th>Hora</th>
                                <th>Observações</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="visitas-body">
                            <?php foreach ($visitas as $visita): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($visita['marca'] . ' ' . $visita['Modelo'] . ' (' . $visita['Ano'] . ')'); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($visita['DataVisita'])); ?></td>
                                    <td><?php echo date('H:i', strtotime($visita['Hora_Visita'])); ?></td>
                                    <td><?php echo htmlspecialchars($visita['Observacoes']); ?></td>
                                    <td>
                                        <?php 
                                        if ($visita['Status'] == 'Aprovado') {
                                            $statusClass = 'bg-success';
                                            $statusText = 'Aprovado';
                                        } elseif ($visita['Status'] == 'Não Aprovado') {
                                            $statusClass = 'bg-danger';
                                            $statusText = 'Não Aprovado';
                                        } else {
                                            $statusClass = 'bg-secondary';
                                            $statusText = 'Aguardando Aprovação';
                                        }
                                        ?>
                                        <span class="badge <?php echo $statusClass; ?>">
                                            <?php echo $statusText; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info" role="alert">
                    Você ainda não tem visitas agendadas.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const refreshBtn = document.getElementById('refresh-btn');
        const loadingIndicator = document.getElementById('loading-indicator');
        const visitasBody = document.getElementById('visitas-body');
        const visitasContainer = document.getElementById('visitas-container');
        const formVisita = document.getElementById('form-visita');
        
        // Obter o ID do carro da URL se existir
        const urlParams = new URLSearchParams(window.location.search);
        const carId = urlParams.get('id_carro');
        
        // Se há um ID de carro na URL, garantir que ele esteja selecionado
        if (carId) {
            const carSelect = document.getElementById('id_carro');
            for (let i = 0; i < carSelect.options.length; i++) {
                if (carSelect.options[i].value === carId) {
                    carSelect.selectedIndex = i;
                    break;
                }
            }
        }
        
        // Função para atualizar a lista de visitas
        function fetchVisitas() {
            loadingIndicator.style.display = 'inline';
            
            fetch('get_visitas.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erro na resposta da rede');
                    }
                    return response.json();
                })
                .then(data => {
                    updateVisitasUI(data);
                    loadingIndicator.style.display = 'none';
                })
                .catch(error => {
                    console.error('Erro ao buscar visitas:', error);
                    loadingIndicator.style.display = 'none';
                });
        }
        
        // Função para atualizar a UI com os dados das visitas
        function updateVisitasUI(visitas) {
            if (visitas.length > 0) {
                let tableHTML = '';
                visitas.forEach(visita => {
                    const dataFormatada = new Date(visita.DataVisita).toLocaleDateString('pt-BR');
                    const horaFormatada = visita.Hora_Visita.substring(0, 5);
                    
                    let statusClass = 'bg-secondary';
                    let statusText = 'Aguardando Aprovação';
                    
                    if (visita.status === 'Aprovado') {
                        statusClass = 'bg-success';
                        statusText = 'Aprovado';
                    } else if (visita.status === 'Não Aprovado') {
                        statusClass = 'bg-danger';
                        statusText = 'Não Aprovado';
                    }
                    
                    tableHTML += `
                        <tr>
                            <td>${visita.marca} ${visita.Modelo} (${visita.Ano})</td>
                            <td>${dataFormatada}</td>
                            <td>${horaFormatada}</td>
                            <td>${visita.Observacoes || ''}</td>
                            <td><span class="badge ${statusClass}">${statusText}</span></td>
                        </tr>
                    `;
                });
                
                // Se já existir uma tabela, apenas atualiza o corpo
                if (visitasBody) {
                    visitasBody.innerHTML = tableHTML;
                } else {
                    // Caso contrário, cria a tabela completa
                    visitasContainer.innerHTML = `
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Carro</th>
                                        <th>Data</th>
                                        <th>Hora</th>
                                        <th>Observações</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="visitas-body">
                                    ${tableHTML}
                                </tbody>
                            </table>
                        </div>
                    `;
                }
            } else {
                visitasContainer.innerHTML = `
                    <div class="alert alert-info" role="alert">
                        Você ainda não tem visitas agendadas.
                    </div>
                `;
            }
        }
        
        // Atualiza a cada 30 segundos
        const updateInterval = 30000;
        setInterval(fetchVisitas, updateInterval);
        
        // Evento para o botão de atualizar manualmente
        refreshBtn.addEventListener('click', function(e) {
            e.preventDefault();
            fetchVisitas();
        });
        
        // Atualiza após envio do formulário
        formVisita.addEventListener('submit', function() {
            // Aguarda um pouco para garantir que os dados foram processados
            setTimeout(fetchVisitas, 1000);
        });
    });
</script>
</body>
</html>
