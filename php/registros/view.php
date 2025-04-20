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
$registration_id = '';
$error_message = '';

// Verificar si se ha proporcionado un ID de registro
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $registration_id = $_GET['id'];
    
    // Preparar consulta para obtener datos detallados del registro
    $stmt = $conn->prepare("
        SELECT r.*, 
               e.title as event_title, e.description as event_description, e.event_date, e.start_time, e.end_time, e.location, e.registration_fee,
               CONCAT(p.first_name, ' ', p.last_name) as participant_name, p.email as participant_email, p.phone as participant_phone, p.institution as participant_institution
        FROM REGISTRATIONS r
        INNER JOIN EVENTS e ON r.event_id = e.event_id
        INNER JOIN PARTICIPANTS p ON r.participant_id = p.participant_id
        WHERE r.registration_id = ?
    ");
    $stmt->bind_param("s", $registration_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $registration = $result->fetch_assoc();
    } else {
        $error_message = "No se encontró el registro solicitado";
    }
    
    $stmt->close();
} else {
    $error_message = "ID de registro no proporcionado";
}

// Actualizar retroalimentación si se envía el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['feedback'])) {
    $feedback = $_POST['feedback'];
    
    $update_stmt = $conn->prepare("UPDATE REGISTRATIONS SET feedback = ? WHERE registration_id = ?");
    $update_stmt->bind_param("ss", $feedback, $registration_id);
    
    if ($update_stmt->execute()) {
        // Actualizar la retroalimentación en el array actual
        $registration['feedback'] = $feedback;
        $success_message = "Retroalimentación guardada con éxito";
    } else {
        $error_message = "Error al guardar la retroalimentación: " . $conn->error;
    }
    
    $update_stmt->close();
}

// Actualizar estado de asistencia
if (isset($_GET['mark_attendance']) && !empty($registration_id)) {
    $attendance_status = ($_GET['mark_attendance'] == 'yes') ? 1 : 0;
    
    $update_att_stmt = $conn->prepare("UPDATE REGISTRATIONS SET attendance_status = ? WHERE registration_id = ?");
    $update_att_stmt->bind_param("is", $attendance_status, $registration_id);
    
    if ($update_att_stmt->execute()) {
        // Actualizar el estado en el array actual
        $registration['attendance_status'] = $attendance_status;
        $success_message = "Estado de asistencia actualizado";
    } else {
        $error_message = "Error al actualizar el estado de asistencia: " . $conn->error;
    }
    
    $update_att_stmt->close();
}

// Actualizar estado de pago
if (isset($_GET['update_payment']) && !empty($registration_id)) {
    $payment_status = $_GET['update_payment'];
    
    if (in_array($payment_status, ['pending', 'completed', 'cancelled'])) {
        $update_pay_stmt = $conn->prepare("UPDATE REGISTRATIONS SET payment_status = ? WHERE registration_id = ?");
        $update_pay_stmt->bind_param("ss", $payment_status, $registration_id);
        
        if ($update_pay_stmt->execute()) {
            // Actualizar el estado en el array actual
            $registration['payment_status'] = $payment_status;
            $success_message = "Estado de pago actualizado";
        } else {
            $error_message = "Error al actualizar el estado de pago: " . $conn->error;
        }
        
        $update_pay_stmt->close();
    }
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <h2>Detalles del Registro</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Registros</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Ver Registro</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <?php if (!empty($error_message)): ?>
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-danger" role="alert">
                <?php echo $error_message; ?>
            </div>
            <div class="text-center">
                <a href="index.php" class="btn btn-primary">Volver a la lista de registros</a>
            </div>
        </div>
    </div>
    <?php elseif (isset($registration)): ?>
    
    <?php if (isset($success_message)): ?>
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Información del Registro</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">ID de Registro:</div>
                        <div class="col-md-8"><?php echo $registration['registration_id']; ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Fecha de Registro:</div>
                        <div class="col-md-8"><?php echo date('d/m/Y H:i', strtotime($registration['registration_date'])); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Estado de Pago:</div>
                        <div class="col-md-8">
                            <?php 
                            switch($registration['payment_status']) {
                                case 'pending':
                                    echo '<span class="badge bg-warning">Pendiente</span>';
                                    break;
                                case 'completed':
                                    echo '<span class="badge bg-success">Completado</span>';
                                    break;
                                case 'cancelled':
                                    echo '<span class="badge bg-danger">Cancelado</span>';
                                    break;
                            }
                            ?>
                            <div class="btn-group btn-group-sm ms-2" role="group">
                                <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $registration_id . "&update_payment=completed"); ?>" class="btn btn-outline-success btn-sm">Completado</a>
                                <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $registration_id . "&update_payment=pending"); ?>" class="btn btn-outline-warning btn-sm">Pendiente</a>
                                <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $registration_id . "&update_payment=cancelled"); ?>" class="btn btn-outline-danger btn-sm">Cancelado</a>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Estado de Asistencia:</div>
                        <div class="col-md-8">
                            <?php if($registration['attendance_status']): ?>
                                <span class="badge bg-success">Asistió</span>
                                <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $registration_id . "&mark_attendance=no"); ?>" class="btn btn-outline-secondary btn-sm ms-2">Marcar como no asistido</a>
                            <?php else: ?>
                                <span class="badge bg-secondary">No registrado</span>
                                <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $registration_id . "&mark_attendance=yes"); ?>" class="btn btn-outline-success btn-sm ms-2">Marcar asistencia</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Información del Evento</h5>
                </div>
                <div class="card-body">
                    <h4><?php echo htmlspecialchars($registration['event_title']); ?></h4>
                    
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Fecha:</div>
                        <div class="col-md-8"><?php echo date('d/m/Y', strtotime($registration['event_date'])); ?></div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Horario:</div>
                        <div class="col-md-8">
                            <?php 
                            echo date('H:i', strtotime($registration['start_time']));
                            echo ' a ';
                            echo date('H:i', strtotime($registration['end_time']));
                            ?>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Ubicación:</div>
                        <div class="col-md-8"><?php echo htmlspecialchars($registration['location']); ?></div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Costo de Inscripción:</div>
                        <div class="col-md-8">
                            <?php 
                            if ($registration['registration_fee'] > 0) {
                                echo '$' . number_format($registration['registration_fee'], 2);
                            } else {
                                echo 'Gratuito';
                            }
                            ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($registration['event_description'])): ?>
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Descripción:</div>
                        <div class="col-md-8"><?php echo nl2br(htmlspecialchars($registration['event_description'])); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="text-end">
                        <a href="../eventos/view.php?id=<?php echo $registration['event_id']; ?>" class="btn btn-info btn-sm">Ver detalles del evento</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Información del Participante</h5>
                </div>
                <div class="card-body">
                    <h5><?php echo htmlspecialchars($registration['participant_name']); ?></h5>
                    
                    <div class="mb-2">
                        <strong>Email:</strong> <?php echo htmlspecialchars($registration['participant_email']); ?>
                    </div>
                    
                    <?php if (!empty($registration['participant_phone'])): ?>
                    <div class="mb-2">
                        <strong>Teléfono:</strong> <?php echo htmlspecialchars($registration['participant_phone']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($registration['participant_institution'])): ?>
                    <div class="mb-2">
                        <strong>Institución:</strong> <?php echo htmlspecialchars($registration['participant_institution']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="text-end mt-3">
                        <a href="../participantes/view.php?id=<?php echo $registration['participant_id']; ?>" class="btn btn-info btn-sm">Ver perfil completo</a>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Retroalimentación</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $registration_id); ?>">
                        <div class="mb-3">
                            <textarea class="form-control" id="feedback" name="feedback" rows="5" placeholder="Ingrese comentarios o retroalimentación"><?php echo htmlspecialchars($registration['feedback']); ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Guardar Retroalimentación</button>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Acciones</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="edit.php?id=<?php echo $registration_id; ?>" class="btn btn-primary">Editar Registro</a>
                        <a href="delete.php?id=<?php echo $registration_id; ?>" class="btn btn-danger">Eliminar Registro</a>
                        <a href="index.php" class="btn btn-secondary">Volver a la Lista</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
// Incluir pie de página
require_once '../includes/footer.php';

// Cerrar la conexión a la base de datos
$conn->close();
?>