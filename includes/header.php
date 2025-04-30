<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php if(isset($title)) echo $title; else echo "Stand Automóvel"; ?></title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <!-- Include Bootstrap Icons -->
    <link rel="icon" type="image/x-icon" href="Imagens/favicon.ico">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            /* Add padding to account for the fixed navbar */
            padding-top: 70px;
        }
    </style>
</head>
<body>
<?php
if (!isset($_SESSION)) {
    session_start();
}
?>
