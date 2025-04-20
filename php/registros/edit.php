<?php
// Incluir archivos de configuración y cabecera
require_once '../config/database.php';
require_once '../includes/header.php';

// Inicializar variables
$registration_id = '';
$event_id = '';
$participant_id = '';
$payment_status = '';
$attendance_status = 0;
$feedback = '';
$error_message = '';
$success_message = '';

// Verificar si se ha proporcionado un ID de registro
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $registration_id = $_GET['id'];
    
    // Preparar consulta para obtener datos del registro
    $stmt = $conn->prepare("SELECT * FROM REGISTRATIONS WHERE registration_id = ?");
    $stmt->bind_param("s", $registration_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $registration = $result->fetch_assoc();
        $event_id = $registration['event_id'];
        $participant_id = $registration['participant_id'];
        $payment_status = $registration['payment_status'];
        $attendance_status = $registration['attendance_status'];
        $feedback = $registration['feedback'];
    } else {
        // Si no se encuentra el registro, redirigir a la lista
        header("Location: index.php");
        exit();
    }
    
    $stmt->close();
} else {
    // Si no se proporciona ID, redirigir a la lista
    header("Location: index.php");
    exit();
}

// Obtener eventos disponibles
$events_query = "SELECT event_id, title, event_date, registration_fee FROM EVENTS ORDER BY event_date";
$events_result = $conn->query($events_query);

// Obtener participantes
$participants_query = "SELECT participant_id, CONCAT(first_name, ' ', last_name) as full_name, email FROM PARTICIPANTS ORDER BY last_name, first_name";
$participants_result = $conn->query($participants_query);

// Procesar el formulario cuando se envía
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validar entrada
    if (empty($_POST['event_id']) || empty($_POST['participant_id'])) {
        $error_message = "Debe seleccionar un evento y un participante";
    } else {
        // Recoger datos del formulario
        $new_event_id = $_POST['event_id'];
        $new_participant_id = $_POST['participant_id'];
        $payment_status = $_POST['payment_status'];
        $attendance_status = isset($_POST['attendance_status']) ? 1 : 0;
        $feedback = $_POST['feedback'];
        
        // Si cambia el evento o participante, verificar que no exista otro registro igual
        if ($new_event_id != $event_id || $new_participant_id != $participant_id) {
            $check_stmt = $conn->prepare("SELECT id FROM REGISTRATIONS WHERE event_id = ? AND participant_id = ? AND registration_id != ?");
            $check_stmt->bind_param("sss", $new_event_id, $new_participant_id, $registration_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $error_message = "Este participante ya está registrado en este evento";
                $check_stmt->close();
            } else {
                $check_stmt->close();
                
                // Actualizar el registro
                $update_stmt = $conn->prepare("UPDATE REGISTRATIONS SET event_id = ?, participant_id = ?, payment_status = ?, attendance_status = ?, feedback = ? WHERE registration_id = ?");
                $update_stmt->bind_param("sssiss", $new_event_id, $new_participant_id, $payment_status, $attendance_status, $feedback, $registration_id);
                
                if ($update_stmt->execute()) {
                    $success_message = "Registro actualizado con éxito";
                    
                    // Actualizar variables para reflejar los cambios
                    $event_id = $new_event_id;
                    $participant_id = $new_participant_id;
                } else {
                    $error_message = "Error al actualizar el registro: " . $conn->error;
                }
                
                $update_stmt->close();
            }
        } else {
            // Solo actualizar estado de pago, asistencia y feedback
            $update_stmt = $conn->prepare("UPDATE REGISTRATIONS SET payment_status = ?, attendance_status = ?, feedback = ? WHERE registration_id = ?");
            $update_stmt->bind_param("siss", $payment_status, $attendance_status, $feedback, $registration_id);
            
            if ($update_stmt->execute()) {
                $success_message = "Registro actualizado con éxito";
            } else {
                $error_message = "Error al actualizar el registro: " . $conn->error;
            }
            
            $update_stmt->close();
        }
    }
}

// Volver a cargar los resultados para asegurar que tenemos los datos actualizados
$events_result->data_seek(0);
$participants_result->data_seek(0);
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <h2>Editar Registro</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Registros</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Editar Registro</li>
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
                <?php endif; ?>
                
                <?php if (!empty($success_message)): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $success_message; ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $registration_id); ?>">
                    <div class="mb-3">
                        <label for="registration_id" class="form-label">ID de Registro</label>
                        <input type="text" class="form-control" id="registration_id" value="<?php echo htmlspecialchars($registration_id); ?>" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="event_id" class="form-label">Evento *</label>
                        <select class="form-select" id="event_id" name="event_id" required>
                            <option value="">Seleccione un evento</option>
                            <?php while($event = $events_result->fetch_assoc()): ?>
                            <option value="<?php echo $event['event_id']; ?>" <?php if($event_id == $event['event_id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($event['title']); ?> - 
                                <?php echo date('d/m/Y', strtotime($event['event_date'])); ?> 
                                <?php if($event['registration_fee'] > 0): ?>
                                    (Costo: $<?php echo number_format($event['registration_fee'], 2); ?>)
                                <?php else: ?>
                                    (Gratuito)
                                <?php endif; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="participant_id" class="form-label">Participante *</label>
                        <select class="form-select" id="participant_id" name="participant_id" required>
                            <option value="">Seleccione un participante</option>
                            <?php while($participant = $participants_result->fetch_assoc()): ?>
                            <option value="<?php echo $participant['participant_id']; ?>" <?php if($participant_id == $participant['participant_id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($participant['full_name']); ?> (<?php echo $participant['email']; ?>)
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="payment_status" class="form-label">Estado de Pago</label>
                        <select class="form-select" id="payment_status" name="payment_status">
                            <option value="pending" <?php if($payment_status == 'pending') echo 'selected'; ?>>Pendiente</option>
                            <option value="completed" <?php if($payment_status == 'completed') echo 'selected'; ?>>Completado</option>
                            <option value="cancelled" <?php if($payment_status == 'cancelled') echo 'selected'; ?>>Cancelado</option>
                        </select>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="attendance_status" name="attendance_status" <?php if($attendance_status) echo 'checked'; ?>>
                        <label class="form-check-label" for="attendance_status">Confirmar asistencia</label>
                    </div>
                    
                    <div class="mb-3">
                        <label for="feedback" class="form-label">Retroalimentación/Comentarios</label>
                        <textarea class="form-control" id="feedback" name="feedback" rows="3"><?php echo htmlspecialchars($feedback); ?></textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col">
                            <button type="submit" class="btn btn-primary w-100">Actualizar Registro</button>
                        </div>
                        <div class="col">
                            <a href="index.php" class="btn btn-secondary w-100">Cancelar</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Incluir pie de página
require_once '../includes/footer.php';

// Cerrar la conexión a la base de datos
$conn->close();
?>