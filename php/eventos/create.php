<?php
include_once '../config/database.php';
include_once '../includes/header.php';

// Establecer conexión a la base de datos
try {
    $database = new Database();
    $conn = $database->getConnection();
} catch(PDOException $exception) {
    die("Error de conexión: " . $exception->getMessage());
}

// Obtener categorías para el menú desplegable
$query = "SELECT * FROM event_categories ORDER BY name";
$stmt = $conn->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener ponentes para la selección
$query = "SELECT * FROM speakers ORDER BY first_name, last_name";
$stmt = $conn->prepare($query);
$stmt->execute();
$speakers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar envío del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Validar entradas
        if (empty($_POST['title'])) {
            throw new Exception("El título del evento es obligatorio");
        }
        
        if (empty($_POST['category_id'])) {
            throw new Exception("Debes seleccionar una categoría");
        }
        
        if (empty($_POST['event_date'])) {
            throw new Exception("La fecha del evento es obligatoria");
        }
        
        // Iniciar transacción
        $conn->beginTransaction();
        
        // Obtener el siguiente ID de evento
        $query = "SELECT COALESCE(MAX(SUBSTRING(event_id, 4) + 1), 1) as next_id FROM EVENTS";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $next_id = str_pad($result['next_id'], 3, '0', STR_PAD_LEFT);
        $event_id = 'EVT' . $next_id;
        
        // Insertar datos del evento
        $query = "INSERT INTO EVENTS (event_id, title, category_id, description, location, event_date, 
                start_time, end_time, max_participants, registration_fee, created_at) 
                VALUES (:event_id, :title, :category_id, :description, :location, :event_date, 
                :start_time, :end_time, :max_participants, :registration_fee, NOW())";
        
        $stmt = $conn->prepare($query);
        
        // Vincular parámetros
        $stmt->bindParam(':event_id', $event_id);
        $stmt->bindParam(':title', $_POST['title']);
        $stmt->bindParam(':category_id', $_POST['category_id']);
        $stmt->bindParam(':description', $_POST['description']);
        $stmt->bindParam(':location', $_POST['location']);
        $stmt->bindParam(':event_date', $_POST['event_date']);
        $stmt->bindParam(':start_time', $_POST['start_time']);
        $stmt->bindParam(':end_time', $_POST['end_time']);
        $stmt->bindParam(':max_participants', $_POST['max_participants']);
        $stmt->bindParam(':registration_fee', $_POST['registration_fee']);
        
        $stmt->execute();
        
        // Agregar ponentes al evento si se seleccionaron
        if(!empty($_POST['speakers'])) {
            foreach($_POST['speakers'] as $speaker_id) {
                // Para cada ponente, obtener los detalles de la presentación
                $presentation_title = $_POST['presentation_title_' . $speaker_id] ?? '';
                $presentation_time = $_POST['presentation_time_' . $speaker_id] ?? '';
                
                $query = "INSERT INTO event_speakers (event_id, speaker_id, presentation_title, presentation_time) 
                          VALUES (:event_id, :speaker_id, :presentation_title, :presentation_time)";
                
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':event_id', $event_id);
                $stmt->bindParam(':speaker_id', $speaker_id);
                $stmt->bindParam(':presentation_title', $presentation_title);
                $stmt->bindParam(':presentation_time', $presentation_time);
                $stmt->execute();
            }
        }
        
        // Confirmar transacción
        $conn->commit();
        echo "<div class='alert alert-success'>Evento creado exitosamente</div>";
        header("refresh:2;url=index.php"); // Redirigir a la lista de eventos después de 2 segundos
        
    } catch(Exception $e) {
        // Revertir la transacción si algo falló
        $conn->rollBack();
        echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>Crear Nuevo Evento</h4>
                </div>
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" enctype="multipart/form-data">
                        <div class="form-section">
                            <h5>Información Básica</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="title" class="form-label">Título *</label>
                                        <input type="text" class="form-control" id="title" name="title" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="category_id" class="form-label">Categoría *</label>
                                        <select class="form-control" id="category_id" name="category_id" required>
                                            <option value="">Seleccionar categoría</option>
                                            <?php foreach($categories as $category): ?>
                                                <option value="<?php echo $category['category_id']; ?>"><?php echo $category['name']; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Descripción</label>
                                <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="location" class="form-label">Ubicación</label>
                                <input type="text" class="form-control" id="location" name="location">
                            </div>
                        </div>
                        
                        <div class="form-section mt-4">
                            <h5>Fecha y Horario</h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="event_date" class="form-label">Fecha *</label>
                                        <input type="date" class="form-control" id="event_date" name="event_date" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="start_time" class="form-label">Hora de inicio</label>
                                        <input type="time" class="form-control" id="start_time" name="start_time">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="end_time" class="form-label">Hora de finalización</label>
                                        <input type="time" class="form-control" id="end_time" name="end_time">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section mt-4">
                            <h5>Capacidad y Registro</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="max_participants" class="form-label">Cupo máximo</label>
                                        <input type="number" class="form-control" id="max_participants" name="max_participants" min="1">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="registration_fee" class="form-label">Costo de inscripción</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" id="registration_fee" name="registration_fee" min="0" step="0.01">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section mt-4">
                            <h5>Ponentes</h5>
                            <div class="mb-3">
                                <label class="form-label">Seleccionar ponentes</label>
                                <div class="row" id="speakers-container">
                                    <?php foreach($speakers as $speaker): ?>
                                    <div class="col-md-4 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input speaker-checkbox" type="checkbox" value="<?php echo $speaker['speaker_id']; ?>" name="speakers[]" id="speaker_<?php echo $speaker['speaker_id']; ?>">
                                            <label class="form-check-label" for="speaker_<?php echo $speaker['speaker_id']; ?>">
                                                <?php echo $speaker['first_name'] . ' ' . $speaker['last_name']; ?>
                                            </label>
                                        </div>
                                        <div class="speaker-details d-none" id="speaker_details_<?php echo $speaker['speaker_id']; ?>">
                                            <div class="mb-2 mt-2">
                                                <input type="text" class="form-control form-control-sm" name="presentation_title_<?php echo $speaker['speaker_id']; ?>" placeholder="Título de presentación">
                                            </div>
                                            <div class="mb-2">
                                                <input type="time" class="form-control form-control-sm" name="presentation_time_<?php echo $speaker['speaker_id']; ?>" placeholder="Hora de presentación">
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Crear Evento</button>
                            <a href="index.php" class="btn btn-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Show/hide speaker details when checkbox is clicked
document.querySelectorAll('.speaker-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const speakerId = this.value;
        const detailsDiv = document.getElementById('speaker_details_' + speakerId);
        
        if (this.checked) {
            detailsDiv.classList.remove('d-none');
        } else {
            detailsDiv.classList.add('d-none');
        }
    });
});
</script>

<?php include_once '../includes/footer.php'; ?>