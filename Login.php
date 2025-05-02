<?php
session_start();
$title = "Login - G-Cars";
include "includes/header.php";
require('navbar.php');

// Replace the autoloader with direct requires
require 'PHPMailer\src\Exception.php';
require 'PHPMailer\src\PHPMailer.php';
require 'PHPMailer\src\SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

$errorMessage = '';
$successMessage = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['enviado'])) {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $errorMessage = "Por favor, preencha todos os campos.";
        } 

        // Conexão com o banco de dados
        try {
            require_once('config.php');
            $pdo = connect_db();

            // Verifica se o email existe na tabela clientes
            $stmt = $pdo->prepare("SELECT * FROM clientes WHERE Email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verifica se o usuário foi encontrado e se a senha corresponde
            if ($user && password_verify($password, $user['Password'])) {
                $_SESSION['loggedin'] = true;
                $_SESSION['utilizador'] = $user['Email'];
                $_SESSION['nome_utilizador'] = $user['Nome'];
                $_SESSION['Email'] = $user['Email'];
                $_SESSION['Tipo'] = $user['Tipo'];
               
                if ($user['Tipo'] === "A") {
                    header("location: admin/Adminbackofice1.php");
                    exit;
                } else {
                    header("location: Dashboard.php");
                    exit;
                }
            } else {
                $errorMessage = "Email ou senha incorretos.";
            }

        } catch (PDOException $e) {
            $errorMessage = "Erro ao conectar ao banco de dados: " . $e->getMessage();
        }
    } elseif (isset($_POST['recuperar_senha'])) {
        $email = trim($_POST['email'] ?? '');
        
        if (empty($email)) {
            $errorMessage = "Por favor, insira o seu email.";
        } else {
            try {
                require_once('config.php');
                $pdo = connect_db();
                
                // Verificar se o email existe
                $stmt = $pdo->prepare("SELECT * FROM clientes WHERE Email = ?");
                $stmt->execute([$email]);
                
                if ($stmt->rowCount() > 0) {
                    // Generate unique token
                    $token = bin2hex(random_bytes(32));
                    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    
                    // Save token to database
                    $updateStmt = $pdo->prepare("UPDATE clientes SET reset_token = ?, reset_token_expires = ? WHERE Email = ?");
                    $updateStmt->execute([$token, $expiry, $email]);

                    // Create a new PHPMailer instance
                    $mail = new PHPMailer(true);

                    try {
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'standgcars@gmail.com';
                        $mail->Password = 'ichl vvtp dmod pbhc';
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = 587;

                        $mail->setFrom('standgcars@gmail.com', 'G-Cars');
                        $mail->addAddress($email);

                        $mail->isHTML(true);
                        $resetLink = "http://localhost/PAP/reset_password.php?token=" . $token;
                        $mail->Subject = 'Recuperação de Senha - G-Cars';
                        $mail->Body = "Para redefinir sua senha, clique no link abaixo:<br><br>"
                                   . "<a href='{$resetLink}'>Redefinir Senha</a><br><br>"
                                   . "Este link é válido por 1 hora.<br>"
                                   . "Se não solicitou esta recuperação, por favor ignore este email.";

                        if ($mail->send()) {
                            header("Location: emailenviado.php");
                            exit();
                        } else {
                            $errorMessage = "Erro ao enviar o email. Por favor, tente novamente.";
                        }
                    } catch (Exception $e) {
                        $errorMessage = "Erro ao enviar o email. Por favor, tente novamente.";
                    }
                } else {
                    $errorMessage = "Email não encontrado no sistema.";
                }
            } catch (PDOException $e) {
                $errorMessage = "Erro ao processar a solicitação: " . $e->getMessage();
            }
        }
    }
}
?>

<div class="container my-4">
    <div class="bg-white rounded shadow p-4 mt-3 mx-auto" style="max-width: 500px;">
        <?php if (isset($_GET['recuperar'])): ?>
            <h2 class="text-center text-uppercase fw-bold text-dark">Recuperar Senha</h2>
            
            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
            <?php endif; ?>
            
            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div>
            <?php endif; ?>

            <form action="<?= $_SERVER["PHP_SELF"]; ?>" method="post">
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <input type="hidden" name="recuperar_senha" value="TRUE">
                <button type="submit" class="btn btn-success w-100">Recuperar Senha</button>
            </form>
            
            <p class="text-center mt-3">
                <a href="Login.php" class="text-decoration-none">Voltar ao Login</a>
            </p>
        <?php else: ?>
            <h2 class="text-center text-uppercase fw-bold text-dark">Login</h2>

            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
            <?php endif; ?>

            <form action="<?= $_SERVER["PHP_SELF"]; ?>" method="post">
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password" required>
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <input type="hidden" name="enviado" value="TRUE">
                <button type="submit" class="btn btn-success w-100">Entrar</button>
            </form>

            <p class="text-center mt-3">
                Não tem uma conta? <a href="Registo.php" class="text-decoration-none">Registar</a>
            </p>
            <p class="text-center mt-3">
                <a href="Login.php?recuperar=true" class="text-decoration-none">Esqueceu sua senha?</a>
            </p>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('togglePassword').addEventListener('click', function () {
    const passwordInput = document.getElementById('password');
    const icon = this.querySelector('i');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
});
</script>

<?php include 'includes/footer.php'; ?>
