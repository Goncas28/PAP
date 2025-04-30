<?php
session_start();
$title = "Redefinir Senha - G-Cars";
include "includes/header.php";
require('navbar.php');

$error = '';
$success = '';

if (!isset($_GET['token'])) {
    header("Location: Login.php");
    exit();
}

$token = $_GET['token'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once('config.php');
    $pdo = connect_db();
    
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($password) || empty($confirm_password)) {
        $error = "Preencha todos os campos.";
    } elseif ($password !== $confirm_password) {
        $error = "As senhas não correspondem.";
    } else {
        // Verify token and update password
        $stmt = $pdo->prepare("SELECT Email FROM clientes WHERE reset_token = ? AND reset_token_expires > NOW()");
        $stmt->execute([$token]);
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $updateStmt = $pdo->prepare("UPDATE clientes SET Password = ?, reset_token = NULL, reset_token_expires = NULL WHERE Email = ?");
            $updateStmt->execute([$hashed_password, $user['Email']]);
            
            $success = "Senha atualizada com sucesso!";
        } else {
            $error = "Token inválido ou expirado.";
        }
    }
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3>Redefinir Senha</h3>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <?php echo $success; ?>
                            <br>
                            <a href="Login.php">Clique aqui para fazer login</a>
                        </div>
                    <?php else: ?>
                        <form method="post">
                            <div class="mb-3">
                                <label for="password" class="form-label">Nova Senha</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirmar Senha</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Atualizar Senha</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>