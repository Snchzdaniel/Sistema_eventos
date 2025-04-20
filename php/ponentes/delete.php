<?php
// php/ponentes/delete.php - Eliminar un ponente
require_once '../config/database.php';

// Inicializar conexión a la base de datos
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Verificar si se proporcionó un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php?error=No se especificó el ID del ponente");
    exit;
}

$speaker_id = $_GET['id'];

// Verificar que el ponente existe
$check = $conn->prepare("SELECT id FROM SPEAKERS WHERE speaker_id = ?");
$check->bind_param("s", $speaker_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php?error=Ponente no encontrado");
    exit;
}
$check->close();

// Iniciar transacción
$conn->begin_transaction();

try {
    // Primero verificar si el ponente está asociado a eventos
    $check_events = $conn->prepare("SELECT id FROM EVENT_SPEAKERS WHERE speaker_id = ?");
    $check_events->bind_param("s", $speaker_id);
    $check_events->execute();
    $events_result = $check_events->get_result();
    
    // Si está asociado a eventos, eliminar primero esas asociaciones
    if ($events_result->num_rows > 0) {
        $delete_associations = $conn->prepare("DELETE FROM EVENT_SPEAKERS WHERE speaker_id = ?");
        $delete_associations->bind_param("s", $speaker_id);
        $delete_associations->execute();
        $delete_associations->close();
    }
    $check_events->close();
    
    // Ahora eliminar el ponente
    $delete_speaker = $conn->prepare("DELETE FROM SPEAKERS WHERE speaker_id = ?");
    $delete_speaker->bind_param("s", $speaker_id);
    $delete_speaker->execute();
    $delete_speaker->close();
    
    // Confirmar transacción
    $conn->commit();
    
    header("Location: index.php?success=Ponente eliminado correctamente");
    exit;
} catch (Exception $e) {
    // Si hay error, revertir transacción
    $conn->rollback();
    header("Location: index.php?error=Error al eliminar el ponente: " . $e->getMessage());
    exit;
}

$conn->close();
?>