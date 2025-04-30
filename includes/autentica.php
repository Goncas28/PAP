<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
<?php
if (! isset($_SESSION)) {
    session_start();
}

// verifica se fez login, isto é, se o utilizador já foi autenticado.
// Esta verificação é obrigatória no início de todas as páginas.
if (!isset($_SESSION['loggedin'])) {
    header("location: Index.php");
    exit();
}
?>
</body>
</html>