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

require_once('../config.php');
$conn = connect_db();

$mensagem = '';
$erro = '';

// Define o diretório para upload das imagens
$upload_dir = "uploads/carros/";

// Verificar se o diretório existe, se não, criar
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Garantir que o diretório tem permissões corretas
if (is_dir($upload_dir)) {
    chmod($upload_dir, 0777);
}

// Limpar mensagem de sucesso ao carregar a página pela primeira vez
if ($_SERVER["REQUEST_METHOD"] != "POST" && !isset($_GET['success'])) {
    $mensagem = '';
}

// Simplificando o processamento do formulário
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['adicionar_carro'])) {
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

    // Validação por campo
    if (empty($id_modelo)) $errors[] = "Selecione uma marca e modelo.";
    if (empty($transmissao)) $errors[] = "Selecione o tipo de transmissão.";
    if (empty($potencia)) $errors[] = "Informe a potência do veículo.";
    if (empty($preco)) $errors[] = "Informe o preço do veículo.";
    if (empty($km)) $errors[] = "Informe a quilometragem do veículo.";
    if (empty($combustivel)) $errors[] = "Selecione o tipo de combustível.";
    if (empty($lotacao)) $errors[] = "Selecione a lotação do veículo.";
    if (empty($ano)) $errors[] = "Selecione o ano do veículo.";
    
    // Verificar se há arquivos de imagem submetidos
    if (!isset($_FILES['imagens']) || empty($_FILES['imagens']['name'][0])) {
        $errors[] = "Por favor, selecione pelo menos uma imagem.";
    } else {
        // Verificar quantidade e tamanho das imagens
        $totalFiles = count(array_filter($_FILES['imagens']['name']));
        if ($totalFiles < 1) {
            $errors[] = "Por favor, selecione pelo menos uma imagem.";
        } else if ($totalFiles > 3) {
            $errors[] = "Você só pode enviar até 3 imagens.";
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

    // Se houver erros de validação
    if (!empty($errors)) {
        $erro = "<ul><li>" . implode("</li><li>", $errors) . "</li></ul>";
    } else {
        try {
            // Verificar o diretório de upload
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Verificar permissões
            if (!is_writable($upload_dir)) {
                chmod($upload_dir, 0777);
                if (!is_writable($upload_dir)) {
                    throw new Exception("O diretório de upload não tem permissões de escrita. Verifique as permissões da pasta: $upload_dir");
                }
            }
            
            // Iniciar transação
            $conn->beginTransaction();
            
            // Inserir carro - Garantir que os valores estão no formato correto para o BD
            $sql = "INSERT INTO carros (Id_Modelo, Transmissao, Potencia, Preco, KM, Combustivel, Lotacao, Ano) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                   
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([
                (int)$id_modelo,       // Garantir que é um int
                $transmissao,          // String
                $potencia,             // String (VARCHAR(4) na BD)
                (float)$preco,         // Garantir que é um float
                (int)$km,              // Garantir que é um int
                $combustivel,          // String
                $lotacao,              // String (VARCHAR(2) na BD)
                $ano                   // String (VARCHAR(4) na BD)
            ]);
            
            if (!$result) {
                throw new Exception("Falha ao inserir dados do carro no banco de dados: " . implode(", ", $stmt->errorInfo()));
            }
            
            $id_carro = $conn->lastInsertId();
            $uploadedImagesCount = 0;
            
            // Processar imagens com verificações adicionais
            $uploadedFiles = [];
            
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
                
                // Gerar nome único com timestamp para evitar cache
                $new_filename = "carro_" . $id_carro . "_" . time() . "_" . uniqid() . "." . $file_ext;
                $destination = $upload_dir . $new_filename;
                
                // Fazer upload com validação explícita
                if (!is_uploaded_file($tmp_name)) {
                    throw new Exception("Arquivo não é um upload válido: " . $file_name);
                }
                
                if (!move_uploaded_file($tmp_name, $destination)) {
                    throw new Exception("Falha ao fazer upload da imagem: " . $file_name . 
                                       ". Código de erro: " . $_FILES['imagens']['error'][$i] . 
                                       ". Diretório: " . $upload_dir);
                }
                
                // Verificar se o arquivo realmente foi criado
                if (!file_exists($destination)) {
                    throw new Exception("O arquivo não foi criado após o upload: " . $destination);
                }
                
                $uploadedFiles[] = $destination;
                
                // Inserir na tabela de imagens - certifique-se de usar o caminho relativo correto
                $sql_img = "INSERT INTO imagem (ID_Carro, imagem) VALUES (?, ?)";
                $stmt_img = $conn->prepare($sql_img);
                if (!$stmt_img->execute([$id_carro, $destination])) {
                    throw new Exception("Falha ao salvar referência da imagem no banco de dados: " . 
                                       implode(", ", $stmt_img->errorInfo()));
                }
                
                $uploadedImagesCount++;
            }
            
            if ($uploadedImagesCount == 0) {
                throw new Exception("Nenhuma imagem foi carregada. Por favor, tente novamente.");
            }
            
            // Confirmar transação
            $conn->commit();
            
            // Limpar qualquer cache do navegador
            header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
            header("Cache-Control: post-check=0, pre-check=0", false);
            header("Pragma: no-cache");
            
            // Sucesso
            $mensagem = "Carro adicionado com sucesso!";
            echo "<script>
                alert('Carro adicionado com sucesso!');
                window.location.href = 'adicionar_carro.php?success=1&t=" . time() . "';
            </script>";
            exit;
            
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            
            // Remover imagens enviadas em caso de erro
            if (!empty($uploadedFiles)) {
                foreach ($uploadedFiles as $file) {
                    if (file_exists($file)) {
                        @unlink($file);
                    }
                }
            }
            
            $erro = "Erro: " . $e->getMessage();
        }
    }
}

// Exibir mensagem de sucesso após redirecionamento
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $mensagem = "Carro adicionado com sucesso!";
}

// Buscar modelos
try {
    // First, get all brands
    $sql_marcas = "SELECT idMarca, marca FROM marca ORDER BY marca";
    $stmt_marcas = $conn->query($sql_marcas);
    $marcas = $stmt_marcas->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all models (we'll filter them using JavaScript)
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
    <title>Adicionar Carro - G-Cars</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
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
        #imagePreviewContainer {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .preview-item {
            position: relative;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<?php require('../navbar.php'); ?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Adicionar Novo Carro</h1>
        <a href="Adminbackofice1.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Voltar ao Painel
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
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
                <!-- Campos do formulário -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="marca" class="form-label">Marca</label>
                        <select class="form-select" id="marca" name="marca" required>
                            <option value="">Selecione uma marca</option>
                            <?php foreach ($marcas as $marca): ?>
                                <option value="<?php echo $marca['idMarca']; ?>">
                                    <?php echo htmlspecialchars($marca['marca']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="id_modelo" class="form-label">Modelo</label>
                        <select class="form-select" id="id_modelo" name="id_modelo" required disabled>
                            <option value="">Selecione primeiro uma marca</option>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="transmissao" class="form-label">Transmissão</label>
                        <select class="form-select" id="transmissao" name="transmissao" required>
                            <option value="">Selecione o tipo de transmissão</option>
                            <option value="Manual">Manual</option>
                            <option value="Automática">Automática</option>
                            <option value="Semi-automática">Semi-automática</option>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="potencia" class="form-label">Potência (CV)</label>
                        <input type="number" class="form-control" id="potencia" name="potencia" min="1" max="9999" required>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="preco" class="form-label">Preço (€)</label>
                        <input type="number" class="form-control" id="preco" name="preco" step="0.01" min="0" required>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="km" class="form-label">Quilometragem</label>
                        <input type="number" class="form-control" id="km" name="km" min="0" required>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="combustivel" class="form-label">Combustível</label>
                        <select class="form-select" id="combustivel" name="combustivel" required>
                            <option value="">Selecione o combustível</option>
                            <option value="Gasolina">Gasolina</option>
                            <option value="Diesel">Diesel</option>
                            <option value="Elétrico">Elétrico</option>
                            <option value="Híbrido">Híbrido</option>
                            <option value="GPL">GPL</option>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="lotacao" class="form-label">Lotação</label>
                        <select class="form-select" id="lotacao" name="lotacao" required>
                            <option value="">Selecione a lotação</option>
                            <?php for ($i = 2; $i <= 9; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?> lugares</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="ano" class="form-label">Ano</label>
                        <select class="form-select" id="ano" name="ano" required>
                            <option value="">Selecione o ano</option>
                            <?php for ($i = date("Y"); $i >= 1970; $i--): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <hr>
                
                <h4 class="mt-4 mb-3">Imagens do Veículo</h4>
                <p class="text-muted">Adicione de 1 a 3 imagens do veículo (mínimo 1 imagem obrigatória).</p>
                
                <div class="mb-3">
                    <label for="imagens" class="form-label">Selecione as Imagens</label>
                    <input type="file" class="form-control" id="imagens" name="imagens[]" accept="image/jpeg,image/png,image/gif,image/webp" multiple required>
                    <small class="form-text text-muted">Formatos aceitos: JPG, JPEG, PNG, GIF, WEBP. Mínimo 1, máximo 3 arquivos. Tamanho máximo: 5MB por imagem.</small>
                </div>

                <div id="imagePreviewContainer" class="row"></div>
                
                <div class="mt-4">
                    <button type="submit" name="adicionar_carro" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Adicionar Carro
                    </button>
                    <button type="reset" class="btn btn-secondary ms-2" id="resetBtn">
                        <i class="bi bi-arrow-counterclockwise"></i> Limpar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Adicionar script para gerenciar a dependência entre marca e modelo
document.addEventListener('DOMContentLoaded', function() {
    const marcaSelect = document.getElementById('marca');
    const modeloSelect = document.getElementById('id_modelo');
    
    // Store all models data as a JavaScript object
    const modelosData = <?php echo json_encode($modelos); ?>;
    
    console.log("Modelos carregados:", modelosData); // Debug
    
    // Handle change in brand selection
    marcaSelect.addEventListener('change', function() {
        const selectedMarcaId = this.value;
        console.log("Marca selecionada ID:", selectedMarcaId); // Debug
        
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
        // Convert both to same type (string) for comparison
        const filteredModels = modelosData.filter(model => String(model.idMarca) === String(selectedMarcaId));
        console.log("Modelos filtrados:", filteredModels); // Debug
        
        // Add filtered models to the dropdown
        filteredModels.forEach(model => {
            const option = document.createElement('option');
            option.text = model.Modelo;
            option.value = model.Id_Modelo;
            modeloSelect.add(option);
        });
        
        // If no models found for this brand
        if (filteredModels.length === 0) {
            const noModelsOption = document.createElement('option');
            noModelsOption.text = 'Nenhum modelo disponível para esta marca';
            noModelsOption.value = '';
            noModelsOption.disabled = true;
            modeloSelect.add(noModelsOption);
        }
    });
    
    // Existing image preview code
    const fileInput = document.getElementById('imagens');
    const previewContainer = document.getElementById('imagePreviewContainer');
    const resetBtn = document.getElementById('resetBtn');
    
    fileInput.addEventListener('change', function() {
        previewContainer.innerHTML = '';
        
        // Limitar a 3 arquivos
        if (fileInput.files.length > 3) {
            alert('Você pode selecionar no máximo 3 imagens.');
            fileInput.value = ''; // Limpar seleção
            return;
        }
        
        // Verificar tamanho total dos arquivos
        let totalSize = 0;
        let hasInvalidFile = false;
        
        for (let i = 0; i < fileInput.files.length; i++) {
            const file = fileInput.files[i];
            
            // Verificar se é uma imagem
            if (!file.type.match('image/(jpeg|png|gif|webp)')) {
                alert('O arquivo ' + file.name + ' não é uma imagem válida. Use apenas JPEG, PNG, GIF ou WEBP.');
                hasInvalidFile = true;
                break;
            }
            
            // Verificar tamanho individual (5MB por arquivo)
            const maxFileSize = 5 * 1024 * 1024; // 5MB
            if (file.size > maxFileSize) {
                alert('A imagem ' + file.name + ' excede o limite de 5MB. Por favor, selecione uma imagem menor.');
                hasInvalidFile = true;
                break;
            }
            
            totalSize += file.size;
        }
        
        if (hasInvalidFile) {
            fileInput.value = '';
            return;
        }
        
        // Limite de 15MB total
        const maxSize = 15 * 1024 * 1024; // 15MB em bytes
        if (totalSize > maxSize) {
            alert('O tamanho total das imagens excede o limite de 15MB. Por favor, selecione imagens menores.');
            fileInput.value = '';
            return;
        }
        
        // Mostrar previews
        for (let i = 0; i < fileInput.files.length; i++) {
            const file = fileInput.files[i];
            
            // Criar coluna para cada imagem
            const colDiv = document.createElement('div');
            colDiv.className = 'col-md-4 preview-item';
            
            // Criar elemento de imagem
            const img = document.createElement('img');
            img.className = 'img-preview img-fluid';
            img.alt = 'Preview';
            
            // Usar FileReader para exibir a imagem
            const reader = new FileReader();
            reader.onload = function(e) {
                img.src = e.target.result;
            };
            
            reader.readAsDataURL(file);
            
            colDiv.appendChild(img);
            previewContainer.appendChild(colDiv);
        }
    });
    
    // Limpar a visualização quando o botão de reset for clicado
    resetBtn.addEventListener('click', function() {
        previewContainer.innerHTML = '';
    });
    
    // Limpar o formulário e imagens ao carregar a página
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelector('form').reset();
        previewContainer.innerHTML = '';
    });
});
</script>

<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
