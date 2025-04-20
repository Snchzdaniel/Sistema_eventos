<?php
// php/participantes/view.php - Ver detalles de un participante
require_once '../config/database.php';
include_once '../includes/header.php';

// Inicializar conexión a la base de datos
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Verificar si se proporcionó un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php?error=No se especificó el ID del participante");
    exit;
}

$participant_id = $_GET['id'];

// Obtener información del participante
$stmt = $conn->prepare("SELECT * FROM PARTICIPANTS WHERE participant_id = ?");
$stmt->bind_param("s", $participant_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php?error=Participante no encontrado");
    exit;
}

$participant = $result->fetch_assoc();
$stmt->close();

// Obtener historial de eventos del participante
$stmt = $conn->prepare("
    SELECT r.registration_id, r.registration_date, r.payment_status, r.attendance_status, 
           e.event_id, e.title, e.event_date, e.location
    FROM REGISTRATIONS r
    JOIN EVENTS e ON r.event_id = e.event_id
    WHERE r.participant_id = ?
    ORDER BY e.event_date DESC
");
$stmt->bind_param("s", $participant_id);
$stmt->execute();
$events_result = $stmt->get_result();
$stmt->close();
?>

<div class="container mt-4">
    <div class="row mb-3">
        <div class="col">
            <h2>Detalles del Participante</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Participantes</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Ver Detalles</li>
                </ol>
            </nav>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_GET['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Información Personal</h5>
                        <div>
                            <a href="edit.php?id=<?php echo $participant['participant_id']; ?>" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th style="width: 150px;">ID:</th>
                            <td><?php echo htmlspecialchars($participant['participant_id']); ?></td>
                        </tr>
                        <tr>
                            <th>Nombre Completo:</th>
                            <td><?php echo htmlspecialchars($participant['first_name'] . ' ' . $participant['last_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td><?php echo htmlspecialchars($participant['email']); ?></td>
                        </tr>
                        <tr>
                            <th>Teléfono:</th>
                            <td><?php echo htmlspecialchars($participant['phone'] ?: 'No disponible'); ?></td>
                        </tr>
                        <tr>
                            <th>Institución:</th>
                            <td><?php echo htmlspecialchars($participant['institution'] ?: 'No especificado'); ?></td>
                        </tr>
                        <tr>
                            <th>Fecha de Registro:</th>
                            <td><?php echo date('d/m/Y H:i', strtotime($participant['created_at'])); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Acciones</h5>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="edit.php?id=<?php echo $participant['participant_id']; ?>" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Editar Información
                        </a>
                        <a href="../registros/create.php?participant_id=<?php echo $participant['participant_id']; ?>" class="btn btn-success">
                            <i class="fas fa-plus-circle"></i> Inscribir a Evento
                        </a>
                        <a href="delete.php?id=<?php echo $participant['participant_id']; ?>" class="btn btn-danger" 
                           onclick="return confirm('¿Está seguro que desea eliminar este participante? Esta acción no se puede deshacer.')">
                            <i class="fas fa-trash"></i> Eliminar Participante
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Historial de Eventos</h5>
        </div>
        <div class="card-body">
            <?php if ($events_result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Evento</th>
                                <th>Fecha</th>
                                <th>Fecha de Inscripción</th>
                                <th>Estado de Pago</th>
                                <th>Asistencia</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($registration = $events_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <a href="../eventos/view.php?id=<?php echo $registration['event_id']; ?>">
                                            <?php echo htmlspecialchars($registration['title']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($registration['event_date'])); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($registration['registration_date'])); ?></td>
                                    <td>
                                        <?php
                                        $payment_status = $registration['payment_status'];
                                        $badge_class = '';
                                        
                                        switch ($payment_status) {
                                            case 'completed':
                                                $badge_class = 'bg-success';
                                                $payment_status_text = 'Completado';
                                                break;
                                            case 'pending':
                                                $badge_class = 'bg-warning text-dark';
                                                $payment_status_text = 'Pendiente';
                                                break;
                                            case 'cancelled':
                                                $badge_class = 'bg-danger';
                                                $payment_status_text = 'Cancelado';
                                                break;
                                            default:
                                                $badge_class = 'bg-secondary';
                                                $payment_status_text = 'Desconocido';
                                        }
                                        
                                        echo '<span class="badge ' . $badge_class . '">' . $payment_status_text . '</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($registration['attendance_status']): ?>
                                            <span class="badge bg-success">Asistió</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">No registrado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="../registros/view.php?id=<?php echo $registration['registration_id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="../registros/edit.php?id=<?php echo $registration['registration_id']; ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">Este participante no está inscrito en ningún evento.</p>
                <a href="../registros/create.php?participant_id=<?php echo $participant['participant_id']; ?>" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Inscribir a un Evento
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-3">
        <a href="index.php" class="btn btn-secondary">Volver a la lista</a>
    </div>
</div>

<?php
$conn->close();
include_once '../includes/footer.php';
?>