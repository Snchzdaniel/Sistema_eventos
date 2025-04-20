<?php
// Incluir archivos de configuración y cabecera
require_once '../config/database.php';
require_once '../includes/header.php';

// Establecer conexión a la base de datos
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Inicializar variables
$event_id = '';
$participant_id = '';
$payment_status = 'pending';
$attendance_status = 0;
$error_message = '';
$success_message = '';

// Obtener eventos disponibles
$events_query = "SELECT event_id, title, event_date, registration_fee FROM EVENTS WHERE status = 'active' ORDER BY event_date";
$events_result = $conn->query($events_query);
$events = [];
if ($events_result) {
    while ($row = $events_result->fetch_assoc()) {
        $events[] = $row;
    }
} else {
    $error_message = "Error al obtener eventos: " . $conn->error;
}

// Obtener participantes
$participants_query = "SELECT participant_id, first_name, last_name, email FROM PARTICIPANTS ORDER BY last_name, first_name";
$participants_result = $conn->query($participants_query);
$participants = [];
if ($participants_result) {
    while ($row = $participants_result->fetch_assoc()) {
        $participants[] = $row;
    }
} else {
    $error_message = "Error al obtener participantes: " . $conn->error;
}

// Debug: Mostrar eventos y participantes disponibles
echo "<!-- Debug available events: ";
print_r($events);
echo " -->";
echo "<!-- Debug available participants: ";
print_r($participants);
echo " -->";

// Procesar el formulario cuando se envía
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Debug: Mostrar los valores recibidos
    echo "<!-- Debug POST values: ";
    print_r($_POST);
    echo " -->";
    
    // Validar entrada
    $event_id = isset($_POST['event_id']) ? trim($_POST['event_id']) : '';
    $participant_id = isset($_POST['participant_id']) ? trim($_POST['participant_id']) : '';
    $payment_status = isset($_POST['payment_status']) ? $_POST['payment_status'] : 'pending';
    $attendance_status = isset($_POST['attendance_status']) ? 1 : 0;
    
    // Debug: Mostrar los valores procesados
    echo "<!-- Debug processed values: ";
    echo "event_id: " . $event_id . ", ";
    echo "participant_id: " . $participant_id . ", ";
    echo "payment_status: " . $payment_status . ", ";
    echo "attendance_status: " . $attendance_status;
    echo " -->";
    
    // Verificar que los IDs existan en la base de datos
    if (!empty($event_id)) {
        $check_event = $conn->prepare("SELECT event_id FROM EVENTS WHERE event_id = ?");
        $check_event->bind_param("s", $event_id);
        $check_event->execute();
        $check_event->store_result();
        if ($check_event->num_rows == 0) {
            $error_message = "El evento seleccionado no existe";
        }
        $check_event->close();
    }
    
    if (!empty($participant_id)) {
        $check_participant = $conn->prepare("SELECT participant_id FROM PARTICIPANTS WHERE participant_id = ?");
        $check_participant->bind_param("s", $participant_id);
        $check_participant->execute();
        $check_participant->store_result();
        if ($check_participant->num_rows == 0) {
            $error_message = "El participante seleccionado no existe";
        }
        $check_participant->close();
    }
    
    if (empty($event_id) || empty($participant_id)) {
        $error_message = "Debe seleccionar un evento y un participante";
    } elseif (empty($error_message)) {
        // Verificar si ya existe un registro para este participante en este evento
        $check_stmt = $conn->prepare("SELECT id FROM REGISTRATIONS WHERE event_id = ? AND participant_id = ?");
        $check_stmt->bind_param("ss", $event_id, $participant_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error_message = "Este participante ya está registrado en este evento";
        } else {
            // Generar un ID único para el registro
            $registration_id = 'REG' . date('Ymd') . rand(1000, 9999);
            
            // Preparar y ejecutar la consulta de inserción
            $insert_stmt = $conn->prepare("INSERT INTO REGISTRATIONS (registration_id, event_id, participant_id, payment_status, attendance_status) VALUES (?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("ssssi", $registration_id, $event_id, $participant_id, $payment_status, $attendance_status);
            
            if ($insert_stmt->execute()) {
                // Redirigir a la lista con mensaje de éxito
                header("Location: index.php?message=created");
                exit();
            } else {
                $error_message = "Error al crear el registro: " . $conn->error;
            }
            
            $insert_stmt->close();
        }
        
        $check_stmt->close();
    }
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <h2>Nuevo Registro</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Registros</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Nuevo Registro</li>
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
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="mb-3">
                        <label for="event_id" class="form-label">Evento *</label>
                        <select class="form-select" id="event_id" name="event_id" required>
                            <option value="">Seleccione un evento</option>
                            <?php foreach($events as $event): ?>
                            <option value="<?php echo $event['event_id']; ?>" <?php if($event_id == $event['event_id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($event['title']); ?> - 
                                <?php echo date('d/m/Y', strtotime($event['event_date'])); ?> 
                                <?php if($event['registration_fee'] > 0): ?>
                                    (Costo: $<?php echo number_format($event['registration_fee'], 2); ?>)
                                <?php else: ?>
                                    (Gratuito)
                                <?php endif; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="participant_id" class="form-label">Participante *</label>
                        <select class="form-select" id="participant_id" name="participant_id" required>
                            <option value="">Seleccione un participante</option>
                            <?php foreach($participants as $participant): ?>
                            <option value="<?php echo $participant['participant_id']; ?>" <?php if($participant_id == $participant['participant_id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($participant['first_name'] . ' ' . $participant['last_name']); ?> (<?php echo $participant['email']; ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="mt-2">
                            <a href="../participantes/create.php" target="_blank">¿No encuentra al participante? Registre uno nuevo</a>
                        </div>
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
                        <label class="form-check-label" for="attendance_status">Marcar asistencia</label>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col">
                            <button type="submit" class="btn btn-primary w-100">Crear Registro</button>
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