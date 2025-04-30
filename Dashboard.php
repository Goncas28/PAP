<?php
if (!isset($_SESSION)) {
    session_start();
}

// Redirect if not logged in - verificar a variável de sessão correta
if (!isset($_SESSION["utilizador"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit;
}

// Include database configuration
require_once('config.php');
$conn = connect_db(); // Get PDO connection from function

// Handle profile update form submission
$update_error = "";
$update_success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_profile"])) {
    // Get form data
    $nome = trim($_POST["nome"]);
    $current_password = trim($_POST["current_password"]);
    $new_password = trim($_POST["new_password"]);
    $confirm_password = trim($_POST["confirm_password"]);
    $email = $_SESSION["utilizador"];
    
    // First, get the user's current data to verify password
    $sql_get_user = "SELECT * FROM clientes WHERE Email = ?";
    $stmt = $conn->prepare($sql_get_user);
    $stmt->execute([$email]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user_data) {
        // Check if updating password
        if (!empty($new_password)) {
            // Verify current password
            if (password_verify($current_password, $user_data["Password"])) {
                // Check if new passwords match
                if ($new_password === $confirm_password) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    // Update both name and password
                    $update_sql = "UPDATE clientes SET Nome = ?, Password = ?, atualizado_em = NOW() WHERE Email = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    
                    if ($update_stmt->execute([$nome, $hashed_password, $email])) {
                        $update_success = "Perfil atualizado com sucesso!";
                        $_SESSION["nome_utilizador"] = $nome;
                    } else {
                        $update_error = "Erro ao atualizar o perfil.";
                    }
                } else {
                    $update_error = "As novas passwords não coincidem.";
                }
            } else {
                $update_error = "Password atual incorreta.";
            }
        } else {
            // Update only the name
            $update_sql = "UPDATE clientes SET Nome = ?, atualizado_em = NOW() WHERE Email = ?";
            $update_stmt = $conn->prepare($update_sql);
            
            if ($update_stmt->execute([$nome, $email])) {
                $update_success = "Perfil atualizado com sucesso!";
                $_SESSION["nome_utilizador"] = $nome;
            } else {
                $update_error = "Erro ao atualizar o perfil.";
            }
        }
    } else {
        $update_error = "Utilizador não encontrado.";
    }
}

$title = "Dashboard - Perfil";
include "includes/header.php";
?>

<?php require('navbar.php'); ?>

<div class="container my-4">
    <h2 class="mb-4">Dashboard do Usuário</h2>
    
    <?php if (!empty($update_error)): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo $update_error; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($update_success)): ?>
        <div class="alert alert-success" role="alert">
            <?php echo $update_success; ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h3>Dados do Cliente</h3>
        </div>
        <div class="card-body">
            <?php
            // Verificar se há um utilizador na sessão
            if (isset($_SESSION['utilizador']) && !empty($_SESSION['utilizador'])) {
                $username = $_SESSION['utilizador'];
                
                // Consulta para obter os dados do cliente
                $sql = "SELECT * FROM clientes WHERE Email = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$username]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($row) {
                    echo "<div class='row mb-4'>";
                    echo "<div class='col-md-6'>";
                    echo "<p><strong>Nome:</strong> " . htmlspecialchars($row['Nome']) . "</p>";
                    echo "<p><strong>Email:</strong> " . htmlspecialchars($row['Email']) . "</p>";
                    echo "<p><strong>Tipo de Utilizador:</strong> " . 
                        ($row['Tipo'] == 'A' ? 'Administrador' : 
                        ($row['Tipo'] == 'U' ? 'Utilizador' : 'Desconhecido')) . "</p>";
                    echo "</div>";
                    echo "<div class='col-md-6'>";
                    echo "<p><strong>Criado em:</strong> " . date('d/m/Y H:i', strtotime($row['criado_em'])) . "</p>";
                    
                    if (!is_null($row['atualizado_em'])) {
                        echo "<p><strong>Atualizado em:</strong> " . date('d/m/Y H:i', strtotime($row['atualizado_em'])) . "</p>";
                    } else {
                        echo "<p><strong>Atualizado em:</strong> Nunca atualizado</p>";
                    }
                    
                    echo "</div>";
                    echo "</div>";
                    
                    // Botão para editar perfil
                    echo "<button class='btn btn-primary' type='button' onclick='toggleProfileForm()'>Editar Perfil</button>";
                    
                    // Formulário para editar perfil (inicialmente oculto)
                    echo "<div id='profileForm' class='profile-form mt-4'>";
                    echo "<form method='post' action='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "'>";
                    echo "<div class='form-group'>";
                    echo "<label for='nome'>Nome:</label>";
                    echo "<input type='text' class='form-control' id='nome' name='nome' value='" . htmlspecialchars($row['Nome']) . "' required>";
                    echo "</div>";
                    
                    echo "<div class='form-group'>";
                    echo "<label for='email'>Email:</label>";
                    echo "<input type='email' class='form-control' id='email' value='" . htmlspecialchars($row['Email']) . "' disabled>";
                    echo "<small class='form-text text-muted'>O email não pode ser alterado.</small>";
                    echo "</div>";
                    
                    echo "<h4 class='mt-4'>Alterar Senha</h4>";
                    echo "<p><small>Deixe em branco para manter a senha atual</small></p>";
                    
                    echo "<div class='form-group'>";
                    echo "<label for='current_password'>Senha Atual:</label>";
                    echo "<input type='password' class='form-control' id='current_password' name='current_password'>";
                    echo "</div>";
                    
                    echo "<div class='form-group'>";
                    echo "<label for='new_password'>Nova Senha:</label>";
                    echo "<input type='password' class='form-control' id='new_password' name='new_password'>";
                    echo "</div>";
                    
                    echo "<div class='form-group'>";
                    echo "<label for='confirm_password'>Confirmar Nova Senha:</label>";
                    echo "<input type='password' class='form-control' id='confirm_password' name='confirm_password'>";
                    echo "</div>";
                    
                    echo "<button type='submit' name='update_profile' class='btn btn-success'>Salvar Alterações</button>";
                    echo " <button type='button' class='btn btn-secondary' onclick='toggleProfileForm()'>Cancelar</button>";
                    echo "</form>";
                    echo "</div>";
                    
                } else {
                    echo "<p class='alert alert-warning'>Não foram encontrados dados para o email '$username'.</p>";
                }
            } else {
                echo "<p class='alert alert-danger'>Utilizador não autenticado. Por favor, faça login novamente.</p>";
            }
            ?>
        </div>
    </div>
    
    <!-- Adicionar suas visitas agendadas, se aplicável -->
    <div class="card mt-4">
        <div class="card-header bg-primary text-white">
            <h3>Minhas Visitas</h3>
        </div>
        <div class="card-body">
            <?php
            // Query to get user's scheduled visits
            $visitas_sql = "SELECT mv.*, c.ID_Carro, mo.Modelo, ma.marca 
                           FROM marcarvisita mv 
                           JOIN carros c ON mv.ID_Carro = c.ID_Carro
                           JOIN modelo mo ON c.Id_Modelo = mo.Id_Modelo
                           JOIN marca ma ON mo.idMarca = ma.idMarca
                           WHERE mv.email = ?
                           ORDER BY mv.DataVisita DESC, mv.Hora_Visita DESC";
            $visitas_stmt = $conn->prepare($visitas_sql);
            $visitas_stmt->execute([$_SESSION['utilizador']]);
            $visitas = $visitas_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($visitas) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Veículo</th>
                                <th>Data</th>
                                <th>Hora</th>
                                <th>Status</th>
                                <th>Observações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($visitas as $visita): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($visita['marca'] . ' ' . $visita['Modelo']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($visita['DataVisita'])); ?></td>
                                    <td><?php echo date('H:i', strtotime($visita['Hora_Visita'])); ?></td>
                                    <td>
                                        <?php 
                                        $status = $visita['status'];
                                        if ($status == 'Aprovado') {
                                            echo '<span class="badge bg-success">Aprovado</span>';
                                        } elseif ($status == 'Não Aprovado') {
                                            echo '<span class="badge bg-danger">Não Aprovado</span>';
                                        } elseif ($status == 'Aguardar Aprovação' || $status == '') {
                                            echo '<span class="badge bg-warning">Aguardando Aprovação</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($visita['Observacoes']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>Você ainda não tem visitas agendadas.</p>
                <a href="marcar_visita.php" class="btn btn-primary">Agendar uma Visita</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Profile form toggle script -->
<script>
    function toggleProfileForm() {
        var form = document.getElementById('profileForm');
        if (form.style.display === 'block') {
            form.style.display = 'none';
        } else {
            form.style.display = 'block';
        }
    }
    
    // Initialize form as hidden when page loads
    document.addEventListener('DOMContentLoaded', function() {
        var form = document.getElementById('profileForm');
        if (form) {
            form.style.display = 'none';
        }
    });
</script>

<!-- Ensure Bootstrap JS is loaded -->
<script src="js/bootstrap.bundle.min.js"></script>

<?php include 'includes/footer.php'; ?>
</body>
</html>