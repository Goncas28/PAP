<?php
if (!isset($_SESSION)) {
    session_start();
}

// Verificar se o usuário é admin
if (!isset($_SESSION["Tipo"]) || $_SESSION["Tipo"] !== "A") {
    header("Location: ../index.php");
    exit();
}

// Include database connection
require_once("../config.php");
$pdo = connect_db();

$title = "Definições - G-Cars";

// Processar o formulário quando enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $horario = $_POST['horario'];
    $email = $_POST['email'];
    $telefone = $_POST['telefone'];
    
    try {
        // Atualizar as configurações no banco de dados
        $sql = "UPDATE configuracoes SET 
                horario_funcionamento = ?, 
                email_contacto = ?, 
                telefone_contacto = ?";
                
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$horario, $email, $telefone])) {
            $mensagem_sucesso = "Configurações atualizadas com sucesso!";
        } else {
            $mensagem_erro = "Erro ao atualizar as configurações.";
        }
    } catch(PDOException $e) {
        $mensagem_erro = "Erro: " . $e->getMessage();
    }
}

// Buscar configurações atuais
try {
    $stmt = $pdo->query("SELECT * FROM configuracoes LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $mensagem_erro = "Erro ao carregar configurações: " . $e->getMessage();
}
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
        body { padding-top: 60px; }
    </style>
</head>
<body>

<?php require('../navbar.php'); ?>

<div class="container my-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><i class="bi bi-gear-fill"></i> Configurações do Sistema</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($mensagem_sucesso)): ?>
                        <div class="alert alert-success"><?php echo $mensagem_sucesso; ?></div>
                    <?php endif; ?>
                    <?php if (isset($mensagem_erro)): ?>
                        <div class="alert alert-danger"><?php echo $mensagem_erro; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="mb-3">
                            <label for="horario" class="form-label">Horário de Funcionamento</label>
                            <input type="text" class="form-control" id="horario" name="horario" 
                                   value="<?php echo htmlspecialchars($config['horario_funcionamento'] ?? ''); ?>" 
                                   placeholder="Ex: Segunda a Sexta, 9h às 18h">
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email de Contacto</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($config['email_contacto'] ?? ''); ?>"
                                   placeholder="exemplo@email.com">
                        </div>

                        <div class="mb-3">
                            <label for="telefone" class="form-label">Telefone de Contacto</label>
                            <div class="input-group">
                                <span class="input-group-text">+351</span>
                                <input type="text" class="form-control" id="telefone" name="telefone" 
                                       value="<?php echo htmlspecialchars($config['telefone_contacto'] ?? ''); ?>"
                                       placeholder="969053456">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Salvar Alterações
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>