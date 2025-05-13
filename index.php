<?php
if (!isset($_SESSION)) {
    session_start();
}

require_once("config.php");
$pdo = connect_db();

// Fetch configurations
try {
    $stmt = $pdo->query("SELECT * FROM configuracoes LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $config = null;
}

$title = "G-Cars - Stand Automóvel";
include "includes/header.php";
require('navbar.php');
?>

<div class="container my-4">
    <h1 class="mb-4">Bem-vindo a G-Cars</h1>
    <p class="lead">O melhor stand de automóveis de Portugal!</p>
    
    <div class="row mt-5">
        <div class="col-md-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="card-title mb-4">Funcionalidades para Clientes</h2>
                    
                    <div class="accordion" id="functionalities">
                        <div class="accordion-item">
                            <h3 class="accordion-header" id="headingOne">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    <i class="bi bi-car-front me-2"></i> Explorar o Nosso Catálogo de Veículos
                                </button>
                            </h3>
                            <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#functionalities">
                                <div class="accordion-body">
                                    <p>Navegue pelo nosso extenso catálogo de carros disponíveis para venda. Pode filtrar por marca, modelo, ano, preço e outras características para encontrar exatamente o que procura.</p>
                                    <a href="admin/VerViaturas.php" class="btn btn-outline-primary mt-2">Ver Veículos Disponíveis</a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h3 class="accordion-header" id="headingTwo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    <i class="bi bi-calendar-check me-2"></i> Marcar Visitas
                                </button>
                            </h3>
                            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#functionalities">
                                <div class="accordion-body">
                                    <p>Encontrou um veículo interessante? Agende uma visita para ver o carro pessoalmente! Depois de criar uma conta e iniciar sessão, pode marcar visitas diretamente através da página de detalhes do veículo.</p>
                                    <p>As suas visitas agendadas ficam todas organizadas numa área pessoal, onde pode acompanhar as datas e horários.</p>
                                    <div class="d-flex gap-2 mt-2">
                                        <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                                            <a href="marcar_visita.php" class="btn btn-outline-primary">Marcar Visita</a>
                                        <?php else: ?>
                                            <a href="login.php" class="btn btn-outline-primary">Iniciar Sessão</a>
                                            <a href="Registo.php" class="btn btn-outline-secondary">Criar Conta</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h3 class="accordion-header" id="headingThree">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    <i class="bi bi-info-circle me-2"></i> Ver Detalhes Completos
                                </button>
                            </h3>
                            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#functionalities">
                                <div class="accordion-body">
                                    <p>Para cada veículo, oferecemos informações detalhadas e completas: especificações técnicas, fotos em alta qualidade, histórico do veículo, preço e condições de venda.</p>
                                    <p>Basta clicar em qualquer veículo para ver todos os detalhes disponíveis.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h3 class="accordion-header" id="headingFour">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                    <i class="bi bi-telephone me-2"></i> Contactar-nos Diretamente
                                </button>
                            </h3>
                            <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#functionalities">
                                <div class="accordion-body">
                                    <p>Tem dúvidas ou precisa de informações adicionais? Pode contactar-nos diretamente por telefone, email ou através do nosso formulário de contacto.</p>
                                    <p>A nossa equipa de profissionais está disponível para ajudar em todo o processo de escolha e compra do seu veículo.</p>
                                    <a href="contactos.php" class="btn btn-outline-primary mt-2">Contactos</a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h3 class="accordion-header" id="headingFive">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                                    <i class="bi bi-person-circle me-2"></i> Área Pessoal
                                </button>
                            </h3>
                            <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#functionalities">
                                <div class="accordion-body">
                                    <p>Ao criar uma conta no nosso site, terá acesso a uma área pessoal onde pode:</p>
                                    <ul>
                                        <li>Gerir as suas visitas agendadas</li>
                                        <li>Atualizar os seus dados pessoais</li>
                                       
                                    </ul>
                                    <?php if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true): ?>
                                        <a href="Registo.php" class="btn btn-outline-primary mt-2">Criar Conta</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h3 class="card-title">Horário de Funcionamento</h3>
                    <ul class="list-group list-group-flush mt-3">
                        <?php if ($config && $config['horario_funcionamento']): ?>
                            <li class="list-group-item">
                                <?php echo htmlspecialchars($config['horario_funcionamento']); ?>
                            </li>
                        <?php else: ?>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Segunda a Sexta</span>
                                <strong>9h - 19h</strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Sábados</span>
                                <strong>10h - 16h</strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Domingos e Feriados</span>
                                <strong>Fechado</strong>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-body">
                    <h3 class="card-title">Contactos Rápidos</h3>
                    <ul class="list-group list-group-flush mt-3">
                        <li class="list-group-item">
                            <i class="bi bi-telephone-fill me-2 text-primary"></i>
                            <?php echo $config && $config['telefone_contacto'] ? '+351 ' . htmlspecialchars($config['telefone_contacto']) : '+351 969053456'; ?>
                        </li>
                        <li class="list-group-item">
                            <i class="bi bi-envelope-fill me-2 text-primary"></i>
                            <?php echo $config && $config['email_contacto'] ? htmlspecialchars($config['email_contacto']) : 'goncas1416@gmail.com'; ?>
                        </li>
                        <li class="list-group-item">
                            <i class="bi bi-geo-alt-fill me-2 text-primary"></i> Viseu 123
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>

<!-- Make sure Bootstrap JS is loaded correctly -->

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Check if Bootstrap is properly loaded
        if (typeof bootstrap === 'undefined') {
            console.error('Bootstrap JavaScript não foi carregado corretamente.');
            return;
        }
        
        // Initialize dropdowns
        var dropdownElementList = document.querySelectorAll('.dropdown-toggle');
        dropdownElementList.forEach(function(dropdownToggleEl) {
            new bootstrap.Dropdown(dropdownToggleEl);
        });
        
        // Clear any custom event handlers that might be interfering with default behavior
        document.querySelectorAll('.accordion-button').forEach(function(button) {
            // Clone the button element to remove all event listeners
            var newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);
        });
        
        // Ensure Bootstrap's data-bs-toggle attribute works properly by setting it explicitly
        document.querySelectorAll('.accordion-button').forEach(function(btn) {
            btn.setAttribute('data-bs-toggle', 'collapse');
        });
        
        // Make sure the first accordion item is open by default
        var firstItem = document.querySelector('#collapseOne');
        if (firstItem) {
            firstItem.classList.add('show');
        }
    });
</script>
</body>
</html>