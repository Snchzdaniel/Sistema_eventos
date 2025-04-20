<?php
require_once '../config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
} catch(PDOException $exception) {
    die("Error de conexión: " . $exception->getMessage());
}

include_once '../includes/header.php';

// Check if ID is set
if(!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<div class='alert alert-danger'>ID de evento no especificado</div>";
    header("refresh:2;url=index.php");
    exit;
}

$event_id = $_GET['id'];

// Get event details
$query = "SELECT e.*, c.name as category_name
          FROM events e
          LEFT JOIN event_categories c ON e.category_id = c.category_id
          WHERE e.id = :id OR e.event_id = :event_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $event_id);
$stmt->bindParam(':event_id', $event_id);
$stmt->execute();

if($stmt->rowCount() == 0) {
    // Debug information
    echo "<!-- Debug: ID recibido: " . $event_id . " -->";
    $debug_query = "SELECT id, event_id, title FROM events";
    $debug_stmt = $conn->prepare($debug_query);
    $debug_stmt->execute();
    $all_events = $debug_stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<!-- Debug: Eventos en la base de datos: -->";
    foreach($all_events as $e) {
        echo "<!-- Debug: ID: " . $e['id'] . ", Event_ID: " . $e['event_id'] . ", Title: " . $e['title'] . " -->";
    }
    
    echo "<div class='alert alert-danger'>Evento no encontrado</div>";
    header("refresh:2;url=index.php");
    exit;
}

$event = $stmt->fetch(PDO::FETCH_ASSOC);

// Get event speakers
$query = "SELECT s.*, es.presentation_title, es.presentation_time
          FROM speakers s
          JOIN event_speakers es ON s.speaker_id = es.speaker_id
          WHERE es.event_id = :event_id
          ORDER BY es.presentation_time";
$stmt = $conn->prepare($query);
$stmt->bindParam(':event_id', $event['event_id']); // Use the event_id from the found event
$stmt->execute();
$speakers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get registration count
$query = "SELECT COUNT(*) as count FROM registrations WHERE event_id = :event_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':event_id', $event['event_id']); // Use the event_id from the found event
$stmt->execute();
$registration_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$spaces_left = $event['max_participants'] - $registration_count;
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h2>
                    <span class="evento-badge bg-info text-white"><?php echo htmlspecialchars($event['category_name']); ?></span>
                    
                    <div class="mt-3">
                        <p class="text-muted">
                            <i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y', strtotime($event['event_date'])); ?>
                            <?php if(!empty($event['start_time'])): ?>
                                <i class="fas fa-clock ml-3"></i> 
                                <?php echo date('H:i', strtotime($event['start_time'])); ?>
                                <?php if(!empty($event['end_time'])): ?>
                                    - <?php echo date('H:i', strtotime($event['end_time'])); ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </p>
                        
                        <p class="text-muted">
                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['location']); ?>
                        </p>
                    </div>
                    
                    <?php if(!empty($event['image']) && $event['image'] != 'default-event.jpg'): ?>
                        <div class="mt-3 mb-3">
                            <img src="../public/images/eventos/<?php echo $event['image']; ?>" class="img-fluid rounded" alt="<?php echo htmlspecialchars($event['title']); ?>">
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-3">
                        <h5>Descripción</h5>
                        <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                    </div>
                </div>
            </div>
            
            <?php if(count($speakers) > 0): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Ponentes</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach($speakers as $speaker): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <?php if(!empty($speaker['profile_image'])): ?>
                                            <img src="../public/images/ponentes/<?php echo $speaker['profile_image']; ?>" class="rounded-circle me-3" width="50" height="50" alt="<?php echo $speaker['first_name'] . ' ' . $speaker['last_name']; ?>">
                                        <?php else: ?>
                                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                                <?php echo strtoupper(substr($speaker['first_name'], 0, 1) . substr($speaker['last_name'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($speaker['first_name'] . ' ' . $speaker['last_name']); ?></h6>
                                            <small class="text-muted"><?php echo htmlspecialchars($speaker['specialization']); ?></small>
                                        </div>
                                    </div>
                                    
                                    <?php if(!empty($speaker['presentation_title'])): ?>
                                    <div>
                                        <h6>Presentación:</h6>
                                        <p class="mb-1"><?php echo htmlspecialchars($speaker['presentation_title']); ?></p>
                                        <?php if(!empty($speaker['presentation_time'])): ?>
                                            <small class="text-muted">
                                                <i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($speaker['presentation_time'])); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Detalles del Registro</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Costo de inscripción
                            <span>
                                <?php if($event['registration_fee'] > 0): ?>
                                    $<?php echo number_format($event['registration_fee'], 2); ?>
                                <?php else: ?>
                                    <span class="badge bg-success">Gratis</span>
                                <?php endif; ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Cupo máximo
                            <span><?php echo $event['max_participants']; ?> participantes</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Registros actuales
                            <span><?php echo $registration_count; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Espacios disponibles
                            <span>
                                <?php if($spaces_left > 0): ?>
                                    <span class="badge bg-success"><?php echo $spaces_left; ?></span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Agotado</span>
                                <?php endif; ?>
                            </span>
                        </li>
                    </ul>
                    
                    <div class="mt-3 text-center">
                        <?php if($spaces_left > 0): ?>
                            <a href="../registros/create.php?event_id=<?php echo $event_id; ?>" class="btn btn-success">
                                <i class="fas fa-user-plus"></i> Registrar Participante
                            </a>
                        <?php else: ?>
                            <button class="btn btn-secondary" disabled>
                                <i class="fas fa-ban"></i> Cupo Agotado
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5>Acciones</h5>
                </div>
                <div class="card-body">
                    <a href="edit.php?id=<?php echo $event_id; ?>" class="btn btn-primary mb-2 w-100">
                        <i class="fas fa-edit"></i> Editar Evento
                    </a>
                    <a href="../registros/index.php?event_id=<?php echo $event_id; ?>" class="btn btn-info mb-2 w-100">
                        <i class="fas fa-users"></i> Ver Registros
                    </a>
                    <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $event_id; ?>)" class="btn btn-danger w-100">
                        <i class="fas fa-trash"></i> Eliminar Evento
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="mt-3">
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver a la lista
        </a>
    </div>
</div>

<script>
function confirmDelete(id) {
    if(confirm('¿Estás seguro que deseas eliminar este evento? Esta acción eliminará también todos los registros asociados.')) {
        window.location.href = 'index.php?delete=true&id=' + id;
    }
}
</script>

<?php include_once '../includes/footer.php'; ?>