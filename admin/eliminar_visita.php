<?php
/**
 * Processamento de Eliminação de Visitas
 * 
 * Este arquivo gerencia a exclusão de visitas agendadas.
 * Apenas administradores podem acessar esta funcionalidade.
 * Remove a visita específica do arquivo de visitas.
 */

session_start();

// Verifica permissão de administrador
if ($_SESSION['Tipo'] !== "A") {
    header('Location: ../login.php');
    exit();
}

// Processa a eliminação da visita
if (isset($_GET['index'])) {
    $index = (int)$_GET['index'];
    $arquivo = '../visitas.txt';
    
    // Verifica se o arquivo existe
    if (file_exists($arquivo)) {
        // Lê e filtra as visitas
        $visitas = array_filter(explode("\n", file_get_contents($arquivo)));
        if (isset($visitas[$index])) {
            // Remove a visita selecionada
            unset($visitas[$index]);
            file_put_contents($arquivo, implode("\n", $visitas));
            $_SESSION['mensagem'] = "Visita eliminada com sucesso!";
        }
    }
}

header('Location: ver_visitas.php');
exit();
?>
