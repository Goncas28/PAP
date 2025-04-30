<?php
if (!isset($_SESSION)) {
    session_start();
}

// Verifica se o usuário é administrador
if (!isset($_SESSION["Tipo"]) || $_SESSION["Tipo"] !== "A") {
    header("Location: login.php");
    exit;
}

require_once('config.php');
$conn = connect_db();

$mensagem = '';
$erro = '';

// Processar exclusão se solicitado
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_marca = intval($_GET['id']);
    
    try {
        // Verificar se existem modelos associados a esta marca
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM modelo WHERE idMarca = ?");
        $stmt->execute([$id_marca]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['total'] > 0) {
            $erro = "Não é possível excluir esta marca pois existem modelos associados a ela. Exclua os modelos primeiro.";
        } else {
            // Inicie uma transação
            $conn->beginTransaction();
            
            // Exclua a marca
            $stmt = $conn->prepare("DELETE FROM marca WHERE idMarca = ?");
            $stmt->execute([$id_marca]);
            
            // Confirme a transação
            $conn->commit();
            
            $mensagem = "Marca excluída com sucesso.";
        }
    } catch (Exception $e) {
        // Reverta a transação em caso de erro
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $erro = "Erro ao excluir a marca: " . $e->getMessage();
    }
}

// Obtenha a lista de marcas
try {
    $sql = "SELECT * FROM marca ORDER BY marca";
    $stmt = $conn->query($sql);
    $marcas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = "Erro ao buscar marcas: " . $e->getMessage();
    $marcas = [];
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Marcas - G-Cars</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { padding-top: 60px; }
        .action-buttons { white-space: nowrap; }
    </style>
</head>
<body>

<?php require('navbar.php'); ?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Gerir Marcas</h1>
        <div>
            <a href="adicionar_marca.php" class="btn btn-primary me-2">
                <i class="bi bi-plus-circle"></i> Nova Marca
            </a>
            <a href="Adminbackofice1.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Voltar ao Painel
            </a>
        </div>
    </div>

    <?php if (!empty($mensagem)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?php echo $mensagem; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($erro)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $erro; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Marcas Existentes</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                           
                            <th>Nome da Marca</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($marcas)): ?>
                            <tr>
                                <td colspan="3" class="text-center">Nenhuma marca encontrada.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($marcas as $marca): ?>
                                <tr>
                                  <td><?php echo htmlspecialchars($marca['marca']); ?></td>
                                    <td class="action-buttons">
                                        <a href="javascript:void(0);" 
                                           onclick="confirmarExclusao(<?php echo $marca['idMarca']; ?>, '<?php echo htmlspecialchars($marca['marca'], ENT_QUOTES); ?>')" 
                                           class="btn btn-sm btn-danger" title="Excluir">
                                            <i class="bi bi-trash"></i> Remover
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function confirmarExclusao(id, nome) {
    if (confirm('Tem certeza que deseja excluir a marca "' + nome + '"? Esta ação não pode ser desfeita.')) {
        window.location.href = 'gerir_marcas.php?action=delete&id=' + id;
    }
}
</script>

<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
