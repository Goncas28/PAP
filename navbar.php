<?php
if (!isset($_SESSION)) {
    session_start();
}

// Determine if we're in the admin folder to set relative paths correctly
$in_admin_folder = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
$admin_prefix = $in_admin_folder ? '../admin/' : 'admin/';
$root_prefix = $in_admin_folder ? '../' : '';
?>
<nav class="navbar navbar-expand-lg bg-body-tertiary bg-dark border-bottom border-body fixed-top" data-bs-theme="dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo $root_prefix; ?>index.php">
            <img src="<?php echo $root_prefix; ?>Imagens/stand.png" alt="GCars Logo" height="40" class="d-inline-block align-text-top">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav ms-auto me-2 mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" aria-current="page" href="<?php echo $root_prefix; ?>VerViaturas.php">Veículos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $root_prefix; ?>Servicos.php">Serviços</a>
                </li>
                
                <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                    <!-- Opções para usuários autenticados -->
                    <?php if (isset($_SESSION["Tipo"]) && $_SESSION["Tipo"] === "A"): ?>
                        <!-- Opções específicas para administradores -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle show" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-gear-fill"></i> Painel Administrativo
                            </a>
                            <ul class="dropdown-menu dropdown-menu-dark">
                                <li><a class="dropdown-item" href="<?php echo $admin_prefix; ?>ver_visitas.php">Gerir Visitas</a></li>
                                <li><a class="dropdown-item" href="<?php echo $admin_prefix; ?>../gerir_veiculos.php">Gerir Veículos</a></li>
                                <li><a class="dropdown-item" href="<?php echo $admin_prefix; ?>gerir_clientes.php">Gerir Clientes</a></li>
                                <li><a class="dropdown-item" href="<?php echo $admin_prefix; ?>../gerir_marcas.php">Gerir Marca</a></li>
                                <li><a class="dropdown-item" href="<?php echo $admin_prefix; ?>../gerir_modelos.php">Gerir Modelo</a></li>
                                <li><hr class="dropdown-divider"></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <!-- Opções para usuários normais - Marcar Visita sempre na navbar -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $root_prefix; ?>marcar_visita.php">Marcar Visita</a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
                
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $root_prefix; ?>Pesquisa.php">Pesquisar</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $root_prefix; ?>Contactos.php">Contactos</a>
                </li>
            </ul>
            
            <!-- Área de autenticação à direita -->
            <div class="d-flex align-items-center">
                <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                    <!-- Menu dropdown do usuário logado -->
                    <div class="dropdown">
                        <a class="btn btn-outline-light dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i> 
                            <?php 
                            if (isset($_SESSION["nome_utilizador"]) && !empty($_SESSION["nome_utilizador"])) {
                                echo htmlspecialchars($_SESSION["nome_utilizador"]);
                            } else {
                                echo htmlspecialchars($_SESSION["utilizador"]);
                            }
                            ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark">
                            <li><a class="dropdown-item" href="<?php echo $root_prefix; ?>Dashboard.php"><i class="bi bi-person"></i> Meu Perfil</a></li>
                            <li><a class="dropdown-item" href="<?php echo $root_prefix; ?>meus_favoritos.php"><i class="bi bi-heart"></i> Meus Favoritos</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo $root_prefix; ?>logout.php"><i class="bi bi-box-arrow-right"></i> Terminar Sessão</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <!-- Botões para usuários não logados -->
                    <a href="<?php echo $root_prefix; ?>Login.php" class="btn btn-outline-light me-2">
                        <i class="bi bi-box-arrow-in-right"></i> Iniciar Sessão
                    </a>
                    <a href="<?php echo $root_prefix; ?>Registo.php" class="btn btn-success">
                        <i class="bi bi-person-plus"></i> Criar Conta
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
