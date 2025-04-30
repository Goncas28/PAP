<?php
/**
 * Processamento de Marcação de Visitas
 * 
 * Este arquivo processa o formulário de marcação de visitas.
 * Recebe os dados via POST, formata e salva em um arquivo texto.
 * Retorna mensagens de sucesso ou erro após o processamento.
 */

session_start();

// Verifica se o formulário foi enviado via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Coleta os dados do formulário
    $email = $_POST['email'];
    $data = $_POST['data'];
    $hora = $_POST['hora'];
    $observacoes = $_POST['observacoes'];

    // Verificar se já existe uma visita marcada para a mesma data e dentro da margem de uma hora
    if (file_exists('visitas.txt')) {
        $visitas_existentes = file_get_contents('visitas.txt');
        $visitas_array = explode("\n", $visitas_existentes);
        
        // Converta a hora da nova visita para minutos (para facilitar a comparação)
        $hora_partes = explode(':', $hora);
        $hora_minutos = (int)$hora_partes[0] * 60 + (int)$hora_partes[1];
        
        foreach ($visitas_array as $visita_existente) {
            if (empty($visita_existente)) continue;
            
            $dados_visita = explode(" | ", $visita_existente);
            if (count($dados_visita) < 4) continue;
            
            $data_existente = trim($dados_visita[2]);
            $hora_existente = trim($dados_visita[3]);
            
            // Se for a mesma data, vamos verificar se a hora está dentro da margem de 1 hora
            if ($data_existente == $data) {
                $hora_existente_partes = explode(':', $hora_existente);
                $hora_existente_minutos = (int)$hora_existente_partes[0] * 60 + (int)$hora_existente_partes[1];
                
                // Calcular a diferença em minutos
                $diferenca_minutos = abs($hora_minutos - $hora_existente_minutos);
                
                // Se a diferença for menor que 60 minutos, há conflito
                if ($diferenca_minutos < 60) {
                    $_SESSION['error_message'] = "Já existe uma visita marcada próxima a este horário. Por favor, escolha um horário com pelo menos 1 hora de diferença das visitas existentes.";
                    header('Location: marcar_visita.php');
                    exit();
                }
            }
        }
    }

    // Formata os dados para salvamento
    $visita = date('Y-m-d H:i:s') . " | " . 
              $email . " | " . 
              $data . " | " . 
              $hora . " | " . 
              $observacoes . "\n";

    // Tenta salvar os dados no arquivo
    if(file_put_contents('visitas.txt', $visita, FILE_APPEND)) {
        $_SESSION['success_message'] = "A sua visita foi marcada com sucesso!";
    } else {
        $_SESSION['error_message'] = "Erro ao marcar a visita. Por favor, tente novamente.";
    }
    
    header('Location: marcar_visita.php');
    exit();
}
?>
