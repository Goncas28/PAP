<?php
// Script para verificar a estrutura da tabela favoritos

require_once __DIR__ . "/config.php";

try {
    $conn = connect_db();

    // Obter informações sobre a tabela favoritos
    $stmt = $conn->query("DESCRIBE favoritos");
    $tableInfo = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h1>Estrutura da tabela favoritos</h1>";
    echo "<pre>";
    print_r($tableInfo);
    echo "</pre>";

    // Verificar se os campos necessários existem
    $hasIdFavorito = false;
    $hasEmail = false;
    $hasIdCarro = false;

    foreach ($tableInfo as $column) {
        if ($column['Field'] === 'id_favorito') $hasIdFavorito = true;
        if ($column['Field'] === 'Email') $hasEmail = true;
        if ($column['Field'] === 'ID_Carro') $hasIdCarro = true;
    }

    echo "<h2>Verificação de campos</h2>";
    echo "Campo id_favorito: " . ($hasIdFavorito ? "OK" : "FALTANDO") . "<br>";
    echo "Campo Email: " . ($hasEmail ? "OK" : "FALTANDO") . "<br>";
    echo "Campo ID_Carro: " . ($hasIdCarro ? "OK" : "FALTANDO") . "<br>";

    // Se algum campo estiver faltando, sugerir o SQL para criar a tabela corretamente
    if (!$hasIdFavorito || !$hasEmail || !$hasIdCarro) {
        echo "<h2>SQL para criar a tabela corretamente</h2>";
        echo '<pre>
CREATE TABLE favoritos (
    id_favorito INT AUTO_INCREMENT PRIMARY KEY,
    Email VARCHAR(100) NOT NULL,
    ID_Carro INT NOT NULL,
    FOREIGN KEY (Email) REFERENCES clientes(Email) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (ID_Carro) REFERENCES carros(ID_Carro) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY (Email, ID_Carro)
);
        </pre>';
    }

    // Verificar registros existentes
    $stmt = $conn->query("SELECT * FROM favoritos LIMIT 10");
    $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h2>Registros existentes na tabela favoritos</h2>";
    if (count($favorites) > 0) {
        echo "<pre>";
        print_r($favorites);
        echo "</pre>";
    } else {
        echo "<p>Nenhum registro encontrado na tabela favoritos.</p>";
    }
} catch (PDOException $e) {
    echo "<h1>Erro</h1>";
    echo "<p>Ocorreu um erro ao verificar a tabela: " . $e->getMessage() . "</p>";
}
