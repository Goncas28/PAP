<?php
if (!isset($_SESSION)) {
    session_start();
}

// Verifica se o usuário é administrador
if (!isset($_SESSION["Tipo"]) || $_SESSION["Tipo"] !== "A") {
    header("Location: login.php");
    exit;
}

// Aumentar limites de tempo e memória para o upload
ini_set('max_execution_time', 300);
ini_set('memory_limit', '256M');
ini_set('post_max_size', '50M');
ini_set('upload_max_filesize', '20M');
// Habilitar exibição de erros durante o desenvolvimento
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('config.php');
$conn = connect_db();

$mensagem = '';
$erro = '';

// Define o diretório para upload das imagens
$upload_dir = "uploads/carros/";

// Verificar se o ID do carro foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: gerir_veiculos.php?erro=id_nao_fornecido");
    exit;
}

$id_carro = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

// Buscar dados do carro
try {
    $sql_carro = "SELECT c.*, mo.Modelo, mo.idMarca, ma.marca 
                 FROM carros c 
                 JOIN modelo mo ON c.Id_Modelo = mo.Id_Modelo 
                 JOIN marca ma ON mo.idMarca = ma.idMarca 
                 WHERE c.ID_Carro = ?";
    $stmt_carro = $conn->prepare($sql_carro);
    $stmt_carro->execute([$id_carro]);
    
    if ($stmt_carro->rowCount() === 0) {
        header("Location: gerir_veiculos.php?erro=carro_nao_encontrado");
        exit;
    }
    
    $carro = $stmt_carro->fetch(PDO::FETCH_ASSOC);
    
    // Buscar imagens do carro
    $sql_imagens = "SELECT * FROM imagem WHERE ID_Carro = ?";
    $stmt_imagens = $conn->prepare($sql_imagens);
    $stmt_imagens->execute([$id_carro]);
    $imagens = $stmt_imagens->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $erro = "Erro ao buscar dados do carro: " . $e->getMessage();
}

// Processar o formulário quando enviado
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['atualizar_carro'])) {
    // Iniciar array de erros para campos específicos
    $errors = [];
    
    // Obter dados do formulário
    $id_modelo = filter_input(INPUT_POST, 'id_modelo', FILTER_SANITIZE_NUMBER_INT);
    $transmissao = htmlspecialchars(trim($_POST['transmissao'] ?? ''), ENT_QUOTES, 'UTF-8');
    $potencia = htmlspecialchars(trim($_POST['potencia'] ?? ''), ENT_QUOTES, 'UTF-8');
    $preco = filter_input(INPUT_POST, 'preco', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $km = filter_input(INPUT_POST, 'km', FILTER_SANITIZE_NUMBER_INT);
    $combustivel = htmlspecialchars(trim($_POST['combustivel'] ?? ''), ENT_QUOTES, 'UTF-8'); 
    $lotacao = htmlspecialchars(trim($_POST['lotacao'] ?? ''), ENT_QUOTES, 'UTF-8');
    $ano = htmlspecialchars(trim($_POST['ano'] ?? ''), ENT_QUOTES, 'UTF-8');

    // Validação básica
    if (empty($id_modelo)) $errors[] = "Selecione uma marca e modelo.";
    if (empty($transmissao)) $errors[] = "Selecione o tipo de transmissão.";
    if (empty($potencia)) $errors[] = "Informe a potência do veículo.";
    if (empty($preco)) $errors[] = "Informe o preço do veículo.";
    if (empty($km)) $errors[] = "Informe a quilometragem do veículo.";
    if (empty($combustivel)) $errors[] = "Selecione o tipo de combustível.";
    if (empty($lotacao)) $errors[] = "Selecione a lotação do veículo.";
    if (empty($ano)) $errors[] = "Selecione o ano do veículo.";

    // Verificar se há novas imagens para upload
    $novas_imagens = false;
    if (isset($_FILES['imagens']) && !empty($_FILES['imagens']['name'][0])) {
        $novas_imagens = true;
        
        // Verificar quantidade e tamanho das imagens
        $totalFiles = count(array_filter($_FILES['imagens']['name']));
        
        // Contar imagens existentes + novas imagens
        $total_imagens = count($imagens) + $totalFiles;
        
        if ($total_imagens > 3) {
            $errors[] = "O carro não pode ter mais de 3 imagens no total. Já existem " . count($imagens) . " imagens.";
        }
        
        // Verificar tipos de arquivos
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        foreach ($_FILES['imagens']['name'] as $key => $name) {
            if (!empty($name)) {
                $fileType = $_FILES['imagens']['type'][$key];
                if (!in_array($fileType, $allowedTypes)) {
                    $errors[] = "O arquivo '$name' não é uma imagem válida. Use apenas JPEG, PNG, GIF ou WEBP.";
                }
            }
        }
    }
    
    // Verificar imagens excluídas
    $imagens_excluidas = isset($_POST['excluir_imagem']) ? $_POST['excluir_imagem'] : [];
    
    // Se o usuário está tentando excluir todas as imagens e não adicionar novas
    if (count($imagens_excluidas) == count($imagens) && !$novas_imagens) {
        $errors[] = "O carro deve ter pelo menos uma imagem. Adicione novas imagens ou mantenha algumas das existentes.";
    }

    if (!empty($errors)) {
        $erro = "<ul><li>" . implode("</li><li>", $errors) . "</li></ul>";
    } else {
        try {
            // Iniciar transação
            $conn->beginTransaction();
            
            // Atualizar dados do carro
            $sql = "UPDATE carros SET Id_Modelo = ?, Transmissao = ?, Potencia = ?, Preco = ?, 
                   KM = ?, Combustivel = ?, Lotacao = ?, Ano = ? WHERE ID_Carro = ?";
            
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([
                (int)$id_modelo,
                $transmissao,
                $potencia,
                (float)$preco,
                (int)$km,
                $combustivel,
                $lotacao,
                $ano,
                $id_carro
            ]);
            
            if (!$result) {
                throw new Exception("Falha ao atualizar dados do carro: " . implode(", ", $stmt->errorInfo()));
            }
            
            // Processar exclusão de imagens
            foreach ($imagens_excluidas as $id_imagem) {
                // Primeiro obter o nome do arquivo para excluí-lo do sistema de arquivos
                $sql_get_imagem = "SELECT imagem FROM imagem WHERE ID_Imagem = ? AND ID_Carro = ?";
                $stmt_get_imagem = $conn->prepare($sql_get_imagem);
                $stmt_get_imagem->execute([$id_imagem, $id_carro]);
                $imagem_info = $stmt_get_imagem->fetch(PDO::FETCH_ASSOC);
                
                if ($imagem_info) {
                    $caminho_imagem = $imagem_info['imagem'];
                    
                    // Excluir do banco de dados
                    $sql_del = "DELETE FROM imagem WHERE ID_Imagem = ? AND ID_Carro = ?";
                    $stmt_del = $conn->prepare($sql_del);
                    if (!$stmt_del->execute([$id_imagem, $id_carro])) {
                        throw new Exception("Falha ao excluir imagem do banco de dados.");
                    }
                    
                    // Excluir arquivo se existir
                    if (file_exists($caminho_imagem)) {
                        unlink($caminho_imagem);
                    }
                }
            }
            
            // Processar upload de novas imagens
            $uploadedFiles = [];
            if ($novas_imagens) {
                for ($i = 0; $i < count($_FILES['imagens']['name']); $i++) {
                    if ($_FILES['imagens']['error'][$i] !== 0 || empty($_FILES['imagens']['name'][$i])) {
                        continue;
                    }
                    
                    $tmp_name = $_FILES['imagens']['tmp_name'][$i];
                    $file_name = $_FILES['imagens']['name'][$i];
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    
                    // Verificar extensão
                    $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    if (!in_array($file_ext, $allowed_exts)) {
                        throw new Exception("Arquivo inválido. Apenas imagens são permitidas: " . $file_name);
                    }
                    
                    // Gerar nome único
                    $new_filename = "carro_" . $id_carro . "_" . time() . "_" . uniqid() . "." . $file_ext;
                    $destination = $upload_dir . $new_filename;
                    
                    // Fazer upload
                    if (!move_uploaded_file($tmp_name, $destination)) {
                        throw new Exception("Falha ao fazer upload da imagem: " . $file_name);
                    }
                    
                    $uploadedFiles[] = $destination;
                    
                    // Inserir na tabela de imagens
                    $sql_img = "INSERT INTO imagem (ID_Carro, imagem) VALUES (?, ?)";
                    $stmt_img = $conn->prepare($sql_img);
                    if (!$stmt_img->execute([$id_carro, $destination])) {
                        throw new Exception("Falha ao salvar referência da imagem no banco de dados.");
                    }
                }
            }
            
            // Confirmar transação
            $conn->commit();
            
            // Redirecionar após sucesso
            header("Location: edit_carro.php?id=$id_carro&success=1&t=" . time());
            exit;
            
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            
            // Remover arquivos enviados em caso de erro
            foreach ($uploadedFiles as $file) {
                if (file_exists($file)) {
                    @unlink($file);
                }
            }
            
            $erro = "Erro: " . $e->getMessage();
        }
    }
}

// Exibir mensagem de sucesso após redirecionamento
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $mensagem = "Carro atualizado com sucesso!";
}

// Buscar marcas e modelos para os dropdowns
try {
    // Get all brands
    $sql_marcas = "SELECT idMarca, marca FROM marca ORDER BY marca";
    $stmt_marcas = $conn->query($sql_marcas);
    $marcas = $stmt_marcas->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all models
    $sql_modelos = "SELECT mo.Id_Modelo, mo.Modelo, mo.idMarca, ma.marca 
                   FROM modelo mo 
                   JOIN marca ma ON mo.idMarca = ma.idMarca 
                   ORDER BY ma.marca, mo.Modelo";
    $stmt_modelos = $conn->query($sql_modelos);
    $modelos = $stmt_modelos->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $erro = "Erro ao buscar marcas e modelos: " . $e->getMessage();
    $marcas = [];
    $modelos = [];
}

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Carro - GCars</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            padding-top: 60px;
        }
        .img-preview {
            max-width: 100%;
            max-height: 200px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .existing-image {
            position: relative;
            margin-bottom: 20px;
        }
        .image-checkbox {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 100;
        }
        .image-checkbox-label {
            background-color: rgba(255, 255, 255, 0.7);
            padding: 5px;
            border-radius: 3px;
            cursor: pointer;
        }
    </style>
</head>
<body>

<?php require('navbar.php'); ?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Editar Carro</h1>
        <a href="gerir_veiculos.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Voltar para Gerenciamento
        </a>
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
        <div class="card-body">
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $id_carro); ?>" enctype="multipart/form-data">
                <!-- Campos do formulário -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="marca" class="form-label">Marca</label>
                        <select class="form-select" id="marca" name="marca" required>
                            <option value="">Selecione uma marca</option>
                            <?php foreach ($marcas as $marca): ?>
                                <option value="<?php echo $marca['idMarca']; ?>" <?php echo ($marca['idMarca'] == $carro['idMarca']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($marca['marca']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="id_modelo" class="form-label">Modelo</label>
                        <select class="form-select" id="id_modelo" name="id_modelo" required>
                            <option value="">Selecione primeiro uma marca</option>
                            <?php foreach ($modelos as $modelo): 
                                if ($modelo['idMarca'] == $carro['idMarca']): ?>
                                <option value="<?php echo $modelo['Id_Modelo']; ?>" <?php echo ($modelo['Id_Modelo'] == $carro['Id_Modelo']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($modelo['Modelo']); ?>
                                </option>
                                <?php endif; 
                            endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="transmissao" class="form-label">Transmissão</label>
                        <select class="form-select" id="transmissao" name="transmissao" required>
                            <option value="">Selecione o tipo de transmissão</option>
                            <option value="Manual" <?php echo ($carro['Transmissao'] == 'Manual') ? 'selected' : ''; ?>>Manual</option>
                            <option value="Automática" <?php echo ($carro['Transmissao'] == 'Automática') ? 'selected' : ''; ?>>Automática</option>
                            <option value="Semi-automática" <?php echo ($carro['Transmissao'] == 'Semi-automática') ? 'selected' : ''; ?>>Semi-automática</option>
                        </select>
                    </div>
                
                    <div class="col-md-6 mb-3">
                        <label for="combustivel" class="form-label">Combustível</label>
                        <select class="form-select" id="combustivel" name="combustivel" required>
                            <option value="">Selecione o combustível</option>
                            <option value="Gasolina" <?php echo ($carro['Combustivel'] == 'Gasolina') ? 'selected' : ''; ?>>Gasolina</option>
                            <option value="Diesel" <?php echo ($carro['Combustivel'] == 'Diesel') ? 'selected' : ''; ?>>Diesel</option>
                            <option value="Elétrico" <?php echo ($carro['Combustivel'] == 'Elétrico') ? 'selected' : ''; ?>>Elétrico</option>
                            <option value="Híbrido" <?php echo ($carro['Combustivel'] == 'Híbrido') ? 'selected' : ''; ?>>Híbrido</option>
                            <option value="GPL" <?php echo ($carro['Combustivel'] == 'GPL') ? 'selected' : ''; ?>>GPL</option>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="potencia" class="form-label">Potência (CV)</label>
                        <input type="number" class="form-control" id="potencia" name="potencia" min="1" max="9999" required value="<?php echo htmlspecialchars($carro['Potencia']); ?>">
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="preco" class="form-label">Preço (€)</label>
                        <input type="number" class="form-control" id="preco" name="preco" step="0.01" min="0" required value="<?php echo htmlspecialchars($carro['Preco']); ?>">
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="km" class="form-label">Quilometragem</label>
                        <input type="number" class="form-control" id="km" name="km" min="0" required value="<?php echo htmlspecialchars($carro['KM']); ?>">
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="lotacao" class="form-label">Lotação</label>
                        <select class="form-select" id="lotacao" name="lotacao" required>
                            <option value="">Selecione a lotação</option>
                            <?php for ($i = 2; $i <= 9; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo ($carro['Lotacao'] == $i) ? 'selected' : ''; ?>>
                                    <?php echo $i; ?> lugares
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="ano" class="form-label">Ano</label>
                        <select class="form-select" id="ano" name="ano" required>
                            <option value="">Selecione o ano</option>
                            <?php for ($i = date("Y"); $i >= 1970; $i--): ?>
                                <option value="<?php echo $i; ?>" <?php echo ($carro['Ano'] == $i) ? 'selected' : ''; ?>>
                                    <?php echo $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <hr>
                
                <h4 class="mt-4 mb-3">Imagens Atuais</h4>
                <p class="text-muted">Marque as caixas para excluir as imagens existentes. O carro deve ter pelo menos uma imagem.</p>
                
                <div class="row mb-4">
                    <?php if (empty($imagens)): ?>
                        <div class="col-12">
                            <div class="alert alert-info">
                                Este carro não possui imagens. Adicione pelo menos uma imagem abaixo.
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($imagens as $imagem): ?>
                            <div class="col-md-4 existing-image">
                                <div class="image-checkbox">
                                    <label class="image-checkbox-label">
                                        <input type="checkbox" name="excluir_imagem[]" value="<?php echo $imagem['ID_Imagem']; ?>">
                                        Excluir
                                    </label>
                                </div>
                                <img src="<?php echo htmlspecialchars($imagem['imagem']); ?>" class="img-fluid img-preview" alt="Imagem do carro">
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <h4 class="mb-3">Adicionar Novas Imagens</h4>
                <p class="text-muted">Você pode adicionar até 3 imagens no total para o veículo.</p>
                
                <div class="mb-3">
                    <label for="imagens" class="form-label">Selecione as Imagens</label>
                    <input type="file" class="form-control" id="imagens" name="imagens[]" accept="image/jpeg,image/png,image/gif,image/webp" multiple>
                    <small class="form-text text-muted">Formatos aceitos: JPG, JPEG, PNG, GIF, WEBP. O máximo total de imagens é 3.</small>
                </div>

                <div id="imagePreviewContainer" class="row"></div>
                
                <div class="mt-4 d-flex justify-content-between">
                    <div>
                        <button type="submit" name="atualizar_carro" class="btn btn-primary">
                            <i class="bi bi-save"></i> Salvar Alterações
                        </button>
                        <button type="reset" class="btn btn-secondary ms-2" id="resetBtn">
                            <i class="bi bi-arrow-counterclockwise"></i> Restaurar Valores
                        </button>
                    </div>
                    
                    <a href="gerir_veiculos.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Script para gerenciar a dependência entre marca e modelo
document.addEventListener('DOMContentLoaded', function() {
    const marcaSelect = document.getElementById('marca');
    const modeloSelect = document.getElementById('id_modelo');
    
    // Store all models data as a JavaScript object
    const modelosData = <?php echo json_encode($modelos); ?>;
    
    // Handle change in brand selection
    marcaSelect.addEventListener('change', function() {
        const selectedMarcaId = this.value;
        
        // Clear current models
        modeloSelect.innerHTML = '';
        
        // If no brand is selected, disable the model dropdown
        if (!selectedMarcaId) {
            modeloSelect.disabled = true;
            modeloSelect.innerHTML = '<option value="">Selecione primeiro uma marca</option>';
            return;
        }
        
        // Enable the model dropdown
        modeloSelect.disabled = false;
        
        // Add default option
        const defaultOption = document.createElement('option');
        defaultOption.text = 'Selecione um modelo';
        defaultOption.value = '';
        modeloSelect.add(defaultOption);
        
        // Filter models by the selected brand
        const filteredModels = modelosData.filter(model => String(model.idMarca) === String(selectedMarcaId));
        
        // Add filtered models to the dropdown
        filteredModels.forEach(model => {
            const option = document.createElement('option');
            option.text = model.Modelo;
            option.value = model.Id_Modelo;
            modeloSelect.add(option);
        });
    });
    
    // Preview for new images
    const fileInput = document.getElementById('imagens');
    const previewContainer = document.getElementById('imagePreviewContainer');
    
    fileInput.addEventListener('change', function() {
        previewContainer.innerHTML = '';
        
        // Contar imagens existentes não marcadas para exclusão
        const imagensExistentes = document.querySelectorAll('.existing-image').length;
        const imagensParaExcluir = document.querySelectorAll('input[name="excluir_imagem[]"]:checked').length;
        const imagensRestantes = imagensExistentes - imagensParaExcluir;
        
        // Limitar o total de imagens
        if (fileInput.files.length + imagensRestantes > 3) {
            alert(`Você pode ter no máximo 3 imagens no total. Atualmente tem ${imagensRestantes} imagem(ns) e está tentando adicionar ${fileInput.files.length} imagem(ns).`);
            fileInput.value = '';
            return;
        }
        
        // Preview as imagens selecionadas
        for (let i = 0; i < fileInput.files.length; i++) {
            const file = fileInput.files[i];
            
            if (!file.type.match('image.*')) {
                continue;
            }
            
            const colDiv = document.createElement('div');
            colDiv.className = 'col-md-4';
            
            const img = document.createElement('img');
            img.className = 'img-preview img-fluid';
            img.alt = 'Nova imagem';
            
            const reader = new FileReader();
            reader.onload = function(e) {
                img.src = e.target.result;
            };
            
            reader.readAsDataURL(file);
            
            colDiv.appendChild(img);
            previewContainer.appendChild(colDiv);
        }
    });
    
    // Atualizar contagem quando as caixas de exclusão são marcadas/desmarcadas
    const checkboxes = document.querySelectorAll('input[name="excluir_imagem[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const imagensExistentes = document.querySelectorAll('.existing-image').length;
            const imagensParaExcluir = document.querySelectorAll('input[name="excluir_imagem[]"]:checked').length;
            const imagensRestantes = imagensExistentes - imagensParaExcluir;
            
            // Se não houver imagens novas selecionadas e todas as existentes estiverem marcadas para exclusão
            if (fileInput.files.length === 0 && imagensRestantes === 0) {
                alert('O carro deve ter pelo menos uma imagem. Desmarque pelo menos uma imagem para exclusão ou adicione novas imagens.');
                this.checked = false;
            }
        });
    });
});
</script>

<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
