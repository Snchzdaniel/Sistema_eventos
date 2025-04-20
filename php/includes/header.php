<?php
// php/includes/header.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión de Eventos Académicos</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="/SISTEMA-EVENTOS/public/css/styles.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/SISTEMA-EVENTOS/php/index.php">Sistema de Eventos</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="/SISTEMA-EVENTOS/php/index.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/SISTEMA-EVENTOS/php/eventos/index.php">Eventos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/SISTEMA-EVENTOS/php/ponentes/index.php">Ponentes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/SISTEMA-EVENTOS/php/participantes/index.php">Participantes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/SISTEMA-EVENTOS/php/registros/index.php">Registros</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/SISTEMA-EVENTOS/php/dashboard.php">Dashboard</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-4">