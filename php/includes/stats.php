<?php
// php/includes/stats.php

require_once __DIR__ . '/../config/database.php';

// Inicializar el array de estadísticas
$stats = [
    'eventos' => 0,
    'ponentes' => 0,
    'participantes' => 0,
    'registros' => 0
];

// Inicializar arrays para eventos y registros
$proximosEventos = [];
$ultimosRegistros = [];

try {
    // Establecer conexión a la base de datos
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    if ($conn->connect_error) {
        throw new Exception("Conexión fallida: " . $conn->connect_error);
    }

    // Contar eventos
    $query = "SELECT COUNT(*) as total FROM EVENTS";
    $result = $conn->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['eventos'] = $row['total'];
    }

    // Contar ponentes
    $query = "SELECT COUNT(*) as total FROM SPEAKERS";
    $result = $conn->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['ponentes'] = $row['total'];
    }

    // Contar participantes
    $query = "SELECT COUNT(*) as total FROM PARTICIPANTS";
    $result = $conn->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['participantes'] = $row['total'];
    }

    // Contar registros
    $query = "SELECT COUNT(*) as total FROM REGISTRATIONS";
    $result = $conn->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['registros'] = $row['total'];
    }

    // Obtener próximos eventos
    $query = "SELECT e.*, c.name as category_name, 
              (SELECT COUNT(*) FROM REGISTRATIONS r WHERE r.event_id = e.event_id) as registration_count
              FROM EVENTS e 
              LEFT JOIN EVENT_CATEGORIES c ON e.category_id = c.category_id
              WHERE e.event_date >= CURDATE() 
              AND e.status = 'active'
              ORDER BY e.event_date ASC 
              LIMIT 5";
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $proximosEventos[] = $row;
        }
    }

    // Obtener últimos registros
    $query = "SELECT r.*, 
              CONCAT(p.first_name, ' ', p.last_name) as participant_name,
              e.title as event_title
              FROM REGISTRATIONS r
              JOIN PARTICIPANTS p ON r.participant_id = p.participant_id
              JOIN EVENTS e ON r.event_id = e.event_id
              ORDER BY r.created_at DESC
              LIMIT 5";
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $ultimosRegistros[] = $row;
        }
    }

    // Cerrar conexión
    $conn->close();

} catch(Exception $e) {
    // En caso de error, mantener los valores en 0
    error_log("Error al obtener estadísticas: " . $e->getMessage());
}
?> 