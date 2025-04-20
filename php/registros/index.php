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
$search = '';
$filter_event = '';
$filter_status = '';
$success_message = '';

// Procesar mensajes de operaciones previas
if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'created':
            $success_message = 'Registro creado con éxito';
            break;
        case 'updated':
            $success_message = 'Registro actualizado con éxito';
            break;
        case 'deleted':
            $success_message = 'Registro eliminado con éxito';
            break;
    }
}

// Procesar búsqueda y filtros
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    if (isset($_GET['search'])) {
        $search = trim($_GET['search']);
    }
    
    if (isset($_GET['event'])) {
        $filter_event = $_GET['event'];
    }
    
    if (isset($_GET['status'])) {
        $filter_status = $_GET['status'];
    }
}

// Consulta base para obtener registros con información relacionada
$query = "SELECT r.*, 
          e.title as event_title, e.event_date,
          CONCAT(p.first_name, ' ', p.last_name) as participant_name,
          p.email as participant_email
          FROM REGISTRATIONS r
          INNER JOIN EVENTS e ON r.event_id = e.event_id
          INNER JOIN PARTICIPANTS p ON r.participant_id = p.participant_id
          WHERE 1=1";

// Añadir condiciones de búsqueda y filtros
$params = [];
$param_types = "";

if (!empty($search)) {
    $query .= " AND (e.title LIKE ? OR p.first_name LIKE ? OR p.last_name LIKE ? OR p.email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= "ssss";
}

if (!empty($filter_event)) {
    $query .= " AND e.event_id = ?";
    $params[] = $filter_event;
    $param_types .= "s";
}

if (!empty($filter_status)) {
    $query .= " AND r.payment_status = ?";
    $params[] = $filter_status;
    $param_types .= "s";
}

// Ordenar por fecha de registro (más reciente primero)
$query .= " ORDER BY r.registration_date DESC";

// Preparar y ejecutar la consulta
$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// Obtener eventos para el filtro
$events_query = "SELECT event_id, title FROM EVENTS ORDER BY title";
$events_result = $conn->query($events_query);
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <h2>Gestión de Registros</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Registros</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <?php if (!empty($success_message)): ?>
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="row mb-4">
        <div class="col-md-8">
            <form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="row g-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="search" placeholder="Buscar..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="event">
                        <option value="">Todos los eventos</option>
                        <?php while($event = $events_result->fetch_assoc()): ?>
                        <option value="<?php echo $event['event_id']; ?>" <?php if($filter_event == $event['event_id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($event['title']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="">Todos los estados</option>
                        <option value="pending" <?php if($filter_status == 'pending') echo 'selected'; ?>>Pendiente</option>
                        <option value="completed" <?php if($filter_status == 'completed') echo 'selected'; ?>>Completado</option>
                        <option value="cancelled" <?php if($filter_status == 'cancelled') echo 'selected'; ?>>Cancelado</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                </div>
            </form>
        </div>
        <div class="col-md-4 text-end">
            <a href="create.php" class="btn btn-success"><i class="fas fa-plus-circle"></i> Nuevo Registro</a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="card-title mb-0">Registros de Participantes</h5>
                        </div>
                        <div class="col-md-6 text-end">
                            <span class="badge bg-info"><?php echo $result->num_rows; ?> registros encontrados</span>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Evento</th>
                                    <th>Participante</th>
                                    <th>Fecha Registro</th>
                                    <th>Estado Pago</th>
                                    <th>Asistencia</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                    <tr class="evento-item">
                                        <td><?php echo $row['registration_id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($row['event_title']); ?></strong><br>
                                            <small class="text-muted"><?php echo date('d/m/Y', strtotime($row['event_date'])); ?></small>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($row['participant_name']); ?><br>
                                            <small><?php echo htmlspecialchars($row['participant_email']); ?></small>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($row['registration_date'])); ?></td>
                                        <td>
                                            <?php 
                                            switch($row['payment_status']) {
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
                                        </td>
                                        <td>
                                            <?php if($row['attendance_status']): ?>
                                                <span class="badge bg-success">Asistió</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">No registrado</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="view.php?id=<?php echo $row['registration_id']; ?>" class="btn btn-sm btn-info" title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit.php?id=<?php echo $row['registration_id']; ?>" class="btn btn-sm btn-primary" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="delete.php?id=<?php echo $row['registration_id']; ?>" class="btn btn-sm btn-danger" title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">No se encontraron registros</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
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