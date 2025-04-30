<?php
session_start();
$title = "Registar Conta - G-Cars";
include "includes/header.php";
require('navbar.php');

$errorMessage = '';
$successMessage = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['registrar'])) {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($nome) || empty($email) || empty($password) || empty($confirm_password)) {
        $errorMessage = "Por favor, preencha todos os campos.";
    } elseif ($password !== $confirm_password) {
        $errorMessage = "As passwords não coincidem.";
    } elseif (strlen($password) < 6) {
        $errorMessage = "A password deve ter pelo menos 6 caracteres.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = "Por favor, insira um email válido.";
    } else {
        // Conexão com o banco de dados
        try {
            require_once('config.php');
            $pdo = connect_db();

            // Verifica se o email já existe
            $stmt = $pdo->prepare("SELECT * FROM clientes WHERE Email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $errorMessage = "Este email já está registrado. Por favor, use outro email.";
            } else {
                // Hash da senha
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Inserir novo usuário (tipo U = usuário normal)
                $insert_sql = "INSERT INTO clientes (Nome, Email, Password, Tipo, criado_em) VALUES (?, ?, ?, 'U', NOW())";
                $insert_stmt = $pdo->prepare($insert_sql);
                
                if ($insert_stmt->execute([$nome, $email, $hashed_password])) {
                    $successMessage = "Conta criada com sucesso! Agora você pode fazer login.";
                } else {
                    $errorMessage = "Erro ao criar a conta. Por favor, tente novamente.";
                }
            }
        } catch (PDOException $e) {
            $errorMessage = "Erro ao conectar ao banco de dados: " . $e->getMessage();
        }
    }
}
?>

<div class="container my-4">
    <div class="bg-white rounded shadow p-4 mt-3 mx-auto" style="max-width: 500px;">
        <h2 class="text-center text-uppercase fw-bold text-dark">Criar Conta</h2>

        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
        <?php endif; ?>

        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div>
        <?php endif; ?>

        <form action="<?= $_SERVER["PHP_SELF"]; ?>" method="post">
            <div class="mb-3">
                <label for="nome" class="form-label">Nome Completo</label>
                <input type="text" class="form-control" id="nome" name="nome" required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Senha</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="password" name="password" required>
                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirmar Senha</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" name="registrar" class="btn btn-success w-100">Criar Conta</button>
        </form>

        <p class="text-center mt-3">
            Já tem uma conta? <a href="Login.php" class="text-decoration-none">Fazer Login</a>
        </p>
    </div>
</div>

<script>
function togglePasswordVisibility(inputId, buttonId) {
    const passwordInput = document.getElementById(inputId);
    const button = document.getElementById(buttonId);
    const icon = button.querySelector('i');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

document.getElementById('togglePassword').addEventListener('click', function() {
    togglePasswordVisibility('password', 'togglePassword');
});

document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
    togglePasswordVisibility('confirm_password', 'toggleConfirmPassword');
});
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>
