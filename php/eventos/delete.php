<?php
// Incluir archivo de configuración de la base de datos
require_once '../config/database.php';

// Establecer conexión a la base de datos
try {
    $database = new Database();
    $conn = $database->getConnection();
} catch(PDOException $exception) {
    die("Error de conexión: " . $exception->getMessage());
}

// Inicializar variables
$event_id = '';
$error_message = '';
$redirect = true;

// Verificar si se ha proporcionado un ID de evento
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $event_id = $_GET['id'];
    echo "<!-- Debug: ID recibido en la URL: " . $event_id . " -->";
    
    try {
        // Intentar encontrar el evento usando ambos campos (id o event_id)
        $query = "SELECT * FROM events WHERE id = :id OR event_id = :event_id";
        echo "<!-- Debug: Query de verificación: " . $query . " -->";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $event_id);
        $stmt->bindParam(':event_id', $event_id);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            $error_message = "El evento no existe";
            echo "<!-- Debug: No se encontró el evento con ID: " . $event_id . " -->";
            
            // Mostrar todos los eventos para debug
            $list_query = "SELECT id, event_id, title FROM events";
            $list_stmt = $conn->prepare($list_query);
            $list_stmt->execute();
            $existing_events = $list_stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<!-- Debug: Eventos existentes: -->";
            foreach ($existing_events as $event) {
                echo "<!-- Debug: ID numérico: " . $event['id'] . ", Event_ID: " . $event['event_id'] . ", Título: " . $event['title'] . " -->";
            }
        } else {
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<!-- Debug: Evento encontrado - ID numérico: " . $event['id'] . ", Event_ID: " . $event['event_id'] . ", Título: " . $event['title'] . " -->";
            
            // Si se ha confirmado la eliminación
            if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_delete']) && $_POST['confirm_delete'] == 'yes') {
                echo "<!-- Debug: Confirmación de eliminación recibida -->";
                
                // Iniciar transacción
                $conn->beginTransaction();
                
                try {
                    // Eliminar registros asociados primero
                    $query = "DELETE FROM registrations WHERE event_id = :event_id";
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(':event_id', $event['event_id']);
                    $stmt->execute();
                    
                    // Eliminar ponentes asociados
                    $query = "DELETE FROM event_speakers WHERE event_id = :event_id";
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(':event_id', $event['event_id']);
                    $stmt->execute();
                    
                    // Eliminar el evento
                    $query = "DELETE FROM events WHERE id = :id";
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(':id', $event['id']);
                    $stmt->execute();
                    
                    // Confirmar transacción
                    $conn->commit();
                    echo "<!-- Debug: Transacción completada exitosamente -->";
                    header("Location: index.php?message=deleted");
                    exit();
                } catch(PDOException $e) {
                    // Revertir transacción en caso de error
                    $conn->rollBack();
                    $error_message = "Error al eliminar el evento: " . $e->getMessage();
                    echo "<!-- Debug: Error en la transacción: " . $e->getMessage() . " -->";
                    $redirect = false;
                }
            } else {
                // Mostrar página de confirmación
                $redirect = false;
            }
        }
    } catch(PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
        echo "<!-- Debug: Error general: " . $e->getMessage() . " -->";
        $redirect = false;
    }
} else {
    $error_message = "ID de evento no proporcionado";
    echo "<!-- Debug: No se proporcionó ID de evento -->";
}

// Si hay un error o no se debe redirigir, mostrar la página
if (!empty($error_message) || !$redirect) {
    // Incluir cabecera
    require_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <h2>Eliminar Evento</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Eventos</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Eliminar Evento</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="form-section">
                <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error_message; ?>
                </div>
                <div class="text-center mt-3">
                    <a href="index.php" class="btn btn-primary">Volver a la lista de eventos</a>
                </div>
                <?php else: ?>
                <div class="alert alert-warning" role="alert">
                    ¿Está seguro de que desea eliminar este evento?
                    <br>
                    <strong>Título:</strong> <?php echo htmlspecialchars($event['title']); ?>
                </div>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $event_id); ?>">
                    <div class="mb-3">
                        <p>Esta acción no se puede deshacer.</p>
                        <input type="hidden" name="confirm_delete" value="yes">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col">
                            <button type="submit" class="btn btn-danger w-100">Sí, eliminar evento</button>
                        </div>
                        <div class="col">
                            <a href="index.php" class="btn btn-secondary w-100">Cancelar</a>
                        </div>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
    // Incluir pie de página
    require_once '../includes/footer.php';
}

// Si se debe redirigir y no hay errores, hacerlo
if ($redirect && empty($error_message)) {
    header("Location: index.php");
    exit();
}
?>