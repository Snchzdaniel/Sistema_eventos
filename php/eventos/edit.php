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

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Validate inputs
        if (empty($_POST['title'])) {
            throw new Exception("El título del evento es obligatorio");
        }
        
        if (empty($_POST['category_id'])) {
            throw new Exception("Debes seleccionar una categoría");
        }
        
        if (empty($_POST['event_date'])) {
            throw new Exception("La fecha del evento es obligatoria");
        }
        
        // Begin transaction
        $conn->beginTransaction();
        
        // Update event data
        $query = "UPDATE events SET 
                    title = :title, 
                    category_id = :category_id, 
                    description = :description, 
                    location = :location, 
                    event_date = :event_date, 
                    start_time = :start_time, 
                    end_time = :end_time, 
                    max_participants = :max_participants, 
                    registration_fee = :registration_fee";
        
        // Handle image upload
        if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_dir = "../public/images/eventos/";
            $file_extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
            $new_filename = uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            // Check file type
            $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
            if(!in_array(strtolower($file_extension), $allowed_types)) {
                throw new Exception("Solo se permiten archivos JPG, JPEG, PNG y GIF");
            }
            
            // Check file size (max 2MB)
            if($_FILES["image"]["size"] > 2000000) {
                throw new Exception("El archivo es demasiado grande. Máximo 2MB");
            }
            
            if(move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                // Add image to query
                $query .= ", image = :image";
            } else {
                throw new Exception("Error al subir la imagen");
            }
        }
        
        $query .= ", updated_at = NOW() WHERE event_id = :event_id";
        
        $stmt = $conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':title', $_POST['title']);
        $stmt->bindParam(':category_id', $_POST['category_id']);
        $stmt->bindParam(':description', $_POST['description']);
        $stmt->bindParam(':location', $_POST['location']);
        $stmt->bindParam(':event_date', $_POST['event_date']);
        $stmt->bindParam(':start_time', $_POST['start_time']);
        $stmt->bindParam(':end_time', $_POST['end_time']);
        $stmt->bindParam(':max_participants', $_POST['max_participants']);
        $stmt->bindParam(':registration_fee', $_POST['registration_fee']);
        $stmt->bindParam(':event_id', $event_id);
        
        // Bind image if uploaded
        if(isset($_FILES['image']) && $_FILES['image']['error'] == 0 && isset($new_filename)) {
            $stmt->bindParam(':image', $new_filename);
        }
        
        $stmt->execute();
        
        // Delete existing speaker associations
        $query = "DELETE FROM event_speakers WHERE event_id = :event_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':event_id', $event_id);
        $stmt->execute();
        
        // Add speakers to the event if any were selected
        if(!empty($_POST['speakers'])) {
            foreach($_POST['speakers'] as $speaker_id) {
                // For each speaker, get the presentation details
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
        
        // Commit transaction
        $conn->commit();
        echo "<div class='alert alert-success'>Evento actualizado exitosamente</div>";
        header("refresh:2;url=view.php?id=".$event_id); // Redirect to event view after 2 seconds
        
    } catch(Exception $e) {
        // Rollback the transaction if something failed
        $conn->rollBack();
        echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

// Fetch event data
$query = "SELECT * FROM events WHERE id = :id OR event_id = :event_id";
echo "<!-- Debug: ID recibido: " . $event_id . " -->";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $event_id);
$stmt->bindParam(':event_id', $event_id);
$stmt->execute();

if($stmt->rowCount() == 0) {
    echo "<div class='alert alert-danger'>Evento no encontrado</div>";
    // Mostrar información de debug
    $debug_query = "SELECT id, event_id, title FROM events";
    $debug_stmt = $conn->prepare($debug_query);
    $debug_stmt->execute();
    $all_events = $debug_stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<!-- Debug: Eventos en la base de datos: -->";
    foreach($all_events as $e) {
        echo "<!-- Debug: ID: " . $e['id'] . ", Event_ID: " . $e['event_id'] . ", Title: " . $e['title'] . " -->";
    }
    header("refresh:2;url=index.php");
    exit;
}

$event = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<!-- Debug: Evento encontrado - ID: " . $event['id'] . ", Event_ID: " . $event['event_id'] . ", Title: " . $event['title'] . " -->";

// Fetch categories for the dropdown
$query = "SELECT * FROM event_categories ORDER BY name";
$stmt = $conn->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all speakers
$query = "SELECT * FROM speakers ORDER BY first_name, last_name";
$stmt = $conn->prepare($query);
$stmt->execute();
$speakers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch currently assigned speakers
$query = "SELECT speaker_id, presentation_title, presentation_time 
          FROM event_speakers 
          WHERE event_id = :event_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':event_id', $event_id);
$stmt->execute();
$event_speakers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Create an array of assigned speaker IDs for easier checking
$assigned_speakers = [];
$speaker_presentations = [];
foreach($event_speakers as $es) {
    $assigned_speakers[] = $es['speaker_id'];
    $speaker_presentations[$es['speaker_id']] = [
        'title' => $es['presentation_title'],
        'time' => $es['presentation_time']
    ];
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>Editar Evento</h4>
                </div>
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $event_id); ?>" method="POST" enctype="multipart/form-data">
                        <div class="form-section">
                            <h5>Información Básica</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="title" class="form-label">Título *</label>
                                        <input type="text" class="form-control" id="title" name="title" required value="<?php echo htmlspecialchars($event['title']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="category_id" class="form-label">Categoría *</label>
                                        <select class="form-control" id="category_id" name="category_id" required>
                                            <option value="">Seleccionar categoría</option>
                                            <?php foreach($categories as $category): ?>
                                                <option value="<?php echo $category['category_id']; ?>" <?php echo ($category['category_id'] == $event['category_id']) ? 'selected' : ''; ?>>
                                                    <?php echo $category['name']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Descripción</label>
                                <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($event['description']); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="location" class="form-label">Ubicación</label>
                                <input type="text" class="form-control" id="location" name="location" value="<?php echo htmlspecialchars($event['location']); ?>">
                            </div>
                        </div>
                        
                        <div class="form-section mt-4">
                            <h5>Fecha y Horario</h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="event_date" class="form-label">Fecha *</label>
                                        <input type="date" class="form-control" id="event_date" name="event_date" required value="<?php echo $event['event_date']; ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="start_time" class="form-label">Hora de inicio</label>
                                        <input type="time" class="form-control" id="start_time" name="start_time" value="<?php echo $event['start_time']; ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="end_time" class="form-label">Hora de finalización</label>
                                        <input type="time" class="form-control" id="end_time" name="end_time" value="<?php echo $event['end_time']; ?>">
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
                                        <input type="number" class="form-control" id="max_participants" name="max_participants" min="1" value="<?php echo $event['max_participants']; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="registration_fee" class="form-label">Costo de inscripción</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" id="registration_fee" name="registration_fee" min="0" step="0.01" value="<?php echo $event['registration_fee']; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section mt-4">
                            <h5>Imagen del Evento</h5>
                            <?php if(!empty($event['image']) && $event['image'] != 'default-event.jpg'): ?>
                                <div class="mb-3">
                                    <label class="form-label">Imagen actual</label>
                                    <div>
                                        <img src="../public/images/eventos/<?php echo $event['image']; ?>" alt="Evento" class="img-thumbnail" style="max-height: 150px;">
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="mb-3">
                                <label for="image" class="form-label">Nueva imagen (opcional)</label>
                                <input type="file" class="form-control" id="image" name="image">
                                <small class="text-muted">Formatos permitidos: JPG, JPEG, PNG, GIF. Tamaño máximo: 2MB</small>
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
                                            <input class="form-check-input speaker-checkbox" type="checkbox" 
                                                value="<?php echo $speaker['speaker_id']; ?>" 
                                                name="speakers[]" 
                                                id="speaker_<?php echo $speaker['speaker_id']; ?>"
                                                <?php echo in_array($speaker['speaker_id'], $assigned_speakers) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="speaker_<?php echo $speaker['speaker_id']; ?>">
                                                <?php echo $speaker['first_name'] . ' ' . $speaker['last_name']; ?>
                                            </label>
                                        </div>
                                        <div class="speaker-details <?php echo in_array($speaker['speaker_id'], $assigned_speakers) ? '' : 'd-none'; ?>" 
                                             id="speaker_details_<?php echo $speaker['speaker_id']; ?>">
                                            <div class="mb-2 mt-2">
                                                <input type="text" class="form-control form-control-sm" 
                                                    name="presentation_title_<?php echo $speaker['speaker_id']; ?>" 
                                                    placeholder="Título de presentación"
                                                    value="<?php echo isset($speaker_presentations[$speaker['speaker_id']]) ? htmlspecialchars($speaker_presentations[$speaker['speaker_id']]['title']) : ''; ?>">
                                            </div>
                                            <div class="mb-2">
                                                <input type="time" class="form-control form-control-sm" 
                                                    name="presentation_time_<?php echo $speaker['speaker_id']; ?>" 
                                                    placeholder="Hora de presentación"
                                                    value="<?php echo isset($speaker_presentations[$speaker['speaker_id']]) ? $speaker_presentations[$speaker['speaker_id']]['time'] : ''; ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Actualizar Evento</button>
                            <a href="view.php?id=<?php echo $event_id; ?>" class="btn btn-secondary">Cancelar</a>
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