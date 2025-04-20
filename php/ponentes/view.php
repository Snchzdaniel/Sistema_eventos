<?php
// ponentes/view.php - Ver detalles de un ponente específico
require_once '../config/database.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$speaker_id = $_GET['id'];

try {
    // Conexión a la base de datos usando la clase Database
    $database = new Database();
    $conn = $database->getConnection();
    
    // Obtener información del ponente
    $stmt = $conn->prepare("SELECT * FROM SPEAKERS WHERE speaker_id = :speaker_id");
    $stmt->bindParam(':speaker_id', $speaker_id);
    $stmt->execute();
    $ponente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ponente) {
        $error = "Ponente no encontrado";
    } else {
        // Obtener eventos del ponente
        $stmt = $conn->prepare("
            SELECT e.event_id, e.title, e.event_date, e.location, es.presentation_title, es.presentation_time 
            FROM EVENT_SPEAKERS es
            JOIN EVENTS e ON es.event_id = e.event_id
            WHERE es.speaker_id = :speaker_id
            ORDER BY e.event_date DESC
        ");
        $stmt->bindParam(':speaker_id', $speaker_id);
        $stmt->execute();
        $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch(PDOException $e) {
    $error = "Error de conexión: " . $e->getMessage();
}
?>

<?php include_once '../includes/header.php'; ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Detalles del Ponente</h1>
        <div>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-list"></i> Ver todos los ponentes
            </a>
            <?php if (!isset($error)): ?>
                <a href="edit.php?id=<?php echo $speaker_id; ?>" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Editar ponente
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php else: ?>
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Información Personal</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <?php if (!empty($ponente['profile_image'])): ?>
                                <img src="<?php echo htmlspecialchars($ponente['profile_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($ponente['first_name'] . ' ' . $ponente['last_name']); ?>" 
                                     class="img-fluid rounded-circle" style="max-width: 150px;">
                            <?php else: ?>
                                <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto" 
                                     style="width: 150px; height: 150px;">
                                    <span style="font-size: 3rem;">
                                        <?php echo strtoupper(substr($ponente['first_name'], 0, 1) . substr($ponente['last_name'], 0, 1)); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <h4 class="text-center mb-3">
                            <?php echo htmlspecialchars($ponente['first_name'] . ' ' . $ponente['last_name']); ?>
                        </h4>
                        
                        <?php if (!empty($ponente['specialization'])): ?>
                            <p class="text-center mb-4">
                                <span class="evento-badge bg-primary text-white">
                                    <?php echo htmlspecialchars($ponente['specialization']); ?>
                                </span>
                            </p>
                        <?php endif; ?>
                        
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <strong>Email:</strong> <?php echo htmlspecialchars($ponente['email']); ?>
                            </li>
                            <?php if (!empty($ponente['phone'])): ?>
                                <li class="list-group-item">
                                    <strong>Teléfono:</strong> <?php echo htmlspecialchars($ponente['phone']); ?>
                                </li>
                            <?php endif; ?>
                            <li class="list-group-item">
                                <strong>Registrado:</strong> 
                                <?php echo date('d/m/Y', strtotime($ponente['created_at'])); ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-8">
                <?php if (!empty($ponente['biography'])): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Biografía</h5>
                        </div>
                        <div class="card-body">
                            <?php echo nl2br(htmlspecialchars($ponente['biography'])); ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h5>Participación en Eventos</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($eventos) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Evento</th>
                                            <th>Título de Presentación</th>
                                            <th>Fecha</th>
                                            <th>Hora</th>
                                            <th>Ubicación</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($eventos as $evento): ?>
                                            <tr class="evento-item">
                                                <td>
                                                    <a href="../eventos/view.php?id=<?php echo $evento['event_id']; ?>">
                                                        <?php echo htmlspecialchars($evento['title']); ?>
                                                    </a>
                                                </td>
                                                <td><?php echo htmlspecialchars($evento['presentation_title']); ?></td>
                                                <td>
                                                    <?php echo date('d/m/Y', strtotime($evento['event_date'])); ?>
                                                </td>
                                                <td>
                                                    <?php echo date('H:i', strtotime($evento['presentation_time'])); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($evento['location'] ?? 'No especificado'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-center">Este ponente no ha participado en ningún evento hasta el momento.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include_once '../includes/footer.php'; ?>